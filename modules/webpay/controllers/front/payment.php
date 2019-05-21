<?php

require_once _PS_MODULE_DIR_.'webpay/libwebpay/webpay-soap.php';
require_once(_PS_MODULE_DIR_.'webpay/funcs/functions.php');
require_once(_PS_MODULE_DIR_.'webpay/log/WPLogger.php');


class WebPayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $WebPayPayment = new WebPay();
        
        $cart = Context::getContext()->cart;
        $cartId = self::$cart->id;
        
        $order = new Order(Order::getOrderByCartId($cartId));
                
        WPLogger::logDebug("initContent: CartId -> ".$cartId);
        WPLogger::logDebug("initContent: CustomerId -> ".self::$cart->id_customer);
        WPLogger::logDebug("initContent: Cart -> ".json_encode($cart));                
        WPLogger::logDebug("initContent: OrdenId -> ".$order->id);
        WPLogger::logDebug("initContent: Orden -> ".json_encode($order));        
	WPLogger::logDebug("initContent: Valor -> ".$cartId." -- ".(int)$cart->getOrderTotal(true, Cart::BOTH));
	//Casting agregado
        Context::getContext()->smarty->assign(array(
                'nbProducts' => $cart->nbProducts(),
                'cust_currency' => $cart->id_currency,
                'currencies' => $this->module->getCurrency((int)$cart->id_currency),
                'total' => (int)$cart->getOrderTotal(true, Cart::BOTH),
                'this_path' => $this->module->getPathUri(),
                'this_path_bw' => $this->module->getPathUri(),
                'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
        ));
        
        /* preparar pagina de exito o fracaso */
        $url_base = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "index.php?fc=module&module={$WebPayPayment->name}&controller=validate&cartId=" . $cartId;
        $url_exito   = $url_base."&return=ok";
        $url_fracaso = $url_base."&return=error";
        $url_confirmacion = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "modules/{$WebPayPayment->name}/validate.php";

        WPLogger::logDebug("initContent: URL Base -> ".$url_base);
        WPLogger::logDebug("initContent: URL Exito -> ".$url_exito);
        WPLogger::logDebug("initContent: URL Fracaso -> ".$url_fracaso);
        WPLogger::logDebug("initContent: URL Confirmacion -> ".$url_confirmacion);

        Configuration::updateValue('WEBPAY_URL_FRACASO', $url_fracaso);
        Configuration::updateValue('WEBPAY_URL_EXITO', $url_exito);
        Configuration::updateValue('WEBPAY_URL_CONFIRMACION', $url_confirmacion);
        
        //config lo llenan con los datos almacenados en el e-commerce.
        $config = array(
            "MODO"            => Configuration::get('WEBPAY_AMBIENT'),             
            "PRIVATE_KEY"     => Configuration::get('WEBPAY_SECRETCODE'),
            "PUBLIC_CERT"     => Configuration::get('WEBPAY_CERTIFICATE'),
            "WEBPAY_CERT"     => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),            
            "CODIGO_COMERCIO" => Configuration::get('WEBPAY_STOREID'),
            "URL_FINAL"       => Configuration::get('WEBPAY_NOTIFYURL'),
            "URL_RETURN"      => Configuration::get('WEBPAY_POSTBACKURL')
        );    
        
        WPLogger::logDebug("initContent: URL Final -> ".Configuration::get('WEBPAY_NOTIFYURL'));
        WPLogger::logDebug("initContent: URL Return -> ".Configuration::get('WEBPAY_POSTBACKURL'));

        try{
            $webpay = new WebPaySOAP($config);
	    //Casting agregado
            $result = $webpay->webpayNormal->initTransaction((int)$cart->getOrderTotal(true, Cart::BOTH), $sessionId="123abc", $ordenCompra=$cartId);
            WPLogger::logDebug("initContent: Respuesta WebPaySOAP.webpayNormal.initTransaction -> ".json_encode($result));
        }catch(Exception $e){
            WPLogger::logDebug("initContent: Error respuesta WebPaySOAP.webpayNormal.initTransaction -> ".print_r($e, TRUE));
            $result["error"] = "Error conectando a Webpay";
            $result["detail"] = $e->getMessage();
	    }

        $url_token = '0';
        $token_webpay = '0';
        
        if (isset($result["token_ws"])){
            $url_token = $result["url"];
            $token_webpay = $result["token_ws"];
            /*$cart = Context::getContext()->cart;
            $customer = new Customer($cart->id_customer);
            $this->module->validateOrder(
                (int)$cart->id, 
                Configuration::get('PS_OS_BANKWIRE'), 
                (float)$cart->getOrderTotal(true, Cart::BOTH), 
                $this->module->displayName, 
                NULL, NULL, 
                (int)Context::getContext()->currency->id,
                false, 
                $customer->secure_key
            );
            $order = new Order(Order::getOrderByCartId((int)$cart->id));*/
            createTransaction($token_webpay, (int)$cart->id, -1);
        }

        WPLogger::logDebug("initContent: URL Token -> ".$url_token);
        WPLogger::logDebug("initContent: Token Webpay -> ".$token_webpay);

        Context::getContext()->smarty->assign(array(
            'url_token' => $url_token,
            'token_webpay' => $token_webpay
        ));
                
        WPLogger::logDebug("initContent: Invocando TPL -> payment_execution.tpl");
        $this->setTemplate('payment_execution.tpl');        

    }
}

