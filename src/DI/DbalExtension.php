<?php

namespace Nettrine\DBAL\DI;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Portability\Connection as PortabilityConnection;
use Doctrine\DBAL\Tools\Console\Command\ImportCommand;
use Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Validators;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\Tracy\ConnectionPanel;
use PDO;
use Symfony\Component\Console\Application;

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
			'driver' => 'pdo_mysql',
			'driverClass' => NULL,
			'host' => NULL,
			'dbname' => NULL,
			'servicename' => NULL,
			'user' => NULL,
			'password' => NULL,
			'charset' => 'UTF8',
			'portability' => PortabilityConnection::PORTABILITY_ALL,
			'fetchCase' => PDO::CASE_LOWER,
			'persistent' => TRUE,
			'types' => [],
		],
	];

	/**
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$this->loadDoctrineConfiguration();

		$this->loadConnection();

		$builder->addDefinition($this->prefix('panel'))
			->setFactory(ConnectionPanel::class)
			->setAutowired(FALSE);

		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli')
			return;

		// Helpers
		$builder->addDefinition($this->prefix('connectionHelper'))
			->setClass(ConnectionHelper::class)
			->setAutowired(FALSE);

		//Commands
		$builder->addDefinition($this->prefix('importCommand'))
			->setFactory(ImportCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('reservedWordsCommand'))
			->setFactory(ReservedWordsCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('runSqlCommand'))
			->setFactory(RunSqlCommand::class)
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

		$builder->addDefinition($this->prefix('connectionFactory'))
			->setClass(ConnectionFactory::class, [$config['types']]);

		$builder->addDefinition($this->prefix('connection'))
			->setClass(Connection::class)
			->setFactory('@' . $this->prefix('connectionFactory') . '::createConnection', [
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

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforeCompile()
	{
		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli')
			return;

		$builder = $this->getContainerBuilder();
		$application = $builder->getByType(Application::class, FALSE);
		if (!$application)
			return;
		$application = $builder->getDefinition($application);

		// Register helpers
		$connectionHelper = $this->prefix('@connectionHelper');
		$application->addSetup(new Statement('$service->getHelperSet()->set(?)', [$connectionHelper]));
	}

}
