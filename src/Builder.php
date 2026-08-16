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
use DTL\OapiScg\Model\Type\ListType;
use DTL\OapiScg\Model\Type\MixedType;
use DTL\OapiScg\Model\Type\NullType;
use DTL\OapiScg\Model\Type\ShapeType;
use DTL\OapiScg\Model\Type\StringType;
use DTL\OapiScg\Model\Type\UnionType;
use PhpParser\Builder\Property;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use function PHPUnit\Framework\throwException;

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

    public function __construct(
        private SchemaFinder $finder,
        private ?string $namespace = null,
    )
    {
    }

    public function generate(string ...$names): ClassModels
    {
        if ([] === $names) {
            $names = $this->finder->names();
        }

        $models = [];
        foreach ($names as $name) {
            $type = $this->buildPhpType($name);
            if ($type instanceof ShapeType) {
                $models[] = new ClassModel(
                    $this->className($name),
                    array_combine(
                        array_keys($type->properties),
                        array_map(
                            function (string $name, PhpType $type) {return new PropertyModel($name, $type);},
                            array_keys($type->properties),
                            array_values($type->properties)
                        )
                    )
                );
            }
        }

        return ClassModels::fromClassModels(...$models);
    }

    private function buildPhpType(Schema|Reference|string $schema): PhpType
    {
        if (is_string($schema)) {
            $schemaName = $schema;

            $schema = $this->finder->find($schemaName);
        }

        if ($schema instanceof Reference) {
            return $this->resolveReferenceType($schema);
        }

        if ($schema->oneOf) {
            return new UnionType(
                array_values(array_map(
                    fn (Schema|Reference $schema) => $this->buildPhpType($schema),
                    $schema->oneOf
                ))
            );
        }

        if ($schema->allOf) {
            return new ShapeType(array_reduce($schema->allOf, function (array $properties, Schema|Reference $s) {
                $type = $this->buildPhpType($s);

                // if this is a class type, convert it to a shape
                if ($type instanceof ClassType) {
                    $type = $this->buildPhpType($type->name->shortName());
                }

                if (!$type instanceof ShapeType) {
                    throw new \RuntimeException(sprintf(
                        'allOf can only used on object (shape) types, resolved: %s',
                        $type::class
                    ));
                }
                foreach ($type->properties as $name => $type) {
                    $properties[$name] = $type;
                }

                return $properties;
            }, []));
        }

        if (is_array($schema->type)) {
            return new UnionType(
                array_values(array_map(
                    function (mixed $string) use ($schema) {
                        if (!is_string($string)) {
                            throw new \RuntimeException(
                                'Do not know how to deal with non-scalar type here'
                            );
                        }

                        $schema = new Schema([]);
                        $schema->type = $string;

                        return $this->buildPhpType($schema);
                    },
                    $schema->type
                ))
            );
        }

        // if the type is missing, try and guess it
        // @mago-expect analysis:redundant-comparison,impossible-condition
        if ($schema->type === null) {
            if (count($schema->properties) > 0) {
                $schema->type = 'object';
            }
            if ($schema->items !== null) {
                $schema->type = 'array';
            }

            if ($schema->enum) {
                return UnionType::fromValues($schema->enum);
            }
        }

        return match ($schema->type) {
            'string' => new StringType(),
            'integer' => new IntegerType(),
            'boolean' => new BooleanType(),
            'number' => new FloatType(),
            'array' => $this->buildArrayType($schema),
            'object' => $this->buildShape($schema),
            'null' => new NullType(),
            default => throw new \RuntimeException(sprintf(
                'Do not know how to map openapi schema: %s',
                var_export($schema->getRawSpecData(), true)
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
                $this->currentPath(),
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

    private function resolveReferenceType(Reference $property): PhpType
    {
        $path = $property->getJsonReference()->getJsonPointer()->getPath();

        if (array_slice($path, 0, 2) !== ['components', 'schemas']) {
            throw new \RuntimeException(sprintf(
                'Unsupported reference: %s',
                $property->getReference()
            ));
        }

        // TODO: better way?
        $schemaName = array_pop($path);

        $type = $this->buildPhpType((string)$schemaName);

        if ($type instanceof ShapeType) {
            return new ClassType($this->className($schemaName));
        }

        return $type;
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

    private function buildShape(Schema $property): PhpType
    {
        $properties = [];
        foreach ($property->properties as $name => $property) {
            $properties[(string)$name] = $this->buildPhpType($property);
        }
        return new ShapeType($properties);
    }
}
