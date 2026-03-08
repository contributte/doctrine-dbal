# Changelog

All notable changes to `nettrine/dbal` are documented in this file.

## Migration notes

### v0.10

- `nettrine/dbal` uses only `Nettrine\DBAL\DI\DbalExtension`.
- Removed split extension `nettrine.dbal.console` (`Nettrine\DBAL\DI\DbalConsoleExtension`).
- Legacy single-connection configuration (`connection` + `configuration`) was replaced by `connections.<name>`.
- Result caching is configured via `connections.<name>.resultCache` and expects a PSR-6 cache pool.

```neon
# before (legacy)
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension(%debugMode%)
  nettrine.dbal.console: Nettrine\DBAL\DI\DbalConsoleExtension(%consoleMode%)

nettrine.dbal:
  connection:
    driver: pdo_pgsql
    host: localhost
    dbname: app
    user: user
    password: secret
  configuration:
    resultCache: @cacheService

# now
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension(%debugMode%)

nettrine.dbal:
  connections:
    default:
      driver: pdo_pgsql
      host: localhost
      dbname: app
      user: user
      password: secret
      resultCache: @cacheService
```
