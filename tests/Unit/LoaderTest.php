<?php

namespace DTL\OapiScg\Tests\Unit;

use DTL\OapiScg\OpenApi\Loader;
use PHPUnit\Framework\TestCase;

final class LoaderTest extends TestCase
{
    public function testLoad(): void
    {
        (new Loader())->load(__DIR__ . '/../../example/petstore-expanded.json');
    }
}
