<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Doctrine\DBAL\Types\StringType;
use Nettrine\DBAL\ConnectionFactory;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
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

	Assert::equal('foo', $connection->getDatabasePlatform()->getDoctrineTypeMapping('db_foo'));
});
