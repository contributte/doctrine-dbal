<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\LoggerChain;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\AssertionException;
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

	public const TAG_NETTRINE_SUBSCRIBER = 'nettrine.subscriber';

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'debug' => Expect::structure([
				'panel' => Expect::bool(false),
				'sourcePaths' => Expect::arrayOf('string'),
			]),
			'configuration' => Expect::structure([
				'sqlLogger' => Expect::type('string|' . Statement::class),
				'resultCacheImpl' => Expect::type('string|' . Statement::class),
				'filterSchemaAssetsExpression' => Expect::string()->nullable(),
				'autoCommit' => Expect::bool(true),
			]),
			'connection' => Expect::array()->default([
				'driver' => 'pdo_sqlite',
				'types' => [],
				'typesMapping' => [],
			]),
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

		$logger = $builder->addDefinition($this->prefix('logger'))
			->setType(LoggerChain::class)
			->setAutowired('self');

		$configuration = $builder->addDefinition($this->prefix('configuration'));
		$configuration->setFactory(Configuration::class)
			->setAutowired(false)
			->addSetup('setSQLLogger', [$this->prefix('@logger')]);

		// SqlLogger (append to chain)
		if ($config->sqlLogger !== null) {
			$logger->addSetup('addLogger', [$config->sqlLogger]);
		}

		// ResultCacheImpl
		if ($config->resultCacheImpl !== null) {
			$configuration->addSetup('setResultCacheImpl', [$config->resultCacheImpl]);
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
			->setFactory(Connection::class)
			->setFactory('@' . $this->prefix('connectionFactory') . '::createConnection', [
				$config,
				'@' . $this->prefix('configuration'),
				$builder->getDefinitionByType(EventManager::class),
			]);

		$debugConfig = $this->config->debug;
		if ($debugConfig->panel) {
			$connectionDef
				->addSetup('$profiler = ?', [
					new Statement(ProfilerLogger::class, [$connectionDef]),
				]);

			foreach ($debugConfig->sourcePaths as $path) {
				$connectionDef->addSetup('$profiler->addPath(?)', [$path]);
			}

			$connectionDef->addSetup('?->getConfiguration()->getSqlLogger()->addLogger(?)', [
					'@self',
					new PhpLiteral('$profiler'),
				])
				->addSetup('@Tracy\Bar::addPanel', [
					new Statement(QueryPanel::class, [
						new PhpLiteral('$profiler'),
					]),
				])
				->addSetup('@Tracy\BlueScreen::addPanel', [
					[DbalBlueScreen::class, 'renderException'],
				]);
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

		/** @var ServiceDefinition $eventManager */
		$eventManager = $builder->getDefinition($this->prefix('eventManager'));
		foreach ($builder->findByTag(self::TAG_NETTRINE_SUBSCRIBER) as $serviceName => $tag) {
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

}
