<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Events;
use Nette\Bridges\CacheDI\CacheExtension;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// Empty events
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('cache', new CacheExtension(Tests::TEMP_PATH));
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
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

	/** @var EventManager $eventManager */
	$eventManager = $container->getByType(EventManager::class);

	Assert::count(0, $eventManager->getAllListeners());
});

// Subscriber
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

				services:
					- Tests\Fixtures\Events\DummyOnClearSubscriber
			NEON
			));
		})->build();

	/** @var EventManager $eventManager */
	$eventManager = $container->getByType(EventManager::class);

	Assert::count(1, $eventManager->getAllListeners());
	Assert::count(1, $eventManager->getAllListeners()[Events::onClear]);
});
