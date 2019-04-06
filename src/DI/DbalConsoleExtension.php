<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\DBAL\Tools\Console\Command\ImportCommand;
use Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\ServiceCreationException;
use Symfony\Component\Console\Application;

class DbalConsoleExtension extends CompilerExtension
{

	/** @var bool */
	private $cliMode;

	public function __construct(?bool $cliMode = null)
	{
		$this->cliMode = $cliMode ?? PHP_SAPI === 'cli';
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		if (!class_exists(Application::class)) {
			throw new ServiceCreationException(sprintf('Missing "%s" service', Application::class));
		}

		// Skip if it's not CLI mode
		if (!$this->cliMode) {
			return;
		}

		$builder = $this->getContainerBuilder();

		// Helpers
		$builder->addDefinition($this->prefix('connectionHelper'))
			->setFactory(ConnectionHelper::class)
			->setAutowired(false);

		//Commands
		$builder->addDefinition($this->prefix('importCommand'))
			->setFactory(ImportCommand::class)
			->addTag('console.command', 'dbal:import')
			->setAutowired(false);

		$builder->addDefinition($this->prefix('reservedWordsCommand'))
			->setFactory(ReservedWordsCommand::class)
			->addTag('console.command', 'dbal:reserved-words')
			->setAutowired(false);

		$builder->addDefinition($this->prefix('runSqlCommand'))
			->setFactory(RunSqlCommand::class)
			->addTag('console.command', 'dbal:run-sql')
			->setAutowired(false);
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		// Skip if it's not CLI mode
		if (!$this->cliMode) {
			return;
		}

		$builder = $this->getContainerBuilder();

		// Lookup for Symfony Console Application
		/** @var ServiceDefinition $applicationDef */
		$applicationDef = $builder->getDefinitionByType(Application::class);

		// Register helpers
		$connectionHelper = $this->prefix('@connectionHelper');
		$applicationDef->addSetup(new Statement('$service->getHelperSet()->set(?, ?)', [$connectionHelper, 'db']));
	}

}
