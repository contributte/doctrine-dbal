<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware\Debug;

use Nettrine\DBAL\Utils\QueryUtils;

/**
 * @see https://github.com/symfony/doctrine-bridge
 * @internal
 */
class DebugStack
{

	/** @var array<string, array<int, array{sql: string, params: mixed[], types: mixed[], duration: callable|float }>> */
	private array $data = [];

	public function __construct(
		private array $sourcePaths
	)
	{
	}

	public function addQuery(string $connectionName, DebugQuery $query): void
	{
		$backtrace = debug_backtrace();
		$backtrace = QueryUtils::getSource($this->sourcePaths, $backtrace);
		$this->data[$connectionName][] = [
			'sql' => $query->getSql(),
			'params' => $query->getParams(),
			'types' => $query->getTypes(),
			'duration' => $query->getDuration(...), // stop() may not be called at this point
			'source' => $backtrace,
		];
	}

	/**
	 * @return array<string, array<int, array{sql: string, params: mixed[], types: mixed[], duration: float }>>
	 */
	public function getData(): array
	{
		$data = $this->data;

		foreach ($data as $connectionName => $queries) {
			foreach ($queries as $idx => $query) {
				if (is_callable($query['duration'])) {
					$data[$connectionName][$idx]['duration'] = $query['duration']();
				}
			}
		}

		return $data;
	}

	/**
	 * @return array<int, array{sql: string, params: mixed[], types: mixed[], duration: float }>
	 */
	public function getDataBy(string $connectionName): array
	{
		return $this->getData()[$connectionName] ?? [];
	}

	public function reset(): void
	{
		$this->data = [];
	}

}
