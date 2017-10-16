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
		ob_start(function () {
		});
		$elapsedTime = 000;
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


		//		$container = $this->container;
		//		$registry = $this->getContainerProperty('registry');
		//		$file = (new \ReflectionClass($container))->getFileName();
		//		$tags = [];
		//		$meta = $this->getContainerProperty('meta');
		//		$services = $meta[Container::SERVICES];
		//		ksort($services);
		//		if (isset($meta[Container::TAGS])) {
		//			foreach ($meta[Container::TAGS] as $tag => $tmp) {
		//				foreach ($tmp as $service => $val) {
		//					$tags[$service][$tag] = $val;
		//				}
		//			}
		//		}

		ob_start(function () {
		});
		$params = $this->connection->getParams();
		require __DIR__ . '/templates/panel.phtml';
		return ob_get_clean();
	}

}
