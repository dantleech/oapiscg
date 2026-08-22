<?php

declare(strict_types=1);


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
        if (
            substr($path, 0, 5) !== 'http:' && 
            substr($path, 0, 6) !== 'https:' && 
            !file_exists($path)
        ) {
            throw new \RuntimeException(sprintf(
                'No file exists at: %s',
                $path
            ));
        }

        $api = Reader::readFromJsonFile($path, resolveReferences: 'inline');

        if (!$api instanceof OpenApi) {
            throw new \RuntimeException(sprintf(
                'Expected %s got %s',
                $api::class, get_debug_type($api)
            ));
        }

        return new self($api);
    }

    public function find(string $name): Schema
    {
        $schemas = $this->openApi?->components->schemas ?? [];

        if (!array_key_exists($name, $schemas)) {
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
            static fn ($s) => (string)$s,
            array_keys($this->openApi?->components->schemas ?? [])
        );
    }
}
