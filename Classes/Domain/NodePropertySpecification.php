<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;

#[Flow\Proxy(false)]
readonly class NodePropertySpecification
{
    public function __construct(
        public string $propertyName,
        public string $propertyType,
        public mixed $defaultValue,
    ) {
    }

    public function getPhpType(): string
    {
        $phpType = $this->propertyType;

        if (str_ends_with($this->propertyType, '[]')) {
            $phpType = 'array';
        } elseif (str_starts_with($this->propertyType, 'array<') && str_ends_with($this->propertyType, '>')) {
            $phpType = 'array';
        } elseif ($this->propertyType  === 'boolean') {
            $phpType = 'bool';
        } elseif ($this->propertyType  === 'integer') {
            $phpType = 'int';
        } elseif ($this->propertyType  === 'DateTime') {
            $phpType = '\DateTime';
        }

        if (str_contains($phpType, '\\') && !str_starts_with($phpType, '\\')) {
            $phpType =  '\\' . $phpType;
        }

        return $phpType;
    }

    public function getAnnotationType(): ?string
    {
        $annotationType = null;

        if (str_ends_with($this->propertyType, '[]')) {
            $annotationType = $this->propertyType;
        } elseif (str_starts_with($this->propertyType, 'array<') && str_ends_with($this->propertyType, '>')) {
            $annotationType = substr($this->propertyType, 6, -1) . '[]';
        }

        if (is_string($annotationType) && str_contains($annotationType, '\\') && !str_starts_with($annotationType, '\\')) {
            $annotationType =  '\\' . $annotationType;
        }

        return $annotationType;
    }

    public function toPhpClassMethodString(): string
    {
        $propertyIsInternal = str_starts_with($this->propertyName, '_');
        if ($propertyIsInternal) {
            $methodName = 'getInternal' . UnicodeFunctions::ucfirst(substr($this->propertyName, 1));
        } else {
            $methodName = 'get' . UnicodeFunctions::ucfirst($this->propertyName);
        }

        $annotationType = $this->getAnnotationType();
        $phpType = $this->getPhpType();

        $returnType = ($this->defaultValue === null) ? '?' . $phpType : $phpType;

        $defaultReturn = match (true) {
            ($this->propertyType === 'DateTime' && is_string($this->defaultValue)) => 'new \DateTime(\'' . $this->defaultValue . '\')',
            default => var_export($this->defaultValue, true),
        };

        $typeCheck = match ($phpType) {
            'null' => 'is_null($value)',
            'string' => 'is_string($value)',
            'int' => 'is_int($value)',
            'float' => 'is_float($value)',
            'bool' => 'is_bool($value)',
            'array' => 'is_array($value)',
            default => '$value instanceof ' . $phpType,
        };

        if ($annotationType) {
            $propertyAccessor = <<<EOL

                    /**
                     * @return ?$annotationType;
                     */
                    public function $methodName(): $returnType
                    {
                        \$value = \$this->node->getProperty('$this->propertyName');
                        if ($typeCheck) {
                            return \$value;
                        }
                        return $defaultReturn;
                    }

                EOL;
        } else {
            $propertyAccessor = <<<EOL

                    public function $methodName(): $returnType
                    {
                        \$value = \$this->node->getProperty('$this->propertyName');
                        if ($typeCheck) {
                            return \$value;
                        }
                        return $defaultReturn;
                    }

                EOL;
        }

        return $propertyAccessor;
    }


    public function toPhpInterfaceMethodString(): string
    {
        $propertyIsInternal = str_starts_with($this->propertyName, '_');
        if ($propertyIsInternal) {
            $methodName = 'getInternal' . UnicodeFunctions::ucfirst(substr($this->propertyName, 1));
        } else {
            $methodName = 'get' . UnicodeFunctions::ucfirst($this->propertyName);
        }

        $annotationType = $this->getAnnotationType();
        $phpType = $this->getPhpType();
        $returnType = ($this->defaultValue === null) ? '?' . $phpType : $phpType;


        if ($annotationType) {
            $propertyAccessor = <<<EOL

                    /**
                     * @return ?$annotationType;
                     */
                    public function $methodName(): $returnType;

                EOL;
        } else {
            $propertyAccessor = <<<EOL

                    public function $methodName(): $returnType;

                EOL;
        }

        return $propertyAccessor;
    }

    public static function createFromNodeTypeAndPropertyName(NodeType $nodeType, string $propertyName): self
    {
        return new NodePropertySpecification(
            $propertyName,
            $nodeType->getPropertyType($propertyName),
            $nodeType->getConfiguration('properties.' . $propertyName . '.defaultValue') ?? null
        );
    }
}
