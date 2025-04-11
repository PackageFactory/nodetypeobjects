<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<int, NodePropertySpecification>
 */
#[Flow\Proxy(false)]
readonly class NodePropertySpecificationCollection implements \IteratorAggregate
{
    /**
     * @var NodePropertySpecification[]
     */
    public array $items;

    public function __construct(
        NodePropertySpecification ...$properties
    ) {
        $this->items = $properties;
    }

    /**
     * @return \Generator<int, NodePropertySpecification>
     */
    public function getIterator(): \Generator
    {
        yield from $this->items;
    }

    public static function createFromNodeType(NodeType $nodeType): NodePropertySpecificationCollection
    {
        /**
         * @var NodePropertySpecification[] $propertySpecifications
         */
        $propertySpecifications = [];
        foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
            $propertySpecifications[] = new NodePropertySpecification(
                $propertyName,
                $nodeType->getPropertyType($propertyName),
                $propertyConfiguration['defaultValue'] ?? null
            );
        }
        return new NodePropertySpecificationCollection(...$propertySpecifications);
    }
}
