<?php declare(strict_types = 1);

namespace Tests\Cases\Middleware;

use Contributte\Tester\Toolkit;
use Doctrine\DBAL\Logging\Driver as LoggingDriver;
use Nettrine\DBAL\Logger\SnapshotLogger;
use Nettrine\DBAL\Middleware\TracyMiddleware;
use Tester\Assert;
use Tests\Mocks\Driver\MockDriver;

require_once __DIR__ . '/../../bootstrap.php';

// Test constructor creates SnapshotLogger
Toolkit::test(function (): void {
	$middleware = new TracyMiddleware();

	Assert::type(SnapshotLogger::class, $middleware->getLogger());
});

// Test wrap returns LoggingDriver
Toolkit::test(function (): void {
	$middleware = new TracyMiddleware();
	$mockDriver = new MockDriver();

	$wrappedDriver = $middleware->wrap($mockDriver);

	Assert::type(LoggingDriver::class, $wrappedDriver);
});

// Test getLogger returns same instance
Toolkit::test(function (): void {
	$middleware = new TracyMiddleware();

	$logger1 = $middleware->getLogger();
	$logger2 = $middleware->getLogger();

	Assert::same($logger1, $logger2);
});

// Test multiple wrap calls use same logger
Toolkit::test(function (): void {
	$middleware = new TracyMiddleware();
	$mockDriver = new MockDriver();

	$middleware->wrap($mockDriver);
	$logger1 = $middleware->getLogger();

	$middleware->wrap($mockDriver);
	$logger2 = $middleware->getLogger();

	Assert::same($logger1, $logger2);
});
