<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI\Helpers;

use Nette\DI\Definitions\Definition;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\DBAL\DI\Pass\AbstractPass;

final class BuilderMan
{

	private AbstractPass $pass;

	private function __construct(AbstractPass $pass)
	{
		$this->pass = $pass;
	}

	public static function of(AbstractPass $pass): self
	{
		return new self($pass);
	}

	/**
	 * @return array<string, string>
	 */
	public function getConnections(): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		/** @var array<string, array{name: string}> $connections */
		$connections = $builder->findByTag(DbalExtension::CONNECTION_TAG);

		foreach ($connections as $serviceName => $tagValue) {
			$definitions[$tagValue['name']] = $serviceName;
		}

		return $definitions;
	}

	/**
	 * @return array<string, Definition>
	 */
	public function getServiceDefinitionsByTag(string $tag): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		foreach ($builder->findByTag($tag) as $serviceName => $tagValue) {
			$definitions[(string) $tagValue] = $builder->getDefinition($serviceName);
		}

		return $definitions;
	}

	/**
	 * @return array<string, string>
	 */
	public function getServiceNamesByTag(string $tag): array
	{
		$builder = $this->pass->getContainerBuilder();
		$definitions = [];

		foreach ($builder->findByTag($tag) as $serviceName => $tagValue) {
			$definitions[(string) $tagValue] = $serviceName;
		}

		return $definitions;
	}

}
