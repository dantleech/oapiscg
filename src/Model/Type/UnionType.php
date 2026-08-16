<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

final class UnionType extends PhpType
{
    /**
     * @param list<PhpType> $types
     */
    public function __construct(private array $types)
    {
    }

    #[\Override]
    public function nativeTypeString(): string
    {
        return implode('|', array_map(
            fn (PhpType $type) => $type->nativeTypeString(),
            $this->types
        ));
    }

    #[\Override]
    public function phpDocString(): string
    {
        return implode('|', array_map(
            fn (PhpType $type) => $type->phpDocString(),
            $this->types
        ));
    }
}
