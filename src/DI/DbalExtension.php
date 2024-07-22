<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Contributte\Psr6\CachePool;
use Contributte\Psr6\CachePoolFactory;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\Events\ContainerEventManager;
use Nettrine\DBAL\Middleware\TracyMiddleware;
use Nettrine\DBAL\Tracy\BlueScreen\DbalBlueScreen;
use Nettrine\DBAL\Tracy\QueryPanel\QueryPanel;
use ReflectionClass;
use stdClass;
use Throwable;
use Tracy\Bar;
use Tracy\BlueScreen;

/**
 * @property-read stdClass $config
 */
class DbalExtension extends CompilerExtension
{

	public const TAG_MIDDLEWARE = 'nettrine.dbal.middleware';

	public function getConfigSchema(): Schema
	{
		$expectService = Expect::anyOf(
			Expect::string()->required()->assert(fn ($input) => str_starts_with($input, '@') || class_exists($input) || interface_exists($input)),
			Expect::type(Statement::class),
			Expect::string()->assert(fn (string $input) => is_callable($input)),
		)->required();

		return Expect::structure([
			'debug' => Expect::structure([
				'panel' => Expect::bool(false),
				'sourcePaths' => Expect::arrayOf('string'),
			]),
			'configuration' => Expect::structure([
				'middlewares' => Expect::arrayOf(
					$expectService,
					Expect::string()->required()
				),
				'resultCache' => Expect::anyOf($expectService),
				'schemaAssetsFilter' => Expect::anyOf($expectService),
				'filterSchemaAssetsExpression' => Expect::string()->nullable(),
				'schemaManagerFactory' => Expect::anyOf($expectService),
				'autoCommit' => Expect::bool(true),
			]),
			'connection' => Expect::structure([
				'driver' => Expect::mixed()->required(),
				'types' => Expect::arrayOf('string', 'string'),
				'typesMapping' => Expect::array(),
			])->otherItems()->castTo('array'),
		]);
	}

	public function loadConfiguration(): void
	{
		$this->loadDoctrineConfiguration();
		$this->loadConnectionConfiguration();
	}

	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configurationConfig = $this->config->configuration;

		$configuration = $builder->addDefinition($this->prefix('configuration'));
		$configuration->setFactory(Configuration::class)
			->setAutowired(false);

		// Middlewares
		foreach ($configurationConfig->middlewares as $name => $middleware) {
			$builder->addDefinition($this->prefix('middleware.' . $name))
				->setFactory($middleware)
				->addTag(self::TAG_MIDDLEWARE);
		}

		// SchemaAssetsFilter
		if ($configurationConfig->schemaAssetsFilter !== null) {
			$configuration->addSetup('setSchemaAssetsFilter', [$configurationConfig->schemaAssetsFilter]);
		}

		// FilterSchemaAssetsExpression
		if ($configurationConfig->filterSchemaAssetsExpression !== null) {
			$configuration->addSetup('setFilterSchemaAssetsExpression', [$configurationConfig->filterSchemaAssetsExpression]);
		}

		// SchemaManagerFactory
		if ($configurationConfig->schemaManagerFactory !== null) {
			$configuration->addSetup('setSchemaManagerFactory', [$configurationConfig->schemaManagerFactory]);
		}

		// AutoCommit
		$configuration->addSetup('setAutoCommit', [$configurationConfig->autoCommit]);

