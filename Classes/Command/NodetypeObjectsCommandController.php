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
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecification;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectNameSpecificationCollection;
use PackageFactory\NodeTypeObjects\Domain\NodeTypeObjectSpecification;

class NodetypeObjectsCommandController extends CommandController
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
    public function buildCommand(string $packageKeys, string $crId = 'default'): void
    {
        $packages = $this->findPackagesByPackageKeyPattern($packageKeys);

        if (empty($packages)) {
            $this->output->outputLine('No packages found for packageKeys <error>"%s"</error>:', [$packageKeys]);
            $this->quit(1);
        } else {
            $keys = array_map(fn (FlowPackageInterface $package) => $package->getPackageKey(), $packages);
            $this->output->outputLine('Building NodeObjects and NodeInterfaces for packages <info>"%s"</info>:', [implode(', ', $keys)]);
        }

        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($crId));
        $nodeTypeManager = $contentRepository->getNodeTypeManager();
        $nodeTypes = $nodeTypeManager->getNodeTypes(true);
        $nameSpecifications = [];
        foreach ($packages as $package) {
            foreach ($nodeTypes as $nodeType) {
                if (!str_starts_with($nodeType->name->value, $package->getPackageKey()  . ':')) {
                    continue;
                }
                $nameSpecifications[$nodeType->name->value] = NodeTypeObjectNameSpecification::createFromNodeType($nodeType);
            }
        }
        $nameSpecificationsCollection = new NodeTypeObjectNameSpecificationCollection(...$nameSpecifications);

        // loop 2 build interfaces and objects
        foreach ($packages as $package) {
            foreach ($nodeTypes as $nodeType) {
                if (!str_starts_with($nodeType->name->value, $package->getPackageKey() . ':')) {
                    continue;
                }

                $specification = NodeTypeObjectSpecification::createFromPackageAndNodeType($package, $nodeType, $nameSpecificationsCollection);

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
