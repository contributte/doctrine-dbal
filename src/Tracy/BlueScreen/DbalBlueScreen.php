<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Tracy\BlueScreen;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryException;
use Nettrine\DBAL\Utils\QueryUtils;
use PDO;
use PDOException;
use Throwable;
use Tracy\Helpers;

/**
 * Inspired by https://github.com/Kdyby/Doctrine/blob/master/src/Kdyby/Doctrine/Diagnostics/Panel.php.
 */
class DbalBlueScreen
{

	/**
	 * @return mixed[]|null
	 */
	public static function renderException(?Throwable $e): ?array
	{
		if ($e === null) {
			return null;
		}

		if ($e instanceof Exception) {
			if (($prev = $e->getPrevious()) && ($item = Helpers::findTrace($e->getTrace(), Exception::class . '::driverExceptionDuringQuery'))) {
				return [
					'tab' => 'SQL',
					'panel' => QueryUtils::highlight($item['args'][2]),
				];
			}
		} elseif ($e instanceof QueryException) {
			if (($prev = $e->getPrevious()) && preg_match('~^(SELECT|INSERT|UPDATE|DELETE)\s+.*~i', $prev->getMessage())) {
				return [
					'tab' => 'DQL',
					'panel' => QueryUtils::highlight($prev->getMessage()),
				];
			}
		} elseif ($e instanceof PDOException) {
			if (isset($e->queryString)) {
				$sql = $e->queryString;
			} elseif ($item = Helpers::findTrace($e->getTrace(), Connection::class . '::executeQuery')) {
				$sql = $item['args'][0];
			} elseif ($item = Helpers::findTrace($e->getTrace(), PDO::class . '::query')) {
				$sql = $item['args'][0];
			} elseif ($item = Helpers::findTrace($e->getTrace(), PDO::class . '::prepare')) {
				$sql = $item['args'][0];
			}

			return isset($sql) ? [
				'tab' => 'SQL',
				'panel' => QueryUtils::highlight($sql),
			] : null;
		}

		return null;
	}

}
