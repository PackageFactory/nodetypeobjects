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
use PackageFactory\NodeTypeObjects\Factory\NodeTypeObjectFileFactory;
use PackageFactory\NodeTypeObjects\Factory\NodeTypeSpecificationFactory;

class NodetypeObjectsCommandController extends CommandController
{
    private PackageManager $packageManager;
    private NodeTypeSpecificationFactory $nodeTypeSpecificationFactory;
    private NodeTypeObjectFileFactory $nodeTypeObjectFileFactory;
    private ContentRepositoryRegistry $contentRepositoryRegistry;

    public function injectPackageManager(PackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    public function injectContentRepositoryRegistry(ContentRepositoryRegistry $contentRepositoryRegistry): void
    {
        $this->contentRepositoryRegistry = $contentRepositoryRegistry;
    }


    public function injectNodeTypeSpecificationFactory(NodeTypeSpecificationFactory $nodeTypeSpecificationFactory): void
    {
        $this->nodeTypeSpecificationFactory = $nodeTypeSpecificationFactory;
    }

    public function injectNodeTypeObjectFileFactory(NodeTypeObjectFileFactory $nodeTypeObjectFileFactory): void
    {
        $this->nodeTypeObjectFileFactory = $nodeTypeObjectFileFactory;
    }

    /**
     * Remove all NodeTypeObjects from the selected package
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
        $files = Files::readDirectoryRecursively($packagePath, 'NodeObject.php');
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
    public function buildCommand(string $packageKey, string $crId = 'default'): void
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
                $autoloadConfiguration['mappingType'] === 'psr-4'
                && str_ends_with($autoloadConfiguration['namespace'], '\\NodeTypes\\')
                && $autoloadConfiguration['classPath'] === $package->getPackagePath() . 'NodeTypes/'
            ) {
                $namespace = $autoloadConfiguration['namespace'];
                break;
            }
        }

        if ($namespace === null) {
            $this->outputLine('<error>No PSR4-NodeTypes namespace for the NodeTypes folder is registered via composer</error>');
            $this->quit(1);
        }

        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString($crId));
        $nodeTypeManager = $contentRepository->getNodeTypeManager();
        $nodeTypes = $nodeTypeManager->getNodeTypes(false);
        foreach ($nodeTypes as $nodeType) {
            if (!str_starts_with($nodeType->name->value, $packageKey . ':')) {
                continue;
            }

            $specification = $this->nodeTypeSpecificationFactory->createFromPackageKeyAndNodeType(
                $package,
                $nodeType
            );

            $nodeTypeObjectFile = $this->nodeTypeObjectFileFactory->createNodeTypeObjectPhpCodeFromNode($specification);

            Files::createDirectoryRecursively($nodeTypeObjectFile->pathName);

            file_put_contents(
                $nodeTypeObjectFile->fileNameWithPath,
                $nodeTypeObjectFile->fileContent
            );

            $this->outputLine(' - ' . $specification->nodeTypeName . ' -> ' . $specification->className);
        }
    }
}
