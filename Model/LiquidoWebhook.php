<?php

namespace Liquido\PayIn\Model;

use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\DataObject;
use \Magento\Sales\Model\RefundOrder;
use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\Service\InvoiceService;
use \Magento\Sales\Model\Order\Invoice;
use \Magento\Framework\DB\Transaction;
use \Psr\Log\LoggerInterface;

use \Magento\Sales\Model\Order;

use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoSendEmail;

use \LiquidoBrl\PayInPhpSdk\Util\Colombia\PaymentMethod;

class LiquidoWebhook
{

	private Request $request;
	private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
	private LiquidoSendEmail $sendEmail;
	private RefundOrder $refundOrder;
	private ObjectManager $objectManager;
	private InvoiceService $invoiceService;
	private Transaction $transaction;
	private LoggerInterface $logger;

	public function __construct(
		Request $request,
		LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
		LiquidoSendEmail $sendEmail,
		RefundOrder $refundOrder,
		InvoiceService $invoiceService,
		Transaction $transaction,
		LoggerInterface $logger
	) {
		$this->request = $request;
		$this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
		$this->sendEmail = $sendEmail;
		$this->refundOrder = $refundOrder;
		$this->invoiceService = $invoiceService;
		$this->transaction = $transaction;
		$this->logger = $logger;
		$this->objectManager = ObjectManager::getInstance();
	}

	/**
	 * {@inheritdoc}
	 */
	public function processLiquidoCallbackRequest()
	{
		$className = static::class;
		$this->logger->info("###################### BEGIN {$className} processLiquidoCallbackRequest ######################");

		// *** TO DO: get headers from request

		$body = $this->request->getBodyParams();
		$this->logger->info("************* RESPONSE BODY *****************", (array) $body);

		$eventType = $body["eventType"];
		$this->logger->info("************* EVENT TYPE *****************", (array) $eventType);
		// if $eventType == SOMETHING { do something... }

		// if "idempotencyKey" not in $body { do something... }
		$idempotencyKey = $this->isRefund($eventType) ? $body["data"]["chargeDetails"]["referenceId"] : $body["data"]["chargeDetails"]["idempotencyKey"];

		$foundLiquidoSalesOrder = $this->liquidoSalesOrderHelper
			->findLiquidoSalesOrderByIdempotencyKey($idempotencyKey);

		$orderId = $foundLiquidoSalesOrder->getData('order_id');
		$this->logger->info("************* Webhook Order Id ************", (array) $orderId);

		$liquidoSalesOrderAlreadyExists = $orderId != null;

		if ($liquidoSalesOrderAlreadyExists) {

			// if "transferStatus" not in $body { do something... }
			$transferStatus = $this->isRefund($eventType) ? 'REFUNDED' : $body["data"]["chargeDetails"]["transferStatus"];
			$paymentMethod = $body["data"]["chargeDetails"]["paymentMethod"];

			if ($transferStatus == 'REFUNDED') {
				$this->logger->info("*************************** START REFUND *******************************");
				$refundOrder = $this->executeRefundOrder($orderId);
				$this->logger->info("************* REFUND ORDER*****************", (array) $refundOrder);
			} else {
				$this->logger->info("*************************** NOT REFUND *******************************");

				$order = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderId);

				$this->logger->info("************* ORDER INFO *************", (array) $order);

				$this->logger->info("************* ORDER CAN INVOICE *************", (array) $order->canInvoice());
				if ($order->canInvoice() || $order->getStatus() == 'pending_payment') {
					$this->logger->info("*************************** CREATE INVOICE *******************************", (array) $order);

					$invoice = $this->invoiceService->prepareInvoice($order);
					$invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
					$invoice->register();
					$invoice->save();

					$transactionSave = $this->transaction
						->addObject($invoice)
						->addObject($invoice->getOrder());
					$transactionSave->save();

					$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
					$order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
					$order->save();

					$order->addStatusHistoryComment(__('Invoice #' . $invoice->getIncrementId() . ' created automatically'))
						->setIsCustomerNotified(false)
						->save();
				}
			}

			$orderData = new DataObject(array(
				"orderId" => $orderId,
				"idempotencyKey" => $idempotencyKey,
				"transferStatus" => $transferStatus,
				"paymentMethod" => $paymentMethod
			));

			$this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);

			if ($paymentmethod == PaymentMethod::CASH) {
				$params = array(
					'name' => $body['data']['chargeDetails']['payer']['name'],
					'email' => $body['data']['chargeDetails']['payer']['email'],
					'cashCode' => $body['data']['chargeDetails']['transferDetails']['payCash']['referenceNumber'],
					'statusCode' => $body['data']['chargeDetails']['transferStatusCode']
				);
				$this->sendEmail->sendEmail($params, true);
			}
		}

		return [[
			"status" => 200,
			"message" => "received"
		]];
	}

	private function isRefund($eventType)
	{
		return $eventType === 'CHARGE_REFUND_SUCCEEDED';
	}

	private function executeRefundOrder($orderId)
	{
		return $this->refundOrder->execute($orderId);
	}
}
