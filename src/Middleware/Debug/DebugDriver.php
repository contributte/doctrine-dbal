<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware\Debug;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * @see https://github.com/symfony/doctrine-bridge
 * @internal
 */
final class DebugDriver extends AbstractDriverMiddleware
{

	public function __construct(
		DriverInterface $driver,
		private DebugStack $stack,
		private string $connectionName,
	)
	{
		parent::__construct($driver);
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect(array $params): ConnectionInterface
	{
		$connection = parent::connect($params);

		return new DebugConnection(
			$connection,
			$this->stack,
			$this->connectionName
		);
	}

}
