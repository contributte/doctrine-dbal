<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Cache;

use DateInterval;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

final class NullCacheItem implements CacheItemInterface
{

	public function __construct(
		private string $key,
		private mixed $value = null,
	)
	{
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function get(): mixed
	{
		return $this->value;
	}

	public function isHit(): bool
	{
		return false;
	}

	public function set(mixed $value): static
	{
		return $this;
	}

	public function expiresAt(DateTimeInterface|null $expiration): static
	{
		return $this;
	}

	public function expiresAfter(DateInterval|int|null $time): static
	{
		return $this;
	}

}
