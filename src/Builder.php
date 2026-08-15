<?php

namespace DTL\OapiScg;

use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\ClassModels;
use DTL\OapiScg\Model\FullyQualifiedName;
use DTL\OapiScg\Model\PhpType;
use DTL\OapiScg\Model\PropertyModel;
use DTL\OapiScg\Model\Type\BooleanType;
use DTL\OapiScg\Model\Type\FloatType;
use DTL\OapiScg\Model\Type\IntegerType;
use DTL\OapiScg\Model\Type\StringType;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;


class Builder
{
    /**
     * @var array<string,ClassModel>
     */
    private array $resolved = [];

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

        $model = new ClassModel(
            FullyQualifiedName::fromNamespaceAndName(
                $this->namespace ?? '',
                $name
            ),
            $this->buildProperties($schema),
        );
        $this->resolved[$name] = $model;
    }
    /**
     * @return array<string,PropertyModel>
     */
    private function buildProperties(Schema|Reference $schema): array
    {
        $this->assertSchema($schema);
        if ($schema->allOf) {
            return $this->buildAllOf($schema->allOf);
        }

        $properties = [];
        foreach ($schema->properties as $propertyName => $property) {
            $this->assertSchema($property);
            $properties[(string)$propertyName] = new PropertyModel(
                (string)$propertyName,
                $this->buildPhpType($property)
            );
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

    private function buildPhpType(Schema $property): PhpType
    {
        return match ($property->type) {
            'string' => new StringType(),
            'integer' => new IntegerType(),
            'boolean' => new BooleanType(),
            'number' => new FloatType(),
            default => throw new \RuntimeException(sprintf(
                'Do not know how to map openapi type: %s',
                $property->type
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
}
