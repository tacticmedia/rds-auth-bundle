# RDS Authentication bundle

[![codecov](https://codecov.io/gh/tacticmedia/rds-auth-bundle/graph/badge.svg?token=CIQ82XRGYU)](https://codecov.io/gh/tacticmedia/rds-auth-bundle)

A Symfony bundle that registers and configures [`tacticmedia/rds-auth-middleware`](https://github.com/tacticmedia/rds-auth-middleware), a Doctrine DBAL driver middleware that selects the database credential for an Amazon RDS instance at connect time:

- If `iam_username` is set: connect as that user with an [RDS IAM authentication token](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html) as the password.
- If `secret_arn` is set: connect with the configured password; when the database rejects it, re-read the current password from Secrets Manager and retry once.
- Neither set: the connection parameters stay unchanged.

Every option defaults to an environment variable, so one application image runs unchanged in every environment, with either authentication mode or none.

## Installation

The packages are on GitHub and not yet on Packagist, so the consuming project must declare both repositories:

```bash
composer config repositories.rds-auth-bundle vcs https://github.com/tacticmedia/rds-auth-bundle
composer config repositories.rds-auth-middleware vcs https://github.com/tacticmedia/rds-auth-middleware
composer require tacticmedia/rds-auth-bundle:dev-main tacticmedia/rds-auth-middleware:dev-main
```

See [Installation](docs/installation.md) for the reasoning and for bundle registration without Flex.

## Documentation

- [Installation](docs/installation.md) - requirements, Composer setup, bundle registration
- [Configuration](docs/configuration.md) - option reference, environment-variable defaults, examples for each mode
- [The credential cache](docs/credential-cache.md) - cache pool behavior, APCu setup, token lifetime
- [The Doctrine DBAL configuration](docs/doctrine-dbal.md) - what the middleware changes at connect time
- [Bundle internals](docs/architecture.md) - services, middleware registration, package boundary
- [Development and testing](docs/testing.md) - commands, kernel test pattern, CI

## License

MIT. See [LICENSE](LICENSE).
