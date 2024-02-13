<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Nette\DI\CompilerExtension;
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

		// ResultCache
		$resultCache = $configurationConfig->resultCache !== null ? $builder->addDefinition($this->prefix('resultCache'))
			->setFactory($configurationConfig->resultCache) : '@' . Cache::class;
		$configuration->addSetup('setResultCacheImpl', [$resultCache]);

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

		// Set middlewares
		$configurationDef = $builder->getDefinition($this->prefix('configuration'));
		assert($configurationDef instanceof ServiceDefinition);

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

}
