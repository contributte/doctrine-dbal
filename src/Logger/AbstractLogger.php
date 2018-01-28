<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use stdClass;

abstract class AbstractLogger implements SQLLogger
{

	/** @var array */
	protected $queries = [];

	/**
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 * @return void
	 */
	public function startQuery($sql, array $params = NULL, array $types = NULL)
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
	public function stopQuery()
	{
		// Find latest query
		$keys = array_keys($this->queries);
		$key = end($keys);
		$query = $this->queries[$key];

		// Update duration
		$query->end = microtime(TRUE);
		$query->duration = $query->end - $query->start;
		$query->ms = sprintf('%0.1f', $query->duration * 1000);

		return $query;
	}

}
