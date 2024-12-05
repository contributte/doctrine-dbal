<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI\Pass;

use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\DBAL\ConnectionFactory;
use Nettrine\DBAL\ConnectionProvider;
use Nettrine\DBAL\DI\Helpers\BuilderMan;

class DoctrinePass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$builder = $this->extension->getContainerBuilder();
		$config = $this->getConfig();

		// ConnectionFactory
		$builder->addDefinition($this->prefix('connectionFactory'))
			->setFactory(ConnectionFactory::class, [$config->types ?? [], $config->typesMapping ?? []]);

		// ConnectionProvider
		$builder->addDefinition($this->prefix('connectionProvider'))
			->setFactory(ConnectionProvider::class, [
				$builder->getDefinition('container'),
				[],
			]);
	}

	public function beforePassCompile(): void
	{
		$builder = $this->extension->getContainerBuilder();

		$connectionProviderDef = $builder->getDefinition($this->prefix('connectionProvider'));
		assert($connectionProviderDef instanceof ServiceDefinition);

		// ConnectionProvider: build connection map (name => service)
		$connectionProviderDef->setArgument(1, BuilderMan::of($this)->getConnections());
	}

}
