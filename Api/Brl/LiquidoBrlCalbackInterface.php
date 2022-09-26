<?php

namespace Liquido\PayIn\Api\Brl;

interface LiquidoBrlCalbackInterface
{

	/**
	 * @return object
	 */
	public function processLiquidoBrlCallbackRequest();
}
