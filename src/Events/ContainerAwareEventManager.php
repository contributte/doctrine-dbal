<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Events;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager as DoctrineEventManager;
use Nette\DI\Container;
use RuntimeException;

class ContainerAwareEventManager extends DoctrineEventManager
{

	/** @var Container */
	protected $container;

	/** @var mixed[boolean[]] */
	protected $initialized = [];

	/** @var mixed[EventSubscriber[]] */
	protected $listeners = [];

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @param string $eventName
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function dispatchEvent($eventName, ?EventArgs $eventArgs = null): void
	{
		if (isset($this->listeners[$eventName])) {
			$eventArgs = $eventArgs === null ? EventArgs::getEmptyInstance() : $eventArgs;

			$initialized = isset($this->initialized[$eventName]);

			foreach ($this->listeners[$eventName] as $hash => $listener) {
				if (!$initialized && is_string($listener)) {
					$this->listeners[$eventName][$hash] = $listener = $this->container->getService($listener);
				}

				$listener->$eventName($eventArgs);
			}

			$this->initialized[$eventName] = true;
		}
	}

	/**
	 * @param string|NULL $event
	 * @return object[]
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getListeners($event = null): array
	{
		return $event ? $this->listeners[$event] : $this->listeners;
	}

	/**
	 * @param string $event
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function hasListeners($event): bool
	{
		return !empty($this->listeners[$event]);
	}

	/**
	 * Adds an event listener that listens on the specified events.
	 *
	 * @param string|string[] $events The event(s) to listen on.
	 * @param string|object $listener The listener object.
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function addEventListener($events, $listener): void
	{
		if (is_string($listener)) {
			if ($this->initialized) {
				throw new RuntimeException('Adding lazy-loading listeners after construction is not supported.');
			}
			$hash = 'service@' . $listener;
		} else {
			// Picks the hash code related to that listener
			$hash = spl_object_hash($listener);
		}
		foreach ((array) $events as $event) {
			// Overrides listener if a previous one was associated already
			// Prevents duplicate listeners on same event (same instance only)
			$this->listeners[$event][$hash] = $listener;
		}
	}

	/**
	 * Removes an event listener from the specified events.
	 *
	 * @param string|string[] $events
	 * @param string|object $listener
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function removeEventListener($events, $listener): void
	{
		if (is_string($listener)) {
			$hash = 'service@' . $listener;
		} else {
			// Picks the hash code related to that listener
			$hash = spl_object_hash($listener);
		}
		foreach ((array) $events as $event) {
			// Check if actually have this listener associated
			if (isset($this->listeners[$event][$hash])) {
				unset($this->listeners[$event][$hash]);
			}
		}
	}

}
