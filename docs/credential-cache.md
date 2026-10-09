# The credential cache

The middleware caches a credential in the PSR-6 pool named by `cache_pool` only after a connection has accepted it. A database that is down never populates the cache, so an outage costs one connect attempt per request.

A token is cached for 10 minutes of its [15-minute lifetime](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html). In master-password mode the middleware calls Secrets Manager only after the database rejects a password, and caches the secret password without a TTL: it stays valid until the next rotation, and the next rejection replaces it. A `default_lifetime` on the pool still applies.

The bundle passes the pool to the middleware as a lazy proxy, so pass-through mode never constructs it. A credential mode constructs the pool at the first connect: when you configure an APCu pool, add `ext-apcu` to the `require` section of your `composer.json`.

## What a cache miss costs

- IAM mode: a new token. AsyncAws keeps resolved AWS credentials only in the memory of the current PHP-FPM request or CLI process, so the first token in each one resolves the instance role first: three IMDS requests on EC2 with IMDSv2. The token cache therefore also limits IMDS traffic.
- Master-password mode, between a rotation and the next deployment: one rejected login, one Secrets Manager read that resolves AWS credentials the same way, and one [`ConfiguredPasswordOutdated`](password-outdated-event.md) event.

A pool that every connecting process shares pays this once per token lifetime or once per rotation. Every process outside the pool pays it again.

## Choosing a pool

| Deployment | Pool | Shared by | Where the credential lives |
|---|---|---|---|
| One instance or container that runs only web workers | APCu | The PHP-FPM workers of one master | Process memory |
| One host with web workers and CLI: cron, Messenger, deploy commands | Dedicated filesystem pool | Every process on the host that runs as the same user | Local disk under `%kernel.cache_dir%/pools` |
| Several hosts | Redis or Valkey | Every process on every host | Redis memory, and its RDB or AOF files when persistence is on |

A dedicated pool keeps credentials apart from application data, so `cache:pool:clear cache.app` and a shared `cache.app` backend do not touch them.

### APCu

Define the pool and point the bundle at it:

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

PHP-FPM workers share the APCu segment of their master process. Every CLI process creates a private segment that ends with the process. With [`apc.enable_cli=1`](https://www.php.net/manual/en/apcu.configuration.php), a long-running Messenger worker reuses its own credential, but a short-lived `bin/console` command never reuses one. With `apc.enable_cli=0`, the default, the pool constructs in CLI and stores nothing, without an error.

Sites on one php-fpm master, such as a beta and a production site on one server, share one APCu segment and its `apc.shm_size`. Their entries do not collide: Symfony namespaces each pool by `framework.cache.prefix_seed`, which defaults to the project directory and container class, and the middleware keys an entry by host, port, and user, or by secret ARN. APCu has no access control between sites, so code in one site can read the credentials that the other site cached. Run sites that must not share credentials under separate php-fpm masters.

### Filesystem

On one host, a filesystem pool lets cron jobs, Messenger workers, and deploy commands reuse the credential that PHP-FPM cached:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            rds_credentials.cache:
                adapter: cache.adapter.filesystem
```

The pool stores the credential unencrypted on local disk. A deployment that runs `composer dump-env` already stores the configured password in `.env.local.php` on the same disk.

### Redis or Valkey

Several hosts share one token and one secret password through a Redis pool:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            rds_credentials.cache:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
```

The credential leaves process memory: it crosses the network to Redis, and Redis writes it to disk when persistence is on. Encrypt the values with the [`SodiumMarshaller`](https://symfony.com/doc/current/cache.html#encrypting-the-cache). It encrypts values, not keys, and the middleware keys contain only SHA-256 hashes.

### Not recommended: a chain

A [`ChainAdapter`](https://symfony.com/doc/current/components/cache/adapters/chain_adapter.html) with APCu in front of a shared pool saves one shared-pool read per connect, which is small next to the TLS handshake and login of that connect. Each host keeps its own APCu copy, and a rejection on one host does not clear the copies on the other hosts, so each host pays its own rejected login after a rotation. Use one shared pool.

## Tracing

Sentry cache tracing (`sentry/sentry-symfony`) records the hit status, the item size, and the namespace of each cache call, never the value. Span descriptions carry the cache key, which the middleware hashes.

## Disable caching

```yaml
# config/packages/rds_auth.yaml
rds_auth:
    cache_pool: null
```

Each IAM connect then signs a new token, and the first one in each PHP-FPM request or CLI process makes the IMDS requests above. Between a rotation and the next configuration refresh, each master-password connect costs one rejected attempt, one Secrets Manager read, and one event.

## Connection rate

The token cache does not reduce the rate of new connections. PHP-FPM opens a new database connection for each request, so in IAM mode every request costs one IAM authentication on the database, and [IAM database authentication requires compute resources on the database instance, and 300 to 1000 MiB extra memory](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html#UsingWithRDS.IAMDBAuth.Limitations). Reuse connections through FrankenPHP worker mode or [RDS Proxy](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/rds-proxy.html) before you use IAM authentication under a high request rate.

Do not use PDO persistent connections with IAM tokens. PDO keys a persistent connection by DSN, user, and password, so each new token opens another connection, and the previous one stays open until the worker process exits.
