<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Exception (no driver)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(static function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
				$compiler->addConfig(Neonkit::load(
					<<<'NEON'
						nettrine.dbal:
							connections:
								default:
									driver: x
					NEON
				));
			})->build();
	}, InvalidConfigurationException::class, "The item 'nettrine.dbal › connections › default › driver' expects to be 'pdo_sqlite'|'sqlite3'|'pdo_mysql'|'mysqli'|'pdo_pgsql'|'pgsql'|'pdo_oci'|'oci8'|'pdo_sqlsrv'|'sqlsrv'|'ibm_db2', 'x' given.");
});
