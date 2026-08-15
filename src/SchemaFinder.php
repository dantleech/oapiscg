<?php

namespace DTL\OapiScg;

use DTL\OapiScg\Exception\SchemaNotFound;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;

final class SchemaFinder
{
    public function __construct(private OpenApi $openApi)
    {
    }

    public static function fromJson(string $path): self
    {
        return new self(Reader::readFromJson($path));
    }

    public static function fromJsonSpec(string $path): self
    {
        return new self(Reader::readFromJsonFile($path, resolveReferences: 'inline'));
    }

    public function find(string $name): Schema
    {
        $schemas = $this->openApi?->components?->schemas ?? [];

        if (!isset($schemas[$name])) {
            throw new SchemaNotFound(sprintf('No schema with name "%s" found, known schemas: "%s"',
                $name, implode('", "', array_keys($schemas))
            ));
        }

        $schema = $schemas[$name];

        if (!$schema instanceof Schema) {
            throw new \RuntimeException(sprintf(
                'Expected %s got %s',
                Schema::class, get_debug_type($schema)
            ));
        }

        return $schema;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(
            fn ($s) => (string)$s,
            array_keys($this->openApi?->components?->schemas ?? [])
        );
    }
}
