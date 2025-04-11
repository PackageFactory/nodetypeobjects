<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Command;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\GenericPackage;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecification;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecificationCollection;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectSpecification;
use PackageFactory\NodeTypeObjects\Factory\NodeTypeObjectFileFactory;
use PackageFactory\NodeTypeObjects\Factory\NodeTypeObjectSpecificationFactory;

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
     * Remove all *NodeObject.php and *NodeInterface.php from the NodeTypes folder of the specified package
     *
     * @param string $packageKey PackageKey to store the classes in
     * @return void
     */
    public function cleanCommand(string $packageKey): void
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

        $packagePath = $package->getPackagePath();
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
     * Create new NodeTypeObjects for the selected Package
     *
     * @param string $packageKey PackageKey
     */
    public function buildCommand(string $packageKey): void
    {
        $package = $this->getPackage($packageKey);

        $nodeTypes = $this->nodeTypeManager->getNodeTypes(true);
        $nameSpecifications = [];

        foreach ($nodeTypes as $nodeType) {
            if (!str_starts_with($nodeType->getName(), $packageKey . ':')) {
                continue;
            }
            $nameSpecifications[$nodeType->getName()] = NodeTypeObjectNameSpecification::createFromPackageAndNodeType($package, $nodeType);
        }

        $nameSpecificationsCollection = new NodeTypeObjectNameSpecificationCollection(...$nameSpecifications);

        foreach ($nodeTypes as $nodeType) {
            if (!str_starts_with($nodeType->getName(), $packageKey . ':')) {
                continue;
            }

            $specification = NodeTypeObjectSpecification::createFromPackageAndNodeType($package, $nodeType, $nameSpecificationsCollection);

            Files::createDirectoryRecursively($specification->names->directory);

            if ($specification->names->className) {
                file_put_contents(
                    $specification->names->directory . DIRECTORY_SEPARATOR . $specification->names->className . '.php',
                    $specification->toPhpClassString()
                );
                $this->outputLine(' - ' . $specification->names->nodeTypeName . ' -> ' . $specification->names->className);
            }

            if ($specification->names->interfaceName) {
                file_put_contents(
                    $specification->names->directory . DIRECTORY_SEPARATOR . $specification->names->interfaceName . '.php',
                    $specification->toPhpInterfaceString()
                );
                $this->outputLine(' - ' . $specification->names->nodeTypeName . ' -> ' . $specification->names->interfaceName);
            }
        }
    }

    /**
     * @param string $packageKey
     * @return mixed|FlowPackageInterface|GenericPackage|\Neos\Flow\Package\PackageInterface
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     */
    protected function getPackage(string $packageKey): mixed
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
