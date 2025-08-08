<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeObjectNameSpecification
{
    public function __construct(
        public string $nodeTypeName,
        public string $phpNamespace,
        public string $className,
    ) {
    }

    public function getFullyQualifiedClassName(): string
    {
        return $this->phpNamespace . '\\' . $this->className;
    }

    public static function createFromNodeTypeName(
        NodeTypeName $nodeTypeName
    ): self {

        list($packageKey, $nodeName) = explode(':', $nodeTypeName->value, 2);

        $localNameParts = explode('.', $nodeName);
        $localName = array_pop($localNameParts);

        $phpNamespace = str_replace(['.', ':'], ['\\', '\\NodeTypes\\'], $nodeTypeName->value);
        $className = str_replace('.', '\\', $localName) . 'NodeObject';

        return new self(
            $nodeTypeName->value,
            $phpNamespace,
            $className,
        );
    }

    public static function createFromNodeType(
        NodeType $nodeType
    ): self {
        return self::createFromNodeTypeName($nodeType->name);
    }
}
