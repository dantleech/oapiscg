<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests;

use DTL\OapiScg\Tests\Support\Workspace;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    protected function workspace(): Workspace
    {
        return Workspace::create(__DIR__ . '/Workspace');
    }
}
