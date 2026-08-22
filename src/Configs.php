<?php

declare(strict_types=1);


namespace DTL\OapiScg;

use Countable;

final class Configs implements Countable
{
    /**
     * @param non-empty-array<string,Config> $configs
     */
    private function __construct(private array $configs)
    {
    }
    /**
     * @param Config|array<string,Config> $config
     */
    public static function from(Config|array $config): self
    {
        if (!is_array($config)) {
            $config = ['_default' => $config];
        }

        if ([] === $config) {
            throw new \RuntimeException(
                'At least one config must be provided'
            );
        }

        return new self($config);
    }

    public function get(string $name): Config
    {
        return $this->configs[$name] ?? throw new \RuntimeException(sprintf(
            'No configuration exists for "%s", known configurations: "%s"',
            $name,
            implode('", "', array_keys($this->configs))
        ));
    }

    public function count(): int
    {
        return count($this->configs);
    }

    public function first(): Config
    {
        if (empty($this->configs)) {
            throw new \RuntimeException(
                'Cannot get first config when no configs have been registerd'
            );
        }

        return $this->configs[array_key_first($this->configs)];
    }
}
