<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI\Helpers;

use Nette\DI\Definitions\Statement;
use Nettrine\DBAL\Exceptions\LogicalException;

final class SmartStatement
{

	public static function from(mixed $service): Statement
	{
		if (is_string($service)) {
			return new Statement($service);
		} elseif ($service instanceof Statement) {
			return $service;
		} else {
			throw new LogicalException('Unsupported type of service');
		}
	}

}
