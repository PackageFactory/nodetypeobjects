<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeTypeObjectNameSpecification
{
    public function __construct(
        public string $nodeTypeName,
        public string $phpNamespace,
        public ?string $className,
        public ?string $fullyQualifiedClassName,
        public ?string $interfaceName,
        public ?string $fullyQualifiedInterfaceName,
    ) {
    }

    public static function createFromNodeType(
        NodeType $nodeType
    ): self {

        list($packageKey, $nodeName) = explode(':', $nodeType->name->value, 2);

        $localNameParts = explode('.', $nodeName);
        $localName = array_pop($localNameParts);

        $phpNamespace = str_replace(['.', ':'], ['\\', '\\NodeTypes\\'], $nodeType->name->value);


        if ($nodeType->isAbstract()) {
            $className = null;
        } else {
            $className = str_replace('.', '\\', $localName) . 'NodeObject';
        }

        /** @var string|null $interfaceName */
        $interfaceName = str_replace('.', '\\', $localName) . 'NodeInterface';

        return new self(
            $nodeType->name->value,
            $phpNamespace,
            $className,
            $className ? $phpNamespace . '\\' . $className : null,
            $interfaceName,
            $interfaceName ? $phpNamespace . '\\' . $interfaceName : null
        );
    }
}
