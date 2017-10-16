<?php

namespace Nettrine\DBAL\DI;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Portability\Connection as PortabilityConnection;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Validators;
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

		$this->loadDoctrineConfiguration();
		$builder->addDefinition($this->prefix('eventManager'))
			->setClass(EventManager::class)
			->setAutowired(FALSE);
		$this->loadConnection();

		$builder->addDefinition($this->prefix('panel'))
			->setFactory(ConnectionPanel::class)
			->setAutowired(FALSE);
	}

	public function loadDoctrineConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass(Configuration::class)
			->setAutowired(FALSE);

		$configurationConfig = $config['configuration'];

		// SqlLogger
		if ($configurationConfig['sqlLogger'] !== NULL) {
			Validators::assert(
				$configurationConfig['sqlLogger'],
				SQLLogger::class,
				'configuration.sqlLogger'
			);
			$configuration->addSetup('setSQLLogger', [$configurationConfig['sqlLogger']]);
		}

		// ResultCacheImpl
		if ($configurationConfig['resultCacheImpl'] !== NULL) {
			Validators::assert(
				$configurationConfig['resultCacheImpl'],
				Cache::class,
				'configuration.resultCacheImpl'
			);
			$configuration->addSetup('setResultCacheImpl', [$configurationConfig['resultCacheImpl']]);
		}

		// FilterSchemaAssetsExpression
		if ($configurationConfig['filterSchemaAssetsExpression'] !== NULL) {
			Validators::assert(
				$configurationConfig['filterSchemaAssetsExpression'],
				'string',
				'configuration.filterSchemaAssetsExpression'
			);
			$configuration->addSetup('setFilterSchemaAssetsExpression', [$configurationConfig['resultCacheImpl']]);
		}

		// AutoCommit
		Validators::assert($configurationConfig['autoCommit'], 'bool', 'configuration.autoCommit');
		$configuration->addSetup('setAutoCommit', [$configurationConfig['autoCommit']]);
	}

	public function loadConnection()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$builder->addDefinition($this->prefix('connection'))
			->setClass(Connection::class)
			->setFactory(DriverManager::class . '::getConnection', [
				$this->config['connection'],
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
