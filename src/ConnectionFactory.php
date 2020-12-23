<?php declare(strict_types = 1);

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 */

namespace Nettrine\DBAL;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Connection
 */
class ConnectionFactory
{

	/** @var mixed[] */
	private $typesConfig = [];

	/** @var mixed[] */
	private $typesMapping = [];

	/** @var mixed[] */
	private $commentedTypes = [];

	/** @var bool */
	private $initialized = false;

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
	 * Create a connection by name.
	 *
	 * @param mixed[] $params
	 */
	public function createConnection(
		array $params,
		?Configuration $config = null,
		?EventManager $eventManager = null
	): Connection
	{
		if (!$this->initialized) {
			$this->initializeTypes();
		}

		$connection = DriverManager::getConnection($params, $config, $eventManager);

		if (!empty($this->typesMapping)) {
			$platform = $this->getDatabasePlatform($connection);
			foreach ($this->typesMapping as $dbType => $doctrineType) {
				$platform->registerDoctrineTypeMapping((string) $dbType, $doctrineType);
			}
		}

		if (!empty($this->commentedTypes)) {
			$platform = $this->getDatabasePlatform($connection);
			foreach ($this->commentedTypes as $type) {
				$platform->markDoctrineTypeCommented(Type::getType($type));
			}
		}

		return $connection;
	}

	/**
	 * Try to get the database platform.
	 *
	 * This could fail if types should be registered to an predefined/unused connection
	 * and the platform version is unknown.
	 * For details have a look at DoctrineBundle issue #673.
	 *
	 * @throws Exception
	 */
	private function getDatabasePlatform(Connection $connection): AbstractPlatform
	{
		try {
			return $connection->getDatabasePlatform();
		} catch (Exception $driverException) {
			if ($driverException instanceof DriverException) {
				throw new Exception(
					'An exception occurred while establishing a connection to figure out your platform version.' . PHP_EOL .
					'You can circumvent this by setting a \'server_version\' configuration value' . PHP_EOL . PHP_EOL .
					'For further information have a look at:' . PHP_EOL .
					'https://github.com/doctrine/DoctrineBundle/issues/673',
					0,
					$driverException
				);
			}

			throw $driverException;
		}
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
