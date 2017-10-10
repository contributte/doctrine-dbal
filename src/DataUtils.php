<?php

namespace App\Model\Database;

use Doctrine\DBAL\Driver\Statement;

final class DataUtils
{

	/**
	 * @param Statement $statement
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public static function toPairs(Statement $statement, $key = NULL, $value = NULL): array
	{
		$rows = $statement->fetchAll();

		if (!$rows) {
			return [];
		}

		$keys = array_keys((array) reset($rows));
		if (!count($keys)) {
			throw new \LogicException('Result set does not contain any column.');

		} elseif ($key === NULL && $value === NULL) {
			if (count($keys) === 1) {
				list($value) = $keys;
			} else {
				list($key, $value) = $keys;
			}
		}

		$return = [];
		if ($key === NULL) {
			foreach ($rows as $row) {
				$return[] = ($value === NULL ? $row : $row[$value]);
			}
		} else {
			foreach ($rows as $row) {
				$return[is_object($row[$key]) ? (string) $row[$key] : $row[$key]] = ($value === NULL ? $row : $row[$value]);
			}
		}

		return $return;
	}

}
