<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nette\DI\ServiceCreationException;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Logger\ProfilerLogger;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// Debug mode
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.cache', new CacheExtension());
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
					debug:
						panel: true
			NEON
			));
		})->build();

	Assert::type(ProfilerLogger::class, $container->getByType(ProfilerLogger::class));
});

// Server version
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.cache', new CacheExtension());
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
						driver: pdo_pgsql
						serverVersion: 10.0
			NEON
			));
		})->build();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);

	Assert::type(PostgreSQL100Platform::class, $connection->getDatabasePlatform());
	Assert::falsey($connection->isConnected());
});

// Types
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.cache', new CacheExtension());
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
					driver: pdo_pgsql
					types:
						foo: { class: Doctrine\DBAL\Types\StringType }
						bar: Doctrine\DBAL\Types\IntegerType
			NEON
			));
		})->build();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);

	Assert::type(Connection::class, $connection);
	Assert::type(StringType::class, Type::getType('foo'));
	Assert::type(IntegerType::class, Type::getType('bar'));
});

// Exception (no cache extension)
Toolkit::test(function (): void {
	Assert::exception(
		function (): void {
			ContainerBuilder::of()
				->withCompiler(static function (Compiler $compiler): void {
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
		},
		ServiceCreationException::class,
		"~^Service 'nettrine\\.dbal\\.configuration' \\(type of Doctrine\\\\DBAL\\\\Configuration\\): Service of type '?Doctrine\\\\Common\\\\Cache\\\\Cache'? not found\.~"
	);
});

// Exception (no driver)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
			})->build();
	}, InvalidConfigurationException::class, "The mandatory item 'nettrine.dbal › connection › driver' is missing.");
});
