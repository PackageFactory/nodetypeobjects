<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeTypeObjectSpecification
{
    public function __construct(
        public string $nodeTypeName,
        public string $className,
        public string $fileNameWithPath,
        public NodePropertySpecificationCollection $properties
    ) {
    }
}
