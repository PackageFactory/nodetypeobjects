<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Domain\Model\NodeType;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecification;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecificationCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
class NodePropertySpecificationCollectionTest extends TestCase
{
    #[Test]
    public function creationFromNodeTypeWorks(): void
    {
        $mockNodeType = $this->createMock(NodeType::class);
        $mockNodeType->expects(self::once())->method('getProperties')->willReturn(['foo' => [], 'bar' => []]);
        $mockNodeType->expects(self::any())->method('getPropertyType')->willReturnCallback(
            fn($name) => match ($name) {
                'foo' => 'string',
                'bar' => 'integer',
                default => self::fail('this was unexpected')
            }
        );
        $mockNodeType->expects(self::any())->method('getConfiguration')->willReturnCallback(
            fn($name) => match ($name) {
                'properties.foo.defaultValue' => 'example',
                'properties.bar.defaultValue' => null,
                default => self::fail('this was unexpected')
            }
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
            NodePropertySpecificationCollection::createFromNodeType($mockNodeType)
        );
    }
}
