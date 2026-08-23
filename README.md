OAPISCG
=======

This is the **PHP Open API Source Code Generator**. It generates DTOs for OpenAPI
specs.

- Generate all or a subset of DTOs for based on the components of an Open API specification.
- Generates sub-classes up until `--inline-level` is reached and array-shapes
  after that.
- Customize the _models_: Fix errors in the specification.
- Customize the _ast_: Adapt the DTOs to your framework or serialization
  preferences.

Why Would You Do This?
----------------------

- I want strongly typed API clients.
- I _don't_ want to manually write DTOs.
- I _do_ want to write my own API clients.

Usage
-----

Generate DTOs in the root namespace:

```
./bin/oapiscg tests/Unit/Console/petstore-expanded.json path/to/output
```

Generate DTOs in your namespace:

```
./bin/oapiscg tests/Unit/Console/petstore-expanded.json path/to/output --namespace=Your\\Namespace
```

Generate lots of classes:

```
./bin/oapiscg tests/Unit/Console/petstore-expanded.json path/to/output --namespace=Your\\Namespace --inline-level=100
```

Contributions
-------------

Not really.
