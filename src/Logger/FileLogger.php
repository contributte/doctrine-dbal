<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

final class FileLogger extends AbstractLogger
{

	public function __construct(
		private string $file
	)
	{
	}

	public function log($level, Stringable|string $message, array $context = []): void
	{
		file_put_contents($this->file, sprintf('[%s] %s {%s}', date('d.m.Y H:i:s'), $message, json_encode($context)) . "\n", FILE_APPEND);
	}

}
