<?php

namespace DTL\OapiScg\Model\Type;

final class StringLiteralType extends StringType
{
    public function __construct(public string $value)
    {
    }

    #[\Override]
    public function phpDocString(): string
    {
        return sprintf('"%s"', addslashes($this->value));
    }
}
