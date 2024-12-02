# Contributte Doctrine DBAL

Integration of [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) for Nette Framework.

## Content

- [Installation](#installation)
- [Configuration](#configuration)
  - [Console](#console)
  - [Caching](#caching)
  - [Types](#types)
  - [Debug](#debug)
- [Examples](#examples)
- [Other](#other)

## Installation

Install package using composer.

```bash
composer require nettrine/dbal
```

Register prepared [compiler extension](https://doc.nette.org/en/dependency-injection/nette-container) in your `config.neon` file.

```neon
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
```

> [!NOTE]
> This is just **DBAL**, for **ORM** please use [nettrine/orm](https://github.com/contributte/doctrine-orm).

## Configuration

### Minimal configuration

```neon
nettrine.dbal:
  connections:
    default:
      host: localhost
      driver: mysqli
      dbname: nettrine
      user: root
      password: root
```

**PostgreSQL**

```neon
nettrine.dbal:
  connections:
    default:
      driver: pdo_pgsql
```

**MySQL / MariaDB**

```neon
nettrine.dbal:
  connections:
    default:
      driver: mysqli
```

**SQLite**

```neon
nettrine.dbal:
  connections:
    default:
      driver: pdo_sqlite
```

### Advanced configuration

Here is a complete list of all configuration options:

 ```neon
nettrine.dbal:
  debug:
    panel: <boolean>
    sourcePaths: array<string>

  connections:
    <name>:
      # Connection
      url: <string>
      pdo: <string>
      memory: <string>
      driver: <string>
      driverClass: <string>
      host: <string>
      dbname: <string>
      servicename: <string>
      user: <string>
      password: <string>
      charset: <string>
      portability: <int>
      fetchCase: <int>
      persistent: <boolean>
      wrapperClass: <class>

      types: array<string, class-string>
      typesMapping: array<string, class-string>

      # Config
      middlewares: <service[]>
      resultCache: <service>
      filterSchemaAssetsExpression: <string>
      autoCommit: <boolean>
```

For example:

```neon
nettrine.dbal:
  debug:
    panel: %debugMode%
    sourcePaths: [%appDir%]

  connections:
    default:
      driver: pdo_mysql
      host: localhost
      dbname: nettrine
      user: root
      password: root
      charset: utf8
      types:
        uuid: Ramsey\Uuid\Doctrine\UuidType
      middlewares: []
      resultCache: Nette\Caching\Storages\MemoryStorage
```

> [!TIP]
> Take a look at real **Nettrine DBAL** configuration example at [contributte/doctrine-project](https://github.com/contributte/doctrine-project/blob/f226bcf46b9bcce2f68961769a02e507936e4682/config/config.neon).

### Caching

> [!TIP]
> Take a look at more information in official Doctrine documentation:
> - https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/caching.html

A Doctrine can automatically cache result sets. The feature is optional though, and by default, no result set is cached.
You can enable the result cache by setting the `resultCache` configuration option to an instance of a cache driver.

> [!WARNING]
> Cache adapter must implement `Psr\Cache\CacheItemPoolInterface` interface.
> Use any PSR-6 compatible cache library like `symfony/cache` or `nette/caching`.

```neon
nettrine.dbal:
  connections:
    default:
      configuration:
        # Cache as class
        resultCache: App\MyCacheAdapter(%tempDir%/cache/orm)

        # Cache as service
        resultCache: @cacheService
```

If you want to disable cache, you can use `NullCacheAdapter`:

```neon
nettrine.dbal:
  configuration:
    resultCache: Nettrine\DBAL\Cache\NullCacheAdapter
```

### Types

> [!TIP]
> Take a look at more information in official Doctrine documentation:
> - http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
> - http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html

Example of type registration:

```neon
nettrine.dbal:
  connections:
    default:
      types:
        # https://github.com/ramsey/uuid-doctrine
        uuid: Ramsey\Uuid\Doctrine\UuidType
```

Example of type mapping:

```neon
nettrine.dbal:
  connections:
    default:
      types:
        uuid_binary_ordered_time: Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType
      typesMapping:
        uuid_binary_ordered_time: binary
```

### Debug

Enable or disable Tracy panel via `debug.panel` key.

Alternatively, specify your application root path under the `debug.sourcePaths` key to display correct queries source map in Tracy panel.

### Middlewares

> [!TIP]
> Take a look at more information in official Doctrine documentation:
> - https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/architecture.html#middlewares
> - https://github.com/doctrine/dbal/issues/5784
> Since Doctrine v3.6 you have to use middlewares instead of event system.

Middlewares are the way how to extend doctrine library or hook to special events.

```neon
nettrine.dbal:
  connections:
    default:
      middlewares:
        - App\MyMiddleware
```

```php
<?php

namspace App;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

final class MyMiddleware implements Middleware
{

	public function wrap(Driver $driver): Driver
	{
		return new MyDriverMiddleware($driver);
	}

}
```

```php
<?php

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class MyDriverMiddleware extends AbstractDriverMiddleware
{

	public function connect(array $params): Connection
	{
		return new MyConnection(parent::connect($params));
	}

}
```

### Logging

> [!TIP]
> Take a look at more information in official Doctrine documentation:
> - https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/architecture.html#logging
> - https://github.com/doctrine/dbal/issues/5784
> Since Doctrine v3.6 you have to use middlewares instead of event system.

To log all queries you should define your own middleware or you can use `Doctrine\DBAL\Logging\Middleware`.

```neon
nettrine.dbal:
  configuration:
    middlewares:
      logger: Doctrine\DBAL\Logging\Middleware(MyLogger())
```

You can try our prepared loggers.


```neon
nettrine.dbal:
  configuration:
    middlewares:
      # Write logs to file
      logger: Doctrine\DBAL\Logging\Middleware(
        Nettrine\DBAL\Logger\FileLogger(%tempDir%/db.sql)
      )

      # Show logs in tracy file
      logger: Doctrine\DBAL\Logging\Middleware(
        Nettrine\DBAL\Logger\TracyLogger
      )
```

### Console

Take advantage of empowering this package with `symfony/console`and prepared [contributte/console](https://github.com/contributte/console) integration.

```bash
composer require contributte/console
```

```neon
extensions:
  console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)

  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
  nettrine.dbal.console: Nettrine\DBAL\DI\DbalConsoleExtension(%consoleMode%)
```

Since this moment when you type `bin/console`, there'll be registered commands from Doctrine DBAL.

![Console Commands](https://raw.githubusercontent.com/nettrine/dbal/master/.docs/assets/console.png)

## Examples

> [!TIP]
> Take a look at more examples in [contributte/doctrine](
