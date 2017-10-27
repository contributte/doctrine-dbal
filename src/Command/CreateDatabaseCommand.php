<?php

namespace Nettrine\DBAL\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database tool allows you to easily drop and create your configured databases.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class CreateDatabaseCommand extends DoctrineCommand
{

	/**
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('doctrine:database:create')
			->setDescription('Creates the configured database')
			->addOption('shard', NULL, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command')
			->addOption('connection', NULL, InputOption::VALUE_OPTIONAL, 'The connection to use for this command')
			->addOption(
				'if-not-exists',
				NULL,
				InputOption::VALUE_NONE,
				'Don\'t trigger an error, when the database already exists'
			)
			->setHelp('The <info>%command.name%</info> command creates the default connections database:
    <info>php %command.full_name%</info>
You can also optionally specify the name of a connection to create the database for:
    <info>php %command.full_name% --connection=default</info>');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$connection = $this->getDoctrineConnection($input->getOption('connection'));
		$ifNotExists = $input->getOption('if-not-exists');
		$params = $connection->getParams();
		if (isset($params['master'])) {
			$params = $params['master'];
		}
		// Cannot inject `shard` option in parent::getDoctrineConnection
		// cause it will try to connect to a non-existing database
		if (isset($params['shards'])) {
			$shards = $params['shards'];
			// Default select global
			$params = array_merge($params, $params['global']);
			unset($params['global']['dbname']);
			if ($input->getOption('shard')) {
				foreach ($shards as $i => $shard) {
					if ($shard['id'] === (int) $input->getOption('shard')) {
						// Select sharded database
						$params = array_merge($params, $shard);
						unset($params['shards'][$i]['dbname'], $params['id']);
						break;
					}
				}
			}
		}
		$hasPath = isset($params['path']);
		$name = $hasPath ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : FALSE);
		if (!$name) {
			throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
		}
		// Need to get rid of _every_ occurrence of dbname from connection configuration and we have already extracted all relevant info from url
		unset($params['dbname'], $params['path'], $params['url']);
		$tmpConnection = DriverManager::getConnection($params);
		$tmpConnection->connect($input->getOption('shard'));
		$shouldNotCreateDatabase = $ifNotExists && in_array($name, $tmpConnection->getSchemaManager()->listDatabases());
		// Only quote if we don't have a path
		if (!$hasPath) {
			$name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
		}
		$error = FALSE;
		try {
			if ($shouldNotCreateDatabase) {
				$output->writeln(sprintf(
					'<info>Database <comment>%s</comment> for connection named <comment>%s</comment> already exists. Skipped.</info>',
					$name,
					$input->getOption('connection')
				));
			} else {
				$tmpConnection->getSchemaManager()->createDatabase($name);
				$output->writeln(sprintf(
					'<info>Created database <comment>%s</comment> for connection named <comment>%s</comment></info>',
					$name,
					$input->getOption('connection')
				));
			}
		} catch (\Exception $e) {
			$output->writeln(sprintf(
				'<error>Could not create database <comment>%s</comment> for connection named <comment>%s</comment></error>',
				$name,
				$input->getOption('connection')
			));
			$output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
			$error = TRUE;
		}
		$tmpConnection->close();
		return $error ? 1 : 0;
	}

}
