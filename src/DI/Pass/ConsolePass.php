<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI\Pass;

use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;

class ConsolePass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$builder = $this->extension->getContainerBuilder();

		// RunSqlCommand
		$builder->addDefinition($this->prefix('runSqlCommand'))
			->setFactory(RunSqlCommand::class)
			->addTag('console.command', 'dbal:run-sql')
			->setAutowired(false);
	}

}
