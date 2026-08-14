# Installation

## Requirements

- PHP 8.4 or newer
- Symfony 7.4 or 8.x
- DoctrineBundle 2.13 or newer, or 3.x

## Install with Composer

The packages are on GitHub and not yet on Packagist. Composer reads `repositories` only from the root `composer.json`, and it ignores stability flags declared outside the root. The consuming project must declare both repositories and require both packages with an explicit `dev-main` constraint:

```bash
composer config repositories.rds-auth-bundle vcs https://github.com/tacticmedia/rds-auth-bundle
composer config repositories.rds-auth-middleware vcs https://github.com/tacticmedia/rds-auth-middleware
composer require tacticmedia/rds-auth-bundle:dev-main tacticmedia/rds-auth-middleware:dev-main
```

After publication on Packagist, one command replaces all three:

```bash
composer require tacticmedia/rds-auth-bundle
```

## Register the bundle

Symfony Flex registers the bundle automatically. Without Flex, add it to `config/bundles.php`:

```php
return [
    // ...
    TacticMedia\RdsAuthBundle\RdsAuthBundle::class => ['all' => true],
];
```

The bundle works without a configuration file. Every option defaults to an environment variable. See [configuration.md](configuration.md).
