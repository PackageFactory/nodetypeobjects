<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Domain\Model\NodeType;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecification;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class NodeTypeObjectNameSpecificationTest extends TestCase
{

    #[Test]
    public function detectionOfNamesFromNodeType(): void
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

    #[Test]
    public function noClassesButInterfaceForAbstractNodeType(): void
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
