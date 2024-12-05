<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class NullCache implements CacheItemPoolInterface
{

	public function getItem(mixed $key): CacheItemInterface
	{
		return new NullCacheItem($key);
	}

	/**
	 * @param array<string> $keys
	 * @return never[]
	 */
	public function getItems(array $keys = []): iterable
	{
		return [];
	}

	public function hasItem(mixed $key): bool
	{
		return false;
	}

	public function clear(string $prefix = ''): bool
	{
		return true;
	}

	public function deleteItem(mixed $key): bool
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function deleteItems(array $keys): bool
	{
		return true;
	}

	public function save(CacheItemInterface $item): bool
	{
		return true;
	}

	public function saveDeferred(CacheItemInterface $item): bool
	{
		return true;
	}

	public function commit(): bool
	{
		return true;
	}

	public function delete(string $key): bool
	{
		return true;
	}

}
