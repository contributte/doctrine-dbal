<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ExpandArrayParameters;
use Doctrine\DBAL\SQLParserUtils;
use Doctrine\DBAL\Types\Type;
use Nettrine\DBAL\ConnectionAccessor;
use Throwable;

class ProfilerLogger extends AbstractLogger
{

	/** @var ConnectionAccessor */
	protected $connectionAccessor;

	public function __construct(ConnectionAccessor $connectionAccessor)
	{
		$this->connectionAccessor = $connectionAccessor;
	}

	public function getConnection(): Connection
	{
		return $this->connectionAccessor->get();
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
			[$sql, $params, $types] = $this->expandListParameters($sql, $params, $types ?? []);

			// Escape % before vsprintf (example: LIKE '%ant%')
			$sql = str_replace(['%', '?'], ['%%', '%s'], $sql);

			$query = vsprintf(
				$sql,
				call_user_func(function () use ($params, $types) {
					$quotedParams = [];
					foreach ($params as $typeIndex => $value) {
						$type = $types[$typeIndex] ?? null;
						$quotedParams[] = $value === null ? $value : $this->getConnection()->quote($value, $type);
					}

					return $quotedParams;
				})
			);
		} else {
			$query = $sql;
		}

		parent::startQuery($query, $params, $types);
	}

	/**
	 * @param array<int, mixed>|array<string, mixed> $params
	 * @param array<int,Type|int|string|null>|array<string,Type|int|string|null> $types
	 * @return array{string, array<int|string, mixed>, array<int|string,Type|int|string|null>}
	 */
	private function expandListParameters(string $query, array $params, array $types): array
	{
		if (class_exists(SQLParserUtils::class)) { // DBAL 2.x compatibility
			try {
				return SQLParserUtils::expandListParameters($query, $params, $types);
			} catch (Throwable $e) {
				return [$query, $params, $types];
			}
		}

		$parser = $this->getConnection()->getDatabasePlatform()->createSQLParser();
		if (is_int(key($params))) { // Positional parameters, checked same as 2.x SQLParserUtils.php:88
			$visitor = new ExpandArrayParameters(array_values($params), array_values($types)); // ExpandArrayParameters has starting index at 0, flushing new entities at 1
		} else {
			$visitor = new ExpandArrayParameters($params, $types);
		}
		$parser->parse($query, $visitor);

		return [
			$visitor->getSQL(),
			$visitor->getParameters(),
			$visitor->getTypes(),
		];
	}

}
