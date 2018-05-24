<?php

declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\LoggerChain;
use Doctrine\DBAL\Portability\Connection as PortabilityConnection;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\Events\ContainerAwareEventManager;
use Nettrine\DBAL\Events\DebugEventManager;
use Nettrine\DBAL\Tracy\BlueScreen\DbalBlueScreen;
use Nettrine\DBAL\Tracy\QueryPanel\QueryPanel;
use PDO;
use ReflectionClass;

final class DbalExtension extends CompilerExtension
{

	public const TAG_NETTRINE_SUBSCRIBER = 'nettrine.subscriber';

	public const DEFAULT_CONNECTION_NAME = 'default';

	/** @var mixed[] */
	private $defaults = [
		'debug' => false,
		'configuration' => [
			'sqlLogger' => null,
			'resultCacheImpl' => null,
			'filterSchemaAssetsExpression' => null,
			'autoCommit' => true,
		],
		'connections' => [],
	];

	/** @var array */
	private $connectionDefaults = [
		'url' => null,
		'pdo' => null,
		'memory' => null,
		'driver' => 'pdo_mysql',
		'driverClass' => null,
		'host' => null,
		'port' => null,
		'dbname' => null,
		'servicename' => null,
		'user' => null,
		'password' => null,
		'charset' => 'UTF8',
		'portability' => PortabilityConnection::PORTABILITY_ALL,
		'fetchCase' => PDO::CASE_LOWER,
		'persistent' => true,
		'types' => [],
		'typesMapping' => [],
	];


	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$this->loadDoctrineConfiguration();
		$this->loadConnectionConfiguration();

		if ($config['debug'] === true) {
			$builder->addDefinition($this->prefix('queryPanel'))
				->setFactory(QueryPanel::class)
				->setAutowired(false);
		}
	}


	/**
	 * Register Doctrine Configuration
	 */
	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults['configuration'], $this->config['configuration']);

		$logger = $builder->addDefinition($this->prefix('logger'))
			->setClass(LoggerChain::class)
			->setAutowired('self');

		$configuration = $builder->addDefinition($this->prefix('configuration'));
		$configuration->setFactory(Configuration::class)
			->setAutowired(false)
			->addSetup('setSQLLogger', [$this->prefix('@logger')]);

		// SqlLogger (append to chain)
		if ($config['sqlLogger'] !== null) {
			$logger->addSetup('addLogger', [$config['sqlLogger']]);
		}

		// ResultCacheImpl
		if ($config['resultCacheImpl'] !== null) {
			$configuration->addSetup('setResultCacheImpl', [$config['resultCacheImpl']]);
		}

		// FilterSchemaAssetsExpression
		if ($config['filterSchemaAssetsExpression'] !== null) {
			$configuration->addSetup('setFilterSchemaAssetsExpression', [$config['filterSchemaAssetsExpression']]);
		}

		// AutoCommit
		Validators::assert($config['autoCommit'], 'bool', 'configuration.autoCommit');
		$configuration->addSetup('setAutoCommit', [$config['autoCommit']]);
	}


	public function loadConnectionConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$globalConfig = $this->validateConfig($this->defaults);


		$connections = [];

		foreach ($this->config['connection'] as $k => $v) {
			$connections[$k] = $this->validateConfig($this->connectionDefaults, $v);
		}


		if (!array_key_exists(self::DEFAULT_CONNECTION_NAME, $connections)) {
			throw new Nette\InvalidStateException('Default connection must be set!');
		}


		foreach ($connections as $name => $connection) {
			$autowired = $name === self::DEFAULT_CONNECTION_NAME ? true : false;

			$builder->addDefinition($this->prefix($name . '.eventManager'))
				->setFactory(ContainerAwareEventManager::class)
				->setAutowired($autowired);

			$builder->addDefinition($this->prefix($name . '.connectionFactory'))
				->setFactory(ConnectionFactory::class, [$connection['types'], $connection['typesMapping']])
				->setAutowired($autowired);

			$builder->addDefinition($this->prefix($name . '.connection'))
				->setFactory(Connection::class)
				->setFactory('@' . $this->prefix($name . '.connectionFactory') . '::createConnection', [
					$connection,
					'@' . $this->prefix('configuration'),
					$builder->getDefinitionByType(EventManager::class),
				])
				->setAutowired($autowired);


			if ($globalConfig['debug'] === true) {
				$builder->getDefinition($this->prefix(self::DEFAULT_CONNECTION_NAME . '.eventManager'))
					->setAutowired(false);

				$builder->addDefinition($this->prefix($name . '.eventManager.debug'))
					->setFactory(DebugEventManager::class, [$this->prefix('@' . $name . '.eventManager')])
					->setAutowired(false);

				if ($name === self::DEFAULT_CONNECTION_NAME) {
					$builder->getDefinition($this->prefix(self::DEFAULT_CONNECTION_NAME . '.eventManager.debug'))
						->setAutowired(true);
				}
			}
		}
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Idea by @enumag
		// https://github.com/Arachne/EventManager/blob/master/src/DI/EventManagerExtension.php

		$eventManager = $builder->getDefinition($this->prefix(self::DEFAULT_CONNECTION_NAME . '.eventManager'));
		foreach ($builder->findByTag(self::TAG_NETTRINE_SUBSCRIBER) as $serviceName => $tag) {
			$class = $builder->getDefinition($serviceName)->getClass();

			if (!is_subclass_of($class, EventSubscriber::class)) {
				throw new AssertionException(
					sprintf(
						'Subscriber "%s" doesn\'t implement "%s".',
						$serviceName,
						EventSubscriber::class
					)
				);
			}
			$eventManager->addSetup(
				'?->addEventListener(?, ?)',
				[
					'@self',
					(new ReflectionClass($class))->newInstanceWithoutConstructor()->getSubscribedEvents(),
					$serviceName, // Intentionally without @ for laziness.
				]
			);
		}
	}


	/**
	 * Update initialize method
	 */
	public function afterCompile(ClassType $class): void
	{
		$config = $this->validateConfig($this->defaults);

		if ($config['debug'] === true) {
			$initialize = $class->getMethod('initialize');
			$initialize->addBody(
				'$this->getService(?)->addPanel($this->getService(?));',
				['tracy.bar', $this->prefix('queryPanel')]
			);
			$initialize->addBody(
				'$this->getService(?)->getConfiguration()->getSqlLogger()->addLogger($this->getService(?));',
				[$this->prefix('connection'), $this->prefix('queryPanel')]
			);
			$initialize->addBody(
				'$this->getService(?)->addPanel(new ?);',
				['tracy.blueScreen', ContainerBuilder::literal(DbalBlueScreen::class)]
			);
		}
	}
}
