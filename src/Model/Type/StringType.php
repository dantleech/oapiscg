<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

class StringType extends PhpType
{
    public function nativeTypeString(): string
    {
        return 'string';
    }
}
