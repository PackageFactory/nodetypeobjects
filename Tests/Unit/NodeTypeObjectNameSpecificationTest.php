<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Package\FlowPackageInterface;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecification;
use PHPUnit\Framework\TestCase;

class NodeTypeObjectNameSpecificationTest extends TestCase
{

    public function testDetectionOfNamesFromNodeType(): void
    {
        $nodeType = $this->createMock(NodeType::class);
        $nodeType->expects(self::any())->method('getName')->willReturn('Vendor.Example:Foo.Bar');

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

    public function testNoClassesForAbstractNodeType(): void
    {
        $nodeType = $this->createMock(NodeType::class);
        $nodeType->expects(self::any())->method('getName')->willReturn('Vendor.Example:Foo.Bar');
        $nodeType->expects(self::any())->method('isAbstract')->willReturn(true);

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
