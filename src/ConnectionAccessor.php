<?php declare(strict_types = 1);

namespace Nettrine\DBAL;

use Doctrine\DBAL\Connection;

interface ConnectionAccessor
{

	public function get(): Connection;

}
