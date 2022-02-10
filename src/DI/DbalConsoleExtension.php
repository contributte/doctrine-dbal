<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI;

use Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\DBAL\Tools\Console\ConnectionProvider\SingleConnectionProvider;
use Nette\DI\CompilerExtension;

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
		// Skip if it's not CLI mode
		if (!$this->cliMode) {
			return;
		}

		$builder = $this->getContainerBuilder();

		// Connection provider
		$connectionProvider = $builder->addDefinition($this->prefix('connectionProvider'))
			->setFactory(SingleConnectionProvider::class)
			->setAutowired(false);

		//Commands
		$builder->addDefinition($this->prefix('reservedWordsCommand'))
			->setFactory(ReservedWordsCommand::class, [$connectionProvider])
			->addTag('console.command', 'dbal:reserved-words')
			->setAutowired(false);

		$builder->addDefinition($this->prefix('runSqlCommand'))
			->setFactory(RunSqlCommand::class, [$connectionProvider])
			->addTag('console.command', 'dbal:run-sql')
			->setAutowired(false);
	}

}
