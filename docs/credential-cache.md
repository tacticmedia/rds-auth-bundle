# The credential cache

The middleware caches a credential in the PSR-6 pool named by `cache_pool` only after a connection has accepted it. A database that is down never populates the cache, so an outage costs one connect attempt per request.

A token is cached for 10 minutes of its [15-minute lifetime](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html). In master-password mode the middleware calls Secrets Manager only after the database rejects a password.

## Recommended: a dedicated APCu pool

APCu fits deployments where each instance keeps its own cache: in memory, shared by every PHP worker on the instance, and discarded with the instance. Install `ext-apcu` and set [`apc.enable_cli=1`](https://www.php.net/manual/en/apcu.configuration.php) so `bin/console` commands share the cache.

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

## Disable caching

```yaml
# config/packages/rds_auth.yaml
rds_auth:
    cache_pool: null
```

Each IAM connect then signs a new token. Between a rotation and the next configuration refresh, each master-password connect costs one rejected attempt plus one Secrets Manager read.

## Connection rate

The token cache does not reduce the rate of new connections, and [IAM database authentication consumes compute resources on the database instance](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html#UsingWithRDS.IAMDBAuth.Limitations). Reuse connections through FrankenPHP worker mode or RDS Proxy before you use IAM authentication under a high connection rate.
