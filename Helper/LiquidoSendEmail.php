<?php

namespace Liquido\PayIn\Helper;

use \GuzzleHttp\Client;
use \SendinBlue\Client\Configuration;
use \SendinBlue\Client\Api\TransactionalEmailsApi;
use \SendinBlue\Client\Model\SendSmtpEmail;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Psr\Log\LoggerInterface;

class LiquidoSendEmail
{
    private LoggerInterface $logger;
    private LiquidoConfigData $liquidoConfig;
    private $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        LiquidoConfigData $liquidoConfig
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->liquidoConfig = $liquidoConfig;
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
        if (!$isWebhookUpdate) {
            $sendSmtpEmail['subject'] = 'Su código PayCash';
            $sendSmtpEmail['htmlContent'] = '
            <html>
                <body>
                    <div>
                        <p>Hola {{params.name}}, </p>
                        <p>Aquí está su código de pago de PayCash:  <strong> {{params.cashCode}} </strong></p>
                        <p>La validez de pago de este código es: <strong> {{params.expiration}} </strong></p>
                        <p>Total a pagar: <strong> $COP {{params.amount}} </strong></p>
                        <p>Por favor diríjase a uno de nuestro establecimientos aliados para realizar el pago.</p> 
                        <br/>
                        <small>* Establecimientos aliados: Baloto, Banco de Bogotá, Bancolombia, Brinks, Davivienda, Efecty, Superpagos, Sured.</small> 
                        <br/>
                        <small>* Para pagos en redes Efecty se debe presentar el número de convenio <strong>112766</strong>.</small>
                    </div>
                </body>
            </html>';
            $sendSmtpEmail['sender'] = array('name' => $senderName, 'email' => $senderEmail); 
            $sendSmtpEmail['to'] = array(
                array('email' => $params['email'], 'name' => $params['name'])
            );
        } else {
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
        }
        $sendSmtpEmail['params'] = $params;

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
