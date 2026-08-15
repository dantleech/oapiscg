<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

class NullType extends PhpType
{
    public function nativeTypeString(): string
    {
        return 'null';
    }
}
