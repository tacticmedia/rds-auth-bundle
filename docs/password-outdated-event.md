# The ConfiguredPasswordOutdated event

In master-password mode the recovery is silent: the database rejects the deployed password, the middleware reads the current password from Secrets Manager, the retry succeeds, and the application never learns that its deployed configuration is outdated. The bundle passes the `event_dispatcher` service to the middleware, so `TacticMedia\RdsAuth\ConfiguredPasswordOutdated` is dispatched at that moment. A listener can alert operations or trigger the redeployment that reloads the password.

## When it fires

The database rejected the password from the connection parameters and then accepted the current secret password. Connection parameters without a `password` key count as an outdated configured password.

It does not fire when:

- A [cached](credential-cache.md) password is rejected. That is a rotation inside the cache TTL and says nothing about the deployed configuration.
- The secret password is rejected too. The exception propagates instead.
- IAM mode or pass-through mode is active. Neither reads a configured password.

## Payload

Public readonly properties: `secretArn`, `host`, `port`, `dbname`, `user`, and `sqlState` of the rejection. A property is null when the connection parameters did not supply the value; no engine default is substituted. The event never carries a password or the rejection exception, whose trace frames can hold unredacted parameters.

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
