<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Nette\Bridges\CacheDI\CacheExtension;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// Types
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
						types:
							foo: Doctrine\DBAL\Types\StringType
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
