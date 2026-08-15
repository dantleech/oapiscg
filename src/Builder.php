<?php

namespace DTL\OapiScg;

use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\ClassModels;
use DTL\OapiScg\Model\FullyQualifiedName;
use DTL\OapiScg\Model\PhpType;
use DTL\OapiScg\Model\PropertyModel;
use DTL\OapiScg\Model\Type\BooleanType;
use DTL\OapiScg\Model\Type\ClassType;
use DTL\OapiScg\Model\Type\FloatType;
use DTL\OapiScg\Model\Type\IntegerType;
use DTL\OapiScg\Model\Type\IntersectionType;
use DTL\OapiScg\Model\Type\ListType;
use DTL\OapiScg\Model\Type\MixedType;
use DTL\OapiScg\Model\Type\NullType;
use DTL\OapiScg\Model\Type\StringType;
use DTL\OapiScg\Model\Type\UnionType;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

final class Builder
{
    /**
     * @var array<string,ClassModel>
     */
    private array $resolved = [];

    /**
     * @var array<string,list<string>>
     */
    private array $pathStack = [];

    /**
     * @var list<string>
     */
    private array $modelStack = [];

    public function __construct(private SchemaFinder $finder, private ?string $namespace = null)
    {
    }

    public function generateAll(): ClassModels
    {
        return $this->generateNames(...$this->finder->names());
    }

    public function generateNames(string ...$names): ClassModels
    {
        foreach ($names as $name) {
            $this->build($name);
        }

        $resolved = $this->resolved;
        $this->resolved = [];

        return ClassModels::fromClassModels(...array_values($resolved));
    }

    private function build(string $name): ClassModel
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $schema = $this->finder->find($name);

        $this->modelStack[] = $name;
        $this->pushPath($name);
        $model = new ClassModel(
            $this->className($name),
            $this->buildProperties($schema),
        );
        $this->popPath();
        array_pop($this->modelStack);
        $this->resolved[$name] = $model;
        return $model;
    }
    /**
     * @return array<array-key,PropertyModel>
     */
    private function buildProperties(Schema|Reference $schema): array
    {
        if ($schema instanceof Reference) {
            $type = $this->resolveReferenceClassType($schema);
            return $this->build($type->name->shortName())->properties;
        }

        if ($schema->allOf !== null) {
            return $this->buildAllOf($schema->allOf);
        }

        $properties = [];
        foreach ($schema->properties as $propertyName => $property) {
            if ($property instanceof Reference) {
                $properties[(string)$propertyName] = new PropertyModel(
                    (string)$propertyName,
                    $this->resolveReferenceClassType($property)
                );
            }

            $this->pushPath((string)$propertyName);
            $properties[(string)$propertyName] = new PropertyModel(
                (string)$propertyName,
                $this->buildPhpType($property)
            );
            $this->popPath();
        }

        return $properties;
    }

    /**
     * @param Schema[]|Reference[] $schemas
     * @return array<string,PropertyModel>
     */
    private function buildAllOf(array $schemas): array
    {
        $properties = [];

        foreach ($schemas as $schema) {
            $properties = array_merge($properties, $this->buildProperties($schema));
        }

        return $properties;
    }

    private function buildPhpType(Schema|Reference $property): PhpType
    {
        if ($property instanceof Reference) {
            return $this->resolveReferenceClassType($property);
        }

        if ($property->oneOf) {
            return new UnionType(
                array_values(array_map(
                    fn (Schema|Reference $schema) => $this->buildPhpType($schema),
                    $property->oneOf
                ))
            );
        }

        if ($property->allOf) {
            return new IntersectionType(
                array_values(array_map(
                    fn (Schema|Reference $schema) => $this->buildPhpType($schema),
                    $property->allOf    
                ))
            );
        }
        if (is_array($property->type)) {
            return new UnionType(
                array_values(array_map(
                    function (mixed $string) use ($property) {
                        if (!is_string($string)) {
                            throw new \RuntimeException(
                                'Do not know how to deal with non-scalar type here'
                            );
                        }

                        $property = new Schema([]);
                        $property->type = $string;

                        return $this->buildPhpType($property);
                    },
                    $property->type
                ))
            );
        }

        return match ($property->type) {
            'string' => new StringType(),
            'integer' => new IntegerType(),
            'boolean' => new BooleanType(),
            'number' => new FloatType(),
            'array' => $this->buildArrayType($property),
            'object' => $this->buildObjectType($property),
            'null' => new NullType(),
            null => new MixedType(),
            default => throw new \RuntimeException(sprintf(
                'Do not know how to map openapi type "%s": %s',
                implode('.', $this->pathStack),
                var_export($property->type, true)
            )),
        };
    }

    /**
     * @phpstan-assert Schema $schema
     */
    private function assertSchema(Schema|Reference $schema): void
    {
        if ($schema instanceof Reference) {
            throw new \RuntimeException(sprintf(
                'Resolving references not currently supported at "%s": %s',
                implode('.', $this->pathStack),
                $schema->getReference()
            ));
        }
    }

    private function buildArrayType(Schema $property): PhpType
    {
        $itemType = $property->items;
        if ($itemType === null) {
            return new ListType(new MixedType());
        }
        return new ListType($this->buildPhpType($itemType));
    }

    private function buildObjectType(Schema $property): PhpType
    {
        $name = FullyQualifiedName::fromString(
            $this->anonymousName()
        );

        $class = new ClassModel($name, $this->buildProperties($property));
        $this->resolved[$name->toString()] = $class;

        return new ClassType($name);
    }

    private function pushPath(string $name): void
    {
        $model = $this->currentModelName();
        if (!is_array($this->pathStack[$model] ?? null)) {
            $this->pathStack[$model] = [];
        }
        $this->pathStack[$model][] = $name;
    }

    private function popPath(): void
    {
        $model = $this->currentModelName();
        if (!is_array($this->pathStack[$model] ?? null)) {
            return;
        }
        array_pop($this->pathStack[$model]);
    }

    private function anonymousName(?int $inc = null): string
    {
        $model = $this->currentModelName();
        $name = implode('_', array_map(fn (string $path) => ucfirst($path), $this->pathStack[$model] ?? []));

        if ($inc !== null) {
            $name .= (string)$inc;
        }

        if (isset($this->resolved[$name])) {
            return $this->anonymousName($inc === null ? 1 : $inc + 1);
        }

        return $name;
    }

    private function resolveReferenceClassType(Reference $property): ClassType
    {
        $path = $property->getJsonReference()->getJsonPointer()->getPath();

        if (array_slice($path, 0, 2) !== ['components', 'schemas']) {
            throw new \RuntimeException(sprintf(
                'Unsupported reference: %s',
                $property->getReference()
            ));
        }

        $schemaName = array_pop($path);

        $this->build((string)$schemaName);

        return new ClassType($this->className($schemaName));
    }

    private function className(string $name): FullyQualifiedName
    {
        return FullyQualifiedName::fromNamespaceAndName(
            $this->namespace ?? '',
            $name
        );
    }

    private function currentModelName(): string
    {
        $model = end($this->modelStack) ?: null;
        if (null === $model) {
            throw new \RuntimeException(sprintf(
                'model stack is empty - this should not happen!'
            ));
        }
        return $model;
    }
}
