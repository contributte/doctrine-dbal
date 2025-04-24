<?php declare(strict_types = 1);

namespace Nettrine\DBAL;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * @see https://github.com/doctrine/DoctrineBundle
 * @phpstan-import-type Params from DriverManager
 */
class ConnectionFactory
{

	/** @var array<string, class-string<Type>> */
	private array $typesConfig = [];

	/** @var array<string, string> */
	private array $typesMapping = [];

	private bool $initialized = false;

	/**
	 * @param array<string, class-string<Type>> $typesConfig
	 * @param array<string, string> $typesMapping
	 */
	public function __construct(array $typesConfig = [], array $typesMapping = [])
	{
		$this->typesConfig = $typesConfig;
		$this->typesMapping = $typesMapping;
	}

	/**
	 * @phpstan-param Params $params
	 * @param array<string, string> $typesMapping
	 */
	public function createConnection(
		array $params,
		?Configuration $config = null,
		array $typesMapping = []
	): Connection
	{
		if (!$this->initialized) {
			$this->initializeTypes();
		}

		$config ??= new Configuration();
		$connection = DriverManager::getConnection($params, $config);
		$platform = $this->getDatabasePlatform($connection);

		// Register types mapping (global)
		foreach ($this->typesMapping as $dbType => $doctrineType) {
			$platform->registerDoctrineTypeMapping($dbType, $doctrineType);
		}

		// Register types mapping (local)
		foreach ($typesMapping as $dbType => $doctrineType) {
			$platform->registerDoctrineTypeMapping($dbType, $doctrineType);
		}

		return $connection;
	}

	private function getDatabasePlatform(Connection $connection): AbstractPlatform
	{
		return $connection->getDatabasePlatform();
	}

	private function initializeTypes(): void
	{
		foreach ($this->typesConfig as $type => $class) {
			if (Type::hasType($type)) {
				Type::overrideType($type, $class);
			} else {
				Type::addType($type, $class);
			}
		}

		$this->initialized = true;
	}

}
