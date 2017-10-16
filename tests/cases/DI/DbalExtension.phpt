<?php

/**
 * Test: DI\DbalExtension
 */

use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tester\FileMock;

require_once __DIR__ . '/../../bootstrap.php';

test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->loadConfig(FileMock::create('
			dbal:
				connection:
					driver: mysqli
		', 'neon'));
	}, '1a');

	/** @var Container $container */
	$container = new $class;

	Assert::type(Connection::class, $container->getByType(Connection::class));
});
