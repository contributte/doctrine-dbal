# Nettrine DBAL

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


## Setup

Install package

```bash
composer require nettrine/dbal
```

Register extension

```yaml
extensions:
  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
```


## Relying

Take advantage of enpowering this package with 2 extra packages:

- `doctrine/cache`
- `symfony/console`


### `doctrine/cache`

This package relies on `doctrine/cache`, use prepared [nettrine/cache](https://github.com/nettrine/cache) integration.

```bash
composer require nettrine/cache
```

```yaml
extensions:
  nettrine.cache: Nettrine\Cache\DI\CacheExtension
```

[Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html) needs [Doctrine Cache](https://www.doctrine-project.org/projects/cache.html) to be configured. If you register `nettrine/cache` extension it will detect it automatically.


### `symfony/console`

This package relies on `symfony/console`, use prepared [contributte/console](https://github.com/contributte/console) integration.

```bash
composer require contributte/console
```

```yaml
extensions:
  console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)

  nettrine.dbal: Nettrine\DBAL\DI\DbalExtension
  nettrine.dbal.console: Nettrine\DBAL\DI\DbalConsoleExtension(%consoleMode%)
```

Since this moment when you type `bin/console`, there'll be registered commands from Doctrine DBAL.

![Console Commands](https://raw.githubusercontent.com/nettrine/dbal/master/.docs/assets/console.png)


## Configuration

**Schema definition**

 ```yaml
nettrine.dbal:
  debug:
    panel: <boolean>
    sourcePaths: <string[]>
  configuration:
    sqlLogger: <service>
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

Minimal configuration could looks like this:

```yaml
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

Take a look at real **Nettrine DBAL** configuration example at [Nutella Project](https://github.com/planette/nutella-project/blob/90f1eca94fa62b7589844481549d4823d3ed20f8/app/config/ext/nettrine.neon).


### Types

Here is example how to register custom type for [UUID](https://github.com/ramsey/uuid-doctrine).

```yaml
dbal:
  connection:
    types:
      uuid_binary_ordered_time:
        class: Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType
        commented: false

      typesMapping:
        uuid_binary_ordered_time: binary
```

For more information about custom types, follow the official documention.

- http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
- http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html


### Debug

Enable or disable Tracy panel via `debug.panel` key.

Alternatively specify your application root path under `debug.sourcePaths` key to display correct queries source map in Tracy panel.


### Events

You can use native [Doctrine DBAL event system](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.10/reference/events.html#events).

Create your subscriber class which implements `EventSubscriber` interface. Dependency injection with autowiring is enabled.

```php
namespace App;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

final class PostConnectSubscriber implements EventSubscriber
{
  public function postConnect(ConnectionEventArgs $args): void
  {
    // Magic goes here...
  }

  public function getSubscribedEvents(): array
  {
    return [Events::postConnect];
  }
}
```

Register your subscriber as a service in NEON file.

```yaml
services:
  subscriber1:
    class: App\PostConnectSubscriber
```


## Bridges


### PSR-3

To log all queries with a PSR-3 logger, define service under `configuration.sqlLogger` key.
[Monolog](https://github.com/contributte/monolog) provides PSR compatible services.

```yaml
dbal:
  configuration:
    sqlLogger: Nettrine\DBAL\Logger\PsrLogger()
```


## Examples

You can find more examples in [planette playground](https://github.com/planette/playground) repository.
