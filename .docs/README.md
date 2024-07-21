# Contributte Doctrine DBAL

[Doctrine/DBAL](https://www.doctrine-project.org/projects/dbal.html) for Nette Framework.


## Content

- [Setup](#setup)
- [Relying](#relying)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Types](#types)
  - [Debug](#debug)
  - [Events](#events)
- [Bridges](#bridges)
    - [PSR3](#PSR-3)
- [Examples](#examples)
- [Other](#other)


## Setup

Install package

```bash
composer require nettrine/dbal
```

Register extension

```neon
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
```


## Relying

Take advantage of enpowering this package with 2 extra packages:

- `doctrine/cache`
- `symfony/console`


### `doctrine/cache`

This package relies on `doctrine/cache`, use prepared [nettrine/cache](https://github.com/contributte/doctrine-cache) integration.

```bash
composer require nettrine/cache
```

```neon
extensions:
  nettrine.cache: Nettrine\Cache\DI\CacheExtension
```

[Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) needs [Doctrine Cache](https://www.doctrine-project.org/projects/cache.html) to be configured. If you register `nettrine/cache` extension it will detect it automatically.


### `symfony/console`

This package relies on `symfony/console`, use prepared [contributte/console](https://github.com/contributte/console) integration.

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


## Configuration

**Schema definition**

 ```neon
nettrine.dbal:
  debug:
    panel: <boolean>
    sourcePaths: <string[]>
  configuration:
    middlewares: <service[]>
    resultCache: <service>
    filterSchemaAssetsExpression: <string>
    autoCommit: <boolean>

  connection:
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
    types: []
    typesMapping: []
```

**Under the hood**

Minimal configuration could look like this:

```neon
nettrine.dbal:
  debug:
    panel: %debugMode%
    sourcePaths: [%appDir%]
  connection:
    host: localhost
    driver: mysqli
    dbname: nettrine
    user: root
    password: root
```

Take a look at real **Nettrine DBAL** configuration example at [contributte/webapp-project](https://github.com/contributte/webapp-skeleton/blob/d23e6cbac9b91d6d069583f1661dd1171ccfe077/app/config/ext/nettrine.neon).


### Types

Here is an example of how to register custom type for [UUID](https://github.com/ramsey/uuid-doctrine).

```neon
nettrine.dbal:
  connection:
    types:
      uuid: Ramsey\Uuid\Doctrine\UuidType

      uuid_binary_ordered_time:
        class: Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType
        commented: false

    typesMapping:
      uuid_binary_ordered_time: binary
```

For more information about custom types, take a look at the official documention.

- http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
- http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html


### Debug

Enable or disable Tracy panel via `debug.panel` key.

Alternatively, specify your application root path under the `debug.sourcePaths` key to display correct queries source map in Tracy panel.


### Middlewares

> Since Doctrine v3.6 you have to use middlewares instead of event system, see issue [doctrine/dbal#5784](https://github.com/doctrine/dbal/issues/5784).

Middlewares are the way how to extend doctrine library or hook to special events.

```neon
nettrine.dbal:
  connection:
    middlewares:
      - MyMiddleware
```

```php
<?php

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Tests\Fixtures\Driver\TestDriver;

final class MyMiddleware implements Middleware
{

	public function wrap(Driver $driver): Driver
	{
		return new MyDriver($driver);
	}

}
```

```php
<?php

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class MyDriver extends AbstractDriverMiddleware
{

	public function connect(array $params): Connection
	{
		return new MyConnection(parent::connect($params));
	}

}
```

### Logging

> Since Doctrine v3.6 you have to use middlewares instead of event system, see issue [doctrine/dbal#5784](https://github.com/doctrine/dbal/issues/5784).

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

## Examples

### 1. Manual example

```sh
composer require nettrine/annotations nettrine/cache nettrine/migrations nettrine/fixtures nettrine/dbal nettrine/orm
```

```neon
# Extension > Nettrine
# => order is crucial
#
extensions:
  # Common
  nettrine.annotations: Nettrine\Annotations\DI\AnnotationsExtension
  nettrine.cache: Nettrine\Cache\DI\CacheExtension
  nettrine.migrations: Nettrine\Migrations\DI\MigrationsExtension
  nettrine.fixtures: Nettrine\Fixtures\DI\FixturesExtension

  # DBAL
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
  nettrine.dbal.console: Nettrine\DBAL\DI\DbalConsoleExtension

  # ORM
  nettrine.orm: Nettrine\ORM\DI\OrmExtension
  nettrine.orm.cache: Nettrine\ORM\DI\OrmCacheExtension
  nettrine.orm.console: Nettrine\ORM\DI\OrmConsoleExtension
  nettrine.orm.annotations: Nettrine\ORM\DI\OrmAnnotationsExtension
```

### 2. Example projects

We've made a few skeletons with preconfigured Nettrine nad Contributte packages.

- https://github.com/contributte/webapp-skeleton
- https://github.com/contributte/apitte-skeleton

### 3. Example playground

- https://github.com/contributte/playground (playground)
- https://contributte.org/examples.html (more examples)

## Other

This repository is inspired by these packages.

- https://github.com/doctrine
- https://gitlab.com/Kdyby/Doctrine
- https://gitlab.com/etten/doctrine
- https://github.com/DTForce/nette-doctrine
- https://github.com/portiny/doctrine

Thank you guys.
