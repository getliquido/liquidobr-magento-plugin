<?php

namespace Liquido\PayIn\Model;


use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\DataObject;
use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Invoice;
use \Magento\Sales\Model\Service\InvoiceService;
use \Magento\Framework\DB\Transaction;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoSendEmail;

use \LiquidoBrl\PayInPhpSdk\Util\Colombia\PaymentMethod;

class LiquidoWebhook
{

	private Request $request;
	private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
	private LiquidoSendEmail $sendEmail;
	private ObjectManager $objectManager;
	private InvoiceService $invoiceService;
	private Transaction $transaction;
	private LoggerInterface $logger;

	public function __construct(
		Request $request,
		LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
		LiquidoSendEmail $sendEmail,
		InvoiceService $invoiceService,
		Transaction $transaction,
		LoggerInterface $logger
	) {
		$this->request = $request;
		$this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
		$this->sendEmail = $sendEmail;
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

		$eventType = $body["eventType"];
		// if $eventType == SOMETHING { do something... }

		// if "idempotencyKey" not in $body { do something... }
		$idempotencyKey = $this->getIdempotencyKey($body);

		$foundLiquidoSalesOrder = $this->liquidoSalesOrderHelper
			->findLiquidoSalesOrderByIdempotencyKey($idempotencyKey);

		$orderId = $foundLiquidoSalesOrder->getData('order_id');

		$liquidoSalesOrderAlreadyExists = $orderId != null;

		if ($liquidoSalesOrderAlreadyExists) 
		{
			// if "transferStatus" not in $body { do something... }
			$transferStatus = $this->isRefund($eventType) ? 'REFUNDED' : $body["data"]["chargeDetails"]["transferStatus"];
			$paymentMethod = $body["data"]["chargeDetails"]["paymentMethod"];
			$orderData = new DataObject(array(
				"orderId" => $orderId,
				"idempotencyKey" => $idempotencyKey,
				"transferStatus" => $transferStatus,
				"paymentMethod" => $paymentMethod
			));

			$order = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderId);

			if ($transferStatus == 'REFUNDED') 
			{
				$refund = $this->refundOrder($order);
				$this->logger->info("Refund response {$refund}");
				$this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
				$order->setState(Order::STATE_CLOSED)->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
				$order->save();
			} 
			else 
			{
				if ($order->canInvoice()) 
				{
					$invoice = $this->invoiceService->prepareInvoice($order);
					$invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
					$invoice->register();
					$invoice->save();

					$transactionSave = $this->transaction
						->addObject($invoice)
						->addObject($invoice->getOrder());
					$transactionSave->save();

					$order->addStatusHistoryComment(__('Invoice #' . $invoice->getIncrementId() . ' created automatically'))
						->setIsCustomerNotified(false)
						->save();	

					$this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
				}
			}

			/*if ($paymentMethod == PaymentMethod::CASH) 
			{
				$params = array(
					'name' => $body['data']['chargeDetails']['payer']['name'],
					'email' => $body['data']['chargeDetails']['payer']['email'],
					'cashCode' => $body['data']['chargeDetails']['transferDetails']['payCash']['referenceNumber'],
					'statusCode' => $body['data']['chargeDetails']['transferStatusCode']
				);
				$this->sendEmail->sendEmail($params, true);
			}*/
		}

		$this->logger->info("###################### END {$className} processLiquidoCallbackRequest ######################");

		return [[
			"status" => 200,
			"message" => "received"
		]];
	}

	private function isRefund($eventType)
	{
		return $eventType === 'CHARGE_REFUND_SUCCEEDED';
	}

	private function getIdempotencyKey($bodyInfo)
	{
		if ($this->isRefund($bodyInfo["eventType"])) {
			return $bodyInfo["data"]["chargeDetails"]["referenceId"];
		} else {
			return $bodyInfo["data"]["chargeDetails"]["idempotencyKey"];
		}
	}

	private function refundOrder($order)
	{
		try {
			$creditMemoFactory = $this->objectManager->create('Magento\Sales\Model\Order\CreditmemoFactory');
			$creditmemoService = $this->objectManager->create('Magento\Sales\Model\Service\CreditmemoService');
			$creditmemo = $creditMemoFactory->createByOrder($order);
			foreach ($creditmemo->getAllItems() as $creditmemoItem)
			{
				$creditmemoItem->setBackToStock(true);
			}
			$creditmemoService->refund($creditmemo);

			$this->logger->info("Refunded");
		} catch (\Exception $e) {
			$this->logger->error("Failed".$e);
		}
	}
}
