<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\LoggerChain;
use Doctrine\DBAL\Portability\Connection as PortabilityConnection;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\InvalidArgumentException;
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

	public const TAG_CONNECTION = 'nettrine.connection';

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

	/** @var mixed[] */
	private $connectionDefaults = [
		'url' => null,
		'pdo' => null,
		'memory' => null,
		'driver' => 'pdo_mysql',
		'driverClass' => null,
		'unix_socket' => null,
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
		'wrapperClass' => null,
	];

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		if (!array_key_exists(self::DEFAULT_CONNECTION_NAME, $config['connections'])) {
			throw new InvalidArgumentException('Default connection must be set!');
		}

		$this->loadDoctrineConfiguration();
		$this->loadConnectionConfiguration();

		if ($config['debug'] === true) {
			foreach ($config['connections'] as $name => $connection) {
				$builder->addDefinition($this->prefix($name . '.queryPanel'))
					->setFactory(QueryPanel::class, ['@' . $this->prefix($name . '.connection')])
					->setAutowired(false);
			}
		}
	}

	/**
	 * Register Doctrine Configuration
	 */
	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		foreach ($config['connections'] as $name => $connection) {
			$logger = $builder->addDefinition($this->prefix($name . '.logger'))
				->setType(LoggerChain::class)
				->setAutowired('self');

			$configuration = $builder->addDefinition($this->prefix($name . '.configuration'))
				->setFactory(Configuration::class)
				->setAutowired(false)
				->addSetup('setSQLLogger', [$this->prefix('@' . $name . '.logger')]);

			// SqlLogger (append to chain)
			if ($config['configuration']['sqlLogger'] !== null) {
				$logger->addSetup('addLogger', [$config['configuration']['sqlLogger']]);
			}

			// ResultCacheImpl
			if ($config['configuration']['resultCacheImpl'] !== null) {
				$configuration->addSetup('setResultCacheImpl', [$config['configuration']['resultCacheImpl']]);
			}

			// FilterSchemaAssetsExpression
			if ($config['configuration']['filterSchemaAssetsExpression'] !== null) {
				$configuration->addSetup('setFilterSchemaAssetsExpression', [$config['configuration']['filterSchemaAssetsExpression']]);
			}

			// AutoCommit
			Validators::assert($config['configuration']['autoCommit'], 'bool', 'configuration.autoCommit');
			$configuration->addSetup('setAutoCommit', [$config['configuration']['autoCommit']]);
		}
	}

	public function loadConnectionConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$globalConfig = $this->validateConfig($this->defaults);
		$config = $globalConfig['connections'];

		$builder->addDefinition($this->prefix('eventManager'))
			->setFactory(ContainerAwareEventManager::class);

		if ($globalConfig['debug'] === true) {
			$builder->getDefinition($this->prefix('eventManager'))
				->setAutowired(false);
			$builder->addDefinition($this->prefix('eventManager.debug'))
				->setFactory(DebugEventManager::class, [$this->prefix('@eventManager')]);
		}

		foreach ($config as $name => $connection) {
			if (!array_key_exists('types', $connection)) {// temporary solution
				$connection['types'] = [];
			}
			if (!array_key_exists('typesMapping', $connection)) {// temporary solution
				$connection['typesMapping'] = [];
			}

			$autowired = $name === self::DEFAULT_CONNECTION_NAME ? true : false;

			$builder->addDefinition($this->prefix($name . '.connectionFactory'))
				->setFactory(ConnectionFactory::class, [$connection['types'], $connection['typesMapping']])
				->setAutowired($autowired);

			$builder->addDefinition($this->prefix($name . '.connection'))
				->setFactory(Connection::class)
				->setFactory('@' . $this->prefix($name . '.connectionFactory') . '::createConnection', [
					$connection,
					'@' . $this->prefix($name . '.configuration'),
					$builder->getDefinitionByType(EventManager::class),
				])
				->setAutowired($autowired)
				->addTag(self::TAG_CONNECTION);
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

		$eventManager = $builder->getDefinition($this->prefix('eventManager'));
		foreach ($builder->findByTag(self::TAG_NETTRINE_SUBSCRIBER) as $serviceName => $tag) {
			$serviceName = (string) $serviceName; // nette/di 2.4 allows numeric names, which are converted to integers
			$class = $builder->getDefinition($serviceName)->getType();

			if ($class === null || !is_subclass_of($class, EventSubscriber::class)) {
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
					call_user_func([(new ReflectionClass($class))->newInstanceWithoutConstructor(), 'getSubscribedEvents']),
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
			foreach ($config['connections'] as $name => $connection) {
				$initialize = $class->getMethod('initialize');
				$initialize->addBody(
					'$this->getService(?)->addPanel($this->getService(?));',
					['tracy.bar', $this->prefix($name . '.queryPanel')]
				);
				$initialize->addBody(
					'$this->getService(?)->getConfiguration()->getSqlLogger()->addLogger($this->getService(?));',
					[$this->prefix($name . '.connection'), $this->prefix($name . '.queryPanel')]
				);
				$initialize->addBody(
					'$this->getService(?)->addPanel(new ?);',
					['tracy.blueScreen', ContainerBuilder::literal(DbalBlueScreen::class)]
				);
			}
		}
	}

}
