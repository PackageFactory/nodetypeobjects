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
        public string $packageNamespace,
        public string $localNamespace,
        public string $interfaceName,
    ) {
    }

    public function getFullNamespace(): string
    {
        return $this->packageNamespace . '\\' . $this->localNamespace;
    }

    public function getFullyQualifiedClassName(): string
    {
        return $this->packageNamespace . '\\' . $this->localNamespace . '\\' . $this->interfaceName;
    }

    public function getLocalDirectoryName(): string
    {
        return str_replace('\\', DIRECTORY_SEPARATOR, $this->localNamespace);
    }

    public function getLocalFileName(): string
    {
        return str_replace('\\', DIRECTORY_SEPARATOR, $this->localNamespace) . DIRECTORY_SEPARATOR . $this->interfaceName . '.php';
        ;
    }

    public static function createFromNodeTypeName(
        NodeTypeName $nodeTypeName
    ): self {
        list($packageKey, $nodeName) = explode(':', $nodeTypeName->value, 2);
        $packageKeyParts = explode('.', $packageKey);
        $localNameParts = explode('.', $nodeName);
        $localName = $localNameParts[array_key_last($localNameParts)];
        return new self(
            $nodeTypeName->value,
            PhpNameHelper::sanitize(implode('\\', $packageKeyParts)),
            'NodeTypes\\' . PhpNameHelper::sanitize(implode('\\', $localNameParts)),
            PhpNameHelper::sanitize(str_replace('.', '\\', $localName)) . 'NodeInterface'
        );
    }

    public static function createFromNodeType(
        NodeType $nodeType
    ): self {
        return self::createFromNodeTypeName($nodeType->name);
    }
}
