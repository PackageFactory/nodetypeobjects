<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\FlowPackageInterface;

#[Flow\Proxy(false)]
readonly class NodeTypeObjectNameSpecification
{
    public function __construct(
        public string $directory,
        public string $nodeTypeName,
        public string $phpNamespace,
        public ?string $className,
        public ?string $interfaceName
    ) {
    }

    public static function createFromPackageAndNodeType(
        FlowPackageInterface $package,
        NodeType $nodeType
    ): self {

        if (!str_starts_with($nodeType->getName(), $package->getPackageKey() . ':')) {
            throw new \Exception("Only nodetypes from the given package are allowed");
        }

        $localNameParts = explode('.', str_replace($package->getPackageKey() . ':', '', $nodeType->getName()));
        $localName = array_pop($localNameParts);
        $localNamespace = implode('.', $localNameParts);

        $namespace = str_replace('.', '\\', $package->getPackageKey())
            . '\\NodeTypes'
            . ($localNamespace ? '\\' . str_replace('.', '\\', $localNamespace) : '')
            . '\\' . $localName;

        $directory = $package->getPackagePath()
            . 'NodeTypes' . DIRECTORY_SEPARATOR
            . ($localNamespace ? str_replace('.', DIRECTORY_SEPARATOR, $localNamespace) . DIRECTORY_SEPARATOR : '')
            . $localName;

        if ($nodeType->getConfiguration('options.nodeTypeObjects.generateClass') === true) {
            $className = str_replace('.', '\\', $localName) . 'NodeObject';
        } else {
            $className = null;
        }
        if ($nodeType->getConfiguration('options.nodeTypeObjects.generateInterface') === true) {
            $interfaceName = str_replace('.', '\\', $localName) . 'NodeInterface';
        } else {
            $interfaceName = null;
        }

        return new self(
            $directory,
            $nodeType->getName(),
            $namespace,
            $className,
            $interfaceName,
        );
    }
}
