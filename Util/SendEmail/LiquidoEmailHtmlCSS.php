<?php

namespace Liquido\PayIn\Util\SendEmail;

class LiquidoEmailHtmlCSS 
{
    public function getEmailHtml()
    {
        $html = '<html>
        <head>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;700;800&display=swap" rel="stylesheet">
            <style>
                '.$this->getEmailCSS().'
            </style>
        </head>
        
        <body>
            <div class="content">
                <div class="content-img">
                    <!--<img src="{{params.imageTop}}" alt="Liquido Pagos"/>-->
                </div>
                <div class="content-title">
                    <p><span>¡Hola {{params.email}}!</span><br/>Finaliza el pago de tu compra a {{params.storeName}}</p>
                </div>
                <div class="content-body">
                    <div class="content-body-title">
                        <p>Pago $ {{params.amount}}</p>
                        <small>Puedes hacerlo hasta el {{params.expiration}}</small>
                    </div>
                    <div class="content-body-instructions">
                        <p><span>1</span> <small> Dirigete a cualquer punto Efecty.</small></p>
                        <p><span>2</span> <small> Notifica que quieres realizar un pago através de Liquido Pagos y dictas los siguientes dados:</small></p>
                    </div>
                    <div class="content-body-info">
                        <small>Código de convenio:</small>
                        <p>112766</p>
                        <small>Referencia de pago:</small>
                        <p>{{params.cashCode}}</p>
                        <!---<small>O también puedes llevar este ticket</small>
                        <br/>
                        <button onclick="{{params.printMail}}">Imprimir ticket</button>
                        <small>El pago se creditará al instante</small>-->
                    </div>
                    <div class="content-body-footer">
                        <p>Detalle de pago</p>
                        <small>Order #{{params.orderId}} in store: <br/><a href="{{params.storeURL}}">{{params.storeURL}}</a></small>
        
                        <br/><br/>
        
                        <small>Efecty - <span>Pago pendiente</span></small>
                        <br/>
                        <small>$ {{params.amount}}</small>
                    </div>
                    <div class="content-body-logo">
                        <!--<img src="{{params.imageLogo}}" alt="Liquido Pagos">-->
                    </div>
                </div>
                <div class="content-footer">
                    <p>Cuando tengas dudas con la compra ponte en contacto com <br/> {{params.storeName}}</p>
                </div>
            </div>
        </body>
        
        </html>';

        return $html;
    }

    public function getEmailCSS()
    {
        $css = "
        * {
            font-family: 'Poppins', sans-serif;
            font-size: medium;  
        }

        body {
            margin: 0;
            padding: 0;           

            display: flex;
            align-items: center;
        }
        .content {
            background-image: linear-gradient(to right, #010D99, #0975ED);
            color: #FFFFFF;
            width: 100%;
        }

        .content-img {
            padding-top: 50px;
        }

        .content-img,
        .content-title,
        .content-footer {
            color: #FFFFFF;
            text-align: center;
            width: 100%;
            height: auto;
            margin: 0 auto;
            padding: 10px;
            position: relative;
        }

        .content-footer p span {
            color: #FFFFFF;
        }

        .content-img img {
            width: 150px;
            height: 150px;
        }

        .content-title {
            color: #FFFFFF;
            text-align: center;
            width: 40%;
            height: auto;
            margin: 0 auto;
            padding: 10px;
            position: relative;
            text-decoration: none;
        }

        .content-title span {
            font-weight:900;
            text-decoration: none;
            color: #FFFFFF;
        }

        .content-title span a {
            font-weight:900;
            text-decoration: none;
            color: #FFFFFF;
        }

        .content-body {
            background-color: #FFFFFF;
            width: 40%; 
            height: auto;
            margin: 0 auto;
            padding: 50px;
            position: relative;
            color: #000;
            font-weight: 700;
            border-radius: 18px;
            box-shadow: 5px 10px 8px #010D99;
        }

        .content-body-instructions span {
            height: 25px;
            width: 25px;
            background-color: #0975ED;
            border-radius: 50%;
            display: inline-block;
            color: #FFFFFF;
            font-weight: 700;
            text-align: center;
        }

        .content-body-title small,
        .content-body-instructions small,
        .content-body-info small,
        .content-body-footer small {
            color: #808080;
        }

        .content-body-instructions {
            text-align: justify;
            width: 80%;
            height: auto;
            margin: 0 auto;
            padding: 10px;
            position: relative;
        }

        .content-body-info,
        .content-body-logo {
            text-align: center;
            width: 40%;
            height: auto;
            margin: 0 auto;
            padding: 10px;
            position: relative;
        }

        .content-body-title p,
        .content-body-info p, 
        .content-body-info span p,
        .content-body-footer p {
            color: #000000;
            font-weight: 900;
        }

        .content-body-info button {
            background-color: #0975ED;
            color: #FFFFFF;
            width: 100%;
            border-radius: 8px;
            border-width: 0;
            font-size: 18px;
            line-height: 45px;
        }

        .content-body-footer,
        .content-body-footer span {
            color: #FF0000;
        }

        .content-body-logo img {
            width: 120px;
            height: 50px;
        }";

        return $css;
    }
}

?>