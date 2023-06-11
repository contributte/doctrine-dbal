<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\Tracy\BlueScreen\DbalBlueScreen;
use stdClass;
use Tracy\BlueScreen;

/**
 * @property-read stdClass $config
 */
class DbalExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		$expectService = Expect::anyOf(
			Expect::string()->required()->assert(fn ($input) => str_starts_with($input, '@') || class_exists($input) || interface_exists($input)),
			Expect::type(Statement::class),
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
		$middlewares = [];
		foreach ($configurationConfig->middlewares as $middleware) {
			$middlewares[] = new Statement($middleware);
		}

		$configuration->addSetup('setMiddlewares', [$middlewares]);

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

		// AutoCommit
		$configuration->addSetup('setAutoCommit', [$configurationConfig->autoCommit]);
	}

	public function loadConnectionConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$connectionConfig = $this->config->connection;

		// Connection factory
		$builder->addDefinition($this->prefix('connectionFactory'))
			->setFactory(ConnectionFactory::class, [$connectionConfig['types'], $connectionConfig['typesMapping']]);

		// Connection
		$builder->addDefinition($this->prefix('connection'))
			->setType(Connection::class)
			->setFactory($this->prefix('@connectionFactory') . '::createConnection', [
				$connectionConfig,
				$this->prefix('@configuration'),
			]);
	}

	public function afterCompile(ClassType $class): void
	{
		$builder = $this->getContainerBuilder();
		$initialization = $this->getInitialization();

		if ($this->config->debug->panel) {
			$initialization->addBody('$this->getService(?)->addPanel(?);', [
				$builder->getDefinitionByType(BlueScreen::class)->getName(),
				[DbalBlueScreen::class, 'renderException'],
			]);
		}
	}

}
