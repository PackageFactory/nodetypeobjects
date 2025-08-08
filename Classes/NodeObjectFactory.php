<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use PackageFactory\NodeTypeObjects\Domain\NodeObjectNameSpecification;

class NodeObjectFactory
{
    public static function forNode(Node $node): NodeObjectInterface
    {
        $nodeObjectName = NodeObjectNameSpecification::createFromNodeTypeName($node->nodeTypeName);
        $nodeObjectClass = $nodeObjectName->getFullyQualifiedClassName();
        if (class_exists($nodeObjectClass) && is_a($nodeObjectClass, NodeObjectInterface::class, true)) {
            return $nodeObjectClass::fromNode($node);
        } else {
            throw new \Exception('NodeObject class does not exist: ' . $nodeObjectClass);
        }
    }

    public static function tryForNode(Node $node): ?NodeObjectInterface
    {
        $nodeObjectName = NodeObjectNameSpecification::createFromNodeTypeName($node->nodeTypeName);
        $nodeObjectClass = $nodeObjectName->getFullyQualifiedClassName();
        if (class_exists($nodeObjectClass) && is_a($nodeObjectClass, NodeObjectInterface::class, true)) {
            return $nodeObjectClass::fromNode($node);
        } else {
            return null;
        }
    }
}
