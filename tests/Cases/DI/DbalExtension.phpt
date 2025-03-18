<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Minimal config
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(
				<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
				NEON
			));
		})->build();

	Assert::type(Connection::class, $container->getByName('nettrine.dbal.connections.default.connection'));
});

// MariaDB
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(
				<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: mysqli
							host: localhost
							port: "3306"
							user: test
							password: test
							serverVersion: 11.0.0
				NEON
			));
		})->build();

	Assert::type(Connection::class, $container->getByName('nettrine.dbal.connections.default.connection'));
});

// PostgreSQL
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(
				<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_pgsql
							host: localhost
							port: 3306
							user: test
							password: test
							serverVersion: 17.0.0
				NEON
			));
		})->build();

	Assert::type(Connection::class, $container->getByName('nettrine.dbal.connections.default.connection'));
});
