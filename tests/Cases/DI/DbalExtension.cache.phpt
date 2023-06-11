<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\ServiceCreationException;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

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
