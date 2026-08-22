<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Unit\Console;

use DTL\OapiScg\Config;
use DTL\OapiScg\ConfigLoader\InMemoryConfigLoader;
use DTL\OapiScg\Configs;
use DTL\OapiScg\Console\GenerateCommand;
use DTL\OapiScg\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateCommandTest extends TestCase
{
    const EXAMPLE_NAME = 'config';

    protected function setUp(): void
    {
        $this->workspace()->reset();
    }

    public function testGenerate(): void
    {
        $tester = $this->createTester(new Config(
            specPath: __DIR__ . '/petstore-expanded.json',
            outPath: $this->workspace()->path()
        ));
        $tester->execute([
            'name' => self::EXAMPLE_NAME,
        ]);

        static::assertTrue($this->workspace()->exists('NewPet.php'));
    }

    public function testInNamespace(): void
    {
        $tester = $this->createTester(new Config(
            specPath: __DIR__ . '/petstore-expanded.json',
            outPath: $this->workspace()->path(),
            namespace: 'Acme\\Balls',
        ));
        $tester->execute([
            'name' => self::EXAMPLE_NAME,
        ]);

        static::assertTrue($this->workspace()->exists('NewPet/Owner.php'));
    }

    public function testInlineLevel(): void
    {
        $tester = $this->createTester(new Config(
            specPath: __DIR__ . '/petstore-expanded.json',
            outPath: $this->workspace()->path(),
            namespace: 'Acme\\Balls',
            inlineLevel: 0,
        ));
        $tester->execute([
            'name' => self::EXAMPLE_NAME,
        ]);

        static::assertTrue($this->workspace()->exists('NewPet.php'));
        static::assertStringContainsString('array{name:?string}', $this->workspace()->getContents('NewPet.php'));
    }

    private function createTester(Config $config): CommandTester
    {
        return new CommandTester(
            new GenerateCommand(
                new InMemoryConfigLoader(
                    Configs::from([
                        self::EXAMPLE_NAME => $config
                    ])
                )
            )
        );
    }
}
