<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use Nette\Utils\Strings;
use Nettrine\DBAL\Exceptions\Runtime\InvalidStateException;
use stdClass;

abstract class AbstractLogger implements SQLLogger
{

	/** @var mixed[] */
	protected $queries = [];

	/** @var float */
	protected $totalTime = 0;

	/** @var string[] */
	private $sourcePaths = [];

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
			'source' => $this->getSource(),
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

	public function getTotalTime(): float
	{
		return $this->totalTime;
	}

	public function addPath(string $path): void
	{
		$p = realpath($path);
		if ($p === false) {
			throw new InvalidStateException(sprintf('Path %s does not exist', $path));
		}

		$this->sourcePaths[] = $p;
	}

	/**
	 * @return mixed[]
	 */
	protected function getSource(): array
	{
		$result = [];
		if (count($this->sourcePaths) === 0) {
			return $result;
		}

		foreach (debug_backtrace() as $i) {
			if (!isset($i['file'], $i['line'])) {
				continue;
			}

			foreach ($this->sourcePaths as $path) {
				if (Strings::contains($i['file'], $path)) {
					$result[] = $i;
				}
			}
		}

		return $result;
	}

}
