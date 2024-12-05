<?php declare(strict_types = 1);

namespace Nettrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\ConnectionProvider as DoctrineConnectionProvider;
use Nette\DI\Container;
use Nettrine\DBAL\Exceptions\LogicalException;

class ConnectionProvider implements DoctrineConnectionProvider
{

	/**
	 * @param array<string, string> $connectionMap
	 */
	public function __construct(
		private Container $container,
		private array $connectionMap
	)
	{
	}

	public function getDefaultConnection(): Connection
	{
		return $this->getConnection('default');
	}

	public function getConnection(string $name): Connection
	{
		$service = $this->connectionMap[$name] ?? null;

		if ($service === null) {
			throw new LogicalException(sprintf('Service for connection "%s" not found', $name));
		}

		return $this->container->getService($service);
	}

}
