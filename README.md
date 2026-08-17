# Symfony RDS IAM authentication bundle

[![codecov](https://codecov.io/gh/tacticmedia/rds-auth-bundle/graph/badge.svg?token=CIQ82XRGYU)](https://codecov.io/gh/tacticmedia/rds-auth-bundle)

**TL;DR**: A bundle you would install in your RDS-powered Symfony application to add seamless support for IAM authentication or managed, automatically rotated password to improve your baseline security posture. 

[![codecov](https://codecov.io/gh/tacticmedia/rds-auth-middleware/graph/badge.svg?token=XZINN5HXOB)](https://codecov.io/gh/tacticmedia/rds-auth-middleware)

A Symfony bundle that registers and configures [`tacticmedia/rds-auth-middleware`](https://github.com/tacticmedia/rds-auth-middleware), which selects the database credential for an Amazon RDS instance at connect time using the following logic:

- If `iam_username` is set: connect as that user with an [RDS IAM authentication token](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.IAMDBAuth.html) as the password.
- If `secret_arn` is set: connect with the configured password; when the database rejects it, re-read the current password from Secrets Manager and retry once.
- Neither set: the connection parameters stay unchanged.

Every option defaults to an environment variable, so one application image runs unchanged in every environment, with either authentication mode or none.

## Installation

```bash
composer require tacticmedia/rds-auth-bundle
```

See [Installation](docs/installation.md) for bundle registration.

## Documentation

- [Installation](docs/installation.md) - requirements, Composer setup, bundle registration
- [Configuration](docs/configuration.md) - option reference, environment-variable defaults, examples for each mode
- [The credential cache](docs/credential-cache.md) - cache pool behavior, APCu setup, token lifetime
- [The Doctrine DBAL configuration](docs/doctrine-dbal.md) - what the middleware changes at connect time
- [Bundle internals](docs/architecture.md) - services, middleware registration, package boundary
- [Development and testing](docs/testing.md) - commands, kernel test pattern, CI

## Contributions

Non-LLM-slop contributions and issues are most definitely welcome. 

## License

MIT. See [LICENSE](LICENSE).

## One more thing

This package is brought to you by [Tactic Media, a South Australian software development business](https://tacticmedia.com.au). 

We love to help businesses become more efficient by automating tasks that shouldn't have been done by a human in the first place.

Head over to our website to check out what we do, and if you think we can help you give your employees more time to spend on something more creative, [let's talk](https://tacticmedia.com.au/contact.html)
