# Configuration

All options with their defaults. The bundle works with no configuration file at all:

```yaml
# config/packages/rds_auth.yaml
rds_auth:
    region: '%env(rds_region:AWS_REGION)%'             # required for the IAM and master-password modes
    iam_username: '%env(default::RDS_IAM_USERNAME)%'   # null or empty disables IAM authentication
    secret_arn: '%env(default::RDS_SECRET_ARN)%'       # null or empty disables the master-password refresh
    cache_pool: cache.app                              # null disables caching
    event_dispatcher: event_dispatcher                 # null disables the ConfiguredPasswordOutdated dispatch
    connections: []                                    # empty applies to every DBAL connection
```

| Option | Default | Effect |
|---|---|---|
| `region` | `%env(rds_region:AWS_REGION)%` | AWS region of the RDS endpoint and the secret. Required when `iam_username` or `secret_arn` is set; see the resolution order below. |
| `iam_username` | `%env(default::RDS_IAM_USERNAME)%` | Database user for RDS IAM token authentication. Null or empty disables IAM authentication. |
| `secret_arn` | `%env(default::RDS_SECRET_ARN)%` | ARN of the RDS-managed master-password secret. Null or empty disables the master-password refresh. |
| `cache_pool` | `cache.app` | Cache pool service id that stores accepted credentials. Null or empty disables caching. |
| `event_dispatcher` | `event_dispatcher` | Event dispatcher service id that receives the `ConfiguredPasswordOutdated` event. Null or empty disables the dispatch. |
| `connections` | `[]` | DBAL connection names the middleware applies to. An empty list applies it to every connection. |

The [`default::` environment variable processor](https://symfony.com/doc/current/configuration/env_var_processors.html) resolves an unset variable to null, and null disables the corresponding mode. One application image therefore serves every environment without a configuration change: set `RDS_IAM_USERNAME` in one environment, `RDS_SECRET_ARN` in another, neither on a developer machine.

The credential providers are lazy services: the region is read at the first token or secret request, never during container warmup, so pass-through deployments do not need it. The region resolves in this order:

1. An explicit `rds_auth.region` value.
2. The `AWS_REGION` environment variable.
3. The `AWS_DEFAULT_REGION` environment variable.
4. The [AsyncAws bundle's](https://github.com/async-aws/symfony-bundle) global `async_aws.config.region`, when that bundle is installed. Per-client regions are ignored.

Empty strings count as unset. When no source provides a region, the first connection in a credential mode fails with an exception that names these options.

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

- The task or instance role must allow `rds-db:connect` on the database user resource. The resource ARN uses the DbiResourceId (cluster resource ID for Aurora), not the instance identifier:

  ```json
  {
      "Effect": "Allow",
      "Action": "rds-db:connect",
      "Resource": "arn:aws:rds-db:ap-southeast-2:123456789012:dbuser:db-ABCDEFGHIJKL01234/app_user"
  }
  ```
- The database user must have IAM authentication enabled. PostgreSQL: `GRANT rds_iam TO app_user`.

## Example: master-password refresh

```bash
AWS_REGION=ap-southeast-2
RDS_SECRET_ARN=arn:aws:secretsmanager:ap-southeast-2:123456789012:secret:rds!cluster-abc123
```

The role needs only `secretsmanager:GetSecretValue` on that one secret ARN. This mode exists because [RDS rotates a managed master-password secret every seven days by default](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/rds-secrets-manager.html), while most deployments resolve the password into an environment variable once, when the container or instance starts: Elastic Beanstalk `environmentsecrets`, an ECS task definition, a Kubernetes secret. From the rotation until the next deployment or restart, the injected password is wrong and every connect fails with `password authentication failed`. Reading the secret at connect time restores database access without a deployment.

### The ConfiguredPasswordOutdated event

When the database rejects the configured password and the Secrets Manager password works, the middleware dispatches `TacticMedia\RdsAuth\ConfiguredPasswordOutdated` on the `event_dispatcher` service. [password-outdated-event.md](password-outdated-event.md) covers the firing conditions, the payload, and listener registration.

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
