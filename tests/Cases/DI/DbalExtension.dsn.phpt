<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Types
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
				$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
			NEON
				));
			})->build();
	}, InvalidConfigurationException::class, "Failed assertion 'Configure DNS url or explicit host, port, user, password, dbname and others.' for item 'nettrine.dbal › connections › default' with value object stdClass.");
});
