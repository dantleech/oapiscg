<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;
use DTL\OapiScg\Model\PropertyModel;

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

    public function phpDocString(): string
    {
        return sprintf('array{%s}', implode(
            ',',
            array_map(function (string $key, PhpType $type) {
                return sprintf('%s:%s', $key, $type->nativeTypeString());
            }, array_keys($this->properties), array_values($this->properties))
        ));
    }
}
