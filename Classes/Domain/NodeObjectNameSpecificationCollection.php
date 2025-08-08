<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

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

    public function combine(self $other): self
    {
        return new self(...$this->items, ...$other->items);
    }
}
