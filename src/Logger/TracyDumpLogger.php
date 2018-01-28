<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Tracy\Debugger;

final class TracyDumpLogger extends AbstractLogger
{

	/**
	 * @return void
	 */
	public function stopQuery(): void
	{
		$query = parent::stopQuery();
		Debugger::barDump($query, 'DBAL');
	}

}
