<?php declare(strict_types = 1);

namespace Tests\Cases\Middleware\Debug;

use Contributte\Tester\Toolkit;
use Doctrine\DBAL\ParameterType;
use Nettrine\DBAL\Middleware\Debug\DebugQuery;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Test constructor sets SQL
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT * FROM users');

	Assert::equal('SELECT * FROM users', $query->getSql());
});

// Test getParams returns empty array initially
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');

	Assert::equal([], $query->getParams());
});

// Test getTypes returns empty array initially
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');

	Assert::equal([], $query->getTypes());
});

// Test getDuration returns null before start
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');

	Assert::null($query->getDuration());
});

// Test getDuration returns null after start but before stop
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');
	$query->start();

	Assert::null($query->getDuration());
});

// Test getDuration returns value after stop
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');
	$query->start();
	usleep(1000); // 1ms
	$query->stop();

	$duration = $query->getDuration();
	Assert::notNull($duration);
	Assert::true($duration >= 0.0001);
});

// Test setValue with integer parameter
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT * FROM users WHERE id = ?');
	$query->setValue(1, 42, ParameterType::INTEGER);

	$params = $query->getParams();
	Assert::equal(42, $params[0]); // 1-indexed becomes 0-indexed
});

// Test setValue with string parameter
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT * FROM users WHERE name = ?');
	$query->setValue(1, 'John', ParameterType::STRING);

	$params = $query->getParams();
	Assert::equal('John', $params[0]);

	$types = $query->getTypes();
	Assert::equal(ParameterType::STRING, $types[0]);
});

// Test setValue with named parameter
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT * FROM users WHERE name = :name');
	$query->setValue('name', 'Jane', ParameterType::STRING);

	$params = $query->getParams();
	Assert::equal('Jane', $params['name']);
});

// Test multiple setValue calls
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT * FROM users WHERE id = ? AND name = ?');
	$query->setValue(1, 42, ParameterType::INTEGER);
	$query->setValue(2, 'John', ParameterType::STRING);

	$params = $query->getParams();
	Assert::equal(42, $params[0]);
	Assert::equal('John', $params[1]);
});

// Test clone creates independent copy of params
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');
	$query->setValue(1, 'original', ParameterType::STRING);

	$clone = clone $query;
	$query->setValue(1, 'modified', ParameterType::STRING);

	Assert::equal('modified', $query->getParams()[0]);
	Assert::equal('original', $clone->getParams()[0]);
});

// Test stop without start has no duration
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');
	$query->stop();

	Assert::null($query->getDuration());
});

// Test duration measures time correctly
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT 1');
	$query->start();
	usleep(10000); // 10ms
	$query->stop();

	$duration = $query->getDuration();
	Assert::true($duration >= 0.009); // At least 9ms
	Assert::true($duration < 0.1); // Less than 100ms
});

// Test setValue with NULL value
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT * FROM users WHERE deleted_at = ?');
	$query->setValue(1, null, ParameterType::NULL);

	$params = $query->getParams();
	Assert::null($params[0]);
});

// Test setValue with boolean value
Toolkit::test(function (): void {
	$query = new DebugQuery('SELECT * FROM users WHERE active = ?');
	$query->setValue(1, true, ParameterType::BOOLEAN);

	$params = $query->getParams();
	Assert::true($params[0]);
});
