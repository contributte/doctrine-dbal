<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Liberator;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
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
			$compiler->addExtension('nette.tracy', new TracyExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"

						second:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
					debug:
						panel: true
			NEON
			));
		})->build();

	$container->getByName('nettrine.dbal.connections.default.connection');
	$container->getByName('nettrine.dbal.connections.second.connection');
	$blueScreen = Debugger::getBlueScreen();
	$panels = Liberator::of($blueScreen)->panels;

	Assert::count(2, $panels);
});
