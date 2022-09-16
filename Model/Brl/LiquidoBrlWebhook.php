<?php

namespace Liquido\PayIn\Model\Brl;

use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\DataObject;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlSalesOrderHelper;

class LiquidoBrlWebhook
{

	private Request $request;
	private LiquidoBrlSalesOrderHelper $liquidoSalesOrderHelper;

	public function __construct(
		Request $request,
		LiquidoBrlSalesOrderHelper $liquidoSalesOrderHelper
	) {
		$this->request = $request;
		$this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
	}

	/**
	 * {@inheritdoc}
	 */
	public function processLiquidoBrlCallbackRequest()
	{

		// *** TO DO: get headers from request

		$body = $this->request->getBodyParams();

		$eventType = $body["eventType"];
		// if $eventType == SOMETHING { do something... }

		// if "idempotencyKey" not in $body { do something... }
		$idempotencyKey = $body["data"]["chargeDetails"]["idempotencyKey"];

		$foundLiquidoSalesOrder = $this->liquidoSalesOrderHelper
			->findLiquidoSalesOrderByIdempotencyKey($idempotencyKey);

		$orderId = $foundLiquidoSalesOrder->getData('order_id');
		$liquidoSalesOrderAlreadyExists = $orderId != null;

		if ($liquidoSalesOrderAlreadyExists) {

			// if "transferStatus" not in $body { do something... }
			$transferStatus = $body["data"]["chargeDetails"]["transferStatus"];
			$paymentMethod = $body["data"]["chargeDetails"]["paymentMethod"];

			$orderData = new DataObject(array(
				"orderId" => $orderId,
				"idempotencyKey" => $idempotencyKey,
				"transferStatus" => $transferStatus,
				"paymentMethod" => $paymentMethod
			));

			$this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
		}

		return [[
			"status" => 200,
			"message" => "received"
		]];
	}
}
