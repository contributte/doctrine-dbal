<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use stdClass;

abstract class AbstractLogger implements SQLLogger
{

	/** @var mixed[] */
	protected $queries = [];

	/** @var int */
	protected $totalTime = 0;

	/**
	 * @param mixed $sql
	 * @param mixed[] $params
	 * @param mixed[] $types
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null): void
	{
		$this->queries[] = (object) [
			'sql' => $sql,
			'start' => microtime(true),
			'end' => null,
			'duration' => null,
			'ms' => null,
			'params' => $params,
			'types' => $types,
		];
	}

	public function stopQuery(): stdClass
	{
		// Find latest query
		$keys = array_keys($this->queries);
		$key = end($keys);
		$query = $this->queries[$key];

		// Update duration
		$query->end = microtime(true);
		$query->duration = $query->end - $query->start;
		$query->ms = sprintf('%0.1f', $query->duration * 1000);

		// Update total time
		$this->totalTime += $query->duration;

		return $query;
	}

}
