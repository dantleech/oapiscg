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
use DTL\OapiScg\Model\Type\StringType;
use DTL\OapiScg\Model\Type\UnionType;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;


class Builder
{
    /**
     * @var array<string,ClassModel>
     */
    private array $resolved = [];

    private array $pathStack = [];

    public function __construct(private SchemaFinder $finder, private ?string $namespace = null)
    {
    }

    public function generateAll(string ...$names): ClassModels
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

    private function build(string $name): void
    {
        $schema = $this->finder->find($name);

        $this->pushPath($name);
        $model = new ClassModel(
            FullyQualifiedName::fromNamespaceAndName(
                $this->namespace ?? '',
                $name
            ),
            $this->buildProperties($schema),
        );
        $this->popPath();
        $this->resolved[$name] = $model;
    }
    /**
     * @return array<string,PropertyModel>
     */
    private function buildProperties(Schema|Reference $schema): array
    {
        $this->assertSchema($schema);
        if ($schema->allOf !== null) {
            return $this->buildAllOf($schema->allOf);
        }

        $properties = [];
        foreach ($schema->properties as $propertyName => $property) {
            $this->assertSchema($property);
            $this->pushPath($propertyName);
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
        $this->assertSchema($property);

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

        return match ($property->type) {
            'string' => new StringType(),
            'integer' => new IntegerType(),
            'boolean' => new BooleanType(),
            'number' => new FloatType(),
            'array' => $this->buildArrayType($property),
            'object' => $this->buildObjectType($property),
            null => new MixedType(),
            default => throw new \RuntimeException(sprintf(
                'Do not know how to map openapi type: %s',
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
                'Resolving references not currently supported: %s',
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
        $this->assertSchema($itemType);

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
        $this->pathStack[] = $name;
    }

    private function popPath(): void
    {
        array_pop($this->pathStack);
    }

    private function anonymousName(?int $inc = null): string
    {
        $name = implode('_', array_map(fn (string $path) => ucfirst($path), $this->pathStack));

        if ($inc !== null) {
            $name .= (string)$inc;
        }

        if (isset($this->resolved[$name])) {
            return $this->anonymousName($inc === null ? 1 : $inc + 1);
        }

        return $name;
    }
}
