<?php declare(strict_types = 1);

namespace Tests\Cases\Cache;

use Contributte\Tester\Toolkit;
use DateInterval;
use DateTime;
use Nettrine\DBAL\Cache\NullCacheItem;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Test constructor and getKey
Toolkit::test(function (): void {
	$item = new NullCacheItem('test-key');

	Assert::equal('test-key', $item->getKey());
});

// Test get returns null by default
Toolkit::test(function (): void {
	$item = new NullCacheItem('key');

	Assert::null($item->get());
});

// Test get returns initial value
Toolkit::test(function (): void {
	$item = new NullCacheItem('key', 'initial-value');

	Assert::equal('initial-value', $item->get());
});

// Test isHit always returns false
Toolkit::test(function (): void {
	$item = new NullCacheItem('key', 'value');

	Assert::false($item->isHit());
});

// Test set returns self for fluent interface
Toolkit::test(function (): void {
	$item = new NullCacheItem('key');
	$result = $item->set('new-value');

	Assert::same($item, $result);
});

// Test expiresAt returns self
Toolkit::test(function (): void {
	$item = new NullCacheItem('key');
	$result = $item->expiresAt(new DateTime());

	Assert::same($item, $result);
});

// Test expiresAt with null
Toolkit::test(function (): void {
	$item = new NullCacheItem('key');
	$result = $item->expiresAt(null);

	Assert::same($item, $result);
});

// Test expiresAfter with DateInterval
Toolkit::test(function (): void {
	$item = new NullCacheItem('key');
	$result = $item->expiresAfter(new DateInterval('PT1H'));

	Assert::same($item, $result);
});

// Test expiresAfter with int
Toolkit::test(function (): void {
	$item = new NullCacheItem('key');
	$result = $item->expiresAfter(3600);

	Assert::same($item, $result);
});

// Test expiresAfter with null
Toolkit::test(function (): void {
	$item = new NullCacheItem('key');
	$result = $item->expiresAfter(null);

	Assert::same($item, $result);
});
