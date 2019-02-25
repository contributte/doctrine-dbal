<?php declare(strict_types = 1);

namespace Tests\Nettrine\DBAL\Cases\Events;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Tests\Nettrine\DBAL\Cases\NeonLoader;
use Tests\Nettrine\DBAL\Cases\TestCase;
use Tests\Nettrine\DBAL\Fixtures\Subscriber\PostConnectSubscriber;

final class EventManagerTest extends TestCase
{

	public function testPostConnectEvent(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig(NeonLoader::load('
			dbal:
				connections:
					default:
						driver: pdo_sqlite
						host: ":memory:"
			
			services:
				sub1: 
					class: Tests\Nettrine\DBAL\Fixtures\Subscriber\PostConnectSubscriber
					tags: [nettrine.subscriber]
			'));
		}, '2' . microtime(true));

		/** @var Container $container */
		$container = new $class();

		/** @var Connection $connection */
		$connection = $container->getByType(Connection::class);

		/** @var PostConnectSubscriber $subscriber */
		$subscriber = $container->getService('sub1');

		self::assertInstanceOf(Connection::class, $connection);
		self::assertInstanceOf(PostConnectSubscriber::class, $subscriber);
		self::assertFalse($connection->isConnected());
		self::assertEmpty($subscriber->events);

		$schema = new Schema();
		$posts = $schema->createTable('posts');
		$posts->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$posts->addColumn('username', 'string', ['length' => 32]);

		$queries = $schema->toSql($connection->getDatabasePlatform());
		foreach ($queries as $query) {
			$connection->executeQuery($query);
		}

		self::assertTrue($connection->isConnected());
		self::assertNotEmpty($subscriber->events);
		self::assertSame($connection, $subscriber->events[0]->getConnection());
	}

}
