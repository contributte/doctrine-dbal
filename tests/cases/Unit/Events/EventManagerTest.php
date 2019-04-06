<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\Events;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Tests\Fixtures\Subscriber\PostConnectSubscriber;
use Tests\Toolkit\NeonLoader;
use Tests\Toolkit\TestCase;

final class EventManagerTest extends TestCase
{

	public function testPostConnectEvent(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
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
		}, '2' . microtime(true));

		/** @var Container $container */
		$container = new $class();

		/** @var Connection $connection */
		$connection = $container->getByType(Connection::class);

		/** @var PostConnectSubscriber $subscriber */
		$subscriber = $container->getService('sub1');

		$this->assertInstanceOf(Connection::class, $connection);
		$this->assertInstanceOf(PostConnectSubscriber::class, $subscriber);
		$this->assertFalse($connection->isConnected());
		$this->assertEmpty($subscriber->events);

		$schema = new Schema();
		$posts = $schema->createTable('posts');
		$posts->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$posts->addColumn('username', 'string', ['length' => 32]);

		$queries = $schema->toSql($connection->getDatabasePlatform());
		foreach ($queries as $query) {
			$connection->executeQuery($query);
		}

		$this->assertTrue($connection->isConnected());
		$this->assertNotEmpty($subscriber->events);
		$this->assertSame($connection, $subscriber->events[0]->getConnection());
	}

}
