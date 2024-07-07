<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Configuration;
use Nette\Bridges\CacheDI\CacheExtension;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// Static method
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
					configuration:
						schemaAssetsFilter: Tests\Fixtures\DummySchemaFilter::filter
					connection:
						driver: pdo_sqlite
			NEON
			));
		})->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByName('nettrine.dbal.configuration');

	Assert::type('callable', $configuration->getSchemaAssetsFilter());
	Assert::true($configuration->getSchemaAssetsFilter()('fake'));
});

// Service
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
					configuration:
						schemaAssetsFilter: @Tests\Fixtures\DummySchemaFilter
					connection:
						driver: pdo_sqlite

				services:
					- Tests\Fixtures\DummySchemaFilter
			NEON
			));
		})->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByName('nettrine.dbal.configuration');

	Assert::type('callable', $configuration->getSchemaAssetsFilter());
	Assert::true($configuration->getSchemaAssetsFilter()('fake'));
});
