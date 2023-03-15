<?php

namespace Liquido\Payin\Plugin\Adminhtml;

use Psr\Log\LoggerInterface;

use Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use Liquido\PayIn\Helper\LiquidoConfigData;
use Liquido\PayIn\Util\MagentoSaleOrderStatus;
use LiquidoBrl\PayInPhpSdk\Util\PayInStatus;
use LiquidoBrl\PayInPhpSdk\Util\Common\PaymentMethod as CommonPaymentMethod;
use LiquidoBrl\PayInPhpSdk\Util\Brazil\PaymentMethod as BrazilPaymentMethod;
use LiquidoBrl\PayInPhpSdk\Util\Country;

class AddRefundButton
{
    private LoggerInterface $logger;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private LiquidoConfigData $liquidoConfigData;

    public function __construct(
        LoggerInterface $logger,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
        LiquidoConfigData $liquidoConfigData
    ) {
        $this->logger = $logger;
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
        $this->liquidoConfigData = $liquidoConfigData;
    }

    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        $className = static::class;
        $this->logger->info("[ {$className} ]: beforePushButtons.");

        if ($context->getRequest()->getFullActionName() == 'sales_order_view') {

            $orderId = $context->getRequest()->getParam('order_id');
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
            $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderId);
            $orderId = $order->getIncrementId();
            $orderStatus = $order->getStatus();

            $paymentInfo = $this->liquidoSalesOrderHelper->getPaymentInfoByOrderId($orderId);

            if (
                $orderStatus == MagentoSaleOrderStatus::COMPLETE
                    && $paymentInfo['transfer_status'] == PayInStatus::SETTLED
                        && ($paymentInfo['payment_method'] == CommonPaymentMethod::CREDIT_CARD
                            || $paymentInfo['payment_method'] == BrazilPaymentMethod::PIX_STATIC_QR)
                                && $this->liquidoConfigData->getCountry() == Country::BRAZIL
            ) {
                $message = __("Confirma a solicitação de reembolso?");
                $url = $context->getUrl('liquido_payin/liquido/refundorder', ['order_id' => $orderId]);
                $buttonList->add(
                    'customButton',
                    [
                        'label' => __('Solicitar Reembolso'),
                        'onclick' => 'confirmSetLocation("' . $message . '", "' . $url . '")',
                        'class' => 'reset'
                    ],
                    -1
                );
            }
        }
    }
}
