<?php declare(strict_types = 1);

namespace Tests\Fixtures\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Tests\Fixtures\Driver\TestDriver;

final class TestMiddleware implements Middleware
{

	public function wrap(Driver $driver): Driver
	{
		return new TestDriver($driver);
	}

}
