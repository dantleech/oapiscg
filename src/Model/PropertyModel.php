<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model;

final class PropertyModel
{
    public function __construct(
        public string $name,
        public PhpType $phpType,
        public ?Value $default = null,
        public ?string $description = null,
    )
    {
    }

}
