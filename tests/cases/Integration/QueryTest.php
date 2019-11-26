<?php declare(strict_types = 1);

namespace Tests\Cases\Integration;

use Doctrine\DBAL\Connection;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Tests\Toolkit\TestCase;
use Tracy\Bridges\Nette\TracyExtension;

final class QueryTest extends TestCase
{

	public function testQueryBuilder(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('cache', new CacheExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => TEMP_PATH,
				],
			]);
		}, 'int1');

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
		$select = $qb->select('id', 'firstname')
			->from('person')
			->execute();

		$this->assertEquals([
			[
				'id' => '1',
				'firstname' => 'John',
			],
			[
				'id' => '2',
				'firstname' => 'Sam',
			],
		], $select->fetchAll());
	}

}
