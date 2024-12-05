<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nettrine\DBAL\ConnectionProvider;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Exceptions\LogicalException;
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
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
			NEON
			));
		})->build();

	/** @var ConnectionProvider $connectionProvider */
	$connectionProvider = $container->getByType(ConnectionProvider::class);

	/** @var Connection $connection */
	$connection = $container->getByName('nettrine.dbal.connections.default.connection');

	Assert::equal($connection, $connectionProvider->getDefaultConnection());
	Assert::equal($connection, $connectionProvider->getConnection('default'));

	Assert::exception(function () use ($connectionProvider): void {
		$connectionProvider->getConnection('unknown');
	}, LogicalException::class, 'Service for connection "unknown" not found');
});
