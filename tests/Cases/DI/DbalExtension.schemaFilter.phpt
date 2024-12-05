<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Configuration;
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
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							schemaAssetsFilter: Tests\Fixtures\DummySchemaFilter
			NEON
			));
		})->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByName('nettrine.dbal.connections.default.configuration');

	Assert::type('callable', $configuration->getSchemaAssetsFilter());
	Assert::true($configuration->getSchemaAssetsFilter()('fake'));
});

// Service
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: "sqlite:///:memory:"
							schemaAssetsFilter: @filter
						second:
							driver: pdo_sqlite
							password: test
							user: test
							path: "sqlite:///:memory:"
							schemaAssetsFilter: @Tests\Fixtures\DummySchemaFilter

				services:
					filter: Tests\Fixtures\DummySchemaFilter
			NEON
			));
		})->build();

	/** @var Configuration $configuration */
	$configuration = $container->getByName('nettrine.dbal.connections.default.configuration');

	Assert::type('callable', $configuration->getSchemaAssetsFilter());
	Assert::true($configuration->getSchemaAssetsFilter()('fake'));
});
