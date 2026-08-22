<?php

declare(strict_types=1);


namespace DTL\OapiScg\ConfigLoader;

use DTL\OapiScg\ConfigLoader;
use DTL\OapiScg\Configs;
use RuntimeException;

final class PhpConfigLoader implements ConfigLoader
{
    const CONFIG_NAME = 'oapiscg';

    public function __construct(private string $cwd)
    {
    }

    public function load(): Configs
    {
        $path = sprintf('%s/%s.php', rtrim($this->cwd, '/'), self::CONFIG_NAME);

        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf(
                'No configuration file exists at: %s',
                $path
            ));
        }

        $configs = (static fn () => require $path)();

        if (!$configs instanceof Configs) {
            throw new \RuntimeException(sprintf(
                'The config file "%s" must return an instanceof %s but it returned %s',
                Configs::class, get_debug_type($configs)
            ));
        }

        return $configs;
    }
}
