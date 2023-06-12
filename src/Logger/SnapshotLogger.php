<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

class SnapshotLogger extends AbstractLogger
{

	/** @var array<int, array{level: mixed, message: string, context: mixed[], timestamp: int}> */
	protected array $snapshots = [];

	/**
	 * @param mixed[] $context
	 */
	public function log(mixed $level, Stringable|string $message, array $context = []): void
	{
		$this->snapshots[] = [
			'level' => $level,
			'message' => (string) $message,
			'context' => $context,
			'timestamp' => time(),
		];
	}

	/**
	 * @return array<int, array{duration:int}>
	 */
	public function getQueries(): array
	{
		return [];
	}

}
