<?php

namespace DTL\OapiScg;


final class ConfigLoader
{
    const CONFIG_NAME = 'oapiscg';

    public function __construct(private string $cwd)
    {
    }

    public function load(): ?Config
    {
        $path = sprintf('%s/%s.php', rtrim($this->cwd, '/'), self::CONFIG_NAME);

        if (!file_exists($path)) {
            return null;
        }

        $config = (function () use ($path): mixed {
            return require $path;
        })();

        if (!$config instanceof Config) {
            throw new \RuntimeException(sprintf(
                'The config file "%s" must return an instanceof %s but it returned %s',
                Config::class, get_debug_type($config)
            ));
        }

        return $config;
    }
}
