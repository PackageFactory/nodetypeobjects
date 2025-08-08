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

    public static function detectionOfNamesFromNodeTypeDataProvider(): \Generator
    {
        yield [
            'Vendor.Example:Foo.Bar',
            'Vendor\Example',
            'NodeTypes\Foo\Bar',
            'BarNodeObject'
        ];

        yield [
            '404:404',
            '_404',
            'NodeTypes\_404',
            '_404NodeObject'
        ];
    }

    /**
     * @test
     * @dataProvider detectionOfNamesFromNodeTypeDataProvider
     */
    public function detectionOfNamesFromNodeType(string $nodeTypeName, string $expectedPackageNamespace, string $expectedLocalNamespace, string $expectedClass): void
    {
        $nodeType = new NodeType(
            NodeTypeName::fromString($nodeTypeName),
            [],
            []
        );

        $specification = NodeObjectNameSpecification::createFromNodeType(
            $nodeType,
        );

        $this->assertEquals(
            new NodeObjectNameSpecification(
                $nodeTypeName,
                $expectedPackageNamespace,
                $expectedLocalNamespace,
                $expectedClass,
            ),
            $specification
        );
    }
}
