<?php

namespace Liquido\PayIn\Api;

interface LiquidoCalbackInterface
{

	/**
	 * @return object
	 */
	public function processLiquidoCallbackRequest();
}
