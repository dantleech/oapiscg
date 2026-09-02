<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

class DictType extends PhpType
{
    public function __construct(public PhpType $value)
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
        return sprintf('array<string,%s>', $this->value->phpDocString());
    }
}
