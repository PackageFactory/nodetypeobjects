<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

interface NodeTypeObjectInterface
{
    public static function fromNode(Node $node): self;
}
