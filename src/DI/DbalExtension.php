<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Portability\Connection as PortabilityConnection;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Validators;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\Tracy\BlueScreen\DbalBlueScreen;
use Nettrine\DBAL\Tracy\ConnectionPanel\ConnectionPanel;
use Nettrine\DBAL\Tracy\QueryPanel\QueryPanel;
use PDO;

final class DbalExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'debug' => FALSE,
		'configuration' => [
			'sqlLogger' => NULL,
			'resultCacheImpl' => NULL,
			'filterSchemaAssetsExpression' => NULL,
			'autoCommit' => TRUE,
		],
		'connection' => [
			'url' => NULL,
			'driver' => 'pdo_mysql',
			'driverClass' => NULL,
			'host' => NULL,
			'dbname' => NULL,
			'servicename' => NULL,
			'user' => NULL,
			'password' => NULL,
			'charset' => 'UTF8',
			'portability' => PortabilityConnection::PORTABILITY_ALL,
			'fetchCase' => PDO::CASE_LOWER,
			'persistent' => TRUE,
			'types' => [],
		],
	];

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$this->loadDoctrineConfiguration();
		$this->loadConnectionConfiguration();

		if ($config['debug'] === TRUE) {
			$builder->addDefinition($this->prefix('connectionPanel'))
				->setFactory(ConnectionPanel::class)
				->setAutowired(FALSE);
			$builder->addDefinition($this->prefix('queryPanel'))
				->setFactory(QueryPanel::class)
				->setAutowired(FALSE);
		}
	}

	/**
	 * Register Doctrine Configuration
	 *
	 * @return void
	 */
	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults['configuration'], $this->config['configuration']);

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setFactory(Configuration::class)
			->setAutowired(FALSE);

		// SqlLogger
		if ($config['sqlLogger'] !== NULL) {
			$configuration->addSetup('setSQLLogger', [$config['sqlLogger']]);
		}

		// ResultCacheImpl
		if ($config['resultCacheImpl'] !== NULL) {
			$configuration->addSetup('setResultCacheImpl', [$config['resultCacheImpl']]);
		}

		// FilterSchemaAssetsExpression
		if ($config['filterSchemaAssetsExpression'] !== NULL) {
			$configuration->addSetup('setFilterSchemaAssetsExpression', [$config['resultCacheImpl']]);
		}

		// AutoCommit
		Validators::assert($config['autoCommit'], 'bool', 'configuration.autoCommit');
		$configuration->addSetup('setAutoCommit', [$config['autoCommit']]);
	}

	/**
	 * @return void
	 */
	public function loadConnectionConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults['connection'], $this->config['connection']);

		$builder->addDefinition($this->prefix('eventManager'))
			->setFactory(EventManager::class);

		$builder->addDefinition($this->prefix('connectionFactory'))
			->setFactory(ConnectionFactory::class, [$config['types']]);

		$builder->addDefinition($this->prefix('connection'))
			->setFactory(Connection::class)
			->setFactory('@' . $this->prefix('connectionFactory') . '::createConnection', [
				$config,
				'@' . $this->prefix('configuration'),
				'@' . $this->prefix('eventManager'),
			]);
	}

	/**
	 * Update initialize method
	 *
	 * @param ClassType $class
	 * @return void
	 */
	public function afterCompile(ClassType $class): void
	{
		$config = $this->validateConfig($this->defaults);
		if ($config['debug'] === TRUE) {
			$initialize = $class->getMethod('initialize');
			$initialize->addBody(
				'$this->getService(?)->addPanel($this->getService(?));',
				['tracy.bar', $this->prefix('connectionPanel')]
			);
			$initialize->addBody(
				'$this->getService(?)->addPanel($this->getService(?));',
				['tracy.bar', $this->prefix('queryPanel')]
			);
			$initialize->addBody(
				'$this->getService(?)->addPanel(new ?);',
				['tracy.blueScreen', ContainerBuilder::literal(DbalBlueScreen::class)]
			);
		}
	}

}
