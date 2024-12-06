<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Liberator;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Middleware\Debug\DebugStack;
use Tester\Assert;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\Debugger;

require_once __DIR__ . '/../../bootstrap.php';

// Debug mode
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nette.tracy', new TracyExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"

						second:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
					debug:
						panel: true
			NEON
			));
		})->build();

	$container->getByName('nettrine.dbal.connections.default.connection');
	$container->getByName('nettrine.dbal.connections.second.connection');
	$blueScreen = Debugger::getBlueScreen();
	$panels = Liberator::of($blueScreen)->panels;

	Assert::count(2, $panels);
});

// Debug mode
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nette.tracy', new TracyExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"

						second:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
					debug:
						panel: true
			NEON
			));
		})->build();

	$doSql = function (Connection $connection): void {
		$connection->executeQuery(
			'CREATE TABLE person (id int, lastname varchar(255), firstname varchar(255), address varchar(255), city varchar(255));'
		);

		$qb = $connection->createQueryBuilder();
		$qb->insert('person')->values(['id' => 1, 'firstname' => '"John"', 'lastname' => '"Doe"'])->executeStatement();
		$qb->insert('person')->values(['id' => 2, 'firstname' => '"Sam"', 'lastname' => '"Smith"'])->executeStatement();

		$qb = $connection->createQueryBuilder();
		$qb->select('id', 'firstname')
			->from('person')
			->executeQuery()
			->fetchAllAssociative();
	};

	/** @var Connection $connection1 */
	$connection1 = $container->getByName('nettrine.dbal.connections.default.connection');
	$doSql($connection1);

	/** @var DebugStack $debugStack1 */
	$debugStack1 = $container->getByName('nettrine.dbal.connections.default.middleware.internal.debug.stack');
	Assert::count(4, $debugStack1->getDataBy('default'));

	/** @var Connection $connection2 */
	$connection2 = $container->getByName('nettrine.dbal.connections.second.connection');
	$doSql($connection2);

	/** @var DebugStack $debugStack2 */
	$debugStack2 = $container->getByName('nettrine.dbal.connections.second.middleware.internal.debug.stack');
	Assert::count(4, $debugStack2->getDataBy('second'));
});