		// Tracy middleware
		if ($this->config->debug->panel) {
			$builder->addDefinition($this->prefix('middleware.internal.tracy'))
				->setFactory(TracyMiddleware::class)
				->addTag(self::TAG_MIDDLEWARE);
		}
	}

	public function loadConnectionConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$connectionConfig = $this->config->connection;

		// Event manager
		$builder->addDefinition($this->prefix('eventManager'))
			->setFactory(ContainerEventManager::class);

		// Connection factory
		$builder->addDefinition($this->prefix('connectionFactory'))
			->setFactory(ConnectionFactory::class, [$connectionConfig['types'], $connectionConfig['typesMapping']]);

		// Connection
		$builder->addDefinition($this->prefix('connection'))
			->setType(Connection::class)
			->setFactory($this->prefix('@connectionFactory') . '::createConnection', [
				$connectionConfig,
				$this->prefix('@configuration'),
				$this->prefix('@eventManager'),
			]);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$configurationDef = $builder->getDefinition($this->prefix('configuration'));
		assert($configurationDef instanceof ServiceDefinition);

		// ResultCache
		$configurationDef->addSetup('setResultCache', [$this->getResultCacheDefinition($this->config->configuration->resultCache)]);

		// Set middlewares
		$configurationDef->addSetup('setMiddlewares', [
			array_map(
				fn (string $name) => $builder->getDefinition($name),
				array_keys($builder->findByTag(self::TAG_MIDDLEWARE))
			),
		]);

		/** @var ServiceDefinition $eventManager */
		$eventManager = $builder->getDefinition($this->prefix('eventManager'));

		foreach ($builder->findByType(EventSubscriber::class) as $serviceName => $serviceDef) {
			/** @var class-string<EventSubscriber> $serviceClass */
			$serviceClass = (string) $serviceDef->getType();
			$rc = new ReflectionClass($serviceClass);

			/** @var EventSubscriber $subscriber */
			$subscriber = $rc->newInstanceWithoutConstructor();
			$events = $subscriber->getSubscribedEvents();

			$eventManager->addSetup('?->addEventListener(?, ?)', ['@self', $events, $serviceName]);
		}
	}

	public function afterCompile(ClassType $class): void
	{
		$builder = $this->getContainerBuilder();
		$initialization = $this->getInitialization();

		if ($this->config->debug->panel) {
			$initialization->addBody(
				$builder->formatPhp('?->addPanel(?);', [
					$builder->getDefinitionByType(BlueScreen::class),
					[DbalBlueScreen::class, 'renderException'],
				])
			);

			$initialization->addBody(
				$builder->formatPhp('?->addPanel(?);', [
					$builder->getDefinitionByType(Bar::class),
					new Statement(QueryPanel::class, [$builder->getDefinitionByType(TracyMiddleware::class)]),
				])
			);
		}
	}

	/**
	 * @param string|mixed[]|Statement|null $cacheConfig
	 */
	private function getResultCacheDefinition(string|array|Statement|null $cacheConfig): Definition
	{
		$builder = $this->getContainerBuilder();

		if ($cacheConfig !== null) {
			if (is_string($cacheConfig)) {
				$cacheConfig = $this->resolveCacheDriverDefinitionString($cacheConfig, $this->prefix('resultCache'));
			}

			if ($cacheConfig instanceof Statement) {
				$entity = $cacheConfig->getEntity();

				if (is_string($entity) && is_a($entity, Storage::class, true)) {
					$entity = Cache::class;
					$cacheConfig = new Statement(
						$entity,
						[
							'storage' => $cacheConfig,
							'namespace' => $this->prefix('resultCache'),
						]
					);
				}

				if (is_string($entity) && is_a($entity, Cache::class, true)) {
					return $builder->addDefinition($this->prefix('resultCache'))
						->setFactory(new Statement(CachePool::class, [$cacheConfig]))
						->setAutowired(false);
				}
			}

			return $builder->addDefinition($this->prefix('resultCache'))
				->setFactory($cacheConfig)
				->setAutowired(false);
		}

		// No driver provided, create CacheItemPoolInterface with autowired Storage

		// ICachePoolFactory doesn't have to be registered in DI container
		$builder->addDefinition($this->prefix('cachePoolFactory'))
			->setFactory(CachePoolFactory::class)
			->setAutowired(false);

		return $builder->addDefinition($this->prefix('resultCache'))
			->setFactory('@' . $this->prefix('cachePoolFactory') . '::create', [$this->prefix('resultCache')])
			->setAutowired(false);
	}

	private function resolveCacheDriverDefinitionString(string $config, string $cacheNamespace): string|Statement
	{
		$builder = $this->getContainerBuilder();

		if (str_starts_with($config, '@')) {
			$service = substr($config, 1);

			if ($builder->hasDefinition($service)) {
				$definition = $builder->getDefinition($service);
			} else {
				try {
					$definition = $builder->getDefinitionByType($service);
				} catch (Throwable) {
					$definition = null;
				}
			}

			$type = $definition?->getType();

			if ($type === null) {
				return $config;
			}

			if (is_a($type, Storage::class, true)) {
				return new Statement(
					Cache::class,
					[
						'storage' => $config,
						'namespace' => $cacheNamespace,
					]
				);
			}

			if (is_a($type, Cache::class, true)) {
				return new Statement(CachePool::class, [$config]);
			}

			return $config;
		}

		if (is_a($config, Storage::class, true)) {
			return new Statement($config);
		}

		return $config;
	}

}
