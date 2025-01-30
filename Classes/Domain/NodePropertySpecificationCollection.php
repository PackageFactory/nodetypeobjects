<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

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
    public array $properties;

    public function __construct(
        NodePropertySpecification ...$properties
    ) {
        $this->properties = $properties;
    }

    /**
     * @return \Generator<int, NodePropertySpecification>
     */
    public function getIterator(): \Generator
    {
        yield from $this->properties;
    }
}
