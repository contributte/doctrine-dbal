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
	 * @return void
	 */
	public function startQuery($sql, ?array $params = NULL, ?array $types = NULL): void
	{
		$this->queries[] = (object) [
			'sql' => $sql,
			'start' => microtime(TRUE),
			'end' => NULL,
			'duration' => NULL,
			'ms' => NULL,
			'params' => $params,
			'types' => $types,
		];
	}

	/**
	 * @return stdClass
	 */
	public function stopQuery(): stdClass
	{
		// Find latest query
		$keys = array_keys($this->queries);
		$key = end($keys);
		$query = $this->queries[$key];

		// Update duration
		$query->end = microtime(TRUE);
		$query->duration = $query->end - $query->start;
		$query->ms = sprintf('%0.1f', $query->duration * 1000);

		// Update total time
		$this->totalTime += $query->duration;

		return $query;
	}

}
