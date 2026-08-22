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

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->classes);
    }
}
