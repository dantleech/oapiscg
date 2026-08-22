<?php

namespace DTL\OapiScg;

use DTL\OapiScg\Model\ClassModel;

final class Config
{
    /**
     * @param list<string> $components
     * @param list<callable(ClassModel):void> $visitors
     */
    public function __construct(
        public string $specPath,
        public string $outPath,
        public string $namespace = '',
        public array $components = [],
        public int $inlineLevel = 2,
        public array $visitors = [],
    )
    {
    }
}
