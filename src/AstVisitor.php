<?php

declare(strict_types=1);


namespace DTL\OapiScg;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class AstVisitor extends NodeVisitorAbstract
{
    /**
     * @param Closure(Node):(null|int|Node|Node[]) $visitor
     */
    public function __construct(
        private \Closure $visitor
    )
    {
    }

    #[\Override]
    public function leaveNode(Node $node)
    {
        return ($this->visitor)($node);
    }
}
