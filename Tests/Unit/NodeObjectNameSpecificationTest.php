<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use PackageFactory\NodeTypeObjects\Domain\NodeObjectNameSpecification;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
class NodeObjectNameSpecificationTest extends TestCase
{
    #[Test]
    public function detectionOfNamesFromNodeType(): void
    {
        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Example:Foo.Bar'),
            [],
            []
        );

        $specification = NodeObjectNameSpecification::createFromNodeType(
            $nodeType,
        );

        $this->assertEquals(
            new NodeObjectNameSpecification(
                'Vendor.Example:Foo.Bar',
                'Vendor\Example\NodeTypes\Foo\Bar',
                'BarNodeObject',
                'Vendor\Example\NodeTypes\Foo\Bar\BarNodeObject',
            ),
            $specification
        );
    }
}
