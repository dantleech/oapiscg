<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

class BooleanType extends PhpType
{
    public function nativeTypeString(): string
    {
        return 'bool';
    }
}
