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
    public static function detectionOfNamesFromNodeTypeDataProvider(): \Generator
    {
        yield [
                'Vendor.Example:Foo.Bar',
                'Vendor\Example',
                'NodeTypes\Foo\Bar',
                'BarNodeInterface'
        ];

        yield [
            '404:404',
            '_404',
            'NodeTypes\_404',
            '_404NodeInterface'
        ];
    }

    /**
     * @test
     * @dataProvider detectionOfNamesFromNodeTypeDataProvider
     */
    public function detectionOfNamesFromNodeType(string $nodeTypeName, string $expectedPackageNamespace, string $expectedLocalNamespace, string $expectedInterface): void
    {
        $nodeType = new NodeType(
            NodeTypeName::fromString($nodeTypeName),
            [],
            []
        );

        $specification = NodeInterfaceNameSpecification::createFromNodeType(
            $nodeType,
        );

        $this->assertEquals(
            new NodeInterfaceNameSpecification(
                $nodeTypeName,
                $expectedPackageNamespace,
                $expectedLocalNamespace,
                $expectedInterface,
            ),
            $specification
        );
    }
}
