<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Exception (no connections)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
			})->build();
	}, InvalidConfigurationException::class, "The mandatory item 'nettrine.dbal › connections' is missing.");
});

// No default connection
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						first:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
			NEON
			));
		})->build();

	Assert::type(Connection::class, $container->getByName('nettrine.dbal.connections.first.connection'));
});
