<?php

namespace Nettrine\DBAL\Logger;

use Tracy\Debugger;

final class TracyDumpLogger extends AbstractLogger
{

	/**
	 * @return void
	 */
	public function stopQuery()
	{
		$query = parent::stopQuery();

		Debugger::barDump($query, 'DBAL');
	}

}
