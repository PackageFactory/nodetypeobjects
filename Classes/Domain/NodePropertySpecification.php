<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodePropertySpecification
{
    public function __construct(
        readonly string $propertyName,
        readonly string $propertyType
    ) {
    }
}
