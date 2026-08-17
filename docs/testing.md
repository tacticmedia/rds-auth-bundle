# Development and testing

## Commands

```bash
composer install
composer test                                  # PHPUnit
vendor/bin/phpunit tests/RdsAuthBundleTest.php # one test file
vendor/bin/phpunit --filter testWrapsTheDefaultConnection
composer test-coverage                         # HTML and clover reports in coverage/
composer stan                                  # PHPStan level 8 over src and tests
composer cs                                    # php-cs-fixer (@auto, @PhpCsFixer, @Symfony)
composer rector
composer qa                                    # rector, cs, stan, test in sequence
```

## Kernel test pattern

Tests are based on `KernelTestCase`. Each test boots FrameworkBundle plus DoctrineBundle against in-memory SQLite and asserts which connections the middleware wraps (the driver is `RdsAuthDriver`) and that queries execute.

Each configuration variant needs its own kernel class in `tests/Support/`:

- `TestKernel`: defaults, single connection.
- `ScopedTestKernel`: two connections, `connections: ['covered']`.
- `NoCacheTestKernel`: `cache_pool: null`.

Kernel cache directories are keyed by `Kernel::VERSION` and `static::class` under the system temp directory, so variants do not share a compiled container and a dependency switch (for example `composer update --prefer-lowest`) does not reuse a container compiled by another Symfony version. A new configuration variant means a new `TestKernel` subclass that overrides `configureContainer()`.

## Gotchas

- Every test class calls `restore_exception_handler()` in `tearDown()`. The booted kernel registers an exception handler it does not remove, and `failOnRisky` is on.
- `phpunit.xml.dist` sets `AWS_REGION` because the `region` default resolves `%env(AWS_REGION)%`.

## CI

`.github/workflows/ci.yml` runs PHPUnit and PHPStan on PHP 8.3, 8.4, and 8.5 crossed with Symfony 6.4, 7.4, and 8 (pinned through flex and `SYMFONY_REQUIRE`) and highest and lowest dependencies, and uploads coverage and JUnit results to Codecov. The PHP 8.3 with Symfony 8 pair is excluded because Symfony 8 requires PHP 8.4.
