<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use stdClass;

final class FileLogger extends AbstractLogger
{

	/** @var string */
	private $file;

	public function __construct(string $file)
	{
		$this->file = $file;
	}

	public function stopQuery(): stdClass
	{
		$query = parent::stopQuery();

		file_put_contents($this->file, sprintf('[%s ms] %s', $query->ms, $query->sql) . "\n", FILE_APPEND);

		return $query;
	}

}
