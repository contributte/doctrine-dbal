<?php declare(strict_types = 1);

namespace Nettrine\DBAL;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;

class ConnectionFactory
{

	/** @var array<string, array{class: class-string<Type>, commented: bool}> */
	private array $typesConfig = [];

	/** @var array<string, string> */
	private array $typesMapping = [];

	/** @var array<string> */
	private array $commentedTypes = [];

	private bool $initialized = false;

	/**
	 * @param array<string, array{class: class-string<Type>, commented: bool}> $typesConfig
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
