<?php

namespace Liquido\PayIn\Helper;

use \GuzzleHttp\Client;
use \SendinBlue\Client\Configuration;
use \SendinBlue\Client\Api\TransactionalEmailsApi;
use \SendinBlue\Client\Model\SendSmtpEmail;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Util\SendEmail\LiquidoEmailHtmlCSS;

class LiquidoSendEmail
{
    private LoggerInterface $logger;
    private LiquidoConfigData $liquidoConfig;
    private $scopeConfig;
    private LiquidoEmailHtmlCSS $liquidoEmailHtmlCSS;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        LiquidoConfigData $liquidoConfig,
        LiquidoEmailHtmlCSS $liquidoEmailHtmlCSS
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoEmailHtmlCSS = $liquidoEmailHtmlCSS;
    }

    private function getApiKey()
    {
        try {
            $apiKey = $this->liquidoConfig->getEmailSecretKey();
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            return $config;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function sendEmail($params = array(), $isWebhookUpdate = false)
    {
        $config = $this->getApiKey();
        $apiInstance = new TransactionalEmailsApi(
            new Client(),
            $config
        );

        $senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $senderName = $this->scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail['params'] = $params;
        if (!$isWebhookUpdate) {
            $sendSmtpEmail['subject'] = 'Referência de Pago de Efectivo - Liquido Pay';
            $sendSmtpEmail['htmlContent'] = $this->liquidoEmailHtmlCSS->getEmailHtml($sendSmtpEmail['params']);
            $sendSmtpEmail['sender'] = array('name' => $senderName, 'email' => $senderEmail); 
            $sendSmtpEmail['to'] = array(
                array('email' => $params['email'], 'name' => $params['name'])
            );
        } /*else {
            $paymentStatus = $this->getPaymentStatusAndDescription($params['statusCode']);
            $params['description'] = $paymentStatus['description'];
            $params['status'] = $paymentStatus['status'];
            $sendSmtpEmail['subject'] = 'Pago {{params.status}}';
            $sendSmtpEmail['htmlContent'] = '
            <html>
                <body>
                    <div>
                        <p>Hola {{params.name}},</p>
                        <p>{{params.description}}</p>
                    </div>
                </body>  
            </html>';
            $sendSmtpEmail['sender'] = array('name' => $senderName, 'email' => $senderEmail);
            $sendSmtpEmail['to'] = array(
                array('email' => $params['email'], 'name' => $params['name'])
            );
        }*/

        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            $this->logger->info("E-mail sendded: ", (array) $result);
        } catch (\Exception $e) {
            echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;
        }
    }

    public function getPaymentStatusAndDescription($status)
    {
        $paymentStatus = array();
        if ($status == 200) {
            $paymentStatus = array(
                "description" => "¡Tu pago ha sido aprobado!",
                "status" => "Aprobado"
            );
        } else {
            $paymentStatus = array(
                "description" => "Su pago ha sido rechazado o cancelado. Póngase en contacto con la tienda para verificar el motivo y, si es necesario, reordenar su compra.",
                "status" => "Rechazado"
            );
        }

        return $paymentStatus;
    }
}
