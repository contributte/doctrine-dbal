<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Context;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\DI\Helpers\SmartStatement;
use Nettrine\DBAL\Middleware\Debug\DebugMiddleware;
use Nettrine\DBAL\Middleware\Debug\DebugStack;
use Nettrine\DBAL\Tracy\ConnectionPanel;
use stdClass;

/**
 * @property-read stdClass $config
 * @phpstan-type TConnectionConfig object{
 *      application_name: string,
 *      autoCommit: bool,
 *      charset: string,
 *      connectstring: string,
 *      dbname: string,
 *      driver: string,
 *      driverOptions: mixed[],
 *      exclusive: bool,
 *      gssencmode: string,
 *      host: string,
 *      instancename: string,
 *      memory: bool,
 *      middlewares: array<string, string|array<string>|Statement>,
 *      password: string,
 *      path: string,
 *      persistent: bool,
 *      pooled: bool,
 *      port: int,
 *      protocol: string,
 *      resultCache: mixed,
 *      schemaAssetsFilter: mixed,
 *      schemaManagerFactory: mixed,
 *      serverVersion: string,
 *      service: bool,
 *      servicename: string,
 *      ssl_ca: string,
 *      ssl_capath: string,
 *      ssl_cert: string,
 *      ssl_cipher: string,
 *      ssl_key: string,
 *      sslcert: string,
 *      sslcrl: string,
 *      sslkey: string,
 *      sslmode: string,
 *      sslrootcert: string,
 *      unix_socket: string,
 *      user: string
 *  }
 */
class DbalExtension extends CompilerExtension
{

	public const TAG_MIDDLEWARE = 'nettrine.dbal.middleware';
	public const TAG_MIDDLEWARE_INTERNAL = 'nettrine.dbal.middleware.internal';
	public const TAG_CONNECTION = 'nettrine.dbal.connection';

	public function getConfigSchema(): Schema
	{
		$expectService = Expect::anyOf(
			Expect::string()->required()->assert(fn ($input) => str_starts_with($input, '@') || class_exists($input) || interface_exists($input)),
			Expect::type(Statement::class)->required(),
		);

		return Expect::structure([
			'debug' => Expect::structure([
				'panel' => Expect::bool(false),
				'sourcePaths' => Expect::arrayOf('string'),
			]),
			'types' => Expect::arrayOf('string', 'string'),
			'typesMapping' => Expect::arrayOf('string', 'string'),
			'connections' => Expect::arrayOf(
				Expect::structure([
					'application_name' => Expect::string(),
					'charset' => Expect::string(),
					'connectstring' => Expect::string(),
					'dbname' => Expect::string(),
					'driver' => Expect::anyOf('pdo_sqlite', 'sqlite3', 'pdo_mysql', 'mysqli', 'pdo_pgsql', 'pgsql', 'pdo_oci', 'oci8', 'pdo_sqlsrv', 'sqlsrv', 'ibm_db2'),
					'driverOptions' => Expect::anyOf(Expect::null(), Expect::array()),
					'exclusive' => Expect::bool(),
					'gssencmode' => Expect::string(),
					'host' => Expect::string(),
					'instancename' => Expect::string(),
					'memory' => Expect::bool(),
					'password' => Expect::string(),
					'path' => Expect::string(),
					'persistent' => Expect::bool(),
					'pooled' => Expect::bool(),
					'port' => Expect::int(),
					'protocol' => Expect::string(),
					'serverVersion' => Expect::string(),
					'service' => Expect::bool(),
					'servicename' => Expect::string(),
					'ssl_ca' => Expect::string(),
					'ssl_capath' => Expect::string(),
					'ssl_cert' => Expect::string(),
					'ssl_cipher' => Expect::string(),
					'ssl_key' => Expect::string(),
					'sslcert' => Expect::string(),
					'sslcrl' => Expect::string(),
					'sslkey' => Expect::string(),
					'sslmode' => Expect::string(),
					'sslrootcert' => Expect::string(),
					'unix_socket' => Expect::string(),
					'user' => Expect::string(),
					// Configuration
					'middlewares' => Expect::arrayOf($expectService, Expect::string()->required()),
					'resultCache' => (clone $expectService),
					'schemaAssetsFilter' => (clone $expectService),
					'schemaManagerFactory' => (clone $expectService),
					'autoCommit' => Expect::bool(true),
				]),
				Expect::string()->required(),
			)->min(1)->required(),
		]);
	}

