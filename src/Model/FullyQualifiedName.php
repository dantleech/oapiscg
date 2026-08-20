<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model;

final class FullyQualifiedName
{
    /**
     * @param non-empty-list<string> $parts
     */
    public function __construct(private array $parts)
    {
    }

    public function namespace(): string
    {
        return implode('\\', array_slice($this->parts, 0, count($this->parts) - 1));
    }

    public function shortName(): string
    {
        return $this->parts[array_key_last($this->parts)];
    }

    public static function fromStrings(string ...$strings): self
    {
        $parts = [];
        foreach ($strings as $string) {
            $parts = array_merge($parts, strlen($string) > 0 ? explode('\\', $string) : []);
        }

        return new self($parts);
    }

    public function toString(): string
    {
        return implode('\\', $this->parts);
    }

    public static function fromString(string $name): self
    {
        if ('' == trim($name)) {
            throw new \RuntimeException(
                'Class name cannot be empty'
            );
        }

        return new self(explode('\\', trim($name)));
    }
}
