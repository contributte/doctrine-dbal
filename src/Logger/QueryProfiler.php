<?php declare(strict_types = 1);

namespace Nettrine\DBAL\Logger;

use Tracy\IBarPanel;
use function count;
use function sprintf;

class QueryProfiler extends AbstractLogger implements IBarPanel
{

	/**
	 * @return string
	 */
	public function getTab(): string
	{
		return '<span title="Doctrine 2">'
			. '<svg viewBox="0 0 2048 2048"><path fill="#aaa" d="M1024 896q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"></path></svg>'
			. '<span class="tracy-label">'
			. count($this->queries) . ' queries'
			. ($this->totalTime ? ' / ' . sprintf('%0.1f', $this->totalTime * 1000) . ' ms' : '')
			. '</span>'
			. '</span>';
	}

	/**
	 * @return string
	 */
	public function getPanel(): string
	{
		if (empty($this->queries)) {
			return '';
		}

		return sprintf(
			'<h1>Queries: %s / %s</h1>',
			count($this->queries),
			($this->totalTime ? ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : '')
		);
	}

}
