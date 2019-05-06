<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;

class PsrLogger implements SQLLogger
{

	/** @var LoggerInterface */
	private $logger;

	/** @var float */
	private $startTime;

	/** @var string */
	private $query;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @param string       $sql
	 * @param mixed[]|null $params
	 * @param mixed[]|null $types
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null): void
	{
		$this->query = $sql;
		$this->startTime = microtime(true);
	}

	public function stopQuery(): void
	{
		$time = round((microtime(true) - $this->startTime) * 1000);

		$this->logger->debug('Query: ' . $this->query, [
			'time' => sprintf('%s ms', $time),
		]);
	}

}
