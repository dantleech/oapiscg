<?php

namespace DTL\OapiScg;

use Closure;
use DTL\OapiScg\Model\ClassModel;

final class ModelVisitors
{
    /**
     * @param list<callable(CLassModel):void> $modelVisitors
     */
    public function __construct(private array $modelVisitors = [])
    {
    }

    public function visit(ClassModel $model): void
    {
        foreach ($this->modelVisitors as $visitor) {
            $visitor($model);
        }
    }
}
