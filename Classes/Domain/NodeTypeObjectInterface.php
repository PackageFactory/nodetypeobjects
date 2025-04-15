<?php

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Domain\Model\NodeInterface;

interface NodeTypeObjectInterface
{
    public static function fromNode(NodeInterface $node): self;
}
