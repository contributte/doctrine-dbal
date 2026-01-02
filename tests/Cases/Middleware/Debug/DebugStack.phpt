<?php declare(strict_types = 1);

namespace Tests\Cases\Middleware\Debug;

use Contributte\Tester\Toolkit;
use Nettrine\DBAL\Middleware\Debug\DebugQuery;
use Nettrine\DBAL\Middleware\Debug\DebugStack;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Test empty stack
Toolkit::test(function (): void {
	$stack = new DebugStack();

	Assert::equal([], $stack->getData());
});

// Test addQuery stores query
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$query = new DebugQuery('SELECT 1');
	$query->start();
	$query->stop();

	$stack->addQuery('default', $query);

	$data = $stack->getData();
	Assert::count(1, $data['default']);
	Assert::equal('SELECT 1', $data['default'][0]['sql']);
});

// Test getDataBy returns queries for specific connection
Toolkit::test(function (): void {
	$stack = new DebugStack();

	$query1 = new DebugQuery('SELECT 1');
	$query1->start();
	$query1->stop();
	$stack->addQuery('conn1', $query1);

	$query2 = new DebugQuery('SELECT 2');
	$query2->start();
	$query2->stop();
	$stack->addQuery('conn2', $query2);

	$data = $stack->getDataBy('conn1');
	Assert::count(1, $data);
	Assert::equal('SELECT 1', $data[0]['sql']);
});

// Test getDataBy returns empty array for unknown connection
Toolkit::test(function (): void {
	$stack = new DebugStack();

	Assert::equal([], $stack->getDataBy('unknown'));
});

// Test reset clears all data
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$query = new DebugQuery('SELECT 1');
	$query->start();
	$query->stop();
	$stack->addQuery('default', $query);

	$stack->reset();

	Assert::equal([], $stack->getData());
});

// Test getData resolves callable duration
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$query = new DebugQuery('SELECT 1');
	$query->start();

	$stack->addQuery('default', $query);

	// Stop query after adding to stack
	usleep(1000);
	$query->stop();

	$data = $stack->getData();
	Assert::type('float', $data['default'][0]['duration']);
});

// Test multiple queries per connection
Toolkit::test(function (): void {
	$stack = new DebugStack();

	for ($i = 0; $i < 5; $i++) {
		$query = new DebugQuery("SELECT $i");
		$query->start();
		$query->stop();
		$stack->addQuery('default', $query);
	}

	$data = $stack->getDataBy('default');
	Assert::count(5, $data);
});

// Test source paths filtering
Toolkit::test(function (): void {
	$stack = new DebugStack([__DIR__]);
	$query = new DebugQuery('SELECT 1');
	$query->start();
	$query->stop();
	$stack->addQuery('default', $query);

	$data = $stack->getData();
	Assert::type('array', $data['default'][0]['source']);
});

// Test query stores params
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$query = new DebugQuery('SELECT * FROM users WHERE id = ?');
	$query->start();
	$query->stop();
	$stack->addQuery('default', $query);

	$data = $stack->getData();
	Assert::type('array', $data['default'][0]['params']);
});

// Test query stores types
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$query = new DebugQuery('SELECT 1');
	$query->start();
	$query->stop();
	$stack->addQuery('default', $query);

	$data = $stack->getData();
	Assert::type('array', $data['default'][0]['types']);
});

// Test multiple connections
Toolkit::test(function (): void {
	$stack = new DebugStack();

	$query1 = new DebugQuery('SELECT 1');
	$query1->start();
	$query1->stop();
	$stack->addQuery('primary', $query1);

	$query2 = new DebugQuery('SELECT 2');
	$query2->start();
	$query2->stop();
	$stack->addQuery('replica', $query2);

	$data = $stack->getData();
	Assert::count(1, $data['primary']);
	Assert::count(1, $data['replica']);
});

// Test reset only affects data
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$query = new DebugQuery('SELECT 1');
	$query->start();
	$query->stop();
	$stack->addQuery('default', $query);

	$stack->reset();

	// Add another query after reset
	$query2 = new DebugQuery('SELECT 2');
	$query2->start();
	$query2->stop();
	$stack->addQuery('default', $query2);

	$data = $stack->getData();
	Assert::count(1, $data['default']);
	Assert::equal('SELECT 2', $data['default'][0]['sql']);
});
