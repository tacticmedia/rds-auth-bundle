# RDS Authentication bundle

[![codecov](https://codecov.io/gh/tacticmedia/rds-auth-bundle/graph/badge.svg?token=CIQ82XRGYU)](https://codecov.io/gh/tacticmedia/rds-auth-bundle)

A Symfony bundle that registers and configures [`tacticmedia/rds-auth-middleware`](https://packagist.org/packages/tacticmedia/rds-auth-middleware), a Doctrine DBAL driver middleware that selects the database credential for an Amazon RDS instance at connect time:

- If `iam_username` is set: connect as that user with an [RDS IAM authentication token](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html) as the password.
- If `secret_arn` is set: connect with the configured password; when the database rejects it, re-read the current password from Secrets Manager and retry once.
- neither set: the connection parameters stay unchanged. 

One application image therefore runs unchanged in every environment, with either authentication mode or none.

The master-password refresh exists because [RDS rotates a managed master-password secret every seven days by default](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/rds-secrets-manager.html), while most deployments resolve the password into an environment variable once, when the container or instance starts: Elastic Beanstalk `environmentsecrets`, an ECS task definition, a Kubernetes secret. From the rotation until the next deployment or restart, the injected password is wrong and every connect fails with `password authentication failed`. Reading the secret at connect time restores database access without a deployment. The only IAM permission this requires is `secretsmanager:GetSecretValue` on that one secret ARN.

## Installation

The packages are on GitHub and not yet on Packagist. Composer reads `repositories` only from the root `composer.json`, and it ignores stability flags declared outside the root, so the consuming project must declare both repositories and require both packages with an explicit `dev-main` constraint:

```bash
composer config repositories.rds-auth-bundle vcs https://github.com/tacticmedia/rds-auth-bundle
composer config repositories.rds-auth-middleware vcs https://github.com/tacticmedia/rds-auth-middleware
composer require tacticmedia/rds-auth-bundle:dev-main tacticmedia/rds-auth-middleware:dev-main
```

After publication on Packagist, `composer require tacticmedia/rds-auth-bundle` replaces all three commands.

Symfony Flex registers the bundle automatically; without Flex, add it to `config/bundles.php`:

```php
TacticMedia\RdsAuthBundle\RdsAuthBundle::class => ['all' => true],
```

## Configuration

Every option defaults to an environment variable, so the bundle works without a configuration file. The defaults:

```yaml
# config/packages/rds_auth.yaml
rds_auth:
    region: '%env(AWS_REGION)%'
    iam_username: '%env(default::RDS_IAM_USERNAME)%'   # null or empty disables IAM authentication
    secret_arn: '%env(default::RDS_SECRET_ARN)%'       # null or empty disables the master-password refresh
    cache_pool: cache.app                              # null disables caching
    connections: []                                    # empty applies to every DBAL connection
```

The [`default::` environment variable processor](https://symfony.com/doc/current/configuration/env_var_processors.html) resolves an unset variable to null, and null disables the corresponding mode, so one image serves every environment without a configuration change.

## The credential cache

The middleware caches a credential in the PSR-6 pool named by `cache_pool` only after a connection has accepted it, so a database that is down never populates the cache and an outage costs one connect attempt per request. APCu fits deployments where each instance keeps its own cache: in memory, shared by every PHP worker on the instance, and discarded with the instance. Install `ext-apcu`, set [`apc.enable_cli=1`](https://www.php.net/manual/en/apcu.configuration.php) so `bin/console` commands share the cache, and set `cache_pool` to a dedicated pool:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            rds_credentials.cache:
                adapter: cache.adapter.apcu
```

```yaml
# config/packages/rds_auth.yaml
rds_auth:
    cache_pool: rds_credentials.cache
```

Caching is optional: set `cache_pool: null` to disable it. Each IAM connect then signs a new token, and between a rotation and the next configuration refresh each connect costs one rejected attempt plus one Secrets Manager read.

A token is cached for 10 minutes of its [15-minute lifetime](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html). In master-password mode the middleware calls Secrets Manager only after the database rejects a password. The token cache does not reduce the rate of new connections, and [IAM database authentication consumes compute resources on the database instance](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html#UsingWithRDS.IAMDBAuth.Limitations). Reuse connections through FrankenPHP worker mode or RDS Proxy before you use IAM authentication under a high connection rate.

## The Doctrine DBAL configuration

The bundle does not configure the database connection; keep your regular DoctrineBundle configuration. The middleware reads it at connect time:

- IAM authentication replaces `user` and `password`, signs the token for `host` and `port`, and sets `sslmode: require` when the configuration does not set it.
- The master-password refresh uses the configured `password` as the first connect attempt and reads Secrets Manager only after the database rejects it.
- Every other parameter passes through unchanged.

A typical production configuration:

```yaml
when@prod:
    doctrine:
        dbal:
            driver: pdo_pgsql
            host: '%env(RDS_HOST)%'
            port: '%env(int:RDS_PORT)%'
            dbname: '%env(RDS_DATABASE)%'
            user: '%env(RDS_USERNAME)%'
            password: '%env(RDS_PASSWORD)%'
            server_version: '%env(RDS_SERVER_VERSION)%'
            sslmode: require
```

## Development

```bash
composer install
composer test            # PHPUnit
composer test-coverage   # HTML and clover reports in coverage/
composer stan            # PHPStan level 8
```

The kernel tests boot a FrameworkBundle plus DoctrineBundle kernel against in-memory SQLite and assert that the middleware wraps the configured connections and no others. Until `tacticmedia/rds-auth-middleware` is published on Packagist, `composer.json` resolves it from GitHub through a VCS repository; at publication, replace the `dev-main` constraint with a version constraint and remove the `repositories` entry.

## License

MIT. See [LICENSE](LICENSE).
