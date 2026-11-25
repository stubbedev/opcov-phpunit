# opcov/phpunit

PHPUnit 12/13 and Pest 4 integration for [opcov](https://github.com/stubbedev/opcov),
the opcode-level line, branch-outcome and loop coverage extension for PHP.

```sh
composer require --dev opcov/phpunit
```

It also installs the `opcov` command as `vendor/bin/opcov`: the package is a
Composer plugin that downloads the native binary matching its version and
checks it against the release's checksums (allow it with
`composer config allow-plugins.opcov/phpunit true`, or let `vendor/bin/opcov`
download it on first use).

The extension itself installs with `pie install opcov/opcov` (see
[docs/install.md](https://github.com/stubbedev/opcov/blob/main/docs/install.md)).
Usage, configuration, paratest, the PHPUnit 13.3 coverage driver and the
`.cov` converter are documented in
[docs/phpunit.md](https://github.com/stubbedev/opcov/blob/main/docs/phpunit.md).

This repository is a read-only split of
[`php/phpunit`](https://github.com/stubbedev/opcov/tree/main/php/phpunit) in
stubbedev/opcov, published on every change there. Report issues and send pull
requests to [stubbedev/opcov](https://github.com/stubbedev/opcov).
