<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;


final class ShapeType extends PhpType
{
    /**
     * @param array<string,PhpType> $properties
     */
    public function __construct(public array $properties)
    {
    }

    #[\Override]
    public function nativeTypeString(): string
    {
        return 'array';
    }

    #[\Override]
    public function phpDocString(): string
    {
        return sprintf('array{%s}', implode(
            ',',
            array_map(static fn (string $key, PhpType $type) => sprintf('%s:%s', $key, $type->phpDocString()), array_keys($this->properties), array_values($this->properties))
        ));
    }
}
