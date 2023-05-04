<?php

namespace Liquido\PayIn\Util\SendEmail;

class LiquidoEmailHtmlCSS 
{
    public function getEmailHtml()
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>    
            <style>
                @font-face {
                font-family: "Poppins"; font-style: normal; font-weight: 300; font-display: swap; src: url("https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLDz8V1s.ttf") format("truetype");
                }
                @font-face {
                font-family: "Poppins"; font-style: normal; font-weight: 700; font-display: swap; src: url("https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLCz7V1s.ttf") format("truetype");
                }
                @font-face {
                font-family: "Poppins"; font-style: normal; font-weight: 800; font-display: swap; src: url("https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLDD4V1s.ttf") format("truetype");
                }
                body {
                margin: 0; padding: 0; font-family: "Poppins", sans-serif; background-color: #E6EBFF; display: flex; align-items: center;
                }
                /*img {
                width: 150px; height: 150px;
                }*/
            </style>
        </head>
        <body style="font-family: "Poppins", sans-serif; display: flex; align-items: center; margin: 0; padding: 0;" bgcolor="#E6EBFF">
            <div class="content" style="background-color: #E6EBFF;" bgcolor="#E6EBFF">
                <div class="content-head" style="width: 100%; height: 50px; background-color: #0A4CE8; padding-top: 25px;"></div>
                <div class="content-body" style="background-color: #FFFFFF; width: 80%; height: auto; position: relative; font-weight: 500; border-radius: 18px; margin: 0 auto; padding: 50px;">
                    <div class="content-body-title" style="color: #959595; width: 100%; height: auto; position: relative; margin: 0 auto; padding: 10px;" align="center">
                        <img src="https://img.mailinblue.com/5577006/images/content_library/original/63ebe726d717e6759c3712d7.png" alt="Liquido Pagos" style="width: 150px; height: 150px;">
                        <p class="bold-text blue-text" style="font-weight: 700; color: #1B4AD3;">&iexcl;Hola {{params.name}}!</p>
                        <p class="blue-text" style="color: #1B4AD3;">Tu compra est&aacute; casi lista, finaliza el pago de tu compra en {{params.storeName}}.</p>
                    </div>     
                    <hr style="border: 1px solid #D7F7F3;">
                    <div class="content-body-instructions" style="color: #959595; width: 80%; height: auto; position: relative; margin: 0 auto; padding: 10px;" align="center">
                        <p><span class="bold-text" style="font-weight: 700;">Valor a pagar:</span> $ {{params.amount}}</p>
                        <small>Puedes acerlo hasta el {{params.expiration}}</small>
                        <p>Para hacer efectivo tu pago sigue los seguientes pasos:</p>
                        <div class="instruction-one" style="width: 45%; float: left; padding-top: 1%; /*padding-bottom: 100%;*/ margin: 20px 20px -100%;" align="left">
                            <p><span style="height: 25px; width: 25px; border-radius: 50%; display: inline-block; font-weight: 700; text-align: center; border: 1px solid #0975ED;">1</span> Dirigete a cualquer punto Efecty del pa&iacute;s.</p>
                        </div>
                        <div class="instruction-two" style="padding-top: 1%; /*padding-bottom: 100%;*/ margin: 20px 20px -100%;" align="left">
                            <p><span style="height: 25px; width: 25px; border-radius: 50%; display: inline-block; font-weight: 700; text-align: center; border: 1px solid #0975ED;">2</span> Notifica que quieres realizar un pago atrav&eacute;s de Liquido Pagos y proporciona la siguiente informaci&oacute;n:</p>
                        </div>
                        <br>
                        <div class="instruction-code" style="width: 50%; background-color: #ECFFFB; position: relative; border-radius: 18px; margin: 0 auto; padding: 20px;" align="left">
                            <p><span class="bold-text" style="font-weight: 700;">C&oacute;digo de convenio: </span>112766</p>
                            <p><span class="bold-text" style="font-weight: 700;">Referencia de pago: </span>{{params.cashCode}}</p>
                        </div>
                        <br>
                    </div>
                    <hr style="border: 1px solid #D7F7F3;">
                    <div class="content-body-footer" style="color: #959595; width: 100%; height: auto; position: relative; margin: 0 auto; padding: 10px;" align="left">
                        <p class="bold-text" style="font-weight: 700;">Detalle de pago</p>
                        <p>Order #{{params.orderId}} in store</p>
                        <p><a href="%7B%7Bparams.storeURL%7D%7D">{{params.storeURL}}</a></p>
                        <br>
                        <p>Efecty - Pago pendiente</p>
                        <p>$ {{params.amount}}</p>
                    </div>       
                </div>
                <div class="content-footer" style="position: relative; margin: 0 auto;" align="center">
                    <p class="blue-text" style="color: #1B4AD3;">Pago processado por:</p>
                    <img src="https://img.mailinblue.com/5577006/images/content_library/original/63f663e6b2cee42b486c017d.png" alt="Liquido Pagos">
                </div>
            </div>
        </body>
        </html>
        ';

        return $html;
    }

    public function getEmailCSS()
    {
        $css = "
        * {
            font-family: 'Poppins', sans-serif;
            font-size: medium;  
        }

        .email-body {
            margin: 0;
            padding: 0;            
            font-family: 'Poppins', sans-serif;
            background-color: #E6EBFF;
        
            /*display: flex;*/
            /*align-items: center;*/
        }
        
        .content {
            width: 100%;
            height: 50px;
            background-color: #0A4CE8;
            padding-top: 25px;
        }
        
        .content-body {
            background-color: #FFFFFF;
            width: 80%; 
            height: auto;
            margin: 0 auto;
            padding: 50px;
            position: relative;
            font-weight: 500;
            border-radius: 18px;
        }
        
        .content-body-title,
        .content-body-instructions,
        .content-body-footer {
            color: #959595;
            text-align: center;
            width: 100%;
            height: auto;
            margin: 0 auto;
            padding: 10px;
            position: relative;
        }
        
        .content-body-instructions {
            width: 80%;
        }
        
        .instructions {
            width: 100%;
            height: auto;
            overflow: hidden;
            margin: 0 auto;
            background-color: #0A4CE8;
        }
        
        .instruction-one {
            width: 45%;
            float: left;
            text-align: left;
            margin: 20px;
            /*padding-bottom: 100%;*/
            margin-bottom: -100%;
        }
        
        .instruction-two {
            padding-top: 2%;
            text-align:left;
            margin: 20px;
            /*padding-bottom: 100%;*/
            margin-bottom: -100%;
        }
        
        .instruction-one span,
        .instruction-two span {
            height: 25px;
            width: 25px;
            border: 1px solid #0975ED;
            border-radius: 50%;
            display: inline-block;
            font-weight: 700;
            text-align: center;
        }
        
        .instruction-code {
            width: 50%;
            background-color: #ECFFFB;
            margin: 0 auto;
            position: relative;
            border-radius: 18px;
            text-align: left;
            padding: 20px;
        }
        
        .content-body-footer {
            text-align: left;
        }
        
        .content-footer {
            margin: 0 auto;
            position: relative;
            text-align: center;
        }
        
        hr {
            border: 1px solid #D7F7F3;
        }
        
        img {
            width: 150px;
            height: 150px;
        }
        
        .bold-text {
            font-weight: 700;
        }
        
        .blue-text {
            color: #1B4AD3;
        }";

        return $css;
    }
}

?>