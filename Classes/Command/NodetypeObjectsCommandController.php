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
     * Remove all *NodeObject.php and *NodeInterface.php from the NodeTypes folder of the specified packages
     *
     * @param string $packageKeys PackageKey or Pattern, can be used seperated by comma
     * @return void
     */
    public function cleanCommand(string $packageKeys): void
    {
        $packages = $this->findPackagesByPackageKeyPattern($packageKeys);

        if (empty($packages)) {
            $this->output->outputLine('No packages found for packageKeys <error>"%s"</error>:', [$packageKeys]);
            $this->quit(1);
        } else {
            $keys = array_map(fn (FlowPackageInterface $package) => $package->getPackageKey(), $packages);
            $this->output->outputLine('Removing NodeObjects and NodeInterfaces from packages <info>"%s"</info>:', [implode(', ', $keys)]);
        }

        foreach ($packages as $package) {
            $packagePath = $package->getPackagePath();
            if (!file_exists($packagePath . DIRECTORY_SEPARATOR . 'NodeTypes')) {
                continue;
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
    }

    /**
     * Create new NodeTypeObjects for the selected Package
     *
     * @param string $packageKeys PackageKey or Pattern, can be used seperated comma
     */
    public function buildCommand(string $packageKeys): void
    {
        $packages = $this->findPackagesByPackageKeyPattern($packageKeys);

        if (empty($packages)) {
            $this->output->outputLine('No packages found for packageKeys <error>"%s"</error>:', [$packageKeys]);
            $this->quit(1);
        } else {
            $keys = array_map(fn (FlowPackageInterface $package) => $package->getPackageKey(), $packages);
            $this->output->outputLine('Building NodeObjects and NodeInterfaces for packages <info>"%s"</info>:', [implode(', ', $keys)]);
        }

        $nodeTypes = $this->nodeTypeManager->getNodeTypes(true);

        // loop 1 collect all nodetypes so in run two we know which interfaces exist
        $nameSpecifications = [];
        foreach ($packages as $package) {
            foreach ($nodeTypes as $nodeType) {
                if (!str_starts_with($nodeType->getName(), $package->getPackageKey() . ':')) {
                    continue;
                }
                $nameSpecifications[ $nodeType->getName() ] = NodeTypeObjectNameSpecification::createFromPackageAndNodeType($package, $nodeType);
            }
        }
        $nameSpecificationsCollection = new NodeTypeObjectNameSpecificationCollection(...$nameSpecifications);

        // loop 2 build interfaces and objects
        foreach ($packages as $package) {
            foreach ($nodeTypes as $nodeType) {
                if (!str_starts_with($nodeType->getName(), $package->getPackageKey() . ':')) {
                    continue;
                }

                $specification = NodeTypeObjectSpecification::createFromPackageAndNodeType($package, $nodeType, $nameSpecificationsCollection);

                Files::createDirectoryRecursively($specification->names->directory);
                $generatedFiles = [];
                if ($specification->names->className) {
                    file_put_contents(
                        $specification->names->directory . DIRECTORY_SEPARATOR . $specification->names->className . '.php',
                        $specification->toPhpClassString()
                    );
                    $generatedFiles[] = $specification->names->phpNamespace . '\\' . $specification->names->className;
                }
                if ($specification->names->interfaceName) {
                    file_put_contents(
                        $specification->names->directory . DIRECTORY_SEPARATOR . $specification->names->interfaceName . '.php',
                        $specification->toPhpInterfaceString()
                    );
                    $generatedFiles[] = $specification->names->phpNamespace . '\\' . $specification->names->interfaceName;
                }

                $this->outputLine(' - ' . $specification->names->nodeTypeName . ' -> <info>' . implode(', ', $generatedFiles) . '</info>');
            }
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

    /**
     * @param string $packageKeys
     * @return FlowPackageInterface[]
     */
    protected function findPackagesByPackageKeyPattern(string $packageKeys): array
    {
        $packageKeyPatterns = explode(',', $packageKeys);

        $allFlowPackages = $this->packageManager->getFlowPackages();

        $packages = [];
        foreach ($allFlowPackages as $flowPackage) {
            foreach ($packageKeyPatterns as $packageKeyPattern) {
                if (fnmatch($packageKeyPattern, $flowPackage->getPackageKey())) {
                    $packages[ $flowPackage->getPackageKey() ] = $flowPackage;
                    break;
                }
            }
        }
        return $packages;
    }
}
