<?php declare(strict_types = 1);

namespace Tests\Toolkit;

use ArrayObject;
use Doctrine\Deprecations\Deprecation;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Tester\Assert;
use Tester\Environment;
use Throwable;

final class DoctrineDeprecations
{

	/** @var bool */
	private static $enabled = false;

	/** @var string[] */
	private static $ignoredLinks = [];

	/** @var ArrayObject<int, array{file: string, line: int, package: string, link: string, message: string}> */
	private static $deprecationsLog;

	private function __construct()
	{
	}

	public static function enable(): void
	{
		if (self::$enabled) {
			return;
		}

		self::$deprecationsLog = new ArrayObject();
		Deprecation::enableWithPsrLogger(self::createLogger(self::$deprecationsLog));

		register_shutdown_function(function (): void {
			try {
				Assert::same([], self::getTriggeredDeprecations(), 'Unexpected doctrine deprecation error');
			} catch (Throwable $exception) {
				Environment::handleException($exception);
			}
		});

		self::$enabled = true;
	}

	public static function ignoreDeprecations(string ...$links): void
	{
		self::$ignoredLinks = array_merge(self::$ignoredLinks, $links);
	}

	/**
	 * @return array<int, array{file: string, line: int, package: string, link: string, message: string}>
	 */
	public static function getTriggeredDeprecations(): array
	{
		return array_values(array_filter(
			self::$deprecationsLog->getArrayCopy(),
			function (array $record): bool {
				return !in_array($record['link'], self::$ignoredLinks, true);
			}
		));
	}

	/**
	 * @param ArrayObject<int, array{file: string, line: int, package: string, link: string, message: string}> $deprecationsLog
	 */
	private static function createLogger(ArrayObject $deprecationsLog): LoggerInterface
	{
		return new class(self::$deprecationsLog) extends AbstractLogger
		{

			/** @var ArrayObject<int, array{file: string, line: int, package: string, link: string, message: string}> */
			private $log;

			/**
			 * @param ArrayObject<int, array{file: string, line: int, package: string, link: string, message: string}> $log
			 */
			public function __construct(ArrayObject $log)
			{
				$this->log = $log;
			}

			public function log($level, $message, array $context = []): void
			{
				$context['message'] = $message;
				$this->log[] = $context;
			}

		};
	}

}
