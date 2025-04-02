<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// DBAL
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
			NEON
			));
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

	Assert::equal(
		expected: [
			[
				'id' => 1,
				'firstname' => 'John',
			],
			[
				'id' => 2,
				'firstname' => 'Sam',
			],
		],
		actual: $result
	);
});

// DBAL + cache
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							resultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: dbal, defaultLifetime: 0, directory: %tempDir%/cache/doctrine/dbal)
			NEON
			));
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
		->enableResultCache(new QueryCacheProfile(3600, 'person'))
		->executeQuery()
		->fetchAllAssociative();

	Assert::equal(
		expected: [
			[
				'id' => 1,
				'firstname' => 'John',
			],
			[
				'id' => 2,
				'firstname' => 'Sam',
			],
		],
		actual: $result
	);
});
