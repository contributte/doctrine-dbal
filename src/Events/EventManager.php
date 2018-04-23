<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Events;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager as DoctrineEventManager;
use Nette\DI\Container;

class EventManager extends DoctrineEventManager
{

	/** @var Container */
	protected $container;

	/** @var mixed[] */
	protected $lazy = [];

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string $eventName
	 * @param EventArgs|NULL $eventArgs
	 * @return void
	 */
	public function dispatchEvent($eventName, ?EventArgs $eventArgs = NULL): void
	{
		$this->loadLazy($eventName);
		parent::dispatchEvent($eventName, $eventArgs);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string|NULL $event
	 * @return object[]
	 */
	public function getListeners($event = NULL): array
	{
		if ($event) {
			$this->loadLazy($event);
		} else {
			$this->loadLazyAll();
		}

		return parent::getListeners($event);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string $event
	 * @return bool
	 */
	public function hasListeners($event): bool
	{
		return parent::hasListeners($event) || !empty($this->lazy[$event]);
	}

	/**
	 * @param string[] $events
	 * @param string $serviceName
	 * @return void
	 */
	public function addLazyEventListener(array $events, string $serviceName): void
	{
		foreach ($events as $event) {
			if (!isset($this->lazy[$event])) {
				$this->lazy[$event] = [];
			}
			$this->lazy[$event] = $serviceName;
		}
	}

	/**
	 * @param string $event
	 * @return void
	 */
	protected function loadLazy(string $event): void
	{
		if (empty($this->lazy[$event])) return;

		foreach ($this->lazy[$event] as $service) {
			$this->addEventListener($event, $this->container->getService($service));
		}

		unset($this->lazy[$event]);
	}

	/**
	 * @return void
	 */
	protected function loadLazyAll(): void
	{
		foreach ($this->lazy as $event => $services) {
			foreach ($services as $service) {
				$this->addEventListener($event, $this->container->getService($service));
			}
			unset($this->lazy[$event]);
		}
	}

}
