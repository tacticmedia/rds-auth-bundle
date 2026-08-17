# Configuration

All options with their defaults. The bundle works with no configuration file at all:

```yaml
# config/packages/rds_auth.yaml
rds_auth:
    region: '%env(AWS_REGION)%'
    iam_username: '%env(default::RDS_IAM_USERNAME)%'   # null or empty disables IAM authentication
    secret_arn: '%env(default::RDS_SECRET_ARN)%'       # null or empty disables the master-password refresh
    cache_pool: cache.app                              # null disables caching
    connections: []                                    # empty applies to every DBAL connection
```

| Option | Default | Effect |
|---|---|---|
| `region` | `%env(AWS_REGION)%` | AWS region of the RDS endpoint and the secret. Must not be empty. |
| `iam_username` | `%env(default::RDS_IAM_USERNAME)%` | Database user for RDS IAM token authentication. Null or empty disables IAM authentication. |
| `secret_arn` | `%env(default::RDS_SECRET_ARN)%` | ARN of the RDS-managed master-password secret. Null or empty disables the master-password refresh. |
| `cache_pool` | `cache.app` | Cache pool service id that stores accepted credentials. Null or empty disables caching. |
| `connections` | `[]` | DBAL connection names the middleware applies to. An empty list applies it to every connection. |

The [`default::` environment variable processor](https://symfony.com/doc/current/configuration/env_var_processors.html) resolves an unset variable to null, and null disables the corresponding mode. One application image therefore serves every environment without a configuration change: set `RDS_IAM_USERNAME` in one environment, `RDS_SECRET_ARN` in another, neither on a developer machine.

## Credential selection at connect time

- `iam_username` is set: the middleware connects as that user with an [RDS IAM authentication token](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html) as the password.
- `secret_arn` is set: the middleware connects with the configured password. When the database rejects it, the middleware reads the current password from Secrets Manager and retries once.
- Neither is set: the connection parameters stay unchanged.

## Example: IAM authentication

Set two environment variables on the deployment; no configuration file change is necessary:

```bash
AWS_REGION=ap-southeast-2
RDS_IAM_USERNAME=app_user
```

Prerequisites on the AWS side, per the [AWS documentation](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html):

- The task or instance role must allow `rds-db:connect` on the database user resource.
- The database user must have IAM authentication enabled. PostgreSQL: `GRANT rds_iam TO app_user`.

## Example: master-password refresh

```bash
AWS_REGION=ap-southeast-2
RDS_SECRET_ARN=arn:aws:secretsmanager:ap-southeast-2:123456789012:secret:rds!cluster-abc123
```

The role needs only `secretsmanager:GetSecretValue` on that one secret ARN. This mode exists because [RDS rotates a managed master-password secret every seven days by default](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/rds-secrets-manager.html), while most deployments resolve the password into an environment variable once, when the container or instance starts: Elastic Beanstalk `environmentsecrets`, an ECS task definition, a Kubernetes secret. From the rotation until the next deployment or restart, the injected password is wrong and every connect fails with `password authentication failed`. Reading the secret at connect time restores database access without a deployment.

## Example: limit the middleware to specific connections

```yaml
# config/packages/rds_auth.yaml
rds_auth:
    connections: ['default']

doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                driver: pdo_pgsql
                host: '%env(RDS_HOST)%'
                dbname: app
            legacy:
                driver: pdo_mysql
                host: '%env(LEGACY_DB_HOST)%'
                dbname: legacy
```

Only the `default` connection gets the middleware; `legacy` keeps its plain driver. [doctrine-dbal.md](doctrine-dbal.md) shows the full connection configuration and the reason the examples do not use a DSN.
