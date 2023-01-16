<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Events;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\Common\EventManager as DoctrineEventManager;

class DebugEventManager extends DoctrineEventManager
{

	/** @var EventManager */
	private $inner;

	public function __construct(EventManager $inner)
	{
		$this->inner = $inner;
	}

	public function dispatchEvent(string $eventName, ?EventArgs $eventArgs = null): void
	{
		$this->inner->dispatchEvent($eventName, $eventArgs);
	}

	/**
	 * @return array<object>
	 */
	public function getListeners(string $event): array
	{
		return $this->inner->getListeners($event);
	}

	/**
	 * @return array<string, array<object>>
	 */
	public function getAllListeners(): array
	{
		return $this->inner->getAllListeners();
	}

	public function hasListeners(string $event): bool
	{
		return $this->inner->hasListeners($event);
	}

	/**
	 * Adds an event listener that listens on the specified events.
	 *
	 * @param string|string[] $events The event(s) to listen on.
	 * @param object $listener The listener object.
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function addEventListener($events, $listener): void
	{
		$this->inner->addEventListener($events, $listener);
	}

	/**
	 * Removes an event listener from the specified events.
	 *
	 * @param string|string[] $events
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function removeEventListener($events, object $listener): void
	{
		$this->inner->removeEventListener($events, $listener);
	}

}
