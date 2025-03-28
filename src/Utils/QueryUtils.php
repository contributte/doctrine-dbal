<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Utils;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ExpandArrayParameters;
use Doctrine\DBAL\SQL\Parser;

/** @phpstan-import-type WrapperParameterTypeArray from Connection */
final class QueryUtils
{

	/**
	 * Highlight given SQL parts
	 */
	public static function highlight(string $sql): string
	{
		static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|SHOW|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE|START\s+TRANSACTION|COMMIT|ROLLBACK|(?:RELEASE\s+|ROLLBACK\s+TO\s+)?SAVEPOINT';
		static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|[RI]?LIKE|REGEXP|TRUE|FALSE';
		$sql = ' ' . $sql . ' ';
		$sql = htmlspecialchars($sql, ENT_IGNORE, 'UTF-8');
		$sql = preg_replace_callback(sprintf('#(/\\*.+?\\*/)|(?<=[\\s,(])(%s)(?=[\\s,)])|(?<=[\\s,(=])(%s)(?=[\\s,)=])#is', $keywords1, $keywords2), function ($matches) {
			if (isset($matches[1]) && $matches[1] !== '') { // comment
				return '<em style="color:gray">' . $matches[1] . '</em>';
			}

			if (isset($matches[2]) && $matches[2] !== '') { // most important keywords
				return '<strong style="color:#2D44AD">' . $matches[2] . '</strong>';
			}

			if (isset($matches[3]) && $matches[3] !== '') { // other keywords
				return '<strong>' . $matches[3] . '</strong>';
			}
		}, $sql);

		return trim((string) $sql);
	}

	/**
	 * @param string[] $sourcePaths
	 * @param array<array{file: ?string, line: ?string}> $backtrace
	 * @return mixed[]
	 */
	public static function getSource(array $sourcePaths, array $backtrace): array
	{
		$result = [];
		if (count($sourcePaths) === 0) {
			return $result;
		}

		foreach ($backtrace as $i) {
			if (!isset($i['file'], $i['line'])) {
				continue;
			}

			foreach ($sourcePaths as $path) {
				$path = realpath($path);
				assert(is_string($path));

				if (str_contains($i['file'], $path)) {
					$result[] = $i;
				}
			}
		}

		return $result;
	}

	/**
	 * @see https://github.com/doctrine/dbal/blob/4.2.x/src/Connection.php#L1379
	 * @param array<int, mixed>|array<string, mixed> $params
	 * @phpstan-param WrapperParameterTypeArray $types
	 * @return array{0: string, 1:array<int, string>, 2: array<int,string>}
	 */
	public static function expand(string $sql, array $params, array $types): array
	{
		$needsConversion = false;
		$nonArrayTypes = [];

		if (is_string(key($params))) {
			$needsConversion = true;
		} else {
			foreach ($types as $key => $type) {
				if ($type instanceof ArrayParameterType) {
					$needsConversion = true;
					break;
				}

				$nonArrayTypes[$key] = $type;
			}
		}

		if (!$needsConversion) {
			return [$sql, $params, $nonArrayTypes]; // @phpstan-ignore-line
		}

		$parser = new Parser(false);
		$visitor = new ExpandArrayParameters($params, $types);

		$parser->parse($sql, $visitor);

		// @phpstan-ignore-next-line
		return [
			$visitor->getSQL(),
			$visitor->getParameters(),
			$visitor->getTypes(),
		];
	}

	/**
	 * @see https://github.com/doctrine/dbal/blob/4.2.x/src/Connection.php#L1379
	 * @param array<int, mixed>|array<string, mixed> $params
	 */
	public static function expandSql(string $sql, array $params): string
	{
		// Escape % before vsprintf (example: LIKE '%ant%')
		$query = str_replace(['%', '?'], ['%%', '%s'], $sql);

		// Expand SQL ? parameters
		$query = vsprintf($query, $params); // @phpstan-ignore-line

		return $query;
	}

}
