<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use stdClass;
use Tracy\Debugger;

final class TracyDumpLogger extends AbstractLogger
{

	public function stopQuery(): stdClass
	{
		$query = parent::stopQuery();

		Debugger::$maxLength = 100000;
		Debugger::barDump(['sql' => $query->sql, 'args' => $query->params], 'DBAL');
		Debugger::$maxLength = 150;

		return $query;
	}

}
