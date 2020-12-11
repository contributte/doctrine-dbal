<?php declare(strict_types = 1);

namespace Tests\Cases;

use Doctrine\DBAL\Types\StringType;
use Nettrine\DBAL\ConnectionFactory;
use Ninjify\Nunjuck\Toolkit;
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
