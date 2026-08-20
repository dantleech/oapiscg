<?php

declare(strict_types=1);


namespace Acme\Balls;

final class Pet
{
    /**
     * @param ?array{name:?string} $owner
     */
    function __construct(public string $name, public int $id, public ?string $tag = null, public ?array $owner = null)
    {
    }
}