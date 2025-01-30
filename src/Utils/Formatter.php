<?php

namespace Nettrine\DBAL\Utils;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ExpandArrayParameters;
use Doctrine\DBAL\Types\Type;

class Formatter
{

	public function formatSql(Connection $connection, string $sql, array $type, ?array $params = null): string
	{
		if ($params) {
			[$sql, $params, $types] = self::expandListParameters($connection, $sql, $params, $types ?? []);

			// Escape % before vsprintf (example: LIKE '%ant%')
			$sql = str_replace(['%', '?'], ['%%', '%s'], $sql);

			$query = vsprintf(
				$sql,
				call_user_func(function () use ($connection, $params, $types) {
					$quotedParams = [];
					foreach ($params as $typeIndex => $value) {
						$type = $types[$typeIndex] ?? null;
						$quotedParams[] = $value === null ? $value : $connection->quote($value, $type);
					}

					return $quotedParams;
				})
			);
		} else {
			$query = $sql;
		}

		return $query;
	}

	/**
	 * @param array<int, mixed>|array<string, mixed> $params
	 * @param array<int,Type|int|string|null>|array<string,Type|int|string|null> $types
	 * @return array{string, array<int|string, mixed>, array<int|string,Type|int|string|null>}
	 */
	private function expandListParameters(Connection $connection, string $query, array $params, array $types): array
	{
		if (!self::needsArrayParameterConversion($params, $types)) {
			return [$query, $params, $types];
		}

		$parser = $connection->getDatabasePlatform()->createSQLParser();
		$visitor = new ExpandArrayParameters($params, $types);
		$parser->parse($query, $visitor);

		return [
			$visitor->getSQL(),
			$visitor->getParameters(),
			$visitor->getTypes(),
		];
	}

	/**
	 * @param array<int, mixed>|array<string, mixed>                               $params
	 * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types
	 */
	private static function needsArrayParameterConversion(array $params, array $types): bool
	{
		if (is_string(key($params))) {
			return true;
		}

		foreach ($types as $type) {
			if (
				$type === ArrayParameterType::INTEGER
				|| $type === ArrayParameterType::STRING
				|| $type === ArrayParameterType::ASCII
			) {
				return true;
			}
		}

		return false;
	}

}
