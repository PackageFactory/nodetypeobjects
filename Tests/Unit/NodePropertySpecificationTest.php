<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Domain\Model\NodeType;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecification;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
class NodePropertySpecificationTest extends TestCase
{
    public function propertiesMatchExpectationDataProvider(): \Generator
    {
        yield 'string without default' => [
            'title',
            'string',
            null,
            <<<'EOF'

                public function getTitle(): ?string;

            EOF,
            <<<'EOF'

                public function getTitle(): ?string
                {
                    $value = $this->node->getProperty('title');
                    if (is_string($value)) {
                        return $value;
                    }
                    return NULL;
                }

            EOF
        ];

        yield 'string with default' => [
            'title',
            'string',
            'defaultValue',
            <<<'EOF'

                public function getTitle(): string;

            EOF,
            <<<'EOF'

                public function getTitle(): string
                {
                    $value = $this->node->getProperty('title');
                    if (is_string($value)) {
                        return $value;
                    }
                    return 'defaultValue';
                }

            EOF
        ];
    }

    #[Test]
    #[DataProvider('propertiesMatchExpectationDataProvider')]
    public function propertiesMatchExpectation(string $propertyName, string $propertyType, mixed $defaultValue, string $expectedSignature, string $expectedMethod): void
    {
        $property = new NodePropertySpecification(
            $propertyName,
            $propertyType,
            $defaultValue
        );

        $this->assertEquals($expectedSignature, $property->toPhpInterfaceMethodString());
        $this->assertEquals($expectedMethod, $property->toPhpClassMethodString());
    }

    public function creationFromNodeTypeWorksDataProvider (): \Generator
    {
        yield 'string without default' => [
            'title',
            'string',
            null
        ];
        yield 'string with default' => [
            'title',
            'string',
            'defaultValue'
        ];
    }

    #[Test]
    #[DataProvider('creationFromNodeTypeWorksDataProvider')]
    public function creationFromNodeTypeWorks($propertyName, $propertyType, $defaultValue): void
    {
        $mockNodeType = $this->createMock(NodeType::class);
        $mockNodeType->expects(self::once())->method('getPropertyType')->with($propertyName)->willReturn($propertyType);
        $mockNodeType->expects(self::once())->method('getConfiguration')->with('properties.' . $propertyName . '.defaultValue')->willReturn($defaultValue);

        $this->assertEquals(
            new NodePropertySpecification(
                $propertyName,
                $propertyType,
                $defaultValue,
            ),
            NodePropertySpecification::createFromNodeTypeAndPropertyName($mockNodeType, $propertyName)
        );
    }
}
