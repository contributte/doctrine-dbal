<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nettrine\DBAL\Cache\NullCache;
use Nettrine\DBAL\DI\DbalExtension;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// no cache configuration
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

	/** @var Connection $connection */
	$connection = $container->getByName('nettrine.dbal.connections.default.connection');

	Assert::null($connection->getConfiguration()->getResultCache());
});

// cache configuration
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
				],
			]);
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						c1:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							resultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine-dbal, defaultLifetime: 0, directory: %tempDir%/cache/dbal)
						c2:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							resultCache: @customCache

				services:
					customCache: Nettrine\DBAL\Cache\NullCache
			NEON
			));
		})->build();

	/** @var Connection $connection */
	$connection = $container->getByName('nettrine.dbal.connections.c1.connection');
	Assert::type(FilesystemAdapter::class, $connection->getConfiguration()->getResultCache());

	/** @var Connection $connection */
	$connection = $container->getByName('nettrine.dbal.connections.c2.connection');
	Assert::type(NullCache::class, $connection->getConfiguration()->getResultCache());
});
