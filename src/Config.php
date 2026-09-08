<?php

declare(strict_types=1);


namespace DTL\OapiScg;

use DTL\OapiScg\Model\ClassModels;
use PhpParser\Node;

final class Config
{
    /**
     * @param list<string> $components
     * @param list<callable(ClassModels):void> $modelVisitors
     * @param list<Closure(Node):(null|int|Node|Node[])> $astVisitors
     * @param array<string, string> $unions
     */
    public function __construct(
        public string $specPath,
        public string $outPath,
        public string $namespace = '',
        public array $components = [],
        public int $inlineLevel = 2,
        public array $modelVisitors = [],
        public array $astVisitors = [],
        public array $unions = [],
    )
    {
    }
}
