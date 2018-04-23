<?php declare(strict_types = 1);

namespace Tests\Nettrine\DBAL\Events;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Tests\Fixtures\Subscriber\PostConnectSubscriber;
use Tests\Nettrine\DBAL\NeonLoader;
use Tests\Nettrine\DBAL\TestCase;

final class EventManagerTest extends TestCase
{

	/**
	 * @return void
	 */
	public function testPostConnectEvent(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, TRUE);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig(NeonLoader::load('
			dbal:
				connection:
					driver: pdo_sqlite
					host: ":memory:"
			
			services:
				sub1: 
					class: Tests\Fixtures\Subscriber\PostConnectSubscriber
					tags: [nettrine.subscriber]
			'));
		}, '2' . microtime(TRUE));

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
		$posts->addColumn('id', 'integer', ['unsigned' => TRUE, 'autoincrement' => TRUE]);
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
