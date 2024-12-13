<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Wrapper class
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_pgsql
							wrapperClass: Doctrine\DBAL\Connections\PrimaryReadReplicaConnection
							url: "postgresql://user:password@localhost:5432/table?charset=utf8&serverVersion=15.0"
							replica:
								read1:
									url: "postgresql://user:password@read-db1:5432/table?charset=utf8"
								read2:
									url: "postgresql://user:password@read-db2:5432/table?charset=utf8"
			NEON
			));
		})->build();

	/** @var PrimaryReadReplicaConnection $connection */
	$connection = $container->getByType(Connection::class);

	Assert::type(PrimaryReadReplicaConnection::class, $connection);
});
