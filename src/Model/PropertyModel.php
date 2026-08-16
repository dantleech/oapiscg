<?php

namespace DTL\OapiScg\Model;

final class PropertyModel
{
    public function __construct(
        public string $name,
        public PhpType $phpType,
        public ?Value $default = null
    )
    {
    }

}
