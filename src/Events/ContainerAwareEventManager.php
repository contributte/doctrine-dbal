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

	/** @var bool[] */
	protected $initialized = [];

	/** @var string[][]|object[][] */
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
		$eventArgs = $eventArgs ?? EventArgs::getEmptyInstance();

		foreach ($this->getInitializedListeners($eventName) as $hash => $listener) {
			$listener->$eventName($eventArgs);
		}
	}

	/**
	 * @param string|null $event
	 * @return object[]|object[][]
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getListeners($event = null): array
	{
		if ($event !== null) {
			return $this->getInitializedListeners($event);
		}

		$stack = [];

		foreach ($this->listeners as $eventName => $listeners) {
			$stack[$eventName] = $this->getInitializedListeners($eventName);
		}

		return $stack;
	}

	/**
	 * @return object[]|object[][]
	 */
	public function getAllListeners(): array
	{
		return $this->getListeners();
	}

	/**
	 * @return object[]
	 */
	private function getInitializedListeners(string $event): array
	{
		$initialized = $this->initialized[$event] ?? false;

		if ($initialized) {
			return $this->listeners[$event] ?? [];
		}

		foreach ($this->listeners[$event] ?? [] as $hash => $listener) {
			if (!is_object($listener)) {
				$this->listeners[$event][$hash] = $this->container->getService($listener);
			}
		}

		$this->initialized[$event] = true;

		return $this->listeners[$event] ?? [];
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
	 * @param string|object   $listener The listener object.
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function addEventListener($events, $listener): void
	{
		if (!is_object($listener)) {
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
	 * @param string|string[]   $events
	 * @param string|int|object $listener
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function removeEventListener($events, $listener): void
	{
		$hash = !is_object($listener)
			? 'service@' . $listener
			: spl_object_hash($listener);
		foreach ((array) $events as $event) {
			// Check if actually have this listener associated
			if (isset($this->listeners[$event][$hash])) {
				unset($this->listeners[$event][$hash]);
			}
		}
	}

}
