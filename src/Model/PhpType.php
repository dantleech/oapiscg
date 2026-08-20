<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model;

use DTL\OapiScg\Model\Type\NullType;
use DTL\OapiScg\Model\Type\UnionType;

abstract class PhpType
{
    public function phpDocString(): string
    {
        return $this->nativeTypeString();
    }

    abstract public function nativeTypeString(): string;

    public function makeNullable(): PhpType
    {
        if ($this instanceof UnionType) {
            return $this->withType(new NullType());
        }

        return new UnionType([$this, new NullType()]);
    }
}
