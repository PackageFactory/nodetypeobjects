<?php

namespace PackageFactory\NodeTypeObjects\Domain;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;

class PhpNameHelper
{
    public static function sanitize(string $name): string
    {
        // pure number segments are prefixed by a number
        /**
         * @var string $name
         */
        $name = preg_replace('/(^|\\\\)([0-9]+)(\\\\|$)/us', '$1_$2$3', $name);
        return $name;
    }
}