	/**
	 * @return array<string, Structure>
	 */
	public function getDriverConfigSchema(): array
	{
		return [
			'pdo_sqlite' => Expect::structure([
				'driver' => Expect::anyOf('pdo_sqlite'),
				'memory' => Expect::bool(),
				'password' => Expect::string()->required(),
				'path' => Expect::string()->required(),
				'serverVersion' => Expect::string(),
				'user' => Expect::string()->required(),
			]),
			'sqlite3' => Expect::structure([
				'driver' => Expect::anyOf('sqlite3'),
				'memory' => Expect::bool(),
				'path' => Expect::string()->required(),
				'serverVersion' => Expect::string(),
			]),
			'pdo_mysql' => Expect::structure([
				'charset' => Expect::string(),
				'dbname' => Expect::string()->required(),
				'driver' => Expect::anyOf('pdo_mysql'),
				'host' => Expect::string()->required(),
				'password' => Expect::string()->required(),
				'port' => Expect::int(),
				'serverVersion' => Expect::string(),
				'unix_socket' => Expect::string(),
				'user' => Expect::string()->required(),
			]),
			'mysqli' => Expect::structure([
				'charset' => Expect::string(),
				'dbname' => Expect::string()->required(),
				'driver' => Expect::anyOf('mysqli'),
				'driverOptions' => Expect::array(),
				'host' => Expect::string()->required(),
				'password' => Expect::string()->required(),
				'port' => Expect::int(),
				'serverVersion' => Expect::string(),
				'ssl_ca' => Expect::string(),
				'ssl_capath' => Expect::string(),
				'ssl_cert' => Expect::string(),
				'ssl_cipher' => Expect::string(),
				'ssl_key' => Expect::string(),
				'unix_socket' => Expect::string(),
				'user' => Expect::string()->required(),
			]),
			'pdo_pgsql' => Expect::structure([
				'application_name' => Expect::string(),
				'charset' => Expect::string(),
				'dbname' => Expect::string()->required(),
				'driver' => Expect::anyOf('pdo_pgsql'),
				'gssencmode' => Expect::string(),
				'host' => Expect::string()->required(),
				'password' => Expect::string()->required(),
				'port' => Expect::int(),
				'serverVersion' => Expect::string(),
				'sslcert' => Expect::string(),
				'sslcrl' => Expect::string(),
				'sslkey' => Expect::string(),
				'sslmode' => Expect::string(),
				'sslrootcert' => Expect::string(),
				'user' => Expect::string()->required(),
			]),
			'pdo_oci' => Expect::structure([
				'charset' => Expect::string(),
				'connectstring' => Expect::string(),
				'dbname' => Expect::string()->required(),
				'driver' => Expect::anyOf('pdo_oci'),
				'driverOptions' => Expect::array(),
				'exclusive' => Expect::bool(),
				'host' => Expect::string(),
				'instancename' => Expect::string(),
				'password' => Expect::string()->required(),
				'persistent' => Expect::bool(),
				'pooled' => Expect::bool(),
				'port' => Expect::int(),
				'protocol' => Expect::string(),
				'serverVersion' => Expect::string(),
				'service' => Expect::bool(),
				'servicename' => Expect::string(),
				'user' => Expect::string()->required(),
			]),
			'pdo_sqlsrv' => Expect::structure([
				'dbname' => Expect::string()->required(),
				'driver' => Expect::anyOf('pdo_sqlsrv'),
				'driverOptions' => Expect::array(),
				'host' => Expect::string()->required(),
				'password' => Expect::string()->required(),
				'port' => Expect::int(),
				'serverVersion' => Expect::string(),
				'user' => Expect::string()->required(),
			]),
			'ibm_db2' => Expect::structure([
				'dbname' => Expect::string()->required(),
				'driver' => Expect::anyOf('ibm_db2'),
				'driverOptions' => Expect::array(),
				'host' => Expect::string()->required(),
				'password' => Expect::string()->required(),
				'persistent' => Expect::bool(),
				'port' => Expect::int(),
				'serverVersion' => Expect::string(),
				'user' => Expect::string()->required(),
			]),
		];
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// ConnectionFactory
		$builder->addDefinition($this->prefix('connectionFactory'))
			->setFactory(ConnectionFactory::class, [$this->config->types ?? [], $this->config->typesMapping ?? []]);

		// Configure connections
		foreach ($this->config->connections as $connectionName => $connectionConfig) {
			// Validate connection configuration
			$this->validateConnectionConfig($connectionName, $connectionConfig);

			// Load connection configuration
			$this->loadConnectionConfiguration($connectionName, $connectionConfig);
		}
	}

