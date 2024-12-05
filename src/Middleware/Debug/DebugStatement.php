<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware\Debug;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

/**
 * @see https://github.com/symfony/doctrine-bridge
 * @internal
 */
final class DebugStatement extends AbstractStatementMiddleware
{

	private DebugQuery $query;

	public function __construct(
		StatementInterface $statement,
		private DebugStack $stack,
		private readonly string $connectionName,
		string $sql,
	)
	{
		parent::__construct($statement);

		$this->query = new DebugQuery($sql);
	}

	public function bindValue(int|string $param, mixed $value, ParameterType $type): void
	{
		$this->query->setValue($param, $value, $type);

		parent::bindValue($param, $value, $type);
	}

	public function execute(): ResultInterface
	{
		// clone to prevent variables by reference to change
		$this->stack->addQuery($this->connectionName, $query = clone $this->query);
		$query->start();

		try {
			return parent::execute();
		} finally {
			$query->stop();
		}
	}

}
