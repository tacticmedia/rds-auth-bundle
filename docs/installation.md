# Installation

## Requirements

- PHP 8.3 or newer
- Symfony 6.4, 7.4, or 8.x
- DoctrineBundle 2.13 or newer, or 3.x (Symfony 8 needs DoctrineBundle 3.x, which needs PHP 8.4)

## Install with Composer

```bash
composer require tacticmedia/rds-auth-bundle
```

## Register the bundle

Ensure `config/bundles.php` contains the bundle:

```php
return [
    // ...
    TacticMedia\RdsAuthBundle\RdsAuthBundle::class => ['all' => true],
];
```

The bundle works without a configuration file: every option defaults to an environment variable, and with none set the middleware passes connection parameters through unchanged. To activate a credential mode, set `AWS_REGION` together with `RDS_IAM_USERNAME` or `RDS_SECRET_ARN`. See [configuration.md](configuration.md).
