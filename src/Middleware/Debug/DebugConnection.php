<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware\Debug;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;

/**
 * @see https://github.com/symfony/doctrine-bridge
 * @internal
 */
final class DebugConnection extends AbstractConnectionMiddleware
{

	public function __construct(
		ConnectionInterface $connection,
		private DebugStack $stack,
		private string $connectionName,
	)
	{
		parent::__construct($connection);
	}

	public function prepare(string $sql): DebugStatement
	{
		return new DebugStatement(
			parent::prepare($sql),
			$this->stack,
			$this->connectionName,
			$sql
		);
	}

	public function query(string $sql): Result
	{
		$this->stack->addQuery($this->connectionName, $query = new DebugQuery($sql));
		$query->start();

		try {
			return parent::query($sql);
		} finally {
			$query->stop();
		}
	}

	public function exec(string $sql): int|string
	{
		$this->stack->addQuery($this->connectionName, $query = new DebugQuery($sql));
		$query->start();

		try {
			$affectedRows = parent::exec($sql);
		} finally {
			$query->stop();
		}

		return $affectedRows;
	}

	public function beginTransaction(): void
	{
		$this->stack->addQuery($this->connectionName, $query = new DebugQuery('START TRANSACTION'));
		$query->start();

		try {
			parent::beginTransaction();
		} finally {
			$query->stop();
		}
	}

	public function commit(): void
	{
		$this->stack->addQuery($this->connectionName, $query = new DebugQuery('COMMIT'));
		$query->start();

		try {
			parent::commit();
		} finally {
			$query->stop();
		}
	}

	public function rollBack(): void
	{
		$this->stack->addQuery($this->connectionName, $query = new DebugQuery('ROLLBACK'));
		$query->start();

		try {
			parent::rollBack();
		} finally {
			$query->stop();
		}
	}

}
