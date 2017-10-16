<?php

namespace Nettrine\DBAL\Tracy;

use Doctrine\DBAL\Connection;
use Tracy\IBarPanel;

class ConnectionPanel implements IBarPanel
{

	/** @var Connection */
	private $connection;

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Renders tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		ob_start();
		$connected = $this->connection->isConnected();
		require __DIR__ . '/templates/tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Renders panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		ob_start();
		$parameters = $this->connection->getParams();
		$parameters['password'] = '****';
		$connected = $this->connection->isConnected();
		require __DIR__ . '/templates/panel.phtml';
		return ob_get_clean();
	}

}
