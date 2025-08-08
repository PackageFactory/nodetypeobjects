<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Test;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use PackageFactory\NodeTypeObjects\Domain\NodePropertySpecification;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
class NodePropertySpecificationTest extends TestCase
{
    public static function propertiesMatchExpectationDataProvider(): \Generator
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

    public static function creationFromNodeTypeWorksDataProvider (): \Generator
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
        $nodeType = new NodeType(
            NodeTypeName::fromString('Vendor.Package:Foo.Bar'),
            [],
            [
                'properties' => [
                    $propertyName => [
                        'type' => $propertyType,
                        'defaultValue' => $defaultValue
                    ]
                ]
            ]
        );

        $this->assertEquals(
            new NodePropertySpecification(
                $propertyName,
                $propertyType,
                $defaultValue,
            ),
            NodePropertySpecification::createFromNodeTypeAndPropertyName($nodeType, $propertyName)
        );
    }
}
