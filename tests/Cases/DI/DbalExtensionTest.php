<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nette\DI\ServiceCreationException;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\Logger\ProfilerLogger;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Debug mode
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
			nettrine.dbal:
				debug:
					panel: true
			NEON
			));
		})->build();

	Assert::type(ProfilerLogger::class, $container->getByType(ProfilerLogger::class));
});

// Server version
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
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
	$container = Container::of()
		->withDefaults()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addConfig(Helpers::neon(<<<'NEON'
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
			Container::of()
				->withCompiler(static function (Compiler $compiler): void {
					$compiler->addExtension('nettrine.dbal', new DbalExtension());
					$compiler->addConfig(Helpers::neon(<<<'NEON'
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
		Container::of()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
			})->build();
	}, InvalidConfigurationException::class, "The mandatory item 'nettrine.dbal › connection › driver' is missing.");
});
