<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Liberator;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Logging\Middleware;
use Monolog\Logger;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(static function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
				],
			]);
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							middlewares:
								logger: Doctrine\DBAL\Logging\Middleware(
									Monolog\Logger(doctrine, [Monolog\Handler\StreamHandler(%tempDir%/doctrine.log)])
								)
			NEON
			));
		})->build();

	/** @var Middleware $middleware */
	$middleware = $container->getByName('nettrine.dbal.connections.default.middleware.logger');

	$logger = Liberator::of($middleware)->logger;
	Assert::type(Logger::class, $logger);
});
