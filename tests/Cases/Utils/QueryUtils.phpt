<?php declare(strict_types = 1);

namespace Tests\Cases\Utils;

use Contributte\Tester\Toolkit;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Nettrine\DBAL\Utils\QueryUtils;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Test highlight basic SQL
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE id = 1';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">SELECT</strong>', $result);
	Assert::contains('<strong style="color:#2D44AD">FROM</strong>', $result);
	Assert::contains('<strong style="color:#2D44AD">WHERE</strong>', $result);
});

// Test highlight INSERT statement
Toolkit::test(function (): void {
	$sql = 'INSERT INTO users (name, email) VALUES (?, ?)';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">INSERT INTO</strong>', $result);
	Assert::contains('<strong style="color:#2D44AD">VALUES</strong>', $result);
});

// Test highlight UPDATE statement
Toolkit::test(function (): void {
	$sql = 'UPDATE users SET name = ? WHERE id = ?';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">UPDATE</strong>', $result);
	Assert::contains('<strong style="color:#2D44AD">SET</strong>', $result);
});

// Test highlight DELETE statement
Toolkit::test(function (): void {
	$sql = 'DELETE FROM users WHERE id = ?';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">DELETE</strong>', $result);
});

// Test highlight with JOIN
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users LEFT JOIN orders ON users.id = orders.user_id';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">LEFT JOIN</strong>', $result);
	Assert::contains('<strong>ON</strong>', $result);
});

// Test highlight with keywords AND, OR, IN
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE status = 1 AND role IN (1, 2) OR active = TRUE';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong>AND</strong>', $result);
	Assert::contains('<strong>OR</strong>', $result);
	Assert::contains('<strong>IN</strong>', $result);
	Assert::contains('<strong>TRUE</strong>', $result);
});

// Test highlight with comments
Toolkit::test(function (): void {
	$sql = 'SELECT /* comment */ * FROM users';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<em style="color:gray">/* comment */</em>', $result);
});

// Test highlight escapes HTML
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE name = "<script>alert(1)</script>"';
	$result = QueryUtils::highlight($sql);

	Assert::notContains('<script>', $result);
	Assert::contains('&lt;script&gt;', $result);
});

// Test getSource with empty source paths
Toolkit::test(function (): void {
	$result = QueryUtils::getSource([], []);

	Assert::equal([], $result);
});

// Test getSource with matching path
Toolkit::test(function (): void {
	$sourcePaths = [__DIR__];
	$backtrace = [
		['file' => __DIR__ . '/Service.php', 'line' => '42'],
		['file' => '/vendor/doctrine/dbal/Connection.php', 'line' => '100'],
	];

	$result = QueryUtils::getSource($sourcePaths, $backtrace);

	Assert::count(1, $result);
	Assert::contains('/Service.php', $result[0]['file']);
});

// Test getSource skips entries without file/line
Toolkit::test(function (): void {
	$sourcePaths = [__DIR__];
	$backtrace = [
		['file' => null, 'line' => null],
		['file' => __DIR__ . '/Service.php', 'line' => '42'],
	];

	$result = QueryUtils::getSource($sourcePaths, $backtrace);

	Assert::count(1, $result);
});

// Test expand with positional parameters (no conversion needed)
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE id = ?';
	$params = ['1'];
	$types = [0 => ParameterType::STRING];

	[$resultSql, $resultParams] = QueryUtils::expand($sql, $params, $types);

	Assert::equal($sql, $resultSql);
	Assert::equal($params, $resultParams);
});

// Test expand with named parameters
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE id = :id';
	$params = ['id' => '1'];
	$types = ['id' => ParameterType::STRING];

	[$resultSql] = QueryUtils::expand($sql, $params, $types);

	Assert::notEqual($sql, $resultSql);
	Assert::contains('?', $resultSql);
});

// Test expand with array parameter
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE id IN (?)';
	$params = [[1, 2, 3]];
	$types = [ArrayParameterType::INTEGER];

	[$resultSql, $resultParams] = QueryUtils::expand($sql, $params, $types);

	Assert::contains('?, ?, ?', $resultSql);
	Assert::count(3, $resultParams);
});

// Test expandSql basic replacement
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE id = ?';
	$params = ['1'];

	$result = QueryUtils::expandSql($sql, $params);

	Assert::equal('SELECT * FROM users WHERE id = 1', $result);
});

// Test expandSql with multiple parameters
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE id = ? AND name = ?';
	$params = ['1', "'John'"];

	$result = QueryUtils::expandSql($sql, $params);

	Assert::equal("SELECT * FROM users WHERE id = 1 AND name = 'John'", $result);
});

// Test expandSql preserves LIKE patterns with %
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE name LIKE ?';
	$params = ["'%john%'"];

	$result = QueryUtils::expandSql($sql, $params);

	Assert::equal("SELECT * FROM users WHERE name LIKE '%john%'", $result);
});

// Test highlight INNER JOIN
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users INNER JOIN orders ON users.id = orders.user_id';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">INNER JOIN</strong>', $result);
});

// Test highlight GROUP BY and ORDER BY
Toolkit::test(function (): void {
	$sql = 'SELECT COUNT(*) FROM users GROUP BY status ORDER BY created_at';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">GROUP BY</strong>', $result);
	Assert::contains('<strong style="color:#2D44AD">ORDER BY</strong>', $result);
});

// Test highlight LIMIT and OFFSET
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users LIMIT 10 OFFSET 20';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong style="color:#2D44AD">LIMIT</strong>', $result);
	Assert::contains('<strong style="color:#2D44AD">OFFSET</strong>', $result);
});

// Test highlight NULL and NOT NULL
Toolkit::test(function (): void {
	$sql = 'SELECT * FROM users WHERE deleted_at IS NULL AND status IS NOT NULL';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong>IS</strong>', $result);
	Assert::contains('<strong>NULL</strong>', $result);
	Assert::contains('<strong>NOT</strong>', $result);
});

// Test highlight DISTINCT
Toolkit::test(function (): void {
	$sql = 'SELECT DISTINCT name FROM users';
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong>DISTINCT</strong>', $result);
});

// Test highlight LIKE
Toolkit::test(function (): void {
	$sql = "SELECT * FROM users WHERE name LIKE '%john%'";
	$result = QueryUtils::highlight($sql);

	Assert::contains('<strong>LIKE</strong>', $result);
});
