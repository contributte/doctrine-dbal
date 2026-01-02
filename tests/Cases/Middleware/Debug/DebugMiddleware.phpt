<?php declare(strict_types = 1);

namespace Tests\Cases\Middleware\Debug;

use Contributte\Tester\Toolkit;
use Nettrine\DBAL\Middleware\Debug\DebugDriver;
use Nettrine\DBAL\Middleware\Debug\DebugMiddleware;
use Nettrine\DBAL\Middleware\Debug\DebugStack;
use Tester\Assert;
use Tests\Mocks\Driver\MockDriver;

require_once __DIR__ . '/../../../bootstrap.php';

// Test wrap returns DebugDriver
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$middleware = new DebugMiddleware($stack);

	$mockDriver = new MockDriver();
	$wrappedDriver = $middleware->wrap($mockDriver);

	Assert::type(DebugDriver::class, $wrappedDriver);
});

// Test wrap with custom connection name
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$middleware = new DebugMiddleware($stack, 'custom');

	$mockDriver = new MockDriver();
	$wrappedDriver = $middleware->wrap($mockDriver);

	Assert::type(DebugDriver::class, $wrappedDriver);
});

// Test default connection name
Toolkit::test(function (): void {
	$stack = new DebugStack();
	$middleware = new DebugMiddleware($stack);

	$mockDriver = new MockDriver();
	$wrappedDriver = $middleware->wrap($mockDriver);

	Assert::type(DebugDriver::class, $wrappedDriver);
});
