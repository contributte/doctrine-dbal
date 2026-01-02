<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Nettrine\DBAL\ConnectionFactory;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

// Test createConnection with no types
Toolkit::test(function (): void {
	$connectionFactory = new ConnectionFactory();
	$connection = $connectionFactory->createConnection(['driver' => 'pdo_sqlite', 'path' => ':memory:']);

	Assert::type(Connection::class, $connection);
});

// Test createConnection with empty types arrays
Toolkit::test(function (): void {
	$connectionFactory = new ConnectionFactory([], []);
	$connection = $connectionFactory->createConnection(['driver' => 'pdo_sqlite', 'path' => ':memory:']);

	Assert::type(Connection::class, $connection);
});

// Test multiple connections reuse initialized types
Toolkit::test(function (): void {
	$connectionFactory = new ConnectionFactory();
	$connection1 = $connectionFactory->createConnection(['driver' => 'pdo_sqlite', 'path' => ':memory:']);
	$connection2 = $connectionFactory->createConnection(['driver' => 'pdo_sqlite', 'path' => ':memory:']);

	Assert::type(Connection::class, $connection1);
	Assert::type(Connection::class, $connection2);
});

// Test local types mapping
Toolkit::test(function (): void {
	$connectionFactory = new ConnectionFactory();
	$connection = $connectionFactory->createConnection(
		['driver' => 'pdo_sqlite', 'path' => ':memory:'],
		null,
		['local_type_test' => 'string']
	);

	Assert::equal('string', $connection->getDatabasePlatform()->getDoctrineTypeMapping('local_type_test'));
});

// Test with custom Configuration
Toolkit::test(function (): void {
	$config = new Configuration();
	$config->setAutoCommit(false);

	$connectionFactory = new ConnectionFactory();
	$connection = $connectionFactory->createConnection(
		['driver' => 'pdo_sqlite', 'path' => ':memory:'],
		$config
	);

	Assert::false($connection->getConfiguration()->getAutoCommit());
});

// Test global and local type mapping combined
Toolkit::test(function (): void {
	$globalMapping = ['global_test_type' => 'string'];
	$localMapping = ['local_test_type' => 'integer'];

	$connectionFactory = new ConnectionFactory([], $globalMapping);
	$connection = $connectionFactory->createConnection(
		['driver' => 'pdo_sqlite', 'path' => ':memory:'],
		null,
		$localMapping
	);

	Assert::equal('string', $connection->getDatabasePlatform()->getDoctrineTypeMapping('global_test_type'));
	Assert::equal('integer', $connection->getDatabasePlatform()->getDoctrineTypeMapping('local_test_type'));
});

// Test type registration with new type
Toolkit::test(function (): void {
	$typeName = 'custom_factory_test_type_' . uniqid();
	$types = [
		$typeName => StringType::class,
	];

	$connectionFactory = new ConnectionFactory($types);
	$connectionFactory->createConnection(['driver' => 'pdo_sqlite', 'path' => ':memory:']);

	Assert::true(Type::hasType($typeName));
	Assert::type(StringType::class, Type::getType($typeName));
});

// Test connection with memory SQLite
Toolkit::test(function (): void {
	$connectionFactory = new ConnectionFactory();
	$connection = $connectionFactory->createConnection([
		'driver' => 'pdo_sqlite',
		'memory' => true,
	]);

	Assert::type(Connection::class, $connection);
});
