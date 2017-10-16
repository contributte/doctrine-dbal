<?php

namespace Nettrine\DBAL\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class AbstractRepository
{

	/** @var Connection */
	protected $connection;

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @return QueryBuilder
	 */
	protected function createQueryBuilder()
	{
		return $this->connection->createQueryBuilder();
	}

}
