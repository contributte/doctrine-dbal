<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Utils;

use Doctrine\DBAL\SQLParserUtils;

final class Compatibility
{

	/** @var bool|null */
	public static $doctrine2 = null;

	public static function isDoctrineV2(): bool
	{
		if (self::$doctrine2 === null) {
			self::$doctrine2 = class_exists(SQLParserUtils::class);
		}

		return self::$doctrine2;
	}

}
