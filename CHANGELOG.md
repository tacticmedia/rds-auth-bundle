# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.1.1]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/tacticmedia/rds-auth-bundle/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/tacticmedia/rds-auth-bundle/releases/tag/1.0.0
