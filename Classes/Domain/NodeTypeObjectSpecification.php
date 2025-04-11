<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\FlowPackageInterface;

#[Flow\Proxy(false)]
readonly class NodeTypeObjectSpecification
{
    public function __construct(
        public NodeTypeObjectNameSpecification $names,
        public NodePropertySpecificationCollection $properties,
        public NodeTypeObjectNameSpecificationCollection $superTypes,
    ) {
    }

    public static function createFromPackageAndNodeType(
        FlowPackageInterface $package,
        NodeType $nodeType,
        NodeTypeObjectNameSpecificationCollection $nameCollection
    ): self {
        return new NodeTypeObjectSpecification(
            NodeTypeObjectNameSpecification::createFromPackageAndNodeType($package, $nodeType),
            NodePropertySpecificationCollection::createFromNodeType($nodeType),
            NodeTypeObjectNameSpecificationCollection::createFromNodeTypeAndCollection($nodeType, $nameCollection)
        );
    }

    public function toPhpClassString(): ?string
    {
        $propertyAccessors = '';
        $internalPropertyAccessors = '';

        foreach ($this->properties as $property) {
            $propertyIsInternal = str_starts_with($property->propertyName, '_');
            if ($propertyIsInternal) {
                $internalPropertyAccessors .= $property->toPhpClassMethodString();
            } else {
                $propertyAccessors .= $property->toPhpClassMethodString();
            }
        }

        $interfaceNames = [];
        if ($this->names->interfaceName) {
            $interfaceNames[] = $this->names->interfaceName;
        }

        foreach ($this->superTypes->items as $superType) {
            if ($superType->interfaceName) {
                $interfaceNames[] = '\\' . $superType->phpNamespace . '\\' . $superType->interfaceName;
            }
        }

        if (count($interfaceNames) > 0) {
            $interfaceDeclaration = 'implements ' . implode(', ', $interfaceNames);
        } else {
            $interfaceDeclaration = '';
        }


        $class = <<<EOL
        <?php

        declare(strict_types=1);

        namespace {$this->names->phpNamespace};

        use Neos\ContentRepository\Domain\Model\NodeInterface;
        use Neos\Flow\Annotations as Flow;

        /**
         * AUTOGENERATED CODE ... DO NOT MODIFY !!!
         *
         * run `./flow nodetypeobjects:build` to regenerate this
         */
        #[Flow\Proxy(false)]
        final readonly class {$this->names->className} {$interfaceDeclaration}
        {
            private function __construct(
                public NodeInterface \$node
            ) {
            }

            public static function fromNode(NodeInterface \$node): self
            {
                if (\$node->getNodeType()->getName() !== "{$this->names->nodeTypeName}") {
                    throw new \Exception("unsupported nodetype " . \$node->getNodeType()->getName());
                }
                return new self(\$node);
            }

            // property accessors
            {$propertyAccessors}

            // internal property accessors
            {$internalPropertyAccessors}
        }

        EOL;

        return $class;
    }

    public function toPhpInterfaceString(): ?string
    {

        $propertyAccessors = '';
        $internalPropertyAccessors = '';

        foreach ($this->properties as $property) {
            $propertyIsInternal = str_starts_with($property->propertyName, '_');
            if ($propertyIsInternal) {
                $internalPropertyAccessors .= $property->toPhpInterfaceMethodString();
            } else {
                $propertyAccessors .= $property->toPhpInterfaceMethodString();
            }
        }

        $class = <<<EOL
        <?php

        declare(strict_types=1);

        namespace {$this->names->phpNamespace};

        use Neos\ContentRepository\Domain\Model\NodeInterface;
        use Neos\Flow\Annotations as Flow;

        /**
         * AUTOGENERATED CODE ... DO NOT MODIFY !!!
         *
         * run `./flow nodetypeobjects:build` to regenerate this
         */
        interface {$this->names->interfaceName}
        {
            // property accessors
            $propertyAccessors

            // internal property accessors
            $internalPropertyAccessors
        }

        EOL;

        return $class;
    }
}
