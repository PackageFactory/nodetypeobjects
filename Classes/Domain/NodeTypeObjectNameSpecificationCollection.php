<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeTypeObjectNameSpecificationCollection
{
    /**
     * @var array<string,NodeTypeObjectNameSpecification>
     */
    public array $items;

    public function __construct(
        NodeTypeObjectNameSpecification ...$items
    ) {
        $itemsIndexedByName = [];
        foreach ($items as $nodeTypeObjectNameSpecification) {
            $itemsIndexedByName[ $nodeTypeObjectNameSpecification->nodeTypeName ] = $nodeTypeObjectNameSpecification;
        }
        $this->items = $itemsIndexedByName;
    }

    public function findByNodeTypeName(string $nodeTypeName): ?NodeTypeObjectNameSpecification
    {
        if (array_key_exists($nodeTypeName, $this->items)) {
            return $this->items[$nodeTypeName];
        }
        return null;
    }

    public static function createFromNodeTypeAndCollection(NodeType $nodeType, self $collection): NodeTypeObjectNameSpecificationCollection
    {
        $typesToInclude = [];
        foreach ($nodeType->getDeclaredSuperTypes() as $superType) {
            $type = $collection->findByNodeTypeName($superType->getName());
            if ($type instanceof NodeTypeObjectNameSpecification) {
                $typesToInclude[] = $type;
            }
        }
        return new self(...$typesToInclude);
    }
}
