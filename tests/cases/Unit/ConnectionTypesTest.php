<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Doctrine\DBAL\Types\StringType;
use Nettrine\DBAL\ConnectionFactory;
use Tests\Toolkit\TestCase;

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

		$this->assertEquals('foo', $connection->getDatabasePlatform()->getDoctrineTypeMapping('db_foo'));
	}

}
