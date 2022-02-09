<?php declare(strict_types = 1);

use Ninjify\Nunjuck\Environment;
use Tests\Toolkit\DoctrineDeprecations;

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}

// Configure environment
DoctrineDeprecations::enable();
Environment::setupTester();
Environment::setupTimezone();
Environment::setupVariables(__DIR__);
