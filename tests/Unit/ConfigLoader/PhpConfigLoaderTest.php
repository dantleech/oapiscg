<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Unit\ConfigLoader;

use DTL\OapiScg\ConfigLoader\PhpConfigLoader;
use DTL\OapiScg\Tests\TestCase;

final class PhpConfigLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
    }

    public function testLoad(): void
    {
        $this->workspace()->put('oapiscg.php', <<<'PHP'
        <?php

        use DTL\OapiScg\Configs;
        use DTL\OapiScg\Config;
        use DTL\OapiScg\Model\ClassModel;

        return Configs::from([
            'hello' => new Config(
                specPath: 'path/to/spec.json',
                outPath: 'out/path',
                namespace: 'To\\Namespace',
                components: ['Component1'],
                modelVisitors: [
                    function (ClassModel $model) {
                    }
                ],
            ),
        ]);
        PHP);

        $configs = (new PhpConfigLoader($this->workspace()->path()))->load();
        $config = $configs->get('hello');
        static::assertSame('path/to/spec.json', $config->specPath);
        static::assertSame('To\\Namespace', $config->namespace);
        static::assertEquals(['Component1'], $config->components);
    }
}
