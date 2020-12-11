<?php declare(strict_types = 1);

namespace Tests\Cases\Events;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Subscriber\PostConnectSubscriber;
use Tests\Toolkit\NeonLoader;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('cache', new CacheExtension());
		$compiler->addExtension('dbal', new DbalExtension());
		$compiler->addConfig(NeonLoader::load('
			dbal:
				connection:
					driver: pdo_sqlite
					host: ":memory:"

			services:
				sub1:
					class: Tests\Fixtures\Subscriber\PostConnectSubscriber
			'));
		$compiler->addConfig([
			'parameters' => [
				'tempDir' => TEMP_DIR,
			],
		]);
	}, __FILE__ . '1');

	/** @var Container $container */
	$container = new $class();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);

	/** @var PostConnectSubscriber $subscriber */
	$subscriber = $container->getService('sub1');

	Assert::type(Connection::class, $connection);
	Assert::type(PostConnectSubscriber::class, $subscriber);
	Assert::falsey($connection->isConnected());
	Assert::equal([], $subscriber->events);

	$schema = new Schema();
	$posts = $schema->createTable('posts');
	$posts->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
	$posts->addColumn('username', 'string', ['length' => 32]);

	$queries = $schema->toSql($connection->getDatabasePlatform());
	foreach ($queries as $query) {
		$connection->executeQuery($query);
	}

	Assert::true($connection->isConnected());
	Assert::notEqual([], $subscriber->events);
	Assert::same($connection, $subscriber->events[0]->getConnection());
});
