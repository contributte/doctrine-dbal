<?php declare(strict_types = 1);

namespace Tests\Cases\Events;

use Contributte\Tester\Toolkit;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Events;
use Mockery;
use Nette\DI\Container;
use Nettrine\DBAL\Events\ContainerEventManager;
use Tester\Assert;
use Tests\Fixtures\Events\DummyOnClearSubscriber;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$entityManager = Mockery::mock(EntityManager::class);

	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	$subscriber = new DummyOnClearSubscriber();
	$eventManager->addEventSubscriber($subscriber);

	$event = new OnClearEventArgs($entityManager, 'foo');

	Assert::count(0, $subscriber->events);
	$eventManager->dispatchEvent(Events::onClear, $event);
	Assert::count(1, $subscriber->events);

	Assert::same([$event], $subscriber->events);
});

Toolkit::test(function (): void {
	$entityManager = Mockery::mock(EntityManager::class);
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$container->addService('dummySubscriber', $subscriber);
	$eventManager = new ContainerEventManager($container);

	$eventManager->addServiceSubscriber(Events::onClear, 'dummySubscriber');

	$event = new OnClearEventArgs($entityManager, 'foo');

	Assert::count(0, $subscriber->events);
	$eventManager->dispatchEvent(Events::onClear, $event);
	Assert::count(1, $subscriber->events);

	Assert::same([$event], $subscriber->events);
});
