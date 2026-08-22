<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Unit;

use DTL\OapiScg\SchemaFinder;
use DTL\OapiScg\Tests\TestCase;

final class SchemaFinderTest extends TestCase
{
    public function testFileNotFound(): void
    {
        $this->expectExceptionMessageMatches('/No file exists at: asdasd/');

        SchemaFinder::fromJsonSpec('asdasd');
    }
}
