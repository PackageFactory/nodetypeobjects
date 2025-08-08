<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use PackageFactory\NodeTypeObjects\Domain\NodeInterfaceNameSpecification;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NodeInterfaceNameSpecificationTest extends TestCase
{
    #[Test]
    public function detectionOfNamesFromNodeType(): void
    {
        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Example:Foo.Bar'),
            [],
            []
        );

        $specification = NodeInterfaceNameSpecification::createFromNodeType(
            $nodeType,
        );

        $this->assertEquals(
            new NodeInterfaceNameSpecification(
                'Vendor.Example:Foo.Bar',
                'Vendor\Example\NodeTypes\Foo\Bar',
                'BarNodeInterface',
                'Vendor\Example\NodeTypes\Foo\Bar\BarNodeInterface',
            ),
            $specification
        );
    }
}
