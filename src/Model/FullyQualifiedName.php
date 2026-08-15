<?php

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

    public static function fromNamespaceAndName(string $namespace, string $name): self
    {
        $parts = strlen($namespace) > 0 ? explode('\\', $namespace) : [];
        $parts[] = $name;

        return new self($parts);
    }

    public function toString(): string
    {
        if ($this->namespace() === '') {
            return $this->shortName();
        }
        return sprintf('%s\\%s', $this->namespace(), $this->shortName());
    }
}
