<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

class FloatType extends PhpType
{
    public function nativeTypeString(): string
    {
        return 'float';
    }
}
