<?php declare(strict_types = 1);

namespace Tests\Fixtures\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

final class PostConnectSubscriber implements EventSubscriber
{

	/** @var ConnectionEventArgs[] */
	public array $events = [];

	public function postConnect(ConnectionEventArgs $args): void
	{
		$this->events[] = $args;
	}

	/**
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [Events::postConnect];
	}

}
