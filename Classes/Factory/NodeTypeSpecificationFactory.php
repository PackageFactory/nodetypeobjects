<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Factory;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Package\FlowPackageInterface;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecification;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecificationCollection;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectSpecification;

class NodeTypeSpecificationFactory
{
    public function createFromPackageKeyAndNodeType(
        FlowPackageInterface $package,
        NodeType $nodeType
    ): NodeTypeObjectSpecification {
        if (!str_starts_with($nodeType->getName(), $package->getPackageKey() . ':')) {
            throw new \Exception("Only nodetypes from the given package are allowed");
        }
        $localNameParts = explode('.', str_replace($package->getPackageKey() . ':', '', $nodeType->getName()));
        $localName = array_pop($localNameParts);
        $localNamespace = implode('.', $localNameParts);

        $fileName = $package->getPackagePath()
            . 'NodeTypes' . DIRECTORY_SEPARATOR
            . str_replace('.', DIRECTORY_SEPARATOR, $localNamespace)
            . DIRECTORY_SEPARATOR . $localName . DIRECTORY_SEPARATOR . $localName . 'NodeObject.php';

        $className = str_replace('.', '\\', $package->getPackageKey())
            . '\\NodeTypes'
            . '\\' . str_replace('.', '\\', $localNamespace)
            . '\\' . str_replace('.', '\\', $localName)
            . '\\' . str_replace('.', '\\', $localName) . 'NodeObject';

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

        return new NodeTypeObjectSpecification(
            $nodeType->getName(),
            $className,
            $fileName,
            new NodePropertySpecificationCollection(...$propertySpecifications)
        );
    }
}
