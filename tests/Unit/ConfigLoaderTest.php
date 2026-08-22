<?php

namespace DTL\OapiScg\Tests\Unit;

use DTL\OapiScg\Config;
use DTL\OapiScg\ConfigLoader;
use DTL\OapiScg\Tests\TestCase;

final class ConfigLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
    }

    public function testLoad(): void
    {
        $this->workspace()->put('oapiscg.php', <<<'PHP'
        <?php

        use DTL\OapiScg\Config;
        use DTL\OapiScg\Model\ClassModel;

        return new Config(
            specPath: 'path/to/spec.json',
            outPath: 'out/path',
            namespace: 'To\\Namespace',
            components: ['Component1'],
            visitors: [
                function (ClassModel $model) {
                }
            ],

        );
        PHP);

        $config = (new ConfigLoader($this->workspace()->path()))->load();
        self::assertNotNull($config);
        self::assertEquals('path/to/spec.json', $config->specPath);
        self::assertEquals('To\\Namespace', $config->namespace);
        self::assertEquals(['Component1'], $config->components);
    }
}
