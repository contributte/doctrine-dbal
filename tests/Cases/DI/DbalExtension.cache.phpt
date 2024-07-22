<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Psr6\CachePool;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\Bridges\CacheDI\CacheExtension;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Caching\Storages\MemoryStorage;
use Nette\DI\Compiler;
use Nette\DI\Definitions\Statement;
use Nettrine\DBAL\DI\DbalExtension;
use Psr\Cache\CacheItemPoolInterface;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// no cache configuration
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('cache', new CacheExtension(Tests::TEMP_PATH));
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nette.tracy', new TracyExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
					'appDir' => Tests::APP_PATH,
				],
			]);
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connection:
						driver: pdo_sqlite
			NEON
			));
		})->build();

	Assert::type(CacheItemPoolInterface::class, $container->getByName('nettrine.dbal.configuration')->getResultCache());
});

// cache configuration
$cacheDefinitions = [
	'Contributte\Psr6\CachePool(Nette\Caching\Cache(@Nette\Caching\Storage, "result-cache"))',
	'Contributte\Psr6\CachePool(Nette\Caching\Cache(namespace: "result-cache"))',
	'Nette\Caching\Cache(@Nette\Caching\Storage, "result-cache")',
	'Nette\Caching\Cache(namespace: "result-cache")',
	'Nette\Caching\Storages\MemoryStorage',
	'@svcCachePool',
	'@svcCache',
	'@svcStorage',
	'@' . CachePool::class,
	'@' . Cache::class,
	'@' . Storage::class,
];
foreach ($cacheDefinitions as $cacheDefinition) {
	Toolkit::test(function () use ($cacheDefinition): void {
		$container = ContainerBuilder::of()
			->withCompiler(static function (Compiler $compiler) use ($cacheDefinition): void {
				$compiler->addExtension('cache', new CacheExtension(Tests::TEMP_PATH));
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
				$compiler->addExtension('nette.tracy', new TracyExtension());
				$compiler->addConfig([
					'parameters' => [
						'tempDir' => Tests::TEMP_PATH,
						'appDir' => Tests::APP_PATH,
					],
				]);
				$compiler->getContainerBuilder()->addDefinition('svcCachePool')
					->setFactory(new Statement(CachePool::class, [new Statement(Cache::class, [1 => Tests::TEMP_PATH])]));
				$compiler->getContainerBuilder()->addDefinition('svcCache')
					->setFactory(new Statement(Cache::class, [1 => Tests::TEMP_PATH]));
				$compiler->getContainerBuilder()->addDefinition('svcStorage')
					->setFactory(MemoryStorage::class)
					->setAutowired(false);
				$compiler->addConfig(Neonkit::load(<<<NEON
				nettrine.dbal:
					connection:
						driver: pdo_sqlite
					configuration:
						resultCache: $cacheDefinition
			NEON
				));
			})->build();

		Assert::type(CacheItemPoolInterface::class, $container->getByName('nettrine.dbal.configuration')->getResultCache());
	});
}
