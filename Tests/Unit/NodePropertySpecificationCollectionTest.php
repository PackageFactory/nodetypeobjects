<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecification;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecificationCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
class NodePropertySpecificationCollectionTest extends TestCase
{
    #[Test]
    public function creationFromNodeTypeWorks(): void
    {
        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Package:Foo.Bar'),
            [],
            [
                'properties' => [
                    'foo' => [
                        'type' => 'string',
                        'defaultValue' => 'example'
                    ],
                    'bar' => [
                        'type' => 'integer'
                    ]
                ]
            ]
        );

        $this->assertEquals(
            new NodePropertySpecificationCollection(
                new NodePropertySpecification(
                    'foo',
                    'string',
                    'example'
                ),
                new NodePropertySpecification(
                    'bar',
                    'integer',
                    null
                )
            ),
            NodePropertySpecificationCollection::createFromNodeType($nodeType)
        );
    }
}
