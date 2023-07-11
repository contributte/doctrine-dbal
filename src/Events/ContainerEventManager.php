<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Events;

use Doctrine\Common\EventManager;
use Nette\DI\Container;

class ContainerEventManager extends EventManager
{

	protected Container $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @param string|string[] $events
	 */
	public function addServiceSubscriber(string|array $events, string $service): void
	{
		$this->addEventListener($events, new class($this->container, $service) {

			public function __construct(
				private readonly Container $container,
				private readonly string $service
			)
			{
			}

			/**
			 * @param mixed[] $arguments
			 */
			public function __call(string $name, array $arguments): mixed
			{
				$subscriber = $this->container->getByName($this->service);

				$callback = [$subscriber, $name];
				assert(is_callable($callback));

				return call_user_func_array($callback, $arguments);
			}

		});
	}

}
