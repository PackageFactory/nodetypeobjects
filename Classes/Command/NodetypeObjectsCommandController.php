<?php
declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Command;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;

class NodetypeObjectsCommandController extends CommandController
{
    private NodeTypeManager $nodeTypeManager;
    private PackageManager $packageManager;

    public function injectNodeTypeManager(NodeTypeManager $nodeTypeManager): void
    {
       $this->nodeTypeManager = $nodeTypeManager;
    }

    public function injectPackageManager(PackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param string $nodeTypeName Pattern for nodeTypeNames to generate classes for
     * @param string $packageKey PackageKey to store the classes in
     * @return void
     */
    public function buildCommand(string $packageKey):void
    {
        if($this->packageManager->isPackageAvailable($packageKey)) {
            $package = $this->packageManager->getPackage($packageKey);
        } else {
            $this->output->outputLine("Unknwn package " . $packageKey);
            $this->quit(1);
        }
        if (!$package instanceof FlowPackageInterface) {
            $this->output->outputLine($packageKey . " is not a flow package");
            $this->quit(1);
        }

        $packagePath = $package->getPackagePath();
        $allNodeTypes = $this->nodeTypeManager->getNodeTypes(false);
        foreach ($allNodeTypes as $nodeType) {
            if (!str_starts_with($nodeType->getName(), $packageKey . ':')) {
                continue;
            }
            $localNameParts = explode('.', str_replace( $packageKey . ':','', $nodeType->getName()));
            $localName = array_pop($localNameParts);
            $localNamespace = implode('.', $localNameParts);

            $filePath = $packagePath
                . 'NodeTypes/'
                . str_replace('.', '/', $localNamespace)
                . '/' . $localName;
            $fileName = $localName . 'NodeObject.php';

            $classNamespace = str_replace('.', '\\' , $packageKey)
                . '\\NodeTypes'
                . '\\' . str_replace('.', '\\' , $localNamespace)
                . '\\' . str_replace('.', '\\' , $localName);

            $className =  $localName . 'NodeObject';

            $this->buildOne(
                $package,
                $nodeType,
                $classNamespace,
                $className,
                $filePath,
                $fileName,
            );
        }
    }

    private function buildOne(FlowPackageInterface $package, NodeType $nodeType, string $classNamespace, string $className, string $filePath, string $fileName):void
    {

        $propertyAccesssors = '';
        foreach ($nodeType->getProperties() as $propertyName => $propertyConfig) {
            if (str_starts_with($propertyName, '_')) {
                $methodName = 'getInternal' . UnicodeFunctions::ucfirst(substr($propertyName, 1));
            } else {
                $methodName = 'get' . UnicodeFunctions::ucfirst($propertyName);
            }
            $type = $propertyConfig[ 'type' ];

            $annotationType = $type;
            $phpType = $type;

            if (str_ends_with($type, '[]')) {
                $phpType = 'array';
            } elseif (str_starts_with($type, 'array<') && str_ends_with($type, '>')) {
                $annotationType = substr($type , 6 ,-1) . '[]';
                $phpType = 'array';
            } elseif ($type  === 'boolean') {
                $phpType = 'bool';
                $annotationType = 'bool';
            } elseif ($type  === 'integer') {
                $phpType = 'int';
                $annotationType = 'int';
            } elseif ($type  === 'DateTime') {
                $phpType = '\DateTime';
                $annotationType = '\DateTime';
            }

            if (str_contains($phpType, '\\') && !str_starts_with($phpType, '\\')) {
                $phpType =  '\\' . $phpType;
            }
            if (str_contains($annotationType, '\\') && !str_starts_with($annotationType, '\\')) {
                $annotationType =  '\\' . $annotationType;
            }

            $propertyAccesssors .= <<<EOL


                /**
                 * @return $annotationType;
                 */
                public function $methodName (): ?$phpType
                {
                    return \$this->node->getProperty('$propertyName');
                }
            EOL;
        }

        $nodeTypeName = $nodeType->getName();

        $class = <<<EOL
        <?php
        declare(strict_types=1);

        namespace $classNamespace;

        use Neos\ContentRepository\Domain\Model\NodeInterface;
        use Neos\Flow\Annotations as Flow;

        /**
         * AUTOGENERATED DO NOT MODIFY
         * run `./ nodetypeobjects:build` to regenerate this
         */
        #[Flow\Proxy(false)]
        readonly class $className {
            private function __construct(
                public NodeInterface \$node
            ) {}

            public static function fromNode(NodeInterface \$node): self
            {
                if (\$node->getNodeType()->getName() !== "$nodeTypeName") {
                    throw new \Exception("unsupported nodetype " . \$node->getNodeType()->getName());
                }
                return new self(\$node);
            }
            $propertyAccesssors
        }
        EOL;

        Files::createDirectoryRecursively($filePath);

        file_put_contents(
            $filePath . '/' . $fileName,
            $class
        );

        $this->outputLine('Generated class %s (%s) from type %s', [$classNamespace . '\\' . $className, $filePath . '/' . $fileName, $nodeType->getName()]);
    }
}
