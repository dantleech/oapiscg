<?php

declare(strict_types=1);


namespace DTL\OapiScg;

interface ConfigLoader
{
    public function load(): Configs;
}
