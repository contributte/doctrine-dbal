<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Middleware\Debug;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

/**
 * @see https://github.com/symfony/doctrine-bridge
 * @internal
 */
final class DebugMiddleware implements MiddlewareInterface
{

	public function __construct(
		private DebugStack $stack,
		private string $connectionName = 'default',
	)
	{
	}

	public function wrap(DriverInterface $driver): DriverInterface
	{
		return new DebugDriver($driver, $this->stack, $this->connectionName);
	}

}
