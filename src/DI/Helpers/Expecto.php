<?php declare(strict_types = 1);

namespace Nettrine\DBAL\DI\Helpers;

use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class Expecto
{

	public static function port(): Schema
	{
		return Expect::anyOf(Expect::int()->dynamic(), Expect::string()->dynamic()->castTo('int'));
	}

}
