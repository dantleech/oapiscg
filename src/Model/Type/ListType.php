<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

final class ListType extends PhpType
{
    public function __construct(public PhpType $type)
    {
    }

    public function nativeTypeString(): string
    {
        return sprintf('list<%s>', $this->type->phpDocString());
    }
}
