<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Psr6\DI\Psr6CachingExtension;
use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Nette\Bridges\CacheDI\CacheExtension;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('cache', new CacheExtension(Tests::TEMP_PATH));
			$compiler->addExtension('psr6', new Psr6CachingExtension());
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load('
				dbal:
					connection:
						driver: pdo_sqlite
			'));
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Environment::getTestDir(),
				],
			]);
		})
		->build();

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
		->executeStatement();

	$qb->insert('person')
		->values([
			'id' => 2,
			'firstname' => '"Sam"',
			'lastname' => '"Smith"',
		])
		->executeStatement();

	$qb = $connection->createQueryBuilder();
	$result = $qb->select('id', 'firstname')
		->from('person')
		->executeQuery()
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

	Assert::equal($expected, $result);
});
