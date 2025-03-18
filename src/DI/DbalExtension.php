<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\DBAL\Tools\DsnParser;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\DBAL\DI\Helpers\Expecto;
use Nettrine\DBAL\DI\Pass\AbstractPass;
use Nettrine\DBAL\DI\Pass\ConnectionPass;
use Nettrine\DBAL\DI\Pass\ConsolePass;
use Nettrine\DBAL\DI\Pass\DoctrinePass;
use stdClass;
use Tracy\Debugger;

/**
 * @property-read stdClass $config
 * @phpstan-type TConnectionConfig object{
 *      application_name: string,
 *      autoCommit: bool,
 *      charset: string,
 *      connectstring: string,
 *      dbname: string,
 *      defaultTableOptions: array<string, mixed>,
 *      driver: string,
 *      driverClass: string,
 *      driverOptions: mixed[],
 *      exclusive: bool,
 *      gssencmode: string,
 *      host: string,
 *      instancename: string,
 *      keepReplica: bool,
 *      memory: bool,
 *      middlewares: array<string, string|array<string>|Statement>,
 *      password: string,
 *      path: string,
 *      persistent: bool,
 *      pooled: bool,
 *      port: int,
 *      primary: array<string, scalar>,
 *      protocol: string,
 *      resultCache: mixed,
 *      schemaAssetsFilter: mixed,
 *      schemaManagerFactory: mixed,
 *      serverVersion: string,
 *      service: bool,
 *      servicename: string,
 *      sessionMode: int,
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
 *      user: string,
 *      wrapperClass: string,
 *  }
 */
class DbalExtension extends CompilerExtension
{

	public const MIDDLEWARE_TAG = 'nettrine.dbal.middleware';
	public const MIDDLEWARE_INTERNAL_TAG = 'nettrine.dbal.middleware.internal';
	public const CONNECTION_TAG = 'nettrine.dbal.connection';
	public const DSN_MAPPING = [
		'mysql' => 'mysqli',
		'mariadb' => 'mysqli',
		'postgres' => 'pdo_pgsql',
		'postgresql' => 'pdo_pgsql',
		'sqlite' => 'pdo_sqlite',
	];

	/** @var AbstractPass[] */
	protected array $passes = [];

	public function __construct(
		private ?bool $debugMode = null
	)
	{
		if ($this->debugMode === null) {
			$this->debugMode = class_exists(Debugger::class) && Debugger::$productionMode === false;
		}

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

		$dsnTransformer = static function (mixed $connection) {
			if (is_array($connection)) {
				if (isset($connection['url'])) {
					assert(is_string($connection['url']));
					$params = (new DsnParser(self::DSN_MAPPING))->parse($connection['url']);
					$connection = array_merge($connection, $params);
				}
			}

			return $connection;
		};

		return Expect::structure([
			'debug' => Expect::structure([
				'panel' => Expect::bool($this->debugMode),
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
					'defaultTableOptions' => Expect::arrayOf(Expect::mixed(), Expect::string()),
					'driver' => Expect::anyOf('pdo_sqlite', 'sqlite3', 'pdo_mysql', 'mysqli', 'pdo_pgsql', 'pgsql', 'pdo_oci', 'oci8', 'pdo_sqlsrv', 'sqlsrv', 'ibm_db2')->required(),
					'driverClass' => Expect::string(),
					'driverOptions' => Expect::anyOf(Expect::null(), Expect::array()),
					'exclusive' => Expect::bool(),
					'gssencmode' => Expect::string(),
					'host' => Expect::string(),
					'instancename' => Expect::string(),
					'keepReplica' => Expect::bool(),
					'memory' => Expect::bool(),
					'password' => Expect::string(),
					'path' => Expect::string(),
					'persistent' => Expect::bool(),
					'pooled' => Expect::bool(),
					'port' => Expecto::port(),
					'protocol' => Expect::string(),
					'serverVersion' => Expect::string(),
					'service' => Expect::bool(),
					'servicename' => Expect::string(),
					'sessionMode' => Expect::int(),
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
					'url' => Expect::string(),
					'user' => Expect::string(),
					'wrapperClass' => Expect::string(),
					'replica' => Expect::arrayOf(
						Expect::arrayOf(
							Expect::scalar(),
							Expect::string()
						)->before($dsnTransformer),
						Expect::string()->required()
					),
					'primary' => Expect::arrayOf(
						Expect::scalar(),
						Expect::string()
					)->before($dsnTransformer),
					// Configuration
					'middlewares' => Expect::arrayOf($expectService, Expect::string()->required()),
					'resultCache' => (clone $expectService),
					'schemaAssetsFilter' => (clone $expectService),
					'schemaManagerFactory' => (clone $expectService),
					'autoCommit' => Expect::bool(true),
				])
					->assert(
						fn (stdClass $connection) => !(
							$connection->url === null
							&& $connection->host === null
							&& $connection->port === null
							&& $connection->path === null
							&& $connection->user === null
							&& $connection->password === null
						),
						'Configure DNS url or explicit host, port, user, password, dbname and others.'
					)
					->before($dsnTransformer),
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
