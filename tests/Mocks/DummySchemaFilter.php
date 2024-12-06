<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Doctrine\DBAL\Schema\AbstractAsset;

final class DummySchemaFilter
{

	public static function filter(string|AbstractAsset $assetName): bool
	{
		return true;
	}

	public function __invoke(string|AbstractAsset $assetName): bool
	{
		return true;
	}

}
