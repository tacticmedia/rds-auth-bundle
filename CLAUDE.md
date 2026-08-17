# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install
composer test    # PHPUnit; single test: vendor/bin/phpunit --filter testName
composer stan    # PHPStan level 8
composer cs      # php-cs-fixer
composer qa      # rector, cs, stan, test in sequence
```

## What this is

A one-class Symfony bundle: `src/RdsAuthBundle.php` wires `tacticmedia/rds-auth-middleware` into the container. The middleware selects the database credential for an Amazon RDS instance at connect time. All credential logic lives in the middleware package; this repository holds only container wiring.

## Documentation

Read the matching file before working on that area; do not load all of them:

- `docs/architecture.md`: services, `doctrine.middleware` tag semantics, package boundary
- `docs/configuration.md`: option reference, environment-variable defaults, mode selection, per-connection scoping
- `docs/credential-cache.md`: cache pool behavior, APCu setup, token lifetime
- `docs/password-outdated-event.md`: when ConfiguredPasswordOutdated fires, payload, listener registration
- `docs/doctrine-dbal.md`: what the middleware changes in the DBAL parameters
- `docs/installation.md`: Composer setup, bundle registration
- `docs/testing.md`: full command list, kernel test pattern, test gotchas, CI matrix

Read `docs/testing.md` before you add or change tests.
