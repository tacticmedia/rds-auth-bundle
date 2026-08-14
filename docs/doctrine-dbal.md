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
