<?php

namespace Liquido\PayIn\Model;

use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\DataObject;

use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoSendEmail;

class LiquidoWebhook
{

	private Request $request;
	private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
	private LiquidoSendEmail $sendEmail;

	public function __construct(
		Request $request,
		LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
		LiquidoSendEmail $sendEmail
	) {
		$this->request = $request;
		$this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
		$this->sendEmail = $sendEmail;
	}

	/**
	 * {@inheritdoc}
	 */
	public function processLiquidoCallbackRequest()
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

		$params = array(
			'name' => $body['data']['chargeDetails']['payer']['name'], 
			'email' => $body['data']['chargeDetails']['payer']['email'],
			'cashCode' => $body['data']['chargeDetails']['transferDetails']['payCash']['referenceNumber'], 
			'statusCode' => $body['data']['chargeDetails']['transferStatusCode']);
		$this->sendEmail->sendEmail($params, true);

		return [[
			"status" => 200,
			"message" => "received"
		]];
	}
}
