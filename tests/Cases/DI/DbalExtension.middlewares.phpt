<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\Bridges\CacheDI\CacheExtension;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Fixtures\Driver\TestDriver;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('cache', new CacheExtension(Tests::TEMP_PATH));
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load('
				dbal:
					configuration:
						middlewares:
							test: Tests\Fixtures\Middleware\TestMiddleware
					connection:
						driver: pdo_sqlite
			'));
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Environment::getTestDir(),
				],
			]);
		})
		->build();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);
	Assert::type(TestDriver::class, $connection->getDriver());
});

Toolkit::test(function (): void {
	Assert::exception(
		function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('dbal', new DbalExtension());
					$compiler->addConfig(Neonkit::load('
				dbal:
					configuration:
						middlewares:
							- Invalid
			'));
				})
				->build();
		},
		InvalidConfigurationException::class,
		"The key of item 'dbal › configuration › middlewares › 0' expects to be string, 0 given."
	);
});
