# Nettrine DBAL

Doctrine DBAL for Nette Framework.

## Content

- [Setup](#setup)
- [Configuration](#configuration)
- [Usage](#usage)
- [Events](#events)
- [Bridges](#bridges)
    - [Symfony\Console](#symfony-console)
    - [Psr\Log](#psr-log)

## Setup

Install package

```bash
composer require nettrine/dbal
```

Register extension

```yaml
extensions:
    dbal: Nettrine\DBAL\DI\DbalExtension
```

There is also bridge to Symfony\Console. More in [Bridges](#bridges).

## Configuration

Minimal configuration could looks like this.

```yaml
dbal:
    debug:
        panel: %debugMode%
    connection:
        host: localhost
        driver: mysqli
        dbname: nettrine
        user: root
        password: root
```

Full configuration options:

```yaml
dbal:
    debug:
        panel: %debugMode%
        sourcePaths: [%appDir%]
    configuration:
        sqlLogger: NULL
        resultCache: NULL
        filterSchemaAssetsExpression: NULL
        autoCommit: TRUE

    connection:
        url: NULL
        pdo: NULL
        memory: NULL
        driver: pdo_mysql
        driverClass: NULL
        host: NULL
        dbname: NULL
        servicename: NULL
        user: NULL
        password: NULL
        charset: UTF8
        portability: PortabilityConnection::PORTABILITY_ALL
        fetchCase: PDO::CASE_LOWER
        persistent: TRUE
        types: []
        typesMapping: []
        wrapperClass: NULL
```

### Types

Here is a example how to custom type. For more information, follow the official documention.

- http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
- http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html

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

### Debug

* `sourcePaths` - displays all source files from provided paths where a query is called from
* Queries table is sortable

## Events

You can use native [Doctrine DBAL event system](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.7/reference/events.html#events).

```yaml
services:
    subscriber1:
      class: App\PostConnectSubscriber
```

Register and create your own subscribers. There're services, so constructor injection will works. There're also
loaded lazily, don't worry about performance.

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

## Bridges

### Symfony\Console

This package works pretty well with [Symfony/Console](https://symfony.com/doc/current/components/console.html). Take a look at [Contributte/Console](https://github.com/contributte/console)
tiny integration for Nette Framework.

```yaml
extensions:
    # Console
    console: Contributte\Console\DI\ConsoleExtension

    # Dbal
    dbal: Nettrine\DBAL\DI\DbalExtension
    dbal.console: Nettrine\DBAL\DI\DbalConsoleExtension(%consoleMode%)
```

From this moment when you type `bin/console`, there'll be registered commands from Doctrine DBAL.

![Commands](https://raw.githubusercontent.com/nettrine/dbal/master/.docs/assets/commands.png)

### Psr\Log

Log all queries with a PSR-3 logger, e.g. [Monolog](https://github.com/contributte/monolog)

```yaml
dbal:
    configuration:
        sqlLogger: Nettrine\DBAL\Logger\PsrLogger()
```
