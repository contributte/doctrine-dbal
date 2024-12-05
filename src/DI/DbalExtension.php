<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\DBAL\DI\Pass\AbstractPass;
use Nettrine\DBAL\DI\Pass\ConnectionPass;
use Nettrine\DBAL\DI\Pass\ConsolePass;
use Nettrine\DBAL\DI\Pass\DoctrinePass;
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

	public const MIDDLEWARE_TAG = 'nettrine.dbal.middleware';
	public const MIDDLEWARE_INTERNAL_TAG = 'nettrine.dbal.middleware.internal';
	public const CONNECTION_TAG = 'nettrine.dbal.connection';

	/** @var AbstractPass[] */
	protected array $passes = [];

	public function __construct()
	{
		$this->passes[] = new DoctrinePass($this);
		$this->passes[] = new ConsolePass($this);
		$this->passes[] = new ConnectionPass($this);
	}

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
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->loadPassConfiguration();
		}
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->beforePassCompile();
		}
	}

	public function afterCompile(ClassType $class): void
	{
		// Trigger passes
		foreach ($this->passes as $pass) {
			$pass->afterPassCompile($class);
		}
	}

}
