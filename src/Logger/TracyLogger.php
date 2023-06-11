<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Psr\Log\AbstractLogger;
use Stringable;
use Tracy\Debugger;
use Tracy\Dumper;

class TracyLogger extends AbstractLogger
{

	public function log($level, Stringable|string $message, array $context = []): void
	{
		Debugger::barDump(
			['message' => $message, 'context' => $context],
			'DBAL',
			[Dumper::TRUNCATE => 10000]
		);
	}

}
