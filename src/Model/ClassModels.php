<?php

namespace DTL\OapiScg\Model;

final class ClassModels
{
    /**
     * @param array<array-key, ClassModel> $classes
     */
    private function __construct(private array $classes)
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
}
