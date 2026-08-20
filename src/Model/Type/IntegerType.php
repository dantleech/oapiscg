<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

class IntegerType extends PhpType
{
    public function nativeTypeString(): string
    {
        return 'int';
    }
}
