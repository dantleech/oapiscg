<?php

namespace DTL\OapiScg\Model;

abstract class PhpType
{
    public function phpDocString(): string
    {
        return $this->nativeTypeString();
    }

    abstract public function nativeTypeString(): string;
}
