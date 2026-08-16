<?php

namespace DTL\OapiScg\Tests\Unit;

use Closure;
use DTL\OapiScg\Builder;
use DTL\OapiScg\Model\ClassModels;
use DTL\OapiScg\SchemaFinder;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BuilderTest extends TestCase
{
    #[DataProvider('provideBuildDTO')]
    public function testBuildDTO(array $spec, Closure $test, bool $objectAsArray = false): void
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
            SchemaFinder::fromJson((string)json_encode($api)),
            '',
            $objectAsArray,
        ))->generate();

        $test($models);
    }
    /**
     * @return Generator<array{array<string,array<mixed>>,Closure(ClassModels):void,2?:bool}>
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
                        'null' => ['type' => 'null'],
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
                self::assertEquals(
                    'null',
                    $models->get('Foo')->property('null')->phpType->nativeTypeString()
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
                    'array',
                    $models->get('Foo')->property('scalarList')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    'list<string>',
                    $models->get('Foo')->property('scalarList')->phpType->phpDocString()
                );
                self::assertEquals(
                    'list<Foo_ObjectList>',
                    $models->get('Foo')->property('objectList')->phpType->phpDocString()
                );
            }
        ];

        yield 'inline union' => [
            [
                'Foo' => [
                    'properties' => [
                        'inlineUnion' => [
                            'type' => ['string', 'boolean'],
                        ],
                    ],
                ],
            ], 
            function (ClassModels $models) {
                self::assertEquals(
                    'string|bool',
                    $models->get('Foo')->property('inlineUnion')->phpType->nativeTypeString()
                );
            }
        ];

        yield 'anonymous object type' => [
            [
                'Foo' => [
                    'properties' => [
                        'object' => [
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
            function (ClassModels $models) {
                $name = $models->get('Foo')->property('object')->phpType->nativeTypeString();
                self::assertEquals(
                    'Foo_Object',
                    $name
                );
                self::assertEquals(
                    'int',
                    $models->get($name)->property('id')->phpType->nativeTypeString()
                );
            }
        ];

        yield 'object as array' => [
            [
                'Foo' => [
                    'properties' => [
                        'object' => [
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
            function (ClassModels $models) {
                $name = $models->get('Foo')->property('object')->phpType->phpDocString();
                self::assertEquals(
                    'array{id:int}',
                    $name
                );
            },
            true
        ];

        yield 'oneOf type' => [
            [
                'Foo' => [
                    'properties' => [
                        'oneOf' => [
                            'oneOf' => [
                                ['type' => 'string'],
                                ['type' => 'integer'],
                            ],
                        ],
                        'emptyOneOf' => [
                            'oneOf' => [
                            ],
                        ],
                    ],
                ],
            ], 
            function (ClassModels $models) {
                self::assertEquals(
                    'string|int',
                    $models->get('Foo')->property('oneOf')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    'mixed',
                    $models->get('Foo')->property('emptyOneOf')->phpType->nativeTypeString()
                );
            }
        ];

        yield 'allOf type' => [
            [
                'Foo' => [
                    'properties' => [
                        'allOf' => [
                            'allOf' => [
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'foo' => [ 'type' => 'string'],
                                    ],
                                ],
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'bar' => [ 'type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 
            function (ClassModels $models) {
                self::assertEquals(
                    'Foo_AllOf&Foo_AllOf1',
                    $models->get('Foo')->property('allOf')->phpType->nativeTypeString()
                );
            }
        ];

        yield 'class-level allOf with reference' => [
            [
                'Foo' => [
                    'allOf' => [
                        [
                            '$ref' => '#/components/schemas/Bar',
                        ],
                        [
                            'type' => 'object',
                            'properties' => [
                                'newField' => [ 'type' => 'integer'],
                            ],
                        ],
                    ],
                ],
                'Bar' => [
                    'allOf' => [
                        [
                            '$ref' => '#/components/schemas/Baz',
                        ],
                        [
                            'type' => 'object',
                            'properties' => [
                                'bar' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'Baz' => [
                    'type' => 'object',
                    'properties' => [
                        'bar' => ['type' => 'string'],
                    ],
                ],
            ], 
            function (ClassModels $models) {
                self::assertEquals('Foo', $models->get('Foo')->name->toString());
                self::assertEquals('newField', $models->get('Foo')->property('newField')->name);
            },
            true
        ];

        yield 'ref object' => [
            [
                'Foo' => [
                    'properties' => [
                        'object' => [
                            '$ref' => '#/components/schemas/Bar',
                        ],
                    ],
                ],
                'Bar' => [
                    'properties' => [
                        'obj1' => [
                            'type' => 'object',
                            'properties' => [
                                'foobar' => [
                                    'type' => 'string'
                                ],
                                'barfoo' => [
                                    '$ref' => '#/components/schemas/Baz'
                                ],
                            ],
                        ]
                    ],
                ],
                'Baz' => [
                    'properties' => [
                        'bazzz' => [
                            'type' => 'object',
                            'properties' => [
                                'foobar' => [
                                    'type' => 'string'
                                ],
                            ],
                        ]
                    ],
                ],
            ], 
            function (ClassModels $models) {
                $name = $models->get('Foo')->property('object')->phpType->nativeTypeString();

                self::assertEquals('Bar', $name);
                self::assertEquals('Bar_Obj1', $models->get($name)->property('obj1')->phpType->nativeTypeString());
                self::assertEquals('Baz', $models->get('Bar_Obj1')->property('barfoo')->phpType->nativeTypeString());
            }
        ];

        yield 'model one of' => [
            [
                'Bar' => [
                    'properties' => [
                        'foo' => [
                            '$ref' => '#/components/schemas/Foo',
                        ],
                    ],
                ],
                'Foo' => [
                    'oneOf' => [
                        [
                            'type' => ['string']
                        ],
                        [
                            'type' => ['boolean']
                        ],
                    ],
                ],
            ],
            function (ClassModels $models) {
                // `builder->build()` can probably be removed in favour of resolvePhpType...
                // ... or at least the class structure be represented in the type system
                // so that references can resolve to types.
                self::assertEquals(
                    'string|bool',
                    $models->get('Bar')->property('foo')->phpType->nativeTypeString()
                );
            }
        ];

    }
}
