# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Requires `tacticmedia/rds-auth-middleware` ^1.2, which caches the Secrets Manager password without a TTL and dispatches `ConfiguredPasswordOutdated` whenever the accepted secret password differs from the configured one. `psr/cache` is a direct requirement.
- `docs/credential-cache.md` compares APCu, filesystem, and Redis pools per deployment, explains why a chain is not worth it, states what a cache miss costs in IMDS requests, Secrets Manager reads, and events, and describes the IAM authentication cost of one connection per PHP-FPM request. It also covers sites that share one php-fpm master, PDO persistent connections with tokens, and Sentry cache tracing.

### Fixed

- Pass-through mode no longer constructs the cache pool. An APCu pool on a host without `ext-apcu` threw `CacheException: APCu is not enabled.` when Doctrine built the connection, even with no `RDS_*` variable set. The bundle passes the pool as the lazy `rds_auth.cache_pool` proxy, and a credential mode constructs it at the first connect.
- The lazy providers work with every allowed dependency version. The `^1.1` middleware requirement allowed 1.1.0 to 1.1.2, whose final providers a proxy cannot subclass without native lazy objects, so the container failed to compile on PHP 8.3 and on Symfony 6.4. `symfony/dependency-injection` before 6.4.4 generated a non-readonly proxy for the readonly providers, a fatal error at the first connection. The requirements are `^1.2` and `^6.4.4`.
- The documentation no longer claims that `apc.enable_cli=1` lets `bin/console` commands share the APCu cache. Each CLI process has a private segment, and without `apc.enable_cli=1` APCu stores nothing in CLI.
- The lowest-dependency CI jobs install `symfony/polyfill-uuid` 1.13.1 or newer through `require-dev`; 1.13.0 fails to autoload.

## [1.1.2] - 2026-08-18

### Added

- The `region` default resolves through a fallback chain: `AWS_REGION`, then `AWS_DEFAULT_REGION`, then the AsyncAws bundle's global `async_aws.config.region` when that bundle is installed. With no source, the first connection in a credential mode fails with a message that names `rds_auth.region` and the fallbacks instead of Symfony's bare variable-not-found error. `async-aws/async-aws-bundle` is a suggested package.

### Fixed

- A fresh install without `AWS_REGION` env var set no longer fails

### Changed

- The documentation states that `region` is required for the IAM and master-password modes and unnecessary for pass-through, instead of presenting every option as optional.

## [1.1.1] - 2026-08-17

### Added

- `SECURITY.md` with the vulnerability report procedure.
- Packagist version, PHP version, and license badges in the README.

### Fixed

- Test classes declare `#[CoversClass(RdsAuthBundle::class)]` instead of `#[CoversNothing]`, which suppressed all coverage attribution.
- `tests/bootstrap.php` deletes stale kernel caches before each run. A reused compiled container skips the compile step where the bundle code runs and zeroes the coverage.
- CI enables `zend.assertions` so pcov does not report the `assert()` call in `configure()` as a miss.
- The README no longer shows the Codecov badge of the middleware repository.

## [1.1.0] - 2026-08-17

### Added

- `event_dispatcher` option. The middleware receives the configured dispatcher and dispatches `ConfiguredPasswordOutdated` when the database rejects the deployed password and the current secret password succeeds. Set the option to null to disable the dispatch.

## [1.0.1] - 2026-08-17

### Changed

- README describes how to contribute and who maintains the bundle.

## [1.0.0] - 2026-08-17

### Added

- Initial release. The bundle registers `tacticmedia/rds-auth-middleware` as a Doctrine DBAL middleware with the `region`, `iam_username`, `secret_arn`, `cache_pool`, and `connections` options.

[Unreleased]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.1.2...HEAD
[1.1.2]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/tacticmedia/rds-auth-bundle/releases/tag/1.0.0
