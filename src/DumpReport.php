<?php

namespace DTL\OapiScg;

final class DumpReport
{
    public function __construct(public string $path, public int $written)
    {
    }
}
