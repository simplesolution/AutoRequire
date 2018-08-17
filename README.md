# AutoRequire
Composer plugin to auto-require all private repositories.

This Plugin just makes it so easy to add private repositories to your composer project!

## Requirements

 * php >= 7
 * composer >= 1.7
 
## Installation

```
composer require simplesolution/auto-require
```
 
## Configuration

You can configurate the vendor name just by adding:

```json
"extra": {
  "auto-require": {
    "vendor-name": "yourpackagename"
  }
}
```

You can configurate the path scheme just by adding:

```json
"extra": {
  "auto-require": {
    "path-scheme": "'{vendorName}/{vendorName}.{name}'"
  }
}
```

`vendorName` and `name` are fixed variables that return the vendorName or the name of the package.

## How To Use

just use the `composer require` or the `composer update` command as normal and your Packages will be included. When you use the Plugin for the first time composer will ask you to create an github token that allows it to access the repository easier.

## Authors

[Tobias Franek (tobias.franek@gmail.com)](https://github.com/TobiasFranek)
