<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware\Debug;

use Doctrine\DBAL\ParameterType;

/**
 * @see https://github.com/symfony/doctrine-bridge
 * @internal
 */
class DebugQuery
{

	/** @var array<int|string, mixed> */
	private array $params = [];

	/** @var array<ParameterType|int> */
	private array $types = [];

	private ?float $start = null;

	private ?float $duration = null;

	private ?array $sourcePaths = [];

	public function __construct(
		private readonly string $sql,
	)
	{
	}

	public function start(): void
	{
		$this->start = microtime(true);
	}

	public function stop(): void
	{
		if ($this->start !== null) {
			$this->duration = microtime(true) - $this->start;
		}
	}

	public function setValue(string|int $param, mixed $value, ParameterType|int $type): void
	{
		// Numeric indexes start at 0 in profiler
		$idx = is_int($param) ? $param - 1 : $param;

		$this->params[$idx] = $value;
		$this->types[$idx] = $type;
	}

	public function getSql(): string
	{
		return $this->sql;
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * @return array<int, int|ParameterType>
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	/**
	 * Query duration in seconds.
	 */
	public function getDuration(): ?float
	{
		return $this->duration;
	}

	public function getSource()
	{
		$result = [];
		/*if (count($this->sourcePaths) === 0) {
			return $result;
		}*/
		bdump($this->sourcePaths);

		foreach (debug_backtrace() as $i) {
			if (!isset($i['file'], $i['line'])) {
				continue;
			}

			//foreach ($this->sourcePaths as $path) {
				//if (str_contains($i['file'], $path)) {
					$result[] = $i;
				//}
			//}
		}

		return $result;
	}

	public function __clone()
	{
		$copy = [];

		foreach ($this->params as $param => $valueOrVariable) {
			$copy[$param] = $valueOrVariable;
		}

		$this->params = $copy;
	}

}
