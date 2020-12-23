<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Utils;

use Doctrine\DBAL\Query\QueryBuilder;
use Nette\Utils\Paginator;

final class OracleUtils
{

	public static function limitSql(QueryBuilder $builder, Paginator $paginator): string
	{
		$sql = $builder->getSQL();

		$sql1 = sprintf(
			'SELECT a.*, ROWNUM AS doctrine_rownum FROM (%s) a',
			$sql
		);

		return sprintf(
			'SELECT * FROM (%s) WHERE doctrine_rownum BETWEEN %d AND %d',
			$sql1,
			$paginator->offset,
			$paginator->offset + $paginator->length
		);
	}

}
