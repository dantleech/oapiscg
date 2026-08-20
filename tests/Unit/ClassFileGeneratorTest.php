<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Unit;

use DTL\OapiScg\ClassFileGenerator;
use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\FullyQualifiedName;
use DTL\OapiScg\Model\PropertyModel;
use DTL\OapiScg\Model\Type\ClassType;
use DTL\OapiScg\Model\Type\ListType;
use DTL\OapiScg\Model\Type\StringType;
use DTL\OapiScg\Model\Value;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Filesystem\Filesystem;

final class ClassFileGeneratorTest extends TestCase
{
    public function testGenerateSubDirectory(): void
    {
        $model = new ClassModel(FullyQualifiedName::fromString('Foobar\\Barfoo'), []);
        $file = (new ClassFileGenerator())->generate($model);
        static::assertSame('Foobar/Barfoo', $file->name);

        $model = new ClassModel(FullyQualifiedName::fromString('Foo\\Bar\\Baz'), []);
        $file = (new ClassFileGenerator(namespacePrefix: 'Foo'))->generate($model);
        static::assertSame('Bar/Baz', $file->name);
    }

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
            static::markTestSkipped('Snapshot generated');
        }

        $expected = (string)file_get_contents($name);

        static::assertEquals($expected, $printed);
    }

    public static function provideGenerate(): Generator
    {
        yield [
            new ClassModel(FullyQualifiedName::fromString('Foobar'), [
                'string' => new PropertyModel('prop1', new StringType()),
                'list' => new PropertyModel('list', new ListType(new StringType())),
            ]),
        ];

        yield [
            new ClassModel(FullyQualifiedName::fromString('FoobarWithDefault'), [
                'string' => new PropertyModel('prop1', new StringType(), new Value('hello!')),
            ]),
        ];

        yield [
            new ClassModel(FullyQualifiedName::fromString('WithNamepace\Foobar'), [
                'string' => new PropertyModel('prop1', new StringType()),
                'list' => new PropertyModel('list', new ListType(new StringType())),
            ]),
        ];

        yield [
            new ClassModel(FullyQualifiedName::fromString('WithClassType'), [
                'class' => new PropertyModel('prop1', new ClassType(FullyQualifiedName::fromString('Foobar\\Barfoo'))),
            ]),
        ];

        yield [
            new ClassModel(FullyQualifiedName::fromString('WithRequiredParamsBeforeOptional'), [
                'one' => new PropertyModel('one', new StringType(), new Value('')),
                'two' => new PropertyModel('two', new StringType()),
                'three' => new PropertyModel('three', new StringType(), new Value('')),
                'four' => new PropertyModel('four', new StringType()),
            ]),
        ];

        yield [
            new ClassModel(FullyQualifiedName::fromString('WithDescribedParameter'), [
                'one' => new PropertyModel('one', new StringType(), description: 'this is a thing'),
            ], description: 'This is a class'),
        ];
    }
}
