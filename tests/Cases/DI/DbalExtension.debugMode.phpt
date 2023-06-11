<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Liberator;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\Debugger;

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

	call_user_func([$container, 'initialize']);

	$blueScreen = Debugger::getBlueScreen();
	$panels = Liberator::of($blueScreen)->panels;

	Assert::count(1, $panels);
});
