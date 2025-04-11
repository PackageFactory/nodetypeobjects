<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
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

        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Example:Foo.Bar'),
            [],
            [
                'options' => [
                    'nodeTypeObjects' => [
                        'generateClass' => true,
                        'generateInterface' => true,
                    ]
                ]
            ]
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
        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Other:Foo.Bar'),
            [],
            [
                'options' => [
                    'nodeTypeObjects' => [
                        'generateClass' => true,
                        'generateInterface' => true,
                    ]
                ]
            ]
        );

        $this->expectException(\Exception::class);

        NodeTypeObjectNameSpecification::createFromPackageAndNodeType(
            $package,
            $nodeType,
        );
    }
}
