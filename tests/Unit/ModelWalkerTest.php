<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Unit;

use DTL\OapiScg\ModelVisitor;
use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\ClassModels;
use DTL\OapiScg\Model\FullyQualifiedName;
use DTL\OapiScg\Model\PropertyModel;
use DTL\OapiScg\Model\Type\IntegerType;
use DTL\OapiScg\Model\Type\StringType;
use DTL\OapiScg\Model\Value;
use DTL\OapiScg\Tests\TestCase;

final class ModelWalkerTest extends TestCase
{
    public function testWalk(): void
    {
        $models = ClassModels::fromClassModels(
            new ClassModel(
                name: FullyQualifiedName::fromString('Foobar\\Barfoo'),
                properties: [
                    new PropertyModel('hello', new IntegerType(), new Value(0)),
                    new PropertyModel('goodbye', new StringType(), new Value(0)),
                ],
            )
        );

        (new ModelVisitor([
            static function (ClassModel $value) {
                $value->property('goodbye')->phpType = new IntegerType();
            }
        ]))->walk(
            $models
        );

        static::assertInstanceOf(IntegerType::class, $models->get('Foobar\\Barfoo')->property('goodbye')->phpType);
    }
}
