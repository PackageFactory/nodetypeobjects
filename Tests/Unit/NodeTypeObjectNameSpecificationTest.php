<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecification;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NodeTypeObjectNameSpecificationTest extends TestCase
{

    #[Test]
    public function detectionOfNamesFromNodeType(): void
    {
        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Example:Foo.Bar'),
            [],
            []
        );

        $specification = NodeTypeObjectNameSpecification::createFromNodeType(
            $nodeType,
        );

        $this->assertEquals(
            new NodeTypeObjectNameSpecification(
                'Vendor.Example:Foo.Bar',
                'Vendor\Example\NodeTypes\Foo\Bar',
                'BarNodeObject',
                'Vendor\Example\NodeTypes\Foo\Bar\BarNodeObject',
                'BarNodeInterface',
                'Vendor\Example\NodeTypes\Foo\Bar\BarNodeInterface'
            ),
            $specification
        );
    }

    #[Test]
    public function noClassesButInterfaceForAbstractNodeType(): void
    {
        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Example:Foo.Bar'),
            [],
            [
                'abstract' => true
            ]
        );

        $specification = NodeTypeObjectNameSpecification::createFromNodeType(
            $nodeType,
        );

        $this->assertEquals(
            new NodeTypeObjectNameSpecification(
                'Vendor.Example:Foo.Bar',
                'Vendor\Example\NodeTypes\Foo\Bar',
                null,
                null,
                'BarNodeInterface',
                'Vendor\Example\NodeTypes\Foo\Bar\BarNodeInterface'
            ),
            $specification
        );
    }
}