	/**
	 * @phpstan-param TConnectionConfig $connectionConfig
	 */
	public function loadConnectionConfiguration(string $connectionName, mixed $connectionConfig): void
	{
		$builder = $this->getContainerBuilder();

		// Configuration
		$configuration = $builder->addDefinition($this->prefix(sprintf('connections.%s.configuration', $connectionName)));
		$configuration->setFactory(Configuration::class)
			->setAutowired(false);

		// Configuration: schema assets filter
		if ($connectionConfig->schemaAssetsFilter !== null) {
			$configuration->addSetup('setSchemaAssetsFilter', [SmartStatement::from($connectionConfig->schemaAssetsFilter)]);
		}

		// Configuration: schema manager factory
		if ($connectionConfig->schemaManagerFactory !== null) {
			$configuration->addSetup('setSchemaManagerFactory', [SmartStatement::from($connectionConfig->schemaManagerFactory)]);
		}

		// Configuration: auto commit
		$configuration->addSetup('setAutoCommit', [$connectionConfig->autoCommit]);

		// Middlewares
		foreach ($connectionConfig->middlewares as $middlewareName => $middleware) {
			$builder->addDefinition($this->prefix(sprintf('connections.%s.middleware.%s', $connectionName, $middlewareName)))
				->setFactory($middleware)
				->addTag(self::TAG_MIDDLEWARE, ['name' => $middlewareName])
				->setAutowired(false);
		}

		// Middlewares: debug
		if ($this->config->debug->panel) {
			$builder->addDefinition($this->prefix(sprintf('connections.%s.middleware.internal.debug.stack', $connectionName)))
				->setFactory(DebugStack::class)
				->setAutowired(false);
			$builder->addDefinition($this->prefix(sprintf('connections.%s.middleware.internal.debug', $connectionName)))
				->setFactory(DebugMiddleware::class, [$this->prefix(sprintf('@connections.%s.middleware.internal.debug.stack', $connectionName))])
				->addTag(self::TAG_MIDDLEWARE_INTERNAL, ['name' => 'debug'])
				->setAutowired(false);
		}

		// Connection
		$builder->addDefinition($this->prefix(sprintf('connections.%s.connection', $connectionName)))
			->setType(Connection::class)
			->setFactory($this->prefix('@connectionFactory') . '::createConnection', [
				(array) $connectionConfig,
				$this->prefix(sprintf('@connections.%s.configuration', $connectionName)),
				[],
			])
			->addTag(self::TAG_CONNECTION, ['name' => $connectionName])
			->setAutowired($connectionName === 'default');
	}

	public function beforeCompile(): void
	{
		// Configure connections
		foreach ($this->config->connections as $connectionName => $connectionConfig) {
			$this->beforeConnectionCompile($connectionName, $connectionConfig);
		}
	}

	/**
	 * @phpstan-param TConnectionConfig $connectionConfig
	 */
	public function beforeConnectionCompile(string $connectionName, mixed $connectionConfig): void
	{
		$builder = $this->getContainerBuilder();

		$configurationDef = $builder->getDefinition($this->prefix(sprintf('connections.%s.configuration', $connectionName)));
		assert($configurationDef instanceof ServiceDefinition);

		$connectionDef = $builder->getDefinition($this->prefix(sprintf('connections.%s.connection', $connectionName)));
		assert($connectionDef instanceof ServiceDefinition);

		// Configuration: result cache
		if ($connectionConfig->resultCache !== null) {
			$configurationDef->addSetup('setResultCache', [SmartStatement::from($connectionConfig->resultCache)]);
		}

		// Configuration: middlewares
		$configurationDef->addSetup('setMiddlewares', [
			array_map(
				fn (string $name) => $builder->getDefinition($name),
				array_keys($builder->findByTag(self::TAG_MIDDLEWARE))
			),
		]);

		// Connection: tracy panel
		if ($this->config->debug->panel) {
			$debugStackDef = $builder->getDefinition($this->prefix(sprintf('connections.%s.middleware.internal.debug.stack', $connectionName)));
			assert($debugStackDef instanceof ServiceDefinition);
			$connectionDef->addSetup(
				[ConnectionPanel::class, 'initialize'],
				[$debugStackDef, $connectionName],
			);
		}
	}

	/**
	 * @phpstan-param TConnectionConfig $connectionConfig
	 */
	private function validateConnectionConfig(string $connectionName, mixed $connectionConfig): void
	{
		$config = (array) $connectionConfig;

		// Unset unrelevant configuration
		unset($config['middlewares']);
		unset($config['resultCache']);
		unset($config['schemaAssetsFilter']);
		unset($config['schemaManagerFactory']);
		unset($config['autoCommit']);

		// Filter out null values
		$config = array_filter($config, fn ($value) => $value !== null);

		$processor = new Processor();
		$processor->onNewContext[] = function (Context $context) use ($connectionName): void {
			$context->path = array_merge(['connections', $connectionName], $context->path);
		};
		$processor->process($this->getDriverConfigSchema()[$connectionConfig->driver], $config);
	}

}
