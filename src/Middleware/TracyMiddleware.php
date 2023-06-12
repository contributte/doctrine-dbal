<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Logging\Driver as LoggingDriver;
use Nettrine\DBAL\Logger\SnapshotLogger;

class TracyMiddleware implements Middleware
{

	protected SnapshotLogger $logger;

	public function __construct()
	{
		$this->logger = new SnapshotLogger();
	}

	public function wrap(Driver $driver): Driver
	{
		return new LoggingDriver($driver, $this->logger);
	}

	public function getLogger(): SnapshotLogger
	{
		return $this->logger;
	}

}
