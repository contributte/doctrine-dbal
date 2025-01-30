<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI\Pass;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Context;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\DI\Helpers\BuilderMan;
use Nettrine\DBAL\DI\Helpers\SmartStatement;
use Nettrine\DBAL\Middleware\Debug\DebugMiddleware;
use Nettrine\DBAL\Middleware\Debug\DebugStack;
use Nettrine\DBAL\Tracy\ConnectionPanel;

/**
 * @phpstan-import-type TConnectionConfig from DbalExtension
 */
class ConnectionPass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$config = $this->getConfig();

		// Configure connections
		foreach ($config->connections as $connectionName => $connectionConfig) {
			// Validate connection configuration
			$this->validateConnectionConfig($connectionName, $connectionConfig);

			// Load connection configuration
			$this->loadConnectionConfiguration($connectionName, $connectionConfig);
		}
	}

	public function beforePassCompile(): void
	{
		$config = $this->getConfig();

		// Configure connections
		foreach ($config->connections as $connectionName => $connectionConfig) {
			$this->beforeConnectionCompile($connectionName, $connectionConfig);
		}
	}

	/**
	 * @phpstan-param TConnectionConfig $connectionConfig
	 */
	public function loadConnectionConfiguration(string $connectionName, mixed $connectionConfig): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

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
				->addTag(DbalExtension::MIDDLEWARE_TAG, ['connection' => $connectionName, 'middleware' => $middlewareName])
				->setAutowired(false);
		}

		// Middlewares: debug
		if ($config->debug->panel) {
			$sourcePaths = $config->debug->sourcePaths;
			$builder->addDefinition($this->prefix(sprintf('connections.%s.middleware.internal.debug.stack', $connectionName)))
				->setFactory(DebugStack::class, ['sourcePaths' => $sourcePaths])
				->setAutowired(false);
			$builder->addDefinition($this->prefix(sprintf('connections.%s.middleware.internal.debug', $connectionName)))
				->setFactory(DebugMiddleware::class, [$this->prefix(sprintf('@connections.%s.middleware.internal.debug.stack', $connectionName)), $connectionName])
				->addTag(DbalExtension::MIDDLEWARE_INTERNAL_TAG, ['connection' => $connectionName, 'middleware' => 'debug'])
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
			->addTag(DbalExtension::CONNECTION_TAG, ['name' => $connectionName])
			->setAutowired($connectionName === 'default');
	}

	/**
	 * @phpstan-param TConnectionConfig $connectionConfig
	 */
	private function beforeConnectionCompile(string $connectionName, mixed $connectionConfig): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$configurationDef = $builder->getDefinition($this->prefix(sprintf('connections.%s.configuration', $connectionName)));
		assert($configurationDef instanceof ServiceDefinition);

		$connectionDef = $builder->getDefinition($this->prefix(sprintf('connections.%s.connection', $connectionName)));
		assert($connectionDef instanceof ServiceDefinition);

		// Configuration: result cache
		if ($connectionConfig->resultCache !== null) {
			$configurationDef->addSetup('setResultCache', [SmartStatement::from($connectionConfig->resultCache)]);
		}

		// Configuration: middlewares
		$configurationDef->addSetup('setMiddlewares', [BuilderMan::of($this)->getMiddlewaresBy($connectionName)]);

		// Connection: tracy panel
		if ($config->debug->panel) {
			$debugStackDef = $builder->getDefinition($this->prefix(sprintf('connections.%s.middleware.internal.debug.stack', $connectionName)));
			assert($debugStackDef instanceof ServiceDefinition);
			$connectionDef->addSetup(
				[ConnectionPanel::class, 'initialize'],
				[$debugStackDef, $connectionName],
			);
		}
	}

	/**
	 * @return array<string, Structure>
	 */
	private function getDriverConfigSchema(): array
	{
		$shared = [
			'serverVersion' => Expect::string(),
			'wrapperClass' => Expect::string(),
			'defaultTableOptions' => Expect::arrayOf(Expect::mixed(), Expect::string()),
			'driverOptions' => Expect::array(),
			'replica' => Expect::arrayOf(
				Expect::arrayOf(
					Expect::scalar(),
					Expect::string()
				),
				Expect::string()->required()
			),
			'primary' => Expect::arrayOf(
				Expect::scalar(),
				Expect::string()
			),
		];

		return [
			'pdo_sqlite' => Expect::structure([
				'driver' => Expect::anyOf('pdo_sqlite'),
				'memory' => Expect::bool(),
				'password' => Expect::string(),
				'path' => Expect::string(),
				'user' => Expect::string(),
				'host' => Expect::string(),
				...$shared,
			]),
			'sqlite3' => Expect::structure([
				'driver' => Expect::anyOf('sqlite3'),
				'memory' => Expect::bool(),
				'path' => Expect::string(),
				'host' => Expect::string(),
				...$shared,
			]),
			'pdo_mysql' => Expect::structure([
				'charset' => Expect::string(),
				'dbname' => Expect::string(),
				'driver' => Expect::anyOf('pdo_mysql'),
				'host' => Expect::string(),
				'password' => Expect::string(),
				'port' => Expect::int(),
				'unix_socket' => Expect::string(),
				'user' => Expect::string(),
				...$shared,
			]),
			'mysqli' => Expect::structure([
				'charset' => Expect::string(),
				'dbname' => Expect::string(),
				'driver' => Expect::anyOf('mysqli'),
				'host' => Expect::string(),
				'password' => Expect::string(),
				'port' => Expect::int(),
				'ssl_ca' => Expect::string(),
				'ssl_capath' => Expect::string(),
				'ssl_cert' => Expect::string(),
				'ssl_cipher' => Expect::string(),
				'ssl_key' => Expect::string(),
				'unix_socket' => Expect::string(),
				'user' => Expect::string(),
				...$shared,
			]),
			'pdo_pgsql' => Expect::structure([
				'application_name' => Expect::string(),
				'charset' => Expect::string(),
				'dbname' => Expect::string(),
				'driver' => Expect::anyOf('pdo_pgsql'),
				'gssencmode' => Expect::string(),
				'host' => Expect::string(),
				'password' => Expect::string(),
				'port' => Expect::int(),
				'sslcert' => Expect::string(),
				'sslcrl' => Expect::string(),
				'sslkey' => Expect::string(),
				'sslmode' => Expect::string(),
				'sslrootcert' => Expect::string(),
				'user' => Expect::string(),
				...$shared,
			]),
			'pdo_oci' => Expect::structure([
				'charset' => Expect::string(),
				'connectstring' => Expect::string(),
				'dbname' => Expect::string(),
				'driver' => Expect::anyOf('pdo_oci'),
				'exclusive' => Expect::bool(),
				'host' => Expect::string(),
				'instancename' => Expect::string(),
				'password' => Expect::string(),
				'persistent' => Expect::bool(),
				'pooled' => Expect::bool(),
				'port' => Expect::int(),
				'protocol' => Expect::string(),
				'service' => Expect::bool(),
				'servicename' => Expect::string(),
				'user' => Expect::string(),
				...$shared,
			]),
			'pdo_sqlsrv' => Expect::structure([
				'dbname' => Expect::string(),
				'driver' => Expect::anyOf('pdo_sqlsrv'),
				'host' => Expect::string(),
				'password' => Expect::string(),
				'port' => Expect::int(),
				'user' => Expect::string(),
				...$shared,
			]),
			'ibm_db2' => Expect::structure([
				'dbname' => Expect::string(),
				'driver' => Expect::anyOf('ibm_db2'),
				'host' => Expect::string(),
				'password' => Expect::string(),
				'persistent' => Expect::bool(),
				'port' => Expect::int(),
				'user' => Expect::string(),
				...$shared,
			]),
		];
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
		unset($config['url']);

		// Filter out null values
		$config = array_filter($config, fn ($value) => $value !== null);

		$processor = new Processor();
		$processor->onNewContext[] = function (Context $context) use ($connectionName): void {
			$context->path = array_merge(['connections', $connectionName], $context->path);
		};
		$processor->process($this->getDriverConfigSchema()[$connectionConfig->driver], $config);
	}

}
