<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

final class IntersectionType extends PhpType
{
    /**
     * @param list<PhpType> $types
     */
    public function __construct(private array $types)
    {
    }

    public function nativeTypeString(): string
    {
        return implode('&', array_map(
            fn (PhpType $type) => $type->nativeTypeString(),
            $this->types
        ));
    }
}
