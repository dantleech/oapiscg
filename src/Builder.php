<?php

declare(strict_types=1);


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
use DTL\OapiScg\Model\Type\OptionalType;
use DTL\OapiScg\Model\Type\ShapeType;
use DTL\OapiScg\Model\Type\StringType;
use DTL\OapiScg\Model\Type\UnionType;
use DTL\OapiScg\Model\Value;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

final class Builder
{
    /**
     * @var list<string>
     */
    private array $path = [];

    /**
     * @var array<string,ShapeType>
     */
    private array $pending = [];
    private array $resolved = [];

    public function __construct(
        private SchemaFinder $finder,
        private ?string $namespace = null,
        private int $inlineLevel = 0,
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
            $schema = $this->finder->find($name);

            $type = $this->buildPhpType($schema, [$name]);
            $default = null;

            if (!$type instanceof ShapeType) {
                continue;
            }
            $this->pending[$name] = $type;
        }

        while (count($this->pending) > 0) {
            $queue = $this->pending;
            $this->pending = [];
            foreach ($queue as $name => $type) {
                $models[] = new ClassModel(
                    $this->className($name),
                    array_combine(
                        array_keys($type->properties),
                        array_map(
                            static function (string $name, PhpType $type) {
                                $default = null;
                                if ($type instanceof OptionalType) {
                                    $type = $type->type;
                                    $default = new Value(null);
                                }
                                return new PropertyModel(
                                    $name,
                                    $type,
                                    $default,
                                );
                            },
                            array_keys($type->properties),
                            array_values($type->properties)
                        )
                    ),
                );
                $this->resolved[$name] = true;
            }
        }

        return ClassModels::fromClassModels(...$models);
    }
    /**
     * @param list<string> $path
     */
    private function buildPhpType(Schema|Reference|string $schema, array $path): PhpType
    {
        if (is_string($schema)) {
            $schema = $this->finder->find($schema);
        }

        if ($schema instanceof Reference) {
            return $this->resolveReferenceType($schema, $path);
        }

        if ($schema->oneOf) {
            return new UnionType(
                array_values(array_map(
                    fn (Schema|Reference $s) => $this->buildPhpType($s, $path),
                    $schema->oneOf
                ))
            );
        }

        if ($schema->allOf) {
            return new ShapeType(array_reduce($schema->allOf, function (array $properties, Schema|Reference $s) use ($path) {
                $type = $this->buildPhpType($s, $path);

                // if this is a class type, convert it to a shape
                if ($type instanceof ClassType) {
                    $type = $this->pending[$type->name->toString()] ?? $this->buildPhpType($type->name->shortName(), $path);
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

        // the type is a lie, if it's an array build a union
        // @mago-expect analyzer:impossible-condition,impossible-type-comparison
        if (is_array($schema->type)) {
            return new UnionType(
                array_values(array_map(
                    function (mixed $string) use ($schema, $path) {
                        if (!is_string($string)) {
                            throw new \RuntimeException(
                                'Do not know how to deal with non-scalar type here'
                            );
                        }

                        $schema = new Schema([]);
                        $schema->type = $string;

                        return $this->buildPhpType($schema, $path);
                    },
                    // @mago-expect analyzer:no-value
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
                return UnionType::fromValues(array_values($schema->enum));
            }
        }

        return match ($schema->type) {
            'string' => new StringType(),
            'integer' => new IntegerType(),
            'boolean' => new BooleanType(),
            'number' => new FloatType(),
            'array' => $this->buildArrayType($schema, $path),
            'object' => $this->buildShape($schema, $path),
            'null' => new NullType(),
            default => throw new \RuntimeException(sprintf(
                'Do not know how to map openapi schema: %s',
                var_export($schema->getRawSpecData(), true)
            )),
        };
    }
    /**
     * @param list<string> $path
     */
    private function buildArrayType(Schema $property, array $path): PhpType
    {
        $itemType = $property->items;
        if ($itemType === null) {
            return new ListType(new MixedType());
        }
        return new ListType($this->buildPhpType($itemType, $path));
    }
    /**
     * @param list<string> $path
     */
    private function resolveReferenceType(Reference $property, array $path): PhpType
    {
        $refPath = $property->getJsonReference()->getJsonPointer()->getPath();

        if (array_slice($refPath, 0, 2) !== ['components', 'schemas']) {
            throw new \RuntimeException(sprintf(
                'Unsupported reference: %s',
                $property->getReference()
            ));
        }

        $schemaName = (string)array_pop($refPath);

        $type = $this->buildPhpType($schemaName, $path);

        if ($type instanceof ShapeType) {
            return new ClassType($this->className($schemaName));
        }

        return $type;
    }

    private function className(string $name): FullyQualifiedName
    {
        return FullyQualifiedName::fromStrings(
            $this->namespace ?? '',
            $name
        );
    }
    /**
     * @param list<string> $path
     */
    private function buildShape(Schema $schema, array $path = []): PhpType
    {
        $properties = [];
        foreach ($schema->properties as $name => $property) {
            $newPath = $path;   
            $newPath[] = (string)$name;
            $phpType = $this->buildPhpType($property, $newPath);

            // @mago-expect analyzer:redundant-null-coalesce
            if (false === in_array($name, $schema->required ?? [], true)) {
                $phpType = new OptionalType($phpType->makeNullable());
            }

            $properties[(string)$name] = $phpType;
        }

        $type = new ShapeType($properties);

        if (count($path) > $this->inlineLevel) {
            return $type;
        }

        $name = $this->className(implode('\\', array_map('ucfirst', $path)));
        if (!isset($this->resolved[$name->toString()])) {
            $this->pending[$name->toString()] = $type;
        }
        return new ClassType($name);
    }
}
