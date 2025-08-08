<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeObjectNameSpecificationCollection
{
    /**
     * @var array<string,NodeObjectNameSpecification>
     */
    public array $items;

    public function __construct(
        NodeObjectNameSpecification ...$items
    ) {
        $itemsIndexedByName = [];
        foreach ($items as $nodeTypeObjectNameSpecification) {
            $itemsIndexedByName[ $nodeTypeObjectNameSpecification->nodeTypeName ] = $nodeTypeObjectNameSpecification;
        }
        $this->items = $itemsIndexedByName;
    }

    public function findByNodeTypeName(string $nodeTypeName): ?NodeObjectNameSpecification
    {
        if (array_key_exists($nodeTypeName, $this->items)) {
            return $this->items[$nodeTypeName];
        }
        return null;
    }

    public function combine(self $other): self
    {
        return new self(...$this->items, ...$other->items);
    }
}
