<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Tracy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryException;
use Nettrine\DBAL\Middleware\Debug\DebugStack;
use Nettrine\DBAL\Utils\QueryUtils;
use PDO;
use PDOException;
use Throwable;
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Helpers;
use Tracy\IBarPanel;

class ConnectionPanel implements IBarPanel
{

	protected DebugStack $stack;

	protected Connection $connection;

	protected string $connectionName;

	private function __construct(DebugStack $stack, Connection $connection, string $connectionName)
	{
		$this->stack = $stack;
		$this->connection = $connection;
		$this->connectionName = $connectionName;
	}

	public static function initialize(
		DebugStack $stack,
		Connection $connection,
		string $connectionName,
		?Bar $bar = null,
		?BlueScreen $blueScreen = null,
	): self
	{
		$blueScreen ??= Debugger::getBlueScreen();
		$blueScreen->addPanel(self::renderException(...));

		$panel = new self($stack, $connection, $connectionName);
		$bar ??= Debugger::getBar();
		$bar->addPanel($panel);

		return $panel;
	}

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

	public function getTab(): string
	{
		// phpcs:disable
		return Helpers::capture(function (): void {
			$queries = $this->getQueries();
			$queriesNum = count($queries);
			$totalTime = 0;

			foreach ($queries as $query) {
				$totalTime += $query['duration'];
			}

			require __DIR__ . '/templates/tab.phtml';
		});
		// phpcs:enable
	}

	public function getPanel(): string
	{
		// phpcs:disable
		return Helpers::capture(function (): void {
			$connection = $this->connection;
			$connectionName = $this->connectionName;
			$connectionParams = $connection->getParams();
			$connectionParams['password'] = '****';

			$queries = $this->getQueries();
			$queriesNum = count($queries);
			$totalTime = 0;

			foreach ($queries as $query) {
				$totalTime += $query['duration'];
			}

			require __DIR__ . '/templates/panel.phtml';
		});
		// phpcs:enable
	}

	/**
	 * @return array<int, array{sql2: string, sql: string, params: mixed[], types: mixed[], duration: float, source: array<mixed> }>
	 */
	private function getQueries(): array
	{
		$queries = $this->stack->getDataBy($this->connectionName);

		$output = [];
		foreach ($queries as $idx => $query) {
			[$sql, $params, $types] = QueryUtils::expand($query['sql'], $query['params'], $query['types']); // @phpstan-ignore-line

			$query['sql2'] = QueryUtils::expandSql($sql, $params);
			$query['params'] = $params;
			$query['types'] = $types;

			$output[$idx] = $query;
		}

		return $output;
	}

}
