# The ConfiguredPasswordOutdated event

In master-password mode the recovery is silent: the database rejects the deployed password, the middleware reads the current password from Secrets Manager, the retry succeeds, and the application never learns that its deployed configuration is outdated. The bundle passes the `event_dispatcher` service to the middleware, so `TacticMedia\RdsAuth\ConfiguredPasswordOutdated` is dispatched at that moment. A listener can alert operations or trigger the redeployment that reloads the password.

## When it fires

The database rejected a password, then accepted the current secret password, and that password differs from the password in the connection parameters. The rejected password can be the configured one or a [cached](credential-cache.md) one: a warm cache never tries the configured password, so the comparison reports an outdated configuration after every rotation. Connection parameters without a `password` key count as an outdated configured password.

It does not fire when:

- The secret password equals the configured password. The deployed configuration is current.
- The secret password is rejected too. The exception propagates instead.
- IAM mode or pass-through mode is active. Neither reads a configured password.

## How often it fires

Once per rotation for each cache that misses: once for a pool that every process shares, once per process outside the pool, and once per connection with `cache_pool: null`. With an APCu pool, each cron run or `bin/console` command between a rotation and the next deployment dispatches its own event. [credential-cache.md](credential-cache.md#choosing-a-pool) compares pools that CLI processes share. Connections that miss at the same moment each dispatch. Deduplicate by `secretArn` before a listener alerts a person.

## Payload

Public readonly properties: `secretArn`, `host`, `port`, `dbname`, `user`, and `sqlState` of the rejection, whether the rejected password was cached or configured. A property is null when the connection parameters did not supply the value; no engine default is substituted. The event never carries a password or the rejection exception, whose trace frames can hold unredacted parameters.

## Listening

Autoconfiguration registers the listener from the parameter type:

```php
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use TacticMedia\RdsAuth\ConfiguredPasswordOutdated;

#[AsEventListener]
final class OutdatedPasswordListener
{
    public function __invoke(ConfiguredPasswordOutdated $event): void
    {
        // Alert operations or trigger the redeployment that reloads the password.
    }
}
```

Listeners run synchronously inside `connect()`, after the accepted password enters the cache. A listener that throws therefore fails the connection, but the cached password survives for the next attempt. Keep the listener cheap and robust; offload slow alerting to Messenger.

## Configuration

The `event_dispatcher` option selects the dispatcher service. The default is `event_dispatcher`; null or empty disables the dispatch. See [configuration.md](configuration.md).
