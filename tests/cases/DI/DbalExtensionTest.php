<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\InvalidStateException;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Events\DebugEventManager;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// Debug mode
Toolkit::test(function (): void {
	$loader = new ContainerLoader(TMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('tracy', new TracyExtension());
		$compiler->addExtension('cache', new CacheExtension());
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->addConfig([
			'parameters' => [
				'tempDir' => TMP_DIR,
			],
			'dbal' => [
				'debug' => [
					'panel' => true,
				],
			],
		]);
	}, __FILE__ . '1');

	/** @var Container $container */
	$container = new $class();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);

	Assert::type(DebugEventManager::class, $connection->getEventManager());
});

// Server version
Toolkit::test(function (): void {
	$loader = new ContainerLoader(TMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('cache', new CacheExtension());
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->addConfig([
			'parameters' => [
				'tempDir' => TMP_DIR,
			],
			'dbal' => ['connection' => ['driver' => 'pdo_pgsql', 'serverVersion' => '10.0']],
		]);
	}, __FILE__ . '2');

	/** @var Container $container */
	$container = new $class();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);

	Assert::type(PostgreSQL100Platform::class, $connection->getDatabasePlatform());
	Assert::falsey($connection->isConnected());
});

// No cache
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$loader = new ContainerLoader(TMP_DIR, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
		}, __FILE__ . '3');

		new $class();
	}, InvalidStateException::class, 'Service \'dbal.configuration\' (type of Doctrine\DBAL\Configuration): Service of type \'Doctrine\Common\Cache\Cache\' not found.');
});

// Types
Toolkit::test(function (): void {
	$loader = new ContainerLoader(TMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('cache', new CacheExtension());
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->addConfig([
			'parameters' => [
				'tempDir' => TMP_DIR,
			],
			'dbal' => [
				'connection' => [
					'driver' => 'pdo_sqlite',
					'types' => [
						'foo' => ['class' => StringType::class],
						'bar' => IntegerType::class,
					],
				],
			],
		]);
	}, __FILE__ . '4');

	/** @var Container $container */
	$container = new $class();

	/** @var Connection $connection */
	$container->getByType(Connection::class);

	Assert::type(StringType::class, Type::getType('foo'));
	Assert::type(IntegerType::class, Type::getType('bar'));
});
