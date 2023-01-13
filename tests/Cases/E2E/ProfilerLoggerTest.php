<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\ConnectionAccessor;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Logger\ProfilerLogger;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\DoctrineDeprecations;
use Tests\Toolkit\NeonLoader;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	// Ignore deprecations that are impossible to fix while keeping DBAL 2.x support
	DoctrineDeprecations::ignoreDeprecations(
		'https://github.com/doctrine/dbal/pull/4967',
		'https://github.com/doctrine/dbal/pull/4620',
		'https://github.com/doctrine/dbal/pull/4578'
	);

	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('cache', new CacheExtension());
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->addConfig(NeonLoader::load('
			dbal:
				connection:
					driver: pdo_sqlite
			'));
		$compiler->addConfig([
			'parameters' => [
				'tempDir' => TMP_DIR,
			],
		]);
	}, __FILE__ . '1');

	/** @var Container $container */
	$container = new $class();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);

	$connection->getConfiguration()->setSQLLogger(new ProfilerLogger($container->getByType(ConnectionAccessor::class)));

	Assert::noError(function () use ($connection) {
		// Orm insert queries have starting index 1, if ExpandArrayParameters would use parameters, it would throw an exception
		$connection->getConfiguration()->getSQLLogger()->startQuery('INSERT INTO person (id, lastname, firstname) VALUES (?, ?, ?)', [
			1 => 1,
			2 => 'John',
			3 => 'Doe',
		]);
	});

	Assert::noError(function () use ($connection) {
		$connection->getConfiguration()->getSQLLogger()->startQuery('UPDATE person SET firstname = ?, lastname = ?', [
			0 => 'John',
			1 => 'Doe',
		]);
	});
});
