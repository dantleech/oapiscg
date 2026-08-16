<?php

namespace DTL\OapiScg\Tests\Unit;

use DTL\OapiScg\ClassFileGenerator;
use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\FullyQualifiedName;
use DTL\OapiScg\Model\PropertyModel;
use DTL\OapiScg\Model\Type\ListType;
use DTL\OapiScg\Model\Type\StringType;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Filesystem\Filesystem;

final class ClassGeneratorTest extends TestCase
{
    #[DataProvider('provideGenerate')]
    public function testGenerate(ClassModel $model):  void
    {
        $fs = new Filesystem();
        $file = (new ClassFileGenerator())->generate($model);

        $printer = new Standard([]);

        $name = sprintf('%s/example/%s.php.example', __DIR__, str_replace('\\', '/', $model->name->toString()));
        $printed = $printer->prettyPrint($file->stmts);

        if (!file_exists($name)) {
            $fs->dumpFile($name, $printed);
            $this->markTestSkipped('Snapshot generated');
        }

        $expected = (string)file_get_contents($name);

        self::assertEquals($expected, $printed);
    }

    public static function provideGenerate(): Generator
    {
        yield [
            new ClassModel(FullyQualifiedName::fromString('Barfoo\\Foobar'), [
                'string' => new PropertyModel('prop1', new StringType()),
                'list' => new PropertyModel('list', new ListType(new StringType())),
            ]),
        ];
    }
}
