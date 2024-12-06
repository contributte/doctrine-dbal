<?php declare(strict_types = 1);

namespace Tests\Mocks\Driver;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class TestDriver extends AbstractDriverMiddleware
{

	public Connection $connection;

	/**
	 * {@inheritDoc}
	 */
	public function connect(array $params): Connection
	{
		$this->connection = parent::connect($params);

		return $this->connection;
	}

}
