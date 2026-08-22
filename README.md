OAPISCG
=======

This is the **PHP Open API Source Code Generator**. It generates DTOs for OpenAPI
specs.

- Generate all or a subset of DTOs for an Open API specification.
- All the types.
- Generates classes sub-objects up until `--inline-level` is reached and
  it will then generate an `array-shape` phpdoc type after that.

Why
---

There are many ways to generate code from API specs, why did you create this?

🤷

- Because I don't want to drop 5k lines of API code into a repository when
  only a small subset of that code is going to be used.
- I want to build my own API clients, I don't want to code DTOs.

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
