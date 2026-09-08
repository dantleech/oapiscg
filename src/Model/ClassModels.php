<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model;

use IteratorAggregate;
use Traversable;
/**
 * @implements IteratorAggregate<ClassModel>
 */
final class ClassModels implements IteratorAggregate
{
    /**
     * @param array<array-key, ClassModel> $classes
     */
    private function __construct(public array $classes)
    {
    }

    public static function fromClassModels(ClassModel ...$classModels): self
    {
        $models = [];
        foreach ($classModels as $model) {
            $models[$model->name->toString()] = $model;
        }
        return new self($models);
    }

    public function get(string $string): ClassModel
    {
        if (!array_key_exists($string, $this->classes)) {
            throw new \RuntimeException(sprintf(
                'Class "%s" does not exist, known classes: "%s"',
                $string,
                implode('", "', array_keys($this->classes)),
            ));
        }

        return $this->classes[$string];
    }

    /**
     * Return a new model represnting the union of all the classes in this collection
     */
    public function union(string $name): ClassModel
    {
        $properties = [];
        foreach ($this->classes as $class) {
            foreach ($class->properties as $property) {
                if (!isset($properties[$property->name])) {
                    $properties[$property->name] = $property;
                    continue;
                }

                if ($properties[$property->name] == $property) {
                    continue;
                }

                throw new \RuntimeException(sprintf(
                    'Property "%s" on class "%s" is incompatible with previously parsed property: "%s"',
                    $property->name, $class->name->toString(), $properties[$property->name]->phpType->phpDocString()
                ));
            }
        }

        return new ClassModel(FullyQualifiedName::fromString($name), $properties);
    }

    public function set(ClassModel $model): void
    {
        $this->classes[$model->name->toString()] = $model;
    }

    public function remove(ClassModel ...$models): void
    {
       $this->classes = array_filter($this->classes, static fn (ClassModel $model) => false === in_array($model, $models, true));
    }

    public function byShortNames(string ...$shortNames): ClassModels
    {
        return new self(array_filter($this->classes, static fn (ClassModel $model) => in_array($model->name->shortName(), $shortNames, true)));
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->classes);
    }
}
