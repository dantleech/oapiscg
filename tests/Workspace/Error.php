<?php

declare(strict_types=1);


namespace Acme\Balls;

final class Error
{
    function __construct(public int $code, public string $message)
    {
    }
}