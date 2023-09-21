<?php

namespace Liquido\PayIn\Plugin;

use \Magento\Framework\App\ObjectManager;

use \Liquido\PayIn\Logger\Logger;
use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;

use \LiquidoBrl\PayInPhpSdk\Util\Brazil\PaymentMethod as BrazilPaymentMethod;
use \LiquidoBrl\PayInPhpSdk\Util\Common\PaymentMethod as CommonPaymentMethod;

class BankAccountInfoForRefund
{
    protected Logger $logger;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private $objectManager;
    private $orderInfo;

    public function __construct(
        Logger $logger,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper
    )
    {
        $this->logger = $logger;
        $this->objectManager = ObjectManager::getInstance(); 
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
    }

    public function beforeSetLayout(
        \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items $subject,
        $layout
    ) {
        $this->logger->info("Before set Layout");

        $orderId = $subject->getCreditmemo()->getOrderId();
        $this->orderInfo = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByAttribute('entity_id', $orderId);
        $incrementId = $this->orderInfo->getIncrementId();
        $paymentInfo = $this->liquidoSalesOrderHelper->getPaymentInfoByOrderId($incrementId);

        if ($paymentInfo['payment_method'] == BrazilPaymentMethod::BOLETO || $paymentInfo['payment_method'] == CommonPaymentMethod::CASH)
        {
            $subject->unsetChild("submit_button");

            $subject->addChild(
                'submit_button',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'id' => 'sendordersms',
                    'label' => __('Refund Offline'),
                    'onclick' => "",
                    'class' => 'action-default submit-button primary',
                    'data-var' => 'creditmemo-data'
                ]
            );
        }
        else
        {
            $subject->addChild(
                'submit_button',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund Offline'),
                    'class' => 'save submit-button primary',
                    'onclick' => 'submitCreditMemoOffline()'
                ]
            );
        }

        return [$layout];
    }

    public function afterToHtml(
        \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items $subject,
        $result
    ) {
        if($subject->getNameInLayout() == 'order_items'){
            $customBlockHtml = $subject->getLayout()->createBlock(
                \Liquido\PayIn\Block\Adminhtml\Order\Creditmemo\Create\RefundModalBox::class,
                $subject->getNameInLayout().'_modal_box'
            )->setOrder($subject->getOrder())
                ->setTemplate('Liquido_PayIn::order/creditmemo/create/refundmodalbox.phtml')
                ->toHtml();
            return $result.$customBlockHtml;
        }
        return $result;
    }
}