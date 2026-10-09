# The Doctrine DBAL configuration

The bundle does not configure the database connection; keep your regular DoctrineBundle configuration. The middleware reads it at connect time:

- IAM authentication replaces `user` and `password`, signs the token for `host` and `port`, and sets `sslmode: require` when the configuration does not set it.
- The master-password refresh uses the configured `password` as the first connect attempt and reads Secrets Manager only after the database rejects it.
- Every other parameter passes through unchanged.

A typical production configuration:

```yaml
# config/packages/doctrine.yaml
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

With `RDS_IAM_USERNAME` set, `user` and `password` above are ignored at connect time. With `RDS_SECRET_ARN` set, `password` is the first attempt and Secrets Manager is the fallback. With neither set, the configuration above is used as written, so the same file serves local development and production.

To read `sslmode` from an environment variable, use `sslmode: '%env(default::DATABASE_SSLMODE)%'`. The `default::` processor resolves an unset or empty variable to null, and DBAL then omits the parameter. A plain `%env(DATABASE_SSLMODE)%` with an empty value sends `sslmode=` to pdo_pgsql, which fails with "invalid sslmode value". The middleware treats an empty `sslmode` as unset in IAM mode only.

## Why parameters, not a DSN?

**TL;DR**: Every rotated RDS master password contains punctuation, and a `%`, `#`, or `?` in the password breaks a DSN.

The examples use individual parameters instead of `url` / `DATABASE_URL` on purpose:

- A password inside a DSN must be percent-encoded. AWS documents that [a rotated master password is always 28 characters long and contains at least one punctuation character](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/rds-secrets-manager.html#rds-secrets-manager-rotate-db-instance), and an [RDS master password](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/CHAP_Limits.html) can contain any printable ASCII character except `/`, `'`, `"`, `@`, and space. `%`, `#`, and `?` are therefore legal, and each one breaks or silently corrupts a URL. With weekly rotation, a DSN that holds the raw password breaks at the first rotation that generates one of them.
- Secret-injection mechanisms such as an ECS task definition, Elastic Beanstalk `environmentsecrets`, or a Kubernetes secret inject the raw value and cannot encode it.
- The Flex recipe's `%env(resolve:DATABASE_URL)%` breaks the password a second time. The `resolve:` processor replaces `%%` with `%` and reads `%name%` as a container parameter, so a percent-encoded password such as `a%40b%23c` fails with a missing-parameter error.
- With `secret_arn` set, a URL-corrupted password behaves like a rotated one: every uncached connect pays a failed attempt plus a Secrets Manager read instead of surfacing the misconfiguration.

A DSN is safe in IAM mode. DoctrineBundle parses `url` into `host`, `port`, `user`, and the other parameters before the driver connects, so the middleware signs the token for the right endpoint and replaces the password. The split into parameters matters only when the configuration carries a password.

## Quoting the password in .env files

Wrap the password in single quotes. [Symfony Dotenv](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files) keeps `$` and `\` literal inside single quotes. Unquoted and double-quoted values expand `$` references, and in an unquoted value a space followed by `#` starts a comment. An RDS master password cannot contain `'`, so single quotes are always safe:

```bash
RDS_PASSWORD='p#ss$word\with%punctuation'
```
