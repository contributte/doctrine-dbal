<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Tracy\BlueScreen;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryException;
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
			if (($e->getPrevious() !== null) && ($item = Helpers::findTrace($e->getTrace(), Exception::class . '::driverExceptionDuringQuery')) !== null) {
				return [
					'tab' => 'SQL',
					'panel' => $item['args'][2],
				];
			}
		} elseif ($e instanceof QueryException) {
			if ((($prev = $e->getPrevious()) !== null) && preg_match('~^(SELECT|INSERT|UPDATE|DELETE)\s+.*~i', $prev->getMessage()) !== false) {
				return [
					'tab' => 'DQL',
					'panel' => $prev->getMessage(),
				];
			}
		} elseif ($e instanceof PDOException) {
			if (($item = Helpers::findTrace($e->getTrace(), Connection::class . '::executeQuery')) !== null) {
				$sql = $item['args'][0];
			} elseif (($item = Helpers::findTrace($e->getTrace(), PDO::class . '::query')) !== null) {
				$sql = $item['args'][0];
			} elseif (($item = Helpers::findTrace($e->getTrace(), PDO::class . '::prepare')) !== null) {
				$sql = $item['args'][0];
			}

			return isset($sql) ? [
				'tab' => 'SQL',
				'panel' => $sql,
			] : null;
		}

		return null;
	}

}
