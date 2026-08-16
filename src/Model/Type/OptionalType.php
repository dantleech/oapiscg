<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

final class OptionalType extends PhpType
{
    public function __construct(public PhpType $type)
    {
    }

    #[\Override]
    public function nativeTypeString(): string
    {
        return $this->type->nativeTypeString();
    }

    #[\Override]
    public function phpDocString(): string
    {
        return $this->type->phpDocString();
    }
}
