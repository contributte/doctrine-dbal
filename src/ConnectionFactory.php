<?php declare(strict_types = 1);

namespace Nettrine\DBAL;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;

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
	 * @param mixed[] $params
	 */
	public function createConnection(array $params, ?Configuration $config = null, ?EventManager $em = null): Connection
	{
		if (!$this->initialized) {
			$this->initializeTypes();
		}

		/** @phpstan-ignore-next-line */
		$connection = DriverManager::getConnection($params, $config, $em);
		$platform = $connection->getDatabasePlatform();

		foreach ($this->typesMapping as $dbType => $doctrineType) {
			$platform->registerDoctrineTypeMapping($dbType, $doctrineType);
		}

		return $connection;
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
