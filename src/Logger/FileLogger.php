<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

final class FileLogger extends AbstractLogger
{

	/** @var string */
	private $file;

	/**
	 * @param string $file
	 */
	public function __construct(string $file)
	{
		$this->file = $file;
	}

	/**
	 * @return void
	 */
	public function stopQuery(): void
	{
		$query = parent::stopQuery();

		file_put_contents($this->file, sprintf('[%s ms] %s', $query->ms, $query->sql) . "\n", FILE_APPEND);
	}

}
