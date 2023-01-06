<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Contributte\DI\Helper\ExtensionDefinitionsHelper;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\LoggerChain;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\DBAL\ConnectionAccessor;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\Events\ContainerAwareEventManager;
use Nettrine\DBAL\Events\DebugEventManager;
use Nettrine\DBAL\Logger\ProfilerLogger;
use Nettrine\DBAL\Tracy\BlueScreen\DbalBlueScreen;
use Nettrine\DBAL\Tracy\QueryPanel\QueryPanel;
use ReflectionClass;
use stdClass;

/**
 * @property-read stdClass $config
 */
final class DbalExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'debug' => Expect::structure([
				'panel' => Expect::bool(false),
				'sourcePaths' => Expect::arrayOf('string'),
			]),
			'configuration' => Expect::structure([
				'sqlLogger' => Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class)),
				'resultCache' => Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class)),
				'schemaAssetsFilter' => Expect::anyOf(Expect::string(), Expect::type(Statement::class)),
				'filterSchemaAssetsExpression' => Expect::string()->nullable(),
				'autoCommit' => Expect::bool(true),
			]),
			'connection' => Expect::structure([
				'driver' => Expect::mixed()->required(true),
				'types' => Expect::arrayOf(
					Expect::structure([
						'class' => Expect::string()->required(),
						'commented' => Expect::bool(false),
					])
						->before(function ($type) {
							if (is_string($type)) {
								return ['class' => $type];
							}

							return $type;
						})
						->castTo('array')
				),
				'typesMapping' => Expect::array(),
			])->otherItems()->castTo('array'),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		$this->loadDoctrineConfiguration();
		$this->loadConnectionConfiguration();
	}

	/**
	 * Register Doctrine Configuration
	 */
	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config->configuration;
		$definitionsHelper = new ExtensionDefinitionsHelper($this->compiler);

		$loggers = [];

		// SqlLogger (append to chain)
		if ($config->sqlLogger !== null) {
			$configLoggerName = $this->prefix('logger.config');
			$configLoggerDefinition = $definitionsHelper->getDefinitionFromConfig($config->sqlLogger, $configLoggerName);

			// If service is extension specific, then disable autowiring
			if ($configLoggerDefinition instanceof Definition && $configLoggerDefinition->getName() === $configLoggerName) {
				$configLoggerDefinition->setAutowired(false);
			}

			$loggers[] = $configLoggerDefinition;
		}

		$debugConfig = $this->config->debug;
		if ($debugConfig->panel) {
			$profiler = $builder->addDefinition($this->prefix('profiler'))
				->setType(ProfilerLogger::class);
			foreach ($debugConfig->sourcePaths as $path) {
				$profiler->addSetup('addPath', [$path]);
			}

			$loggers[] = $profiler;
		}

		$loggerDefinition = $builder->addDefinition($this->prefix('logger'))
			->setType(LoggerChain::class)
			->setArguments(['loggers' => $loggers])
			->setAutowired('self');

		$configuration = $builder->addDefinition($this->prefix('configuration'));
		$configuration->setFactory(Configuration::class)
			->setAutowired(false)
			->addSetup('setSQLLogger', [$loggerDefinition]);

		// ResultCache
		if ($config->resultCache !== null) {
			$resultCacheName = $this->prefix('resultCache');
			$resultCacheDefinition = $definitionsHelper->getDefinitionFromConfig($config->resultCache, $resultCacheName);

			// If service is extension specific, then disable autowiring
			if ($resultCacheDefinition instanceof Definition && $resultCacheDefinition->getName() === $resultCacheName) {
				$resultCacheDefinition->setAutowired(false);
			}
		} else {
			$resultCacheDefinition = '@' . Cache::class;
		}

		$configuration->addSetup('setResultCacheImpl', [
			$resultCacheDefinition,
		]);

		// SchemaAssetsFilter
		if ($config->schemaAssetsFilter !== null) {
			$configuration->addSetup('setSchemaAssetsFilter', [$config->schemaAssetsFilter]);
		}

		// FilterSchemaAssetsExpression
		if ($config->filterSchemaAssetsExpression !== null) {
			$configuration->addSetup('setFilterSchemaAssetsExpression', [$config->filterSchemaAssetsExpression]);
		}

		// AutoCommit
		$configuration->addSetup('setAutoCommit', [$config->autoCommit]);
	}

	public function loadConnectionConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config->connection;

		$builder->addDefinition($this->prefix('eventManager'))
			->setFactory(ContainerAwareEventManager::class);

		if ($this->config->debug->panel) {
			$builder->getDefinition($this->prefix('eventManager'))
				->setAutowired(false);
			$builder->addDefinition($this->prefix('eventManager.debug'))
				->setFactory(DebugEventManager::class, [$this->prefix('@eventManager')]);
		}

		$builder->addDefinition($this->prefix('connectionFactory'))
			->setFactory(ConnectionFactory::class, [$config['types'], $config['typesMapping']]);

		$connectionDef = $builder->addDefinition($this->prefix('connection'))
			->setType(Connection::class)
			->setFactory('@' . $this->prefix('connectionFactory') . '::createConnection', [
				$config,
				'@' . $this->prefix('configuration'),
				$builder->getDefinitionByType(EventManager::class),
			]);

		if ($this->config->debug->panel) {
			$connectionDef
				->addSetup('@Tracy\Bar::addPanel', [
					new Statement(QueryPanel::class, [
						$this->prefix('@profiler'),
					]),
				])
				->addSetup('@Tracy\BlueScreen::addPanel', [
					[DbalBlueScreen::class, 'renderException'],
				]);
		}

		$builder->addAccessorDefinition($this->prefix('connectionAccessor'))
			->setImplement(ConnectionAccessor::class);
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Idea by @enumag
		// https://github.com/Arachne/EventManager/blob/master/src/DI/EventManagerExtension.php

		/** @var ServiceDefinition $eventManager */
		$eventManager = $builder->getDefinition($this->prefix('eventManager'));
		foreach ($builder->findByType(EventSubscriber::class) as $serviceName => $serviceDef) {
			$eventManager->addSetup(
				'?->addEventListener(?, ?)',
				[
					'@self',
					call_user_func([(new ReflectionClass((string) $serviceDef->getType()))->newInstanceWithoutConstructor(), 'getSubscribedEvents']),
					$serviceName, // Intentionally without @ for laziness.
				]
			);
		}
	}

}
