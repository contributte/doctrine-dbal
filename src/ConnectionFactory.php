<?php declare(strict_types = 1);

namespace Nettrine\DBAL;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;

class ConnectionFactory
{

	/** @var mixed[] */
	private array $typesConfig = [];

	/** @var mixed[] */
	private array $typesMapping = [];

	/** @var mixed[] */
	private array $commentedTypes = [];

	private bool $initialized = false;

	/**
	 * @param mixed[] $typesConfig
	 * @param mixed[] $typesMapping
	 */
	public function __construct(array $typesConfig = [], array $typesMapping = [])
	{
		$this->typesConfig = $typesConfig;
		$this->typesMapping = $typesMapping;
	}

	/**
	 * @param mixed[] $params
	 */
	public function createConnection(array $params, ?Configuration $config = null): Connection
	{
		if (!$this->initialized) {
			$this->initializeTypes();
		}

		$connection = DriverManager::getConnection($params, $config);
		$platform = $connection->getDatabasePlatform();

		foreach ($this->typesMapping as $dbType => $doctrineType) {
			$platform->registerDoctrineTypeMapping((string) $dbType, $doctrineType);
		}

		foreach ($this->commentedTypes as $type) {
			$platform->markDoctrineTypeCommented(Type::getType($type));
		}

		return $connection;
	}

	private function initializeTypes(): void
	{
		foreach ($this->typesConfig as $type => $typeConfig) {
			if (Type::hasType($type)) {
				Type::overrideType($type, $typeConfig['class']);
			} else {
				Type::addType($type, $typeConfig['class']);
			}

			if ($typeConfig['commented']) {
				$this->commentedTypes[] = $type;
			}
		}

		$this->initialized = true;
	}

}
