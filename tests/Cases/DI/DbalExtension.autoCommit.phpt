<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Configuration;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Test autoCommit true (default)
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

	/** @var Configuration $configuration */
	$configuration = $container->getByName('nettrine.dbal.connections.default.configuration');

	Assert::true($configuration->getAutoCommit());
});

// Test autoCommit false
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
							autoCommit: false
			NEON
			));
		})->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByName('nettrine.dbal.connections.default.configuration');

	Assert::false($configuration->getAutoCommit());
});

// Test autoCommit per connection
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						conn1:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							autoCommit: true
						conn2:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							autoCommit: false
			NEON
			));
		})->build();

	/** @var Configuration $configuration1 */
	$configuration1 = $container->getByName('nettrine.dbal.connections.conn1.configuration');
	Assert::true($configuration1->getAutoCommit());

	/** @var Configuration $configuration2 */
	$configuration2 = $container->getByName('nettrine.dbal.connections.conn2.configuration');
	Assert::false($configuration2->getAutoCommit());
});
