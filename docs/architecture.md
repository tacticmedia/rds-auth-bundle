# Bundle internals

`src/` holds two classes. `RdsAuthBundle` extends `AbstractBundle`:

- `configure()` defines the configuration tree. See [configuration.md](configuration.md) for the options.
- `prependExtension()` captures the global region from the raw `async_aws` extension configuration when the AsyncAws bundle is registered.
- `loadExtension()` registers the services and tags the middleware.

`RegionEnvVarProcessor` backs the `rds_region:` prefix in the region default: the named variable, then `AWS_DEFAULT_REGION`, then the captured AsyncAws region; with no source it throws an `EnvNotFoundException` that names the configuration options.

## Services

| Service id | Class | Arguments |
|---|---|---|
| `rds_auth.token_provider` | `TacticMedia\RdsAuth\RdsIamTokenProvider` | `region` |
| `rds_auth.password_provider` | `TacticMedia\RdsAuth\RdsSecretPasswordProvider` | `region` |
| `rds_auth.cache_pool` | `Psr\Cache\CacheItemPoolInterface` proxy | the `cache_pool` service; registered only when `cache_pool` is set |
| `rds_auth.middleware` | `TacticMedia\RdsAuth\RdsAuthMiddleware` | both providers, `iam_username`, `secret_arn`, `rds_auth.cache_pool` or null, the `event_dispatcher` service or null |
| `rds_auth.region_env_processor` | `TacticMedia\RdsAuthBundle\RegionEnvVarProcessor` | the AsyncAws global region or null |

Both providers and the cache pool are lazy services. Doctrine instantiates the middleware, and with it these arguments, when the connection service is created during cache warmup; the lazy proxy defers construction to the first credential request. A deployment without `AWS_REGION` therefore still warms its cache, and pass-through mode never constructs an APCu pool on a host without `ext-apcu`. On PHP 8.3 the provider proxies subclass the provider classes, which the middleware keeps non-final for this. The pool proxy implements only `CacheItemPoolInterface` and wraps the configured pool through the `current()` factory, the definition that `#[Autowire(lazy: true)]` produces.

## Middleware registration

The middleware carries the `doctrine.middleware` tag. A [tag without a `connection` attribute applies to all connections](https://symfony.com/bundles/DoctrineBundle/current/middlewares.html). A non-empty `connections` list produces one tag per named connection instead:

```php
if ([] === $config['connections']) {
    $middleware->tag('doctrine.middleware');

    return;
}

foreach ($config['connections'] as $connection) {
    $middleware->tag('doctrine.middleware', ['connection' => $connection]);
}
```

## Package boundary

All credential logic lives in [`tacticmedia/rds-auth-middleware`](https://github.com/tacticmedia/rds-auth-middleware): token signing, Secrets Manager reads, the retry, the cache. This repository only wires the middleware into the Symfony container. Behavior changes belong in the middleware package; wiring and configuration changes belong here.
