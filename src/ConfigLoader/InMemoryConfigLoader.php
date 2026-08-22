<?php

declare(strict_types=1);


namespace DTL\OapiScg\ConfigLoader;


use DTL\OapiScg\ConfigLoader;
use DTL\OapiScg\Configs;

final class InMemoryConfigLoader implements ConfigLoader
{
    public function __construct(private Configs $configs)
    {
    }

    public function load(): Configs
    {
        return $this->configs;
    }
}
