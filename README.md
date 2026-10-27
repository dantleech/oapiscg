Docbot
======

> [!WARNING]
> This library is experimental and probably contains bugs.

This is the **PHP Open API Source Code Generator**. It generates DTOs for OpenAPI
specs.

- Generate all or a subset of DTOs for based on the components of an Open API specification.
- Generates sub-classes up until `--inline-level` is reached and array-shapes
  after that.
- Customize the _models_: Fix errors in the specification.
- Customize the _ast_: Adapt the DTOs to your framework or serialization
  preferences.


Why would you do this?
----------------------

- I _don't_ want to manually write DTOs.
- I _do_ want to write my own API clients.
- Customize the model generation because API specs often **lie**.
- Customize the AST to fit the DTOs into a project's particular eco-system.
- Because I want to.

Usage
-----

Assume you have an open API spec `openapi-spec.json`:
```json
{
  "openapi": "3.0.0",
  "info": {
    "title": "Swagger Petstore"
  },
  "components": {
    "schemas": {
      "Pet": {
        "allOf": [
          {
            "$ref": "#/components/schemas/NewPet"
          },
          {
            "type": "object",
            "required": ["id"],
            "properties": {
              "id": {
                "type": "integer",
                "format": "int64"
              }
            }
          }
        ]
      },
      "NewPet": {
        "type": "object",
        "required": ["name"],
        "properties": {
          "name": {
            "type": "string"
          },
          "owner": {
              "type": "object",
              "properties": {
                  "name": {"type": "string"}
              }
          },
          "tag": {
            "type": "string"
          }
        }
      },
      "Error": {
        "type": "object",
        "required": ["code", "message"],
        "properties": {
          "code": {
            "type": "integer",
            "format": "int32"
          },
          "message": {
            "type": "string"
          }
        }
      }
    }
  }
}

```
Create an **Oapiscg** configuration file in your project root `oapiscg.php`:
```php
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
```
Generate the DTOs:
```shell
$ oapiscg petstore
Writing to directory: src/PetstoreClient/DTO

 144 bytes > NewPet.Owner
 219 bytes > NewPet
 232 bytes > Pet
 150 bytes > Error

 Generated 4 classes in 0.0032 seconds 


```
The new DTOs have been written to the requested directory
``````php
# src/PetstoreClient/DTO/NewPet.php
<?php

namespace Acme\PetstoreClient\DTO;

final class NewPet
{
    public function __construct(public string $name, public ?string $tag = null, public ?\Acme\PetstoreClient\DTO\NewPet\Owner $owner = null)
    {
    }
}
``````

Partial Generation
------------------

You can select which _components_ you want to generate - any dependencies will be included
```php
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
```
Generate the DTOs:
```shell
$ oapiscg petstore
Writing to directory: src/PetstoreClient/DTO

 144 bytes > NewPet.Owner
 219 bytes > NewPet

 Generated 2 classes in 0.0027 seconds 


```

Inlining (array-shapes)
-----------------------

The inlining level determines at what depth array-shapes are used instead of generating new classes.you can observe this below when we set the `inlineLevel` to 0 (the default is 2)
```php
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
```
Generate the DTOs:
```shell
$ oapiscg petstore
Writing to directory: src/PetstoreClient/DTO

 245 bytes > NewPet

 Generated 1 classes in 0.0030 seconds 


```
The `owner` property as now an array instead of a new class:
``````php
# src/PetstoreClient/DTO/NewPet.php
<?php

namespace Acme\PetstoreClient\DTO;

final class NewPet
{
    /**
     * @param ?array{name:?string} $owner
     */
    public function __construct(public string $name, public ?string $tag = null, public ?array $owner = null)
    {
    }
}
``````

Modifying the models
--------------------

Oapiscg first maps the schema to class "models" which include type-mappingsYou can modify the generated models to fix any errors that may be in the spec
```php
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
            static function (Model\ClassModels $models): void {
                foreach ($models->byShortNames('NewPet') as $model) {
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
                }
            },
        ],
    ),
]);
```
Generate the DTOs:
```shell
$ oapiscg petstore
Writing to directory: src/PetstoreClient/DTO

 144 bytes > NewPet.Owner
 231 bytes > NewPet

 Generated 2 classes in 0.0037 seconds 


```
The NewPet DTO has been customized:
``````php
# src/PetstoreClient/DTO/NewPet.php
<?php

namespace Acme\PetstoreClient\DTO;

final class NewPet
{
    /**
     * @param array{firstName:string,lastName:string,nickname:?string} $owner
     */
    public function __construct(public array $owner = null)
    {
    }
}
``````

Modifying the AST
-----------------

You can modify the PHP AST itself to customize the DTOs to fit within your unfortunate framework or serialization library
```php
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
```
Generate the DTOs:
```shell
$ oapiscg petstore
Writing to directory: src/PetstoreClient/DTO

 202 bytes > NewPet.Owner
 277 bytes > NewPet

 Generated 2 classes in 0.0027 seconds 


```
The NewPet DTO has been customized:
``````php
# src/PetstoreClient/DTO/NewPet.php
<?php

namespace Acme\PetstoreClient\DTO;

// Generated by you 🫵
final class NewPet extends \Spatie\LaravelData\Data
{
    public function __construct(public string $name, public ?string $tag = null, public ?\Acme\PetstoreClient\DTO\NewPet\Owner $owner = null)
    {
    }
}
``````

