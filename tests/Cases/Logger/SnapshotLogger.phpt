<?php declare(strict_types = 1);

namespace Tests\Cases\Logger;

use Contributte\Tester\Toolkit;
use Nettrine\DBAL\Logger\SnapshotLogger;
use Psr\Log\LogLevel;
use Stringable;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Test log stores message
Toolkit::test(function (): void {
	$logger = new SnapshotLogger();
	$logger->log(LogLevel::INFO, 'Test message');

	$reflection = new \ReflectionClass($logger);
	$property = $reflection->getProperty('snapshots');
	$snapshots = $property->getValue($logger);

	Assert::count(1, $snapshots);
	Assert::equal('Test message', $snapshots[0]['message']);
	Assert::equal(LogLevel::INFO, $snapshots[0]['level']);
});

// Test log stores context
Toolkit::test(function (): void {
	$logger = new SnapshotLogger();
	$logger->log(LogLevel::DEBUG, 'Query executed', ['sql' => 'SELECT 1']);

	$reflection = new \ReflectionClass($logger);
	$property = $reflection->getProperty('snapshots');
	$snapshots = $property->getValue($logger);

	Assert::equal(['sql' => 'SELECT 1'], $snapshots[0]['context']);
});

// Test log stores timestamp
Toolkit::test(function (): void {
	$beforeTime = time();
	$logger = new SnapshotLogger();
	$logger->log(LogLevel::INFO, 'Test');
	$afterTime = time();

	$reflection = new \ReflectionClass($logger);
	$property = $reflection->getProperty('snapshots');
	$snapshots = $property->getValue($logger);

	Assert::true($snapshots[0]['timestamp'] >= $beforeTime);
	Assert::true($snapshots[0]['timestamp'] <= $afterTime);
});

// Test multiple log entries
Toolkit::test(function (): void {
	$logger = new SnapshotLogger();
	$logger->info('First');
	$logger->warning('Second');
	$logger->error('Third');

	$reflection = new \ReflectionClass($logger);
	$property = $reflection->getProperty('snapshots');
	$snapshots = $property->getValue($logger);

	Assert::count(3, $snapshots);
});

// Test getQueries returns empty array
Toolkit::test(function (): void {
	$logger = new SnapshotLogger();

	Assert::equal([], $logger->getQueries());
});

// Test log with Stringable object
Toolkit::test(function (): void {
	$logger = new SnapshotLogger();
	$stringable = new class implements Stringable {

		public function __toString(): string
		{
			return 'Stringable message';
		}

	};

	$logger->log(LogLevel::INFO, $stringable);

	$reflection = new \ReflectionClass($logger);
	$property = $reflection->getProperty('snapshots');
	$snapshots = $property->getValue($logger);

	Assert::equal('Stringable message', $snapshots[0]['message']);
});

// Test different log levels via convenience methods
Toolkit::test(function (): void {
	$logger = new SnapshotLogger();
	$logger->emergency('emergency');
	$logger->alert('alert');
	$logger->critical('critical');
	$logger->error('error');
	$logger->warning('warning');
	$logger->notice('notice');
	$logger->info('info');
	$logger->debug('debug');

	$reflection = new \ReflectionClass($logger);
	$property = $reflection->getProperty('snapshots');
	$snapshots = $property->getValue($logger);

	Assert::count(8, $snapshots);
	Assert::equal(LogLevel::EMERGENCY, $snapshots[0]['level']);
	Assert::equal(LogLevel::ALERT, $snapshots[1]['level']);
	Assert::equal(LogLevel::CRITICAL, $snapshots[2]['level']);
	Assert::equal(LogLevel::ERROR, $snapshots[3]['level']);
	Assert::equal(LogLevel::WARNING, $snapshots[4]['level']);
	Assert::equal(LogLevel::NOTICE, $snapshots[5]['level']);
	Assert::equal(LogLevel::INFO, $snapshots[6]['level']);
	Assert::equal(LogLevel::DEBUG, $snapshots[7]['level']);
});
