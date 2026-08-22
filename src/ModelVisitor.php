<?php

declare(strict_types=1);


namespace DTL\OapiScg;

use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\ClassModels;

final class ModelVisitor
{
    /**
     * @param list<callable(ClassModel):mixed> $visitors
     */
    public function __construct(private array $visitors)
    {
    } 

    public function walk(ClassModels $models): void
    {
        foreach ($models as $model) {
            $this->visit($model);
        }
    }

    public function visit(ClassModel $model)
    {
        foreach ($this->visitors as $visitor) {
            $visitor($model);
        }
    }
}
