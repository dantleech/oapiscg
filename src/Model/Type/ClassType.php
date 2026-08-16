<?php

namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\FullyQualifiedName;
use DTL\OapiScg\Model\PhpType;

final class ClassType extends PhpType
{
    public function __construct(public FullyQualifiedName $name)
    {
    }

    #[\Override]
    public function nativeTypeString(): string
    {
        return '\\' . $this->name->toString();
    }
}
