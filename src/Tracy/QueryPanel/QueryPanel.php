<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Tracy\QueryPanel;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\SQLParserUtils;
use Doctrine\DBAL\SQLParserUtilsException;
use Nettrine\DBAL\Logger\AbstractLogger;
use Tracy\IBarPanel;

class QueryPanel extends AbstractLogger implements IBarPanel
{

	/** @var int */
	public static $maxLength = 1000;

	/** @var Connection */
	protected $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @param mixed $sql
	 * @param mixed[] $params
	 * @param mixed[] $types
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null): void
	{
		if ($params && $this->connection) {
			try {
				list($sql, $params, $types) = SQLParserUtils::expandListParameters($sql, $params ?: [], $types ?: []);
			} catch (SQLParserUtilsException $e) {
				// Do nothing
			}

			$query = vsprintf(str_replace('?', '%s', $sql), call_user_func(function () use ($params, $types) {
				$quotedParams = [];
				foreach ($params as $typeIndex => $value) {
					$type = $types[$typeIndex] ?? null;
					$quotedParams[] = $this->connection->quote($value, $type);
				}

				return $quotedParams;
			}));
		} else {
			$query = $sql;
		}

		parent::startQuery($query, $params, $types);
	}

	/**
	 * HTML for tab
	 */
	public function getTab(): string
	{
		$totalTime = 0;
		$count = count($this->queries);
		foreach ($this->queries as $event) {
			$totalTime += $event->duration;
		}

		return '<span title="dbal"><span class="tracy-label"><svg viewBox="0 -1.773 37.176 34.395"><path fill="#9E9F9F" d="M18.588-1.773c7.577,0,13.984,3.128,14.201,6.873c-0.217,3.744-6.624,6.874-14.201,6.874	c-7.577,0-13.984-3.128-14.201-6.874C4.604,1.356,11.011-1.773,18.588-1.773z"/><path fill="#9E9F9F" d="M32.809,25.555c0,3.831-6.512,7.067-14.221,7.067S4.367,29.385,4.367,25.555v-3.379c2.435,2.923,7.82,4.912,14.221,4.912s11.786-1.989,14.221-4.912V25.555L32.809,25.555z"/><path fill="#9E9F9F" d="M32.809,18.624c0,3.831-6.512,7.067-14.221,7.067S4.367,22.455,4.367,18.624v-3.52c2.435,2.923,7.82,4.91,14.221,4.91s11.786-1.989,14.221-4.91V18.624L32.809,18.624z"/><path fill="#9E9F9F" d="M32.809,11.552c0,3.831-6.512,7.067-14.221,7.067S4.367,15.382,4.367,11.552V8.459c2.435,2.923,7.82,4.91,14.221,4.91s11.786-1.988,14.221-4.91V11.552z"/></svg>&nbsp;'
			. $count . ' queries'
			. ($totalTime ? ' / ' . number_format($totalTime * 1000, 1, '.', ' ') . ' ms' : '')
			. '</span></span>';
	}


	/**
	 * HTML for panel
	 */
	public function getPanel(): ?string
	{
		ob_start();
		$parameters = $this->connection->getParams();
		$parameters['password'] = '****';
		$connected = $this->connection->isConnected();
		$queries = $this->queries;
		require __DIR__ . '/templates/panel.phtml';

		return ob_get_clean();
	}

}
