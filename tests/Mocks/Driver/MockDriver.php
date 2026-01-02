<?php declare(strict_types = 1);

namespace Tests\Mocks\Driver;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\API\SQLite\ExceptionConverter as SQLiteExceptionConverter;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\ServerVersionProvider;

final class MockDriver implements Driver
{

	public function connect(array $params): DriverConnection
	{
		throw new \RuntimeException('Not implemented');
	}

	public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
	{
		return new SQLitePlatform();
	}

	public function getExceptionConverter(): ExceptionConverter
	{
		return new SQLiteExceptionConverter();
	}

}
