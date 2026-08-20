<?php

declare(strict_types=1);


namespace Acme\Balls;

final class NewPet
{
    /**
     * @param ?array{name:?string} $owner
     */
    function __construct(public string $name, public ?string $tag = null, public ?array $owner = null)
    {
    }
}