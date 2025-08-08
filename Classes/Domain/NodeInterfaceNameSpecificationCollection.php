<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeInterfaceNameSpecificationCollection
{
    /**
     * @var array<string,NodeInterfaceNameSpecification>
     */
    public array $items;

    public function __construct(
        NodeInterfaceNameSpecification ...$items
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

    public static function createFromNodeType(NodeType $nodeType, bool $checkForExistence = false): self
    {
        $interfaces = [
            NodeInterfaceNameSpecification::createFromNodeType($nodeType)
        ];
        foreach ($nodeType->getDeclaredSuperTypes() as $superType) {
            $interface = NodeInterfaceNameSpecification::createFromNodeType($superType);
            if ($checkForExistence && interface_exists($interface->fullyQualifiedInterfaceName)) {
                $interfaces[] = $interface;
            } else {
                $interfaces[] = $interface;
            }
        }
        return new self(...$interfaces);
    }

    public function asImplementsStatement(): string
    {
        if (empty($this->items)) {
            return '';
        } else {
            return 'implements ' . implode(', ', array_map(fn(NodeInterfaceNameSpecification $item)=> $item->fullyQualifiedInterfaceName, $this->items));
        }
    }
}
