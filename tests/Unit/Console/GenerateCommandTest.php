<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Unit\Console;

use DTL\OapiScg\Console\GenerateCommand;
use DTL\OapiScg\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
    }

    public function testGenerate(): void
    {
        $tester = $this->createTester();
        $tester->execute([
            'spec-path' => __DIR__ . '/petstore-expanded.json',
            'out-path' => $this->workspace()->path()
        ]);

        static::assertTrue($this->workspace()->exists('NewPet.php'));
    }

    public function testGenerateInNamespace(): void
    {
        $tester = $this->createTester();
        $tester->execute([
            'spec-path' => __DIR__ . '/petstore-expanded.json',
            'out-path' => $this->workspace()->path(),
            '--namespace' => 'Acme\\Balls',
        ]);

        static::assertTrue($this->workspace()->exists('NewPet/Owner.php'));
    }

    public function testInlineLevel(): void
    {
        $tester = $this->createTester();
        $tester->execute([
            'spec-path' => __DIR__ . '/petstore-expanded.json',
            'out-path' => $this->workspace()->path(),
            '--namespace' => 'Acme\\Balls',
            '--inline-level' => 0,
        ]);

        static::assertTrue($this->workspace()->exists('NewPet.php'));
        static::assertStringContainsString('array{name:?string}', $this->workspace()->getContents('NewPet.php'));
    }

    private function createTester(): CommandTester
    {
        return new CommandTester(
            new GenerateCommand()
        );
    }
}
