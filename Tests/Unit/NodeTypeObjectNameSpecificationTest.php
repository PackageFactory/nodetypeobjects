<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Package\FlowPackageInterface;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecification;
use PHPUnit\Framework\TestCase;

class NodeTypeObjectNameSpecificationTest extends TestCase
{

    public function testDetectionOfNamesFromPackageAndNodeType(): void
    {
        $package = $this->createMock(FlowPackageInterface::class);
        $package->expects(self::any())->method('getPackageKey')->willReturn('Vendor.Example');
        $package->expects(self::any())->method('getPackagePath')->willReturn('/var/www/html/Packages/Vendor.Example/');

        // nodetype has a name and
        $nodeType = $this->createMock(NodeType::class);
        $nodeType->expects(self::any())->method('getName')->willReturn('Vendor.Example:Foo.Bar');
        $nodeType->expects(self::any())->method('getConfiguration')->willReturnCallback(
            fn($path) => match($path) {
                'options.nodeTypeObjects.generateClass' => true,
                'options.nodeTypeObjects.generateInterface' => true,
                default => $this->fail(),
            }
        );

        $specification = NodeTypeObjectNameSpecification::createFromPackageAndNodeType(
            $package,
            $nodeType,
        );

        $this->assertEquals(
            new NodeTypeObjectNameSpecification(
                '/var/www/html/Packages/Vendor.Example/NodeTypes/Foo/Bar',
                'Vendor.Example:Foo.Bar',
                'Vendor\Example\NodeTypes\Foo\Bar',
                'BarNodeObject',
                'BarNodeInterface',
            ),
            $specification
        );
    }

    public function testNodesFromOtherPackagesThrowException(): void
    {
        $package = $this->createMock(FlowPackageInterface::class);
        $package->expects(self::any())->method('getPackageKey')->willReturn('Vendor.Example');
        $package->expects(self::any())->method('getPackagePath')->willReturn('/var/www/html/Packages/Vendor.Example/');

        // nodetype has a name and
        $nodeType = $this->createMock(NodeType::class);
        $nodeType->expects(self::any())->method('getName')->willReturn('Vendor.Other:Foo.Bar');

        $this->expectException(\Exception::class);

        NodeTypeObjectNameSpecification::createFromPackageAndNodeType(
            $package,
            $nodeType,
        );
    }
}
