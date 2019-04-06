<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\SQLParserUtils;
use Doctrine\DBAL\SQLParserUtilsException;

class ProfilerLogger extends AbstractLogger
{

	/** @var Connection */
	protected $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * @return mixed[]
	 */
	public function getQueries(): array
	{
		return $this->queries;
	}

	public function getTotalTime(): float
	{
		return $this->totalTime;
	}

	/**
	 * @param mixed $sql
	 * @param mixed[] $params
	 * @param mixed[] $types
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null): void
	{
		if ($params) {
			try {
				/** @var string $sql */
				[$sql, $params, $types] = SQLParserUtils::expandListParameters($sql, $params ?? [], $types ?? []);
			} catch (SQLParserUtilsException $e) {
				// Do nothing
			}

			// Escape % before vsprintf (example: LIKE '%ant%')
			$sql = str_replace(['%', '?'], ['%%', '%s'], $sql);

			$query = vsprintf(
				$sql,
				call_user_func(function () use ($params, $types) {
					$quotedParams = [];
					foreach ($params as $typeIndex => $value) {
						$type = $types[$typeIndex] ?? null;
						$quotedParams[] = $this->connection->quote($value, $type);
					}

					return $quotedParams;
				})
			);
		} else {
			$query = $sql;
		}

		parent::startQuery($query, $params, $types);
	}

}
