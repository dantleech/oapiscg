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
    /**
     * @param array<int,mixed> $spec
     * @param Closure(ClassModels): void $test
     */
    #[DataProvider('provideBuildDTO')]
    public function testBuildDTO(array $spec, Closure $test): void
    {
        $api = [
            'openapi' => '3.0.0',
            'info' => [],
            'components' => [
                'schemas' => $spec,
            ],
        ];

        $models = (new Builder(
            SchemaFinder::fromJson((string)json_encode($api)),
            ''
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
                    'type' => 'object',
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
                    '?string',
                    $models->get('Foo')->property('string')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    '?int',
                    $models->get('Foo')->property('integer')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    '?float',
                    $models->get('Foo')->property('number')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    '?bool',
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
                    'type' => 'object',
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
                    '?array',
                    $models->get('Foo')->property('scalarList')->phpType->nativeTypeString()
                );
                self::assertEquals(
                    '?list<string>',
                    $models->get('Foo')->property('scalarList')->phpType->phpDocString()
                );
                self::assertEquals(
                    '?list<array{id:?int}>',
                    $models->get('Foo')->property('objectList')->phpType->phpDocString()
                );
            }
        ];

        yield 'inline union' => [
            [
                'Foo' => [
                    'type' => 'object',
                    'required' => ['inlineUnion'],
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
                    'type' => 'object',
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
                $type = $models->get('Foo')->property('object')->phpType;
                self::assertEquals(
                    '?array{id:?int}',
                    $type->phpDocString()
                );
            }
        ];

        yield 'oneOf type' => [
            [
                'Foo' => [
                    'type' => 'object',
                    'properties' => [
                        'oneOf' => [
                            'oneOf' => [
                                ['type' => 'string'],
                                ['type' => 'integer'],
                            ],
                        ],
                    ],
                ],
            ], 
            function (ClassModels $models) {
                self::assertEquals(
                    'string|int|null',
                    $models->get('Foo')->property('oneOf')->phpType->nativeTypeString()
                );
            }
        ];

        yield 'allOf type' => [
            [
                'Foo' => [
                    'type' => 'object',
                    'required' => ['allOf'],
                    'properties' => [
                        'allOf' => [
                            'allOf' => [
                                [
                                    'type' => 'object',
                                    'required' => ['foo'],
                                    'properties' => [
                                        'foo' => [ 'type' => 'string'],
                                    ],
                                ],
                                [
                                    'type' => 'object',
                                    'required' => ['bar'],
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
                    'array{foo:string,bar:int}',
                    $models->get('Foo')->property('allOf')->phpType->phpDocString()
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
            }
        ];

        yield 'ref object' => [
            [
                'Foo' => [
                    'type' => 'object',
                    'properties' => [
                        'object' => [
                            '$ref' => '#/components/schemas/Bar',
                        ],
                    ],
                ],
                'Bar' => [
                    'type' => 'object',
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
                    'type' => 'object',
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
                $type = $models->get('Foo')->property('object')->phpType;

                self::assertEquals('?\Bar', $type->phpDocString());
                self::assertEquals(
                    '?array{foobar:?string,barfoo:?\Baz}',
                    $models->get('Bar')->property('obj1')->phpType->phpDocString()
                );
            }
        ];

        yield 'model one of' => [
            [
                'Bar' => [
                    'type' => 'object',
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
                    'string|bool|null',
                    $models->get('Bar')->property('foo')->phpType->nativeTypeString()
                );
            }
        ];

        yield 'enum' => [
            [
                'Bar' => [
                    'type' => 'object',
                    'required' => ['missingTypeEnum'],
                    'properties' => [
                        'missingTypeEnum' => [
                            'enum' => [
                                'foo',
                                'bar',
                            ],
                        ],
                        'typeNum' => [
                            'type' => 'integer',
                            'enum' => [
                                'foo',
                                'bar',
                            ],
                        ],
                    ],
                ],
            ],
            function (ClassModels $models) {
                $type = $models->get('Bar')->property('missingTypeEnum')->phpType;
                self::assertEquals('string', $type->nativeTypeString());
                self::assertEquals('"foo"|"bar"', $type->phpDocString());

                // wrong type but its the one it declared
                self::assertEquals('?int', $models->get('Bar')->property('typeNum')->phpType->nativeTypeString());
            }
        ];

        yield 'required' => [
            [
                'Bar' => [
                    'required' => [
                        'prop1',
                    ],
                    'type' => 'object',
                    'properties' => [
                        'prop1' => [
                            'type' => ['string'],
                        ],
                        'prop2' => [
                            'required' => true,
                            'type' => ['string'],
                        ],
                        'prop3' => [
                            'required' => false,
                            'type' => ['string'],
                        ],
                    ],
                ],
            ],
            function (ClassModels $models) {
                $type = $models->get('Bar')->property('prop1')->phpType;
                self::assertEquals('string', $type->nativeTypeString());
            }
        ];
    }
}
