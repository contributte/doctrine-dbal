<?php declare(strict_types = 1);

namespace Tests\Nettrine\DBAL\Cases;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\StringType;
use Nettrine\DBAL\ConnectionFactory;

final class ConnectionTypesTest extends TestCase
{

	public function testTypes(): void
	{
		$types = [
			'foo' => [
				'class' => StringType::class,
				'commented' => false,
			],
		];
		$mapping = [
			'db_foo' => 'foo',
		];

		$connectionFactory = new ConnectionFactory($types, $mapping);
		$connection = $connectionFactory->createConnection(['driver' => 'pdo_sqlite']);

		self::assertInstanceOf(Connection::class, $connection);
		self::assertEquals('foo', $connection->getDatabasePlatform()->getDoctrineTypeMapping('db_foo'));
	}

}
