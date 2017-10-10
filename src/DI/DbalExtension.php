<?php

namespace Nettrine\Dbal\DI;

use Doctrine\DBAL\Connection;
use Nette\DI\CompilerExtension;

final class DbalExtension extends CompilerExtension
{

	/**
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('connection'))
			->setClass(Connection::class);
	}


}
