<?php declare(strict_types = 1);

namespace Tests\Cases\Logger;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Nettrine\DBAL\Logger\FileLogger;
use Psr\Log\LogLevel;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Test log writes to file
Toolkit::test(function (): void {
	$file = Environment::getTestDir() . '/test.log';
	@unlink($file);

	$logger = new FileLogger($file);
	$logger->log(LogLevel::INFO, 'Test message');

	Assert::true(file_exists($file));
	$content = file_get_contents($file);
	Assert::contains('Test message', $content);

	@unlink($file);
});

// Test log appends to file
Toolkit::test(function (): void {
	$file = Environment::getTestDir() . '/append.log';
	@unlink($file);

	$logger = new FileLogger($file);
	$logger->log(LogLevel::INFO, 'First');
	$logger->log(LogLevel::INFO, 'Second');

	$content = file_get_contents($file);
	Assert::contains('First', $content);
	Assert::contains('Second', $content);

	@unlink($file);
});

// Test log includes context as JSON
Toolkit::test(function (): void {
	$file = Environment::getTestDir() . '/context.log';
	@unlink($file);

	$logger = new FileLogger($file);
	$logger->log(LogLevel::INFO, 'Query', ['sql' => 'SELECT 1']);

	$content = file_get_contents($file);
	Assert::contains('"sql":"SELECT 1"', $content);

	@unlink($file);
});

// Test log includes timestamp
Toolkit::test(function (): void {
	$file = Environment::getTestDir() . '/timestamp.log';
	@unlink($file);

	$logger = new FileLogger($file);
	$logger->log(LogLevel::INFO, 'Test');

	$content = file_get_contents($file);
	$today = date('d.m.Y');
	Assert::contains($today, $content);

	@unlink($file);
});

// Test log with empty context
Toolkit::test(function (): void {
	$file = Environment::getTestDir() . '/empty-context.log';
	@unlink($file);

	$logger = new FileLogger($file);
	$logger->log(LogLevel::INFO, 'Test');

	$content = file_get_contents($file);
	// Empty context array [] is JSON-encoded as [] and wrapped in {}
	Assert::contains('{[]}', $content);

	@unlink($file);
});

// Test multiple log entries create multiple lines
Toolkit::test(function (): void {
	$file = Environment::getTestDir() . '/multiple.log';
	@unlink($file);

	$logger = new FileLogger($file);
	$logger->info('Line 1');
	$logger->info('Line 2');
	$logger->info('Line 3');

	$content = file_get_contents($file);
	$lines = array_filter(explode("\n", $content));

	Assert::count(3, $lines);

	@unlink($file);
});
