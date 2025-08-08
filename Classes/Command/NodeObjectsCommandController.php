<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Command;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\GenericPackage;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;
use PackageFactory\NodeTypeObjects\Domain\NodeInterfaceSpecification;
use PackageFactory\NodeTypeObjects\Domain\NodeObjectSpecification;

class NodeObjectsCommandController extends CommandController
{
    private PackageManager $packageManager;

    private ContentRepositoryRegistry $contentRepositoryRegistry;

    public function injectPackageManager(PackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    public function injectContentRepositoryRegistry(ContentRepositoryRegistry $contentRepositoryRegistry): void
    {
        $this->contentRepositoryRegistry = $contentRepositoryRegistry;
    }

    /**
     * Remove all *NodeObject.php and *NodeInterface.php from the NodeTypes folder of the specified package
     *
     * @param string $packageKey PackageKey
     * @return void
     */
    public function cleanCommand(string $packageKey): void
    {
        $package = $this->getPackage($packageKey);

        $this->output->outputLine('Removing NodeObjects and NodeInterfaces from package <info>"%s"</info>:', [$packageKey]);

        $packagePath = $package->getPackagePath();
        if (!file_exists($packagePath . DIRECTORY_SEPARATOR . 'NodeTypes')) {
            return;
        }

        $files = Files::readDirectoryRecursively($packagePath . DIRECTORY_SEPARATOR . 'NodeTypes', 'NodeObject.php');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
                $this->outputLine(' - ' . $file);
            }
        }
        $files = Files::readDirectoryRecursively($packagePath . DIRECTORY_SEPARATOR . 'NodeTypes', 'NodeInterface.php');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
                $this->outputLine(' - ' . $file);
            }
        }
    }

    /**
     * Create new NodeObjects and NodeInterfaces for the selected Package
     *
     * @param string $packageKey PackageKey
     */
    public function buildCommand(string $packageKey, string $crId = 'default'): void
    {
        $package = $this->getPackage($packageKey);

        $this->output->outputLine('Building NodeObjects and NodeInterfaces for package <info>"%s"</info>:', [$packageKey]);

        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($crId));
        $nodeTypeManager = $contentRepository->getNodeTypeManager();

        // loop 1 build interfaces for all nodetypes in package, this is done first as in the next step
        // the node objects will create implements statements for all existing interfaces even those from other packages

        $this->output->outputLine();
        $this->output->outputLine('Creating NodeInterfaces');
        $this->output->outputLine();

        $nodeTypes = array_filter(
            $nodeTypeManager->getNodeTypes(true),
            fn (NodeType $nodeType) => str_starts_with($nodeType->name->value, $packageKey . ':')
        );

        foreach ($nodeTypes as $nodeType) {
            $interfaceSpecification = NodeInterfaceSpecification::createFromNodeType($nodeType);
            Files::createDirectoryRecursively($package->getPackagePath() . DIRECTORY_SEPARATOR . $interfaceSpecification->interfaceName->getLocalDirectoryName());
            file_put_contents(
                $package->getPackagePath() . DIRECTORY_SEPARATOR . $interfaceSpecification->interfaceName->getLocalFileName(),
                $interfaceSpecification->toPhpString()
            );
            $this->outputLine(' - ' . $interfaceSpecification->interfaceName->nodeTypeName . ' -> <info>' . $interfaceSpecification->interfaceName->getFullyQualifiedClassName() . '</info>');
        }

        // loop 2 build objects for all non abstract nodetypes in package
        $this->output->outputLine();
        $this->output->outputLine('Creating NodeObjects');
        $this->output->outputLine();

        $nonAbstractNodeTypes = array_filter(
            $nodeTypeManager->getNodeTypes(false),
            fn (NodeType $nodeType) => str_starts_with($nodeType->name->value, $packageKey . ':')
        );

        foreach ($nonAbstractNodeTypes as $nodeType) {
            $objectSpecification = NodeObjectSpecification::createFromNodeType($nodeType);
            Files::createDirectoryRecursively($package->getPackagePath() . DIRECTORY_SEPARATOR . $objectSpecification->objectName->getLocalDirectoryName());
            file_put_contents(
                $package->getPackagePath() . DIRECTORY_SEPARATOR . $objectSpecification->objectName->getLocalFileName(),
                $objectSpecification->toPhpString()
            );
            $this->outputLine(' - ' . $objectSpecification->objectName->nodeTypeName . ' -> <info>' . $objectSpecification->objectName->getFullyQualifiedClassName() . '</info>');
        }
    }

    /**
     * @param string $packageKey
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     */
    protected function getPackage(string $packageKey): FlowPackageInterface & GenericPackage
    {
        if ($this->packageManager->isPackageAvailable($packageKey)) {
            $package = $this->packageManager->getPackage($packageKey);
        } else {
            $this->output->outputLine("Unknown package " . $packageKey);
            $this->quit(1);
        }
        if (!$package instanceof FlowPackageInterface) {
            $this->output->outputLine($packageKey . " is not a Flow package");
            $this->quit(1);
        }
        if (!$package instanceof GenericPackage) {
            $this->output->outputLine($packageKey . " is not a Generic package");
            $this->quit(1);
        }

        /**
         * @var array<int, array{namespace:string, classPath:string, mappingType:string}> $autoloadConfigurations
         */
        $autoloadConfigurations = $package->getFlattenedAutoloadConfiguration();
        $namespace = null;
        foreach ($autoloadConfigurations as $autoloadConfiguration) {
            if (
                $autoloadConfiguration[ 'mappingType' ] === 'psr-4'
                && str_ends_with($autoloadConfiguration[ 'namespace' ], '\\NodeTypes\\')
                && (
                    $autoloadConfiguration[ 'classPath' ] === $package->getPackagePath() . 'NodeTypes'
                    || $autoloadConfiguration[ 'classPath' ] === $package->getPackagePath() . 'NodeTypes/'
                )
            ) {
                $namespace = $autoloadConfiguration[ 'namespace' ];
                break;
            }
        }

        if ($namespace === null) {
            $this->outputLine('<error>No PSR4-NodeTypes namespace for the NodeTypes folder is registered via composer</error>');
            $this->quit(1);
        }
        return $package;
    }
}
