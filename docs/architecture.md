# Bundle internals

`src/RdsAuthBundle.php` is the entire source. It extends `AbstractBundle`:

- `configure()` defines the configuration tree. See [configuration.md](configuration.md) for the options.
- `loadExtension()` registers the services and tags the middleware.

## Services

| Service id | Class | Arguments |
|---|---|---|
| `rds_auth.token_provider` | `TacticMedia\RdsAuth\RdsIamTokenProvider` | `region` |
| `rds_auth.password_provider` | `TacticMedia\RdsAuth\RdsSecretPasswordProvider` | `region` |
| `rds_auth.middleware` | `TacticMedia\RdsAuth\RdsAuthMiddleware` | both providers, `iam_username`, `secret_arn`, the `cache_pool` service or null, the `event_dispatcher` service or null |

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
