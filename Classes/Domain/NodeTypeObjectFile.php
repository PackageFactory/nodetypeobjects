<?php

declare(strict_types=1);

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class NodeTypeObjectFile
{
    public string $pathName;
    public string $fileName;

    public function __construct(
        public string $fileNameWithPath,
        public string $fileContent,
    ) {
        $filePathParts = explode(DIRECTORY_SEPARATOR, $this->fileNameWithPath);
        /** @var string $fileName */
        $fileName = array_pop($filePathParts);
        $this->fileName = $fileName;
        $this->pathName = implode(DIRECTORY_SEPARATOR, $filePathParts);
    }
}
