# PackageFactory.NodeTypeObjects

!!! THIS IS EXPERIMENTAL ... EVERYTHING MAY CHANGE, USE AT YOUR OWN RISK !!!

Autogenerate php classes and interfaces for NodeTypes with type safe property accessor methods that allow full static analysis. 

- NodeObjects are created for each non abstract NodeType in the namespace of the given package.
- NodeInterfaces are created for each NodeType in the namespace of the given package.
- NodeObjects and NodeInterfaces are stored in the `NodeTypes` folder using all parts of the NodeTypeName as folders
- The namespace of each NodeTypeObject/NodeInterface is derived from the package-key with added ``NodeTypes`
- The className of a NodeObject is defined by the last part of the NodeTypeName with postfix `NodeObject`
- The interfaceName of a NodeInterface is defined by the last part of the NodeTypeName with postfix `NodeInterface`

## Preconditions

The following preconditions have to be met for a package to use NodeObject.

- The php namespace of the package is directly derived from the Neos package key. As is the default and best practice.
- The Package registers a PSR4 Namespace for `NodeTypes` in the `composer.json` that points to the `NodeTypes` folder.
- The pattern `NodeTypes/**/*NodeObject.php` and `NodeTypes/**/*NodeInterface.php` are added to `.gitignore` to avoid committing the generated files.
- The commands `nodeobjects:build` and `nodeobjects:clean` are integrated into build processes and watchers

## Usage 

The package defines the following cli commands

- `./flow nodeobjects:build Vendor.Site` : regenerate all NodeObject in the given package.
- `./flow nodeobjects:clean Vendor.Site` : remove all NodeObject in the given package. This will also remove orphaned NodeObjects.   

## Installation

PackageFactory.NodeTypeObjects is available via packagist. Just run `composer require packagefactory/nodetypeobjects`.

We use semantic-versioning so every breaking change will increase the major-version number.

## License

see [LICENSE file](LICENSE)
