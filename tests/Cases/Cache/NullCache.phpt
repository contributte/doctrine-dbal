<?php declare(strict_types = 1);

namespace Tests\Cases\Cache;

use Contributte\Tester\Toolkit;
use Nettrine\DBAL\Cache\NullCache;
use Nettrine\DBAL\Cache\NullCacheItem;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Test getItem returns NullCacheItem
Toolkit::test(function (): void {
	$cache = new NullCache();
	$item = $cache->getItem('test-key');

	Assert::type(NullCacheItem::class, $item);
	Assert::equal('test-key', $item->getKey());
});

// Test getItems returns empty array
Toolkit::test(function (): void {
	$cache = new NullCache();
	$items = $cache->getItems(['key1', 'key2']);

	Assert::equal([], iterator_to_array($items));
});

// Test hasItem always returns false
Toolkit::test(function (): void {
	$cache = new NullCache();

	Assert::false($cache->hasItem('any-key'));
});

// Test clear returns true
Toolkit::test(function (): void {
	$cache = new NullCache();

	Assert::true($cache->clear());
	Assert::true($cache->clear('prefix'));
});

// Test deleteItem returns true
Toolkit::test(function (): void {
	$cache = new NullCache();

	Assert::true($cache->deleteItem('key'));
});

// Test deleteItems returns true
Toolkit::test(function (): void {
	$cache = new NullCache();

	Assert::true($cache->deleteItems(['key1', 'key2']));
});

// Test save returns true
Toolkit::test(function (): void {
	$cache = new NullCache();
	$item = new NullCacheItem('key', 'value');

	Assert::true($cache->save($item));
});

// Test saveDeferred returns true
Toolkit::test(function (): void {
	$cache = new NullCache();
	$item = new NullCacheItem('key', 'value');

	Assert::true($cache->saveDeferred($item));
});

// Test commit returns true
Toolkit::test(function (): void {
	$cache = new NullCache();

	Assert::true($cache->commit());
});

// Test delete returns true
Toolkit::test(function (): void {
	$cache = new NullCache();

	Assert::true($cache->delete('key'));
});
