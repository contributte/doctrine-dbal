<?php

namespace Nettrine\DBAL\DI;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Portability\Connection as PortabilityConnection;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Validators;
use Nettrine\DBAL\Command\CreateDatabaseCommand;
use Nettrine\DBAL\Command\DropDatabaseCommand;
use Nettrine\DBAL\Tracy\ConnectionPanel;
use PDO;

final class DbalExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'configuration' => [
			'sqlLogger' => NULL,
			'resultCacheImpl' => NULL,
			'filterSchemaAssetsExpression' => NULL,
			'autoCommit' => TRUE,
		],
		'connection' => [
			'url' => NULL,
			'driver' => NULL,
			'driverClass' => NULL, //Null or class implement Doctrine\DBAL\Driver
			'host' => NULL,
			'dbname' => NULL,
			'servicename' => NULL,
			'user' => NULL,
			'password' => NULL,
			'charset' => NULL,
			'portability' => PortabilityConnection::PORTABILITY_ALL,
			'fetchCase' => PDO::CASE_LOWER,
			'persistent' => TRUE,
		],
	];

	/**
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		//Commands
		$builder->addDefinition($this->prefix('createDatabaseCommand'))
			->setFactory(CreateDatabaseCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('dropDatabaseCommand'))
			->setFactory(DropDatabaseCommand::class)
			->setAutowired(FALSE);

		$this->loadDoctrineConfiguration();

		$this->loadConnection();

		$builder->addDefinition($this->prefix('panel'))
			->setFactory(ConnectionPanel::class)
			->setAutowired(FALSE);
	}

	/**
	 * @return void
	 */
	public function loadDoctrineConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$this->validateConfig($this->defaults);
		$config = $this->validateConfig($this->defaults['configuration'], $this->config['configuration']);

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass(Configuration::class)
			->setAutowired(FALSE);

		// SqlLogger
		if ($config['sqlLogger'] !== NULL) {
			$configuration->addSetup('setSQLLogger', [$config['sqlLogger']]);
		}

		// ResultCacheImpl
		if ($config['resultCacheImpl'] !== NULL) {
			$configuration->addSetup('setResultCacheImpl', [$config['resultCacheImpl']]);
		}

		// FilterSchemaAssetsExpression
		if ($config['filterSchemaAssetsExpression'] !== NULL) {
			$configuration->addSetup('setFilterSchemaAssetsExpression', [$config['resultCacheImpl']]);
		}

		// AutoCommit
		Validators::assert($config['autoCommit'], 'bool', 'configuration.autoCommit');
		$configuration->addSetup('setAutoCommit', [$config['autoCommit']]);
	}

	/**
	 * @return void
	 */
	public function loadConnection()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults['connection'], $this->config['connection']);

		$builder->addDefinition($this->prefix('eventManager'))
			->setClass(EventManager::class);

		$builder->addDefinition($this->prefix('connection'))
			->setClass(Connection::class)
			->setFactory(DriverManager::class . '::getConnection', [
				$config,
				'@' . $this->prefix('configuration'),
				'@' . $this->prefix('eventManager'),
			]);
	}

	/**
	 * @param ClassType $class
	 * @return void
	 */
	public function afterCompile(ClassType $class)
	{
		//		$config = $this->compiler->getExtension()->getConfig();
		//		if ($config['debug'] !== TRUE)
		//			return;

		$initialize = $class->getMethod('initialize');
		$initialize->addBody(
			'$this->getService(?)->addPanel($this->getService(?));',
			['tracy.bar', $this->prefix('panel')]
		);
	}

}
