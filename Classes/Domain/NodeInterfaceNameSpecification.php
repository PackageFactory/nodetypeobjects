<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeInterfaceNameSpecification
{
    public function __construct(
        public string $nodeTypeName,
        public string $phpNamespace,
        public string $interfaceName
    ) {
    }

    public function getFullyQualifiedClassName(): string
    {
        return $this->phpNamespace . '\\' . $this->interfaceName;
    }

    public static function createFromNodeTypeName(
        NodeTypeName $nodeTypeName
    ): self {

        list($packageKey, $nodeName) = explode(':', $nodeTypeName->value, 2);

        $localNameParts = explode('.', $nodeName);
        $localName = array_pop($localNameParts);

        $phpNamespace = str_replace(['.', ':'], ['\\', '\\NodeTypes\\'], $nodeTypeName->value);
        $interfaceName = str_replace('.', '\\', $localName) . 'NodeInterface';

        return new self(
            $nodeTypeName->value,
            $phpNamespace,
            $interfaceName
        );
    }

    public static function createFromNodeType(
        NodeType $nodeType
    ): self {
        return self::createFromNodeTypeName($nodeType->name);
    }
}
