<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Types
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_pgsql
							url: "postgresql://test:test@localhost:5432/test"
							charset: utf8
							serverVersion: "15.0"
							defaultTableOptions:
								charset: utf8
								collate: utf8_unicode_ci
			NEON
			));
		})->build();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);

	Assert::type(Driver::class, $connection->getDriver());
});
