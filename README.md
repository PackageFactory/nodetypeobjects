# PackageFactory.NodeTypeObjects

!!! THIS IS EXPERIMENTAL ... EVERYTHING MAY CHANGE, USE AT YOUR OWN RISK !!!

Autogenerate php classes for NodeTypes with type safe property accessor methods that allow full static analysis. 

- NodeTypeObject are created for each non abatrsct NodeType in the namespace of the given package.
- NodeTypeObjects are stored in the `NodeTypes` folder using all parts of the NodeTypeName as folders   
- The namespace of each NodeTypeObject is derived from the packaghe-key with added ``NodeTypes`
- The className of a NodeTypeObject is defined by the last part of the NodeTypeName with postfix `NodeTypeObject`

## Preconditions

The following preconditions have to be met for a package to use NodeTypeObjects.

- The php namespace of the package is directly derived from the Neos package key. As is the default and best practice.
- The Package registers a PSR4 Namespace for `NodeTypes` in the `composer.json` that points to the `NodeTypes` folder.
- The pattern `*NodeTypeObject.php` is added to `.gitignore` to avoid committing the generated files.
- The commands `nodetypeobjects:build` and `nodetypeobjects:clean` are integrated into build processes and watchers

## Usage 

The package defines the following cli commands

- `./flow nodetypeobjects:build Vendor.Site` : regenerate all NodeTypeObject in the given package.
- `./flow nodetypeobjects:clean Vendor.Site` : remove all NodeTypeObject in the given package. This will also remove orphaned NodeObjects.   

## Installation

PackageFactory.NodeTypeObjects is available via packagist. Just run `composer require packagefactory/nodetypeobjects`.

We use semantic-versioning so every breaking change will increase the major-version number.

## License

see [LICENSE file](LICENSE)
