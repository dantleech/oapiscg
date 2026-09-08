<?php

declare(strict_types=1);


namespace DTL\OapiScg;



use DTL\OapiScg\Model\ClassModels;

final class ModelVisitors
{
    /**
     * @param list<callable(ClassModels):void> $modelVisitors
     */
    public function __construct(private array $modelVisitors = [])
    {
    }

    public function visit(ClassModels $model): void
    {
        foreach ($this->modelVisitors as $visitor) {
            $visitor($model);
        }
    }
}
