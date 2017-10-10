# Doctrine DBAL

Doctrine DBAL for Nette Framework.

-----

[![Build Status](https://img.shields.io/travis/nettrine/dbal.svg?style=flat-square)](https://travis-ci.org/nettrine/dbal)
[![Code coverage](https://img.shields.io/coveralls/nettrine/dbal.svg?style=flat-square)](https://coveralls.io/r/nettrine/dbal)
[![Licence](https://img.shields.io/packagist/l/nettrine/dbal.svg?style=flat-square)](https://packagist.org/packages/nettrine/dbal)

[![Downloads this Month](https://img.shields.io/packagist/dm/nettrine/dbal.svg?style=flat-square)](https://packagist.org/packages/nettrine/dbal)
[![Downloads total](https://img.shields.io/packagist/dt/nettrine/dbal.svg?style=flat-square)](https://packagist.org/packages/nettrine/dbal)
[![Latest stable](https://img.shields.io/packagist/v/nettrine/dbal.svg?style=flat-square)](https://packagist.org/packages/nettrine/dbal)
[![Latest unstable](https://img.shields.io/packagist/vpre/nettrine/dbal.svg?style=flat-square)](https://packagist.org/packages/nettrine/dbal)

## Discussion / Help

[![Join the chat](https://img.shields.io/gitter/room/nettrine/nettrine.svg?style=flat-square)](http://bit.ly/nettrine)

## Install

```sh
composer require nettrine/dbal
```

## Usage

```yaml
extensions:
    dbal: Nettrine\Dbal\DI\DbalExtension
```

```
services:
    database.configuration.logger:
        class: Doctrine\DBAL\Logging\LoggerChain
        autowired: no
        setup:
            - addLogger(Nettrine\Dbal\Logger\FileLogger(%tempDir%/doctrine.log))
            - addLogger(Nettrine\Dbal\Logger\TracyDumpLogger())

    database.configuration:
        class: Doctrine\DBAL\Configuration
        autowired: off
        setup:
            - setSQLLogger(@database.configuration.logger)

    database.connection:
        class: Doctrine\DBAL\Connection
        factory: Doctrine\DBAL\DriverManager::getConnection([
            driver: %database.driver%,
            host: %database.host%,
            dbname: %database.dbname%,
            servicename: %database.sid%,
            user: %database.user%,
            password: %database.password%,
            charset: %database.charset%,
            wrapperClass: 'Doctrine\DBAL\Portability\Connection',
            portability: Doctrine\DBAL\Portability\Connection::PORTABILITY_ALL,
            fetch_case: PDO::CASE_LOWER,
            persistent: true
        ],  @database.configuration)
```

## Maintainers

<table>
  <tbody>
    <tr>
      <td align="center">
        <a href="https://github.com/f3l1x">
            <img width="150" height="150" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=150">
        </a>
        </br>
        <a href="https://github.com/f3l1x">Milan Felix Šulc</a>
      </td>
    </tr>
  <tbody>
</table>

---

Thank you for testing, reporting and contributing.