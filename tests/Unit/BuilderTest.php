<?php

namespace DTL\OapiScg\Tests\Unit;

use Closure;
use DTL\OapiScg\Builder;
use DTL\OapiScg\Model\ClassModels;
use DTL\OapiScg\SchemaFinder;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use cebe\openapi\spec\OpenApi;

final class BuilderTest extends TestCase
{
    #[DataProvider('provideBuildDTO')]
    public function testBuildDTO(array $spec, Closure $test): void
    {
        $test->bindTo($this);

        $api = [
            'openapi' => '3.0.0',
            'info' => [],
            'components' => [
                'schemas' => $spec,
            ],
        ];

        $models = (new Builder(
            SchemaFinder::fromJson((string)json_encode($api))
        ))->generateAll();

        $test($models);
    }
    /**
     * @return Generator<array{array<string,array<mixed>>,Closure(ClassModels):void}>
     */
    public static function provideBuildDTO(): Generator
    {
        yield 'scalar types' => [
            [
                'Foo' => [
                    'properties' => [
                        'string' => ['type' => 'string'],
                        'integer' => ['type' => 'integer'],
                        'number' => ['type' => 'number'],
                        'boolean' => ['type' => 'boolean'],
                    ],
                ],
            ], 
            function (ClassModels $models) {
                self::assertEquals(
                    'string',
                    $models->get('Foo')->property('string')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    'int',
                    $models->get('Foo')->property('integer')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    'float',
                    $models->get('Foo')->property('number')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    'bool',
                    $models->get('Foo')->property('boolean')->phpType->nativeTypeString()
                );
            }
        ];

        yield 'array type' => [
            [
                'Foo' => [
                    'properties' => [
                        'scalarList' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'objectList' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 
            function (ClassModels $models) {
                self::assertEquals(
                    'list<string>',
                    $models->get('Foo')->property('scalarList')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    'list<FOOOOO>',
                    $models->get('Foo')->property('objectList')->phpType->nativeTypeString()
                );
            }
        ];
    }
}
