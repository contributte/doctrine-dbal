<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware\Debug;

/**
 * @see https://github.com/symfony/doctrine-bridge
 * @internal
 */
class DebugStack
{

	/** @var array<string, array<int, array{sql: string, params: mixed[], types: mixed[], duration: callable|float }>> */
	private array $data = [];

	public function addQuery(string $connectionName, DebugQuery $query): void
	{
		$this->data[$connectionName][] = [
			'sql' => $query->getSql(),
			'params' => $query->getParams(),
			'types' => $query->getTypes(),
			'duration' => $query->getDuration(...), // stop() may not be called at this point
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

	public function reset(): void
	{
		$this->data = [];
	}

}
