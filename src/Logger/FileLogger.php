<?php

namespace App\Model\Database\Logger;

final class FileLogger extends AbstractLogger
{

	/** @var string */
	private $file;

	/**
	 * @param string $file
	 */
	public function __construct($file)
	{
		$this->file = $file;
	}

	/**
	 * @return void
	 */
	public function stopQuery()
	{
		$query = parent::stopQuery();

		file_put_contents($this->file, sprintf('[%s ms] %s', $query->ms, $query->sql) . "\n", FILE_APPEND);
	}

}
