<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\GenericPackage;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;
use PackageFactory\NodeTypeObjects\Domain\NodeObjectNameSpecification;
use PackageFactory\NodeTypeObjects\Domain\NodeObjectNameSpecificationCollection;
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
        $package = $this->findFlowPackageByPackageKey($packageKey);

        if ($package === null) {
            $this->output->outputLine('No packages found for packageKeys <error>"%s"</error>:', [$packageKey]);
            $this->quit(1);
        } else {
            $this->output->outputLine('Removing NodeObjects and NodeInterfaces from packages <info>"%s"</info>:', [$package->getPackageKey()]);
        }

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
        $package = $this->findFlowPackageByPackageKey($packageKey);

        if ($package === null) {
            $this->output->outputLine('No packages found for packageKeys <error>"%s"</error>:', [$packageKey]);
            $this->quit(1);
        } else {
            $this->output->outputLine('Building NodeObjects and NodeInterfaces for package <info>"%s"</info>:', [$package->getPackageKey()]);
        }

        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($crId));
        $nodeTypeManager = $contentRepository->getNodeTypeManager();
        $nodeTypes = $nodeTypeManager->getNodeTypes(true);
        $nameSpecifications = [];
        foreach ($nodeTypes as $nodeType) {
            if (!str_starts_with($nodeType->name->value, $package->getPackageKey()  . ':')) {
                continue;
            }
            $nameSpecifications[$nodeType->name->value] = NodeObjectNameSpecification::createFromNodeType($nodeType);
        }
        $nameSpecificationsCollection = new NodeObjectNameSpecificationCollection(...$nameSpecifications);

        // loop 1 build interfaces
        // loop 2 build objects
        foreach ($nodeTypes as $nodeType) {
            if (!str_starts_with($nodeType->name->value, $package->getPackageKey() . ':')) {
                continue;
            }

            $specification = NodeObjectSpecification::createFromPackageAndNodeType($package, $nodeType, $nameSpecificationsCollection);

            Files::createDirectoryRecursively($specification->directory);

            $generatedFiles = [];
            if ($specification->classFilename) {
                file_put_contents(
                    $specification->classFilename,
                    $specification->toPhpClassString()
                );
                $generatedFiles[] = $specification->names->fullyQualifiedClassName;
            }
            if ($specification->interfaceFilename) {
                file_put_contents(
                    $specification->interfaceFilename,
                    $specification->toPhpInterfaceString()
                );
                $generatedFiles[] = $specification->names->fullyQualifiedInterfaceName;
            }

            $this->outputLine(' - ' . $specification->names->nodeTypeName . ' -> <info>' . implode(', ', $generatedFiles) . '</info>');
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

    protected function findFlowPackageByPackageKey(string $packageKey): ?FlowPackageInterface
    {
        if ($this->packageManager->isPackageAvailable($packageKey) === false) {
            return null;
        }
        $package = $this->packageManager->getPackage($packageKey);
        if ($package instanceof FlowPackageInterface) {
            return $package;
        }
        return null;
    }
}
