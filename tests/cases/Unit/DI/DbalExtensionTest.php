<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\InvalidStateException;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Events\DebugEventManager;
use Tests\Toolkit\TestCase;
use Tracy\Bridges\Nette\TracyExtension;

final class DbalExtensionTest extends TestCase
{

	public function testDebugMode(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('cache', new CacheExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => TEMP_PATH,
				],
				'dbal' => [
					'debug' => [
						'panel' => true,
					],
				],
			]);
		}, 'di1');

		/** @var Container $container */
		$container = new $class();

		/** @var Connection $connection */
		$connection = $container->getByType(Connection::class);

		$this->assertInstanceOf(DebugEventManager::class, $connection->getEventManager());
	}

	public function testServerVersion(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('cache', new CacheExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => TEMP_PATH,
				],
				'dbal' => ['connection' => ['driver' => 'pdo_pgsql', 'serverVersion' => '10.0']],
			]);
		}, 'di2');

		/** @var Container $container */
		$container = new $class();

		/** @var Connection $connection */
		$connection = $container->getByType(Connection::class);

		$this->assertInstanceOf(PostgreSQL100Platform::class, $connection->getDatabasePlatform());
		$this->assertFalse($connection->isConnected());
	}

	public function testNoCache(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectExceptionMessage('Service \'dbal.configuration\' (type of Doctrine\DBAL\Configuration): Service of type \'Doctrine\Common\Cache\Cache\' not found.');

		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
		}, 'di3');

		new $class();
	}

}
