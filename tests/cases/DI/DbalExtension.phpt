<?php declare(strict_types = 1);

/**
 * Test: DI\DbalExtension
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tester\FileMock;

require_once __DIR__ . '/../../bootstrap.php';

test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->loadConfig(FileMock::create('
			dbal:
				debug: true
				connection:
					driver: pdo_sqlite
		', 'neon'));
	}, '1a');

	/** @var Container $container */
	$container = new $class;
	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);
	$qb = $connection->createQueryBuilder();

	Assert::type(Connection::class, $connection);
	Assert::type(QueryBuilder::class, $qb);

	$connection->executeQuery(
		'CREATE TABLE person (id int, lastname varchar(255), firstname varchar(255), address varchar(255), city varchar(255));'
	);

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
	$select = $qb->select('id', 'firstname')
		->from('person')
		->execute();

	Assert::equal([
		[
			'id' => '1',
			'firstname' => 'John',
		],
		[
			'id' => '2',
			'firstname' => 'Sam',
		],
	], $select->fetchAll());
});
