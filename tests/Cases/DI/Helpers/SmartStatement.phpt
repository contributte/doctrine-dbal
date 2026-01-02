<?php declare(strict_types = 1);

namespace Tests\Cases\DI\Helpers;

use Contributte\Tester\Toolkit;
use Nette\DI\Definitions\Statement;
use Nettrine\DBAL\DI\Helpers\SmartStatement;
use Nettrine\DBAL\Exceptions\LogicalException;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Test from with string class name
Toolkit::test(function (): void {
	$result = SmartStatement::from('SomeClass');

	Assert::type(Statement::class, $result);
	Assert::equal('SomeClass', $result->getEntity());
});

// Test from with Statement object
Toolkit::test(function (): void {
	$statement = new Statement('SomeClass', ['arg1']);
	$result = SmartStatement::from($statement);

	Assert::same($statement, $result);
});

// Test from with invalid type throws exception
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		SmartStatement::from(123);
	}, LogicalException::class, 'Unsupported type of service');
});

// Test from with null throws exception
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		SmartStatement::from(null);
	}, LogicalException::class, 'Unsupported type of service');
});

// Test from with array throws exception
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		SmartStatement::from(['class' => 'SomeClass']);
	}, LogicalException::class, 'Unsupported type of service');
});

// Test from with service reference string
Toolkit::test(function (): void {
	$result = SmartStatement::from('@someService');

	Assert::type(Statement::class, $result);
	// Service references are converted to Reference objects by Statement
	Assert::type('Nette\DI\Definitions\Reference', $result->getEntity());
});

// Test from with empty string
Toolkit::test(function (): void {
	$result = SmartStatement::from('');

	Assert::type(Statement::class, $result);
	Assert::equal('', $result->getEntity());
});

// Test from with fully qualified class name
Toolkit::test(function (): void {
	$result = SmartStatement::from('Nettrine\\DBAL\\Cache\\NullCache');

	Assert::type(Statement::class, $result);
	Assert::equal('Nettrine\\DBAL\\Cache\\NullCache', $result->getEntity());
});

// Test Statement preserves arguments
Toolkit::test(function (): void {
	$statement = new Statement('SomeClass', ['arg1', 'arg2', 'arg3']);
	$result = SmartStatement::from($statement);

	Assert::same(['arg1', 'arg2', 'arg3'], $result->arguments);
});
