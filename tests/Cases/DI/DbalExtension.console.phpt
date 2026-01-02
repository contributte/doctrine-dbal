<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Test RunSqlCommand is registered
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

	$command = $container->getByName('nettrine.dbal.runSqlCommand');

	Assert::type(RunSqlCommand::class, $command);
});

// Test command is registered with multiple connections
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
						second:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
			NEON
			));
		})->build();

	$command = $container->getByName('nettrine.dbal.runSqlCommand');

	Assert::type(RunSqlCommand::class, $command);
});
