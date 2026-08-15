<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

class MixedType extends PhpType
{
    public function nativeTypeString(): string
    {
        return 'mixed';
    }
}
