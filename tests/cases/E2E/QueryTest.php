<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\NeonLoader;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('tracy', new TracyExtension());
		$compiler->addExtension('cache', new CacheExtension());
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->addConfig(NeonLoader::load('
			dbal:
				connection:
					driver: pdo_sqlite
			'));
		$compiler->addConfig([
			'parameters' => [
				'tempDir' => TMP_DIR,
			],
		]);
	}, __FILE__ . '1');

	/** @var Container $container */
	$container = new $class();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);
	$connection->executeQuery(
		'CREATE TABLE person (id int, lastname varchar(255), firstname varchar(255), address varchar(255), city varchar(255));'
	);

	$qb = $connection->createQueryBuilder();

	$qb->insert('person')
		->values([
			'id' => 1,
			'firstname' => '"John"',
			'lastname' => '"Doe"',
		])
		->execute();

	$qb->insert('person')
		->values([
			'id' => 2,
			'firstname' => '"Sam"',
			'lastname' => '"Smith"',
		])
		->execute();

	$qb = $connection->createQueryBuilder();
	$result = $qb->select('id', 'firstname')
		->from('person')
		->execute()
		->fetchAllAssociative();
	$expected = [
		[
			'id' => 1,
			'firstname' => 'John',
		],
		[
			'id' => 2,
			'firstname' => 'Sam',
		],
	];
	if (PHP_VERSION_ID < 80100) { // PDO returns everything as string on PHP <8.1
		array_walk_recursive(
			$expected,
			function (&$value): void {
				$value = (string) $value;
			}
		);
	}

	Assert::equal($expected, $result);
});
