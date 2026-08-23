<?php

use DTL\Docbot\Article\Article;
use DTL\Docbot\Extension\Core\Block\CreateFileBlock;
use DTL\Docbot\Extension\Core\Block\SectionBlock;
use DTL\Docbot\Extension\Core\Block\ShellBlock;
use DTL\Docbot\Extension\Core\Block\ShowFileBlock;
use DTL\Docbot\Extension\Core\Block\TextBlock;

return Article::create('../README', 'Docbot', [
    new TextBlock(<<<'MD'
        > [!WARNING]
        > This library is experimental and contains bugs. Some bugs are evident in this [scripted](https://github.com/dantleech/docbot) README file.
        > See if you can spot them 🪄

        This is the **PHP Open API Source Code Generator**. It generates DTOs for OpenAPI
        specs.

        - Generate all or a subset of DTOs for based on the components of an Open API specification.
        - Generates sub-classes up until `--inline-level` is reached and array-shapes
          after that.
        - Customize the _models_: Fix errors in the specification.
        - Customize the _ast_: Adapt the DTOs to your framework or serialization
          preferences.

        MD,
    ),
    new SectionBlock('Why would you do this?', [
        new TextBlock(<<<'MD'
            - I _don't_ want to manually write DTOs.
            - I _do_ want to write my own API clients.
            - Customize the model generation because API specs often **lie**.
            - Customize the AST to fit the DTOs into a project's particular eco-system.
            - Because I want to.
            MD
        ),
    ]),
    new SectionBlock('Usage', [
        new TextBlock(
            'Assume you have an open API spec `%path%`:',
            context: new CreateFileBlock(
                'openapi-spec.json',
                language: 'json',
                content: (string)file_get_contents(__DIR__ . '/petstore-spec.json')
            ),
        ),
        new TextBlock(
            'Create an **Oapiscg** configuration file in your project root `%path%`:',
            context: new CreateFileBlock(
                'oapiscg.php',
                language: 'php',
                content: <<<'PHP'
                    <?php

                    use DTL\OapiScg\Configs;
                    use DTL\OapiScg\Config;

                    return Configs::from([
                        'petstore' => new Config(
                            specPath: __DIR__ . '/openapi-spec.json',
                            outPath: 'src/PetstoreClient/DTO',
                            namespace: 'Acme\\PetstoreClient\\DTO',
                            components: [],
                        ),
                    ]);
                    PHP
            ),
        ),
        'Generate the DTOs:',
        new ShellBlock('oapiscg petstore', 0, env: ['PATH' => (string)getenv('PATH') . ':' . __DIR__ . '/../bin']),
        'The new DTOs have been written to the requested directory',
        new ShowFileBlock('src/PetstoreClient/DTO/NewPet.php', language: 'php'),
    ]),
    new SectionBlock('Partial Generation', [
        new TextBlock(
            'You can select which _components_ you want to generate - any dependencies will be included',
            context: new CreateFileBlock(
                'oapiscg.php',
                language: 'php',
                content: <<<'PHP'
                    <?php

                    use DTL\OapiScg\Configs;
                    use DTL\OapiScg\Config;

                    return Configs::from([
                        'petstore' => new Config(
                            specPath: __DIR__ . '/openapi-spec.json',
                            outPath: 'src/PetstoreClient/DTO',
                            namespace: 'Acme\\PetstoreClient\\DTO',
                            components: ['NewPet'],
                        ),
                    ]);
                    PHP
            ),
        ),
        'Generate the DTOs:',
        new ShellBlock('oapiscg petstore', 0, env: ['PATH' => (string)getenv('PATH') . ':' . __DIR__ . '/../bin']),
    ]),
    new SectionBlock('Inlining (array-shapes)', [
        new TextBlock(
            'The inlining level determines at what depth array-shapes are used instead of generating new classes.' .
            'you can observe this below when we set the `inlineLevel` to 0 (the default is 2)',
            context: new CreateFileBlock(
                'oapiscg.php',
                language: 'php',
                content: <<<'PHP'
                    <?php

                    use DTL\OapiScg\Configs;
                    use DTL\OapiScg\Config;

                    return Configs::from([
                        'petstore' => new Config(
                            specPath: __DIR__ . '/openapi-spec.json',
                            outPath: 'src/PetstoreClient/DTO',
                            namespace: 'Acme\\PetstoreClient\\DTO',
                            components: ['NewPet'],
                            inlineLevel: 0,
                        ),
                    ]);
                    PHP
            ),
        ),
        'Generate the DTOs:',
        new ShellBlock('oapiscg petstore', 0, env: ['PATH' => (string)getenv('PATH') . ':' . __DIR__ . '/../bin']),
        'The `owner` property as now an array instead of a new class:',
        new ShowFileBlock('src/PetstoreClient/DTO/NewPet.php', language: 'php'),
    ]),
    new SectionBlock('Modifying the models', [
        new TextBlock(
            'Oapiscg first maps the schema to class "models" which include type-mappings'.
            'You can modify the generated models to fix any errors that may be in the spec',
            context: new CreateFileBlock(
                'oapiscg.php',
                language: 'php',
                content: <<<'PHP'
                    <?php

                    use DTL\OapiScg\Configs;
                    use DTL\OapiScg\Config;
                    use DTL\OapiScg\Model;

                    return Configs::from([
                        'petstore' => new Config(
                            specPath: __DIR__ . '/openapi-spec.json',
                            outPath: 'src/PetstoreClient/DTO',
                            namespace: 'Acme\\PetstoreClient\\DTO',
                            components: ['NewPet'],
                            modelVisitors: [
                                static function (Model\ClassModel $model): void {
                                    if ($model->name->shortName() !== 'NewPet') {
                                        return;
                                    }
                                    $model->property('owner')->phpType = new Model\Type\ShapeType([
                                        'firstName' => new Model\Type\StringType(),
                                        'lastName' => new Model\Type\StringType(),
                                        'nickname' => new Model\Type\UnionType([
                                            new Model\Type\NullType(),
                                            new Model\Type\StringType(),
                                        ]),
                                    ]);
                                    unset($model->properties['name']);
                                    unset($model->properties['tag']);
                                },
                            ],
                        ),
                    ]);
                    PHP
            ),
        ),
        'Generate the DTOs:',
        new ShellBlock('oapiscg petstore', 0, env: ['PATH' => (string)getenv('PATH') . ':' . __DIR__ . '/../bin']),
        'The NewPet DTO has been customized:',
        new ShowFileBlock('src/PetstoreClient/DTO/NewPet.php', language: 'php'),
    ]),
    new SectionBlock('Modifying the AST', [
        new TextBlock(
            'You can modify the PHP AST itself to customize the DTOs to fit within your unfortunate framework or serialization library',
            context: new CreateFileBlock(
                'oapiscg.php',
                language: 'php',
                content: <<<'PHP'
                    <?php

                    use DTL\OapiScg\Configs;
                    use DTL\OapiScg\Config;
                    use DTL\OapiScg\Model;

                    return Configs::from([
                        'petstore' => new Config(
                            specPath: __DIR__ . '/openapi-spec.json',
                            outPath: 'src/PetstoreClient/DTO',
                            namespace: 'Acme\\PetstoreClient\\DTO',
                            components: ['NewPet'],
                            astVisitors: [
                                static function (\PhpParser\Node $node) {
                                    if (!$node instanceof \PhpParser\Node\Stmt\Class_) {
                                        return null;
                                    }
                                    $node->setDocComment(new \PhpParser\Comment\Doc('// Generated by you 🫵'));
                                    $node->extends = new \PhpParser\Node\Name('\\Spatie\\LaravelData\\Data');
                                    return $node;
                                }
                            ],
                        ),
                    ]);
                    PHP
            ),
        ),
        'Generate the DTOs:',
        new ShellBlock('oapiscg petstore', 0, env: ['PATH' => (string)getenv('PATH') . ':' . __DIR__ . '/../bin']),
        'The NewPet DTO has been customized:',
        new ShowFileBlock('src/PetstoreClient/DTO/NewPet.php', language: 'php'),
    ]),
]);
