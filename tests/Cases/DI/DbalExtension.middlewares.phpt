<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Mocks\Driver\TestDriver;

require_once __DIR__ . '/../../bootstrap.php';

// Register middleware
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
							middlewares:
								test: Tests\Mocks\Middleware\TestMiddleware
			NEON
			));
		})->build();

	/** @var Connection $connection */
	$connection = $container->getByName('nettrine.dbal.connections.default.connection');

	Assert::type(TestDriver::class, $connection->getDriver());
});

// Invalid middleware
Toolkit::test(function (): void {
	Assert::exception(
		function (): void {
			ContainerBuilder::of()
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
									middlewares:
										test: Invalid
					NEON
					));
				})->build();
		},
		InvalidConfigurationException::class,
		"Failed assertion #0 for item 'nettrine.dbal › connections › default › middlewares › test' with value 'Invalid'."
	);
});
