<?php

declare(strict_types=1);


namespace DTL\OapiScg;

use DTL\OapiScg\Model\ClassModel;
use PhpParser\Node;

final class Config
{
    /**
     * @param list<string> $components
     * @param list<callable(ClassModel):void> $modelVisitors
     * @param list<callable(Node):void> $astVisitors
     */
    public function __construct(
        public string $specPath,
        public string $outPath,
        public string $namespace = '',
        public array $components = [],
        public int $inlineLevel = 2,
        public array $modelVisitors = [],
        public array $astVisitors = [],
    )
    {
    }
}
