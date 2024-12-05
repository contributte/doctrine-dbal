<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Tracy;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryException;
use Nettrine\DBAL\Middleware\Debug\DebugStack;
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

	protected string $connectionName;

	private function __construct(DebugStack $stack, string $connectionName)
	{
		$this->stack = $stack;
		$this->connectionName = $connectionName;
	}

	public static function initialize(
		DebugStack $stack,
		string $connectionName,
		?Bar $bar = null,
		?BlueScreen $blueScreen = null,
	): self
	{
		$blueScreen ??= Debugger::getBlueScreen();
		$blueScreen->addPanel(self::renderException(...));

		$panel = new self($stack, $connectionName);
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
			$allQueries = $this->stack->getData();
			$queries = $allQueries[$this->connectionName] ?? [];
			$totalTime = 0;
			$count = count($queries);

			foreach ($queries as $query) {
				$totalTime += $query['duration'];
			}

			$iconNoQuery = '<img style="height: 16px; width: auto; display: inline;" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCAyODAuMDI3IDI4MC4wMjciIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDI4MC4wMjcgMjgwLjAyNzsiIHhtbDpzcGFjZT0icHJlc2VydmUiIHdpZHRoPSI1MTJweCIgaGVpZ2h0PSI1MTJweCI+CjxnPgoJPHBhdGggc3R5bGU9ImZpbGw6I0NDRDBEMjsiIGQ9Ik0xNy41MDIsNTIuNTA1djE3NS4wMTdjMCwyOC45ODMsNTQuODUsNTIuNTA1LDEyMi41MTIsNTIuNTA1czEyMi41MTItMjMuNTIyLDEyMi41MTItNTIuNTA1VjUyLjUwNSAgIEgxNy41MDJ6Ii8+Cgk8cGF0aCBzdHlsZT0iZmlsbDojQURCMEIyOyIgZD0iTTIyNy41MjIsMTIyLjUxMmM0LjgzOSwwLDguNzUxLTMuOTEyLDguNzUxLTguNzUxYzAtNC44My0zLjkxMi04Ljc1MS04Ljc1MS04Ljc1MSAgIGMtNC44MzksMC04Ljc1MSwzLjkyLTguNzUxLDguNzUxQzIxOC43NzEsMTE4LjYsMjIyLjY4MiwxMjIuNTEyLDIyNy41MjIsMTIyLjUxMnogTTIyNy41MjIsMTY2LjI2NiAgIGMtNC44MzksMC04Ljc1MSwzLjkyLTguNzUxLDguNzUxYzAsNC44MzksMy45MTIsOC43NTEsOC43NTEsOC43NTFjNC44MzksMCw4Ljc1MS0zLjkxMiw4Ljc1MS04Ljc1MSAgIEMyMzYuMjcyLDE3MC4xODcsMjMyLjM1MywxNjYuMjY2LDIyNy41MjIsMTY2LjI2NnogTTIyNy41MjIsMjI3LjUyMmMtNC44MzksMC04Ljc1MSwzLjkxMi04Ljc1MSw4Ljc1MXMzLjkxMiw4Ljc1MSw4Ljc1MSw4Ljc1MSAgIGM0LjgzOSwwLDguNzUxLTMuOTEyLDguNzUxLTguNzUxUzIzMi4zNTMsMjI3LjUyMiwyMjcuNTIyLDIyNy41MjJ6Ii8+Cgk8cGF0aCBzdHlsZT0iZmlsbDojQjdCQkJEOyIgZD0iTTE0MC4wMTQsMTY2LjI3NWM2Ny42NjIsMCwxMjIuNTEyLTI1LjM0MiwxMjIuNTEyLTU2LjYwOWMwLTEuNTkzLTAuMjM2LTMuMTUtMC41MTYtNC43MTcgICBjLTUuMTk4LDI5LjA1My01Ny43ODIsNTEuODkzLTEyMS45OTYsNTEuODkzUzIzLjIyNSwxMzQuMDExLDE4LjAxOCwxMDQuOTQ5Yy0wLjI4LDEuNTY2LTAuNTE2LDMuMTI0LTAuNTE2LDQuNzE3ICAgQzE3LjUwMiwxNDAuOTI0LDcyLjM0MywxNjYuMjc1LDE0MC4wMTQsMTY2LjI3NXogTTE0MC4wMTQsMjE4LjA5OGMtNjQuMjE0LDAtMTE2Ljc4OS0yMi44MjItMTIxLjk5Ni01MS44OTMgICBjLTAuMjgsMS41NjYtMC41MTYsMy4xMjQtMC41MTYsNC43MTdjMCwzMS4yNDksNTQuODUsNTYuNjA5LDEyMi41MTIsNTYuNjA5czEyMi41MTItMjUuMzQyLDEyMi41MTItNTYuNjA5ICAgYzAtMS42MDEtMC4yMzYtMy4xNS0wLjUxNi00LjcxN0MyNTYuODIsMTk1LjI0OSwyMDQuMjM2LDIxOC4wOTgsMTQwLjAxNCwyMTguMDk4eiIvPgoJPHBhdGggc3R5bGU9ImZpbGw6I0MyQzVDNzsiIGQ9Ik00My43NTQsMjU5LjkzNVY1Mi41MDVIMTcuNTAydjE3NS4wMTdDMTcuNTAyLDIzOS43NTYsMjcuMzU1LDI1MS4wMSw0My43NTQsMjU5LjkzNXoiLz4KCTxnPgoJCTxwYXRoIHN0eWxlPSJmaWxsOiNCMkI1Qjc7IiBkPSJNNDMuNzU0LDIwNS44NjR2LTkuNDg2Yy0xNC4zNjktOC40NjItMjMuNjk3LTE4LjgyMy0yNS43MzYtMzAuMTczICAgIGMtMC4yOCwxLjU2Ni0wLjUxNiwzLjEyNC0wLjUxNiw0LjcxN0MxNy41MDIsMTg0LjEyNywyNy4zNTUsMTk2LjIzOCw0My43NTQsMjA1Ljg2NHogTTQzLjc1NCwxNDQuNjA4di05LjQ3NyAgICBjLTE0LjM2OS04LjQ2Mi0yMy42OTctMTguODMyLTI1LjczNi0zMC4xOWMtMC4yOCwxLjU3NS0wLjUxNiwzLjEzMy0wLjUxNiw0LjcyNUMxNy41MDIsMTIyLjg3MSwyNy4zNTUsMTM0Ljk4Miw0My43NTQsMTQ0LjYwOHoiLz4KCTwvZz4KCTxwYXRoIHN0eWxlPSJmaWxsOiNFNEU3RTc7IiBkPSJNMTQwLjAxNCwwYzY3LjY2MiwwLDEyMi41MTIsMjMuNTE0LDEyMi41MTIsNTIuNTA1cy01NC44NSw1Mi41MDUtMTIyLjUxMiw1Mi41MDUgICBTMTcuNTAyLDgxLjQ5NywxNy41MDIsNTIuNTA1UzcyLjM0MywwLDE0MC4wMTQsMHoiLz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K" />';
			$iconQuery = '<img style="height: 16px; width: auto; display: inline;" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDUzIDUzIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1MyA1MzsiIHhtbDpzcGFjZT0icHJlc2VydmUiIHdpZHRoPSI1MTJweCIgaGVpZ2h0PSI1MTJweCI+CjxwYXRoIHN0eWxlPSJmaWxsOiM0MjRBNjA7IiBkPSJNNTAuNDU1LDhMNTAuNDU1LDhDNDkuNzI0LDMuNTM4LDM5LjI4MSwwLDI2LjUsMFMzLjI3NiwzLjUzOCwyLjU0NSw4bDAsMEgyLjV2MC41VjIwdjAuNVYyMXYxMXYwLjUgIFYzM3YxMmgwLjA0NWMwLjczMSw0LjQ2MSwxMS4xNzUsOCwyMy45NTUsOHMyMy4yMjQtMy41MzksMjMuOTU1LThINTAuNVYzM3YtMC41VjMyVjIxdi0wLjVWMjBWOC41VjhINTAuNDU1eiIvPgo8Zz4KCTxwYXRoIHN0eWxlPSJmaWxsOiM0MjRBNjA7IiBkPSJNMjYuNSw0MWMtMTMuMjU1LDAtMjQtMy44MDYtMjQtOC41VjQ1aDAuMDQ1YzAuNzMxLDQuNDYxLDExLjE3NSw4LDIzLjk1NSw4czIzLjIyNC0zLjUzOSwyMy45NTUtOCAgIEg1MC41VjMyLjVDNTAuNSwzNy4xOTQsMzkuNzU1LDQxLDI2LjUsNDF6Ii8+Cgk8cGF0aCBzdHlsZT0iZmlsbDojNDI0QTYwOyIgZD0iTTIuNSwzMnYwLjVjMC0wLjE2OCwwLjAxOC0wLjMzNCwwLjA0NS0wLjVIMi41eiIvPgoJPHBhdGggc3R5bGU9ImZpbGw6IzQyNEE2MDsiIGQ9Ik01MC40NTUsMzJjMC4wMjcsMC4xNjYsMC4wNDUsMC4zMzIsMC4wNDUsMC41VjMySDUwLjQ1NXoiLz4KPC9nPgo8Zz4KCTxwYXRoIHN0eWxlPSJmaWxsOiNFRkNFNEE7IiBkPSJNMjYuNSwyOWMtMTMuMjU1LDAtMjQtMy44MDYtMjQtOC41VjMzaDAuMDQ1YzAuNzMxLDQuNDYxLDExLjE3NSw4LDIzLjk1NSw4czIzLjIyNC0zLjUzOSwyMy45NTUtOCAgIEg1MC41VjIwLjVDNTAuNSwyNS4xOTQsMzkuNzU1LDI5LDI2LjUsMjl6Ii8+Cgk8cGF0aCBzdHlsZT0iZmlsbDojRUZDRTRBOyIgZD0iTTIuNSwyMHYwLjVjMC0wLjE2OCwwLjAxOC0wLjMzNCwwLjA0NS0wLjVIMi41eiIvPgoJPHBhdGggc3R5bGU9ImZpbGw6I0VGQ0U0QTsiIGQ9Ik01MC40NTUsMjBjMC4wMjcsMC4xNjYsMC4wNDUsMC4zMzIsMC4wNDUsMC41VjIwSDUwLjQ1NXoiLz4KPC9nPgo8ZWxsaXBzZSBzdHlsZT0iZmlsbDojN0ZBQkRBOyIgY3g9IjI2LjUiIGN5PSI4LjUiIHJ4PSIyNCIgcnk9IjguNSIvPgo8Zz4KCTxwYXRoIHN0eWxlPSJmaWxsOiM3MzgzQkY7IiBkPSJNMjYuNSwxN2MtMTMuMjU1LDAtMjQtMy44MDYtMjQtOC41VjIxaDAuMDQ1YzAuNzMxLDQuNDYxLDExLjE3NSw4LDIzLjk1NSw4czIzLjIyNC0zLjUzOSwyMy45NTUtOCAgIEg1MC41VjguNUM1MC41LDEzLjE5NCwzOS43NTUsMTcsMjYuNSwxN3oiLz4KCTxwYXRoIHN0eWxlPSJmaWxsOiM3MzgzQkY7IiBkPSJNMi41LDh2MC41YzAtMC4xNjgsMC4wMTgtMC4zMzQsMC4wNDUtMC41SDIuNXoiLz4KCTxwYXRoIHN0eWxlPSJmaWxsOiM3MzgzQkY7IiBkPSJNNTAuNDU1LDhDNTAuNDgyLDguMTY2LDUwLjUsOC4zMzIsNTAuNSw4LjVWOEg1MC40NTV6Ii8+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPC9zdmc+Cg==" />';

			require __DIR__ . '/templates/tab.phtml';
		});
		// phpcs:enable
	}

	public function getPanel(): string
	{
		// phpcs:disable
		return Helpers::capture(function (): void {
			$allQueries = $this->stack->getData();
			$queries = $allQueries[$this->connectionName] ?? [];
			require __DIR__ . '/templates/panel.phtml';
		});
		// phpcs:enable
	}

}
