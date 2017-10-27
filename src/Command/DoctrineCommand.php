<?php

namespace Nettrine\DBAL\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;

class DoctrineCommand extends Command
{

	/** @var Connection */
	private $connection;

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		parent::__construct();
		$this->connection = $connection;
	}

	/**
	 * @param string|NULL $name
	 * @return Connection
	 */
	public function getDoctrineConnection($name)
	{
		return $this->connection;
	}

}
