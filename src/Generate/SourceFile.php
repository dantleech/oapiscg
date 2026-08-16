<?php

namespace DTL\OapiScg\Generate;

use PhpParser\Node\Stmt;

final class SourceFile
{
    /**
     * @param list<Stmt> $stmts
     */
    public function __construct(public string $name, public array $stmts)
    {
    }
}
