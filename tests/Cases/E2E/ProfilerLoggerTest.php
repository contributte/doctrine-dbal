<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\ConnectionAccessor;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Logger\ProfilerLogger;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('cache', new CacheExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load('
				dbal:
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

	$connection->getConfiguration()->setMiddlewares(new ProfilerLogger($container->getByType(ConnectionAccessor::class)));

	Assert::noError(function () use ($connection): void {
		// Orm insert queries have starting index 1, if ExpandArrayParameters would use parameters, it would throw an exception
		$connection->getConfiguration()->getSQLLogger()->startQuery('INSERT INTO person (id, lastname, firstname) VALUES (?, ?, ?)', [
			1 => 1,
			2 => 'John',
			3 => 'Doe',
		]);
	});

	Assert::noError(function () use ($connection): void {
		$connection->getConfiguration()->getSQLLogger()->startQuery('UPDATE person SET firstname = ?, lastname = ?', [
			0 => 'John',
			1 => 'Doe',
		]);
	});
});
