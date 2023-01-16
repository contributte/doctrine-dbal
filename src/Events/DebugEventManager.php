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

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string $eventName
	 */
	public function dispatchEvent($eventName, ?EventArgs $eventArgs = null): void
	{
		$this->inner->dispatchEvent($eventName, $eventArgs);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string|NULL $event
	 * @return object[]|object[][]
	 */
	public function getListeners($event = null): array
	{
		return $this->inner->getListeners($event);
	}

	/**
	 * @return object[]|object[][]
	 */
	public function getAllListeners(): array
	{
		return $this->getListeners();
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @param string $event
	 */
	public function hasListeners($event): bool
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
	 * @param string|string $events
	 * @param object $listener
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function removeEventListener($events, $listener): void
	{
		$this->inner->removeEventListener($events, $listener);
	}

}
