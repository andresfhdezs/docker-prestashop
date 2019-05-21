<?php

if (!defined('_PS_VERSION_'))
    exit;

class WebPay extends PaymentModule {

    protected $_errors = array();

    public function __construct() {
        $this->name = 'webpay';
        // Calling the parent's constructor. This must be done before any use of the $this->l() method, and after the creation of $this->name.
        parent::__construct();
        //$this->integration = include( 'integration/integration.php' );
        $this->displayName = $this->l('Webpay Plus');
        $this->description = $this->l('Recibe pagos en linea con Tarjetas de Credito y Redcompra en tu Prestashop a traves de Webpay Plus');
        $this->author = 'Transbank';
        $this->version = '1.0.0';
        $this->tab = 'payments_gateways';
        $this->controllers = array('payment', 'validate');        
        Context::getContext()->cookie->__set('WEBPAY_TITLE', "Pago con Tarjetas de Credito o Redcompra");
        Context::getContext()->cookie->__set('WEBPAY_BUTTON_TITLE', "Pago electronico con Tarjetas de Credito o Redcompra a traves de Webpay Plus");        
        // Module settings
        $this->setModuleIntegracion();        
        // Check module requirements
        $this->checkModuleRequirements();
    }

    public function addTables(){
        return Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'xpec_order_info` (
                `id_order` int(10) UNSIGNED,
                `creditCardDigits` VARCHAR(20) NOT NULL,
                `authCode` VARCHAR(10) NOT NULL,
                `canceled` VARCHAR(4) NOT NULL,                
                PRIMARY KEY (`id_order`)
            ) ENGINE=INNODB DEFAULT CHARACTER SET utf8
                DEFAULT COLLATE utf8_general_ci;'
        );
    }

    public function install() {
        // Module settings
        $this->setModuleInitSettings();        
        if (!$this->addTables() OR !parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn'))
            return false;
        return true;
    }

    public function uninstall() {
        if (!parent::uninstall() || !Configuration::deleteByName("WEBPAY"))
            return false;        
        // Drop the paymentmethod table
        Db::getInstance()->execute("DROP TABLE if exists {$this->dbPmInfo}");
        // Drop the paymentmethod raw data table
        Db::getInstance()->execute("DROP TABLE if exists {$this->dbRawData}");
        return true;
    }

    public function hookPaymentReturn($params) {        
        if (!$this->active)
                return;        
        $state = $params['objOrder']->getCurrentState();
        //if (in_array($state, array(Configuration::get('PS_OS_BANKWIRE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
        //{
                $this->smarty->assign(array(
                    'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                    'status' => 'ok',
                    'id_order' => $params['objOrder']->id,
                    'WEBPAY_RESULT_DESC' => Context::getContext()->cookie->WEBPAY_RESULT_DESC,
                    'WEBPAY_VOUCHER_NROTARJETA' => Context::getContext()->cookie->WEBPAY_VOUCHER_NROTARJETA,
                    'WEBPAY_VOUCHER_TXDATE_FECHA' => Context::getContext()->cookie->WEBPAY_VOUCHER_TXDATE_FECHA,
                    'WEBPAY_VOUCHER_TXDATE_HORA' => Context::getContext()->cookie->WEBPAY_VOUCHER_TXDATE_HORA,
                    'WEBPAY_VOUCHER_TOTALPAGO' => Context::getContext()->cookie->WEBPAY_VOUCHER_TOTALPAGO,
                    'WEBPAY_VOUCHER_ORDENCOMPRA' => Context::getContext()->cookie->WEBPAY_VOUCHER_ORDENCOMPRA,
                    'WEBPAY_VOUCHER_AUTCODE' => Context::getContext()->cookie->WEBPAY_VOUCHER_AUTCODE,
                    'WEBPAY_VOUCHER_TIPOCUOTAS' => Context::getContext()->cookie->WEBPAY_VOUCHER_TIPOCUOTAS,
                    'WEBPAY_VOUCHER_TIPOPAGO' => Context::getContext()->cookie->WEBPAY_VOUCHER_TIPOPAGO,
                    'WEBPAY_VOUCHER_NROCUOTAS' => Context::getContext()->cookie->WEBPAY_VOUCHER_NROCUOTAS,
                    'WEBPAY_RESULT_CODE' => Context::getContext()->cookie->WEBPAY_RESULT_CODE,
                    'WEBPAY_TX_ANULADA' => Context::getContext()->cookie->WEBPAY_TX_ANULADA                                     
                ));
                Db::getInstance()->insert(_DB_PREFIX_.'xpec_order_info', array(
                    'id_order' => $params['objOrder']->id,
                    'creditCardDigits' => Context::getContext()->cookie->WEBPAY_VOUCHER_NROTARJETA,
                    'authCode' => Context::getContext()->cookie->WEBPAY_VOUCHER_AUTCODE,
                    'canceled' => Context::getContext()->cookie->WEBPAY_TX_ANULADA
                ));
                if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
                        $this->smarty->assign('reference', $params['objOrder']->reference);
        //}
        //else
        //        $this->smarty->assign('status', 'failed');
        return $this->display(__FILE__, 'payment_return.tpl');        
    }
    
    public function hookPayment($params) {
        if (!$this->active)
            return;
        global $smarty;
        //Se setea valor inicial de Transaccion anulada.    
        //Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "SI");
        Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', "");
        // Get active Shop ID for multistore shops
        $activeShopID = (int) Context::getContext()->shop->id;
        $title = Context::getContext()->cookie->WEBPAY_TITLE;            
        $smarty->assign(array(
        	'logo' => "https://www.transbank.cl/public/img/LogoWebpay.png",
        	'title' => $title
        ));
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function getContent() {         
        // Get active Shop ID for multistore shops
        $activeShopID = (int)Context::getContext()->shop->id;
        $shopDomainSsl = Tools::getShopDomainSsl(true, true);                        
        if (Tools::getIsset('webpay_updateSettings')) {
            Configuration::updateValue('WEBPAY_STOREID', trim(Tools::getValue('storeID')));
            Configuration::updateValue('WEBPAY_SECRETCODE', trim(Tools::getValue('secretCode')));
            Configuration::updateValue('WEBPAY_CERTIFICATE', Tools::getValue('certificate'));
            Configuration::updateValue('WEBPAY_CERTIFICATETRANSBANK', Tools::getValue('certificateTransbank'));
            Configuration::updateValue('WEBPAY_AMBIENT', Tools::getValue('ambient'));
            Configuration::updateValue('WEBPAY_NOTIFYURL', Context::getContext()->link->getModuleLink($this->name, 'validate', array(), true));       
            Configuration::updateValue('WEBPAY_POSTBACKURL', Context::getContext()->link->getModuleLink($this->name, 'validate', array(), true));                      
            $this->setModuleSettings();
            $this->checkModuleRequirements();
        }else{
            $this->setModuleSettings();
        }
        Context::getContext()->smarty->assign(
            array(
                'errors' => $this->errors,
                'post_url' => $_SERVER['REQUEST_URI'],
                'data_storeid_init' => $this->storeID_init,
                'data_secretcode_init' => $this->secretCode_init,
                'data_certificate_init' => $this->certificate_init,
                'data_certificatetransbank_init' => $this->certificateTransbank_init,                
                'data_storeid' => $this->storeID,
                'data_secretcode' => $this->secretCode,
                'data_certificate' => $this->certificate,
                'data_certificatetransbank' => $this->certificateTransbank,
                'data_ambient' => $this->ambient,
                'data_title' => $this->title,
                'version' => $this->version,
                'api_version' => '1.0',
                'img_icono' => "https://www.transbank.cl/public/img/LogoWebpay.png",
                'webpay_notify_url' => $shopDomainSsl . __PS_BASE_URI__ . "modules/{$this->name}/validate.php",
                'webpay_postback_url' => $shopDomainSsl . __PS_BASE_URI__ . "modules/{$this->name}/validate.php"
            )
        );                           
        return $this->display($this->name, 'views/templates/admin/config.tpl');
    }
    
    private function checkModuleRequirements() {
        $this->_errors = array();       
    }
    
    private function adminValidation() {
        $this->_errors = array();
        
    }    

    private function setModuleSettings() {
        $this->storeID = Configuration::get('WEBPAY_STOREID');
        $this->secretCode = Configuration::get('WEBPAY_SECRETCODE');
        $this->certificate = Configuration::get('WEBPAY_CERTIFICATE');
        $this->certificateTransbank = Configuration::get('WEBPAY_CERTIFICATETRANSBANK');
        $this->ambient = Configuration::get('WEBPAY_AMBIENT');
        $this->title = Context::getContext()->cookie->WEBPAY_TITLE;
        $this->webpay_notify_url = Configuration::get('WEBPAY_NOTIFYURL');
        $this->webpay_postback_url = Configuration::get('WEBPAY_POSTBACKURL');
    }    
    
    private function setModuleInitSettings() {
    
     $this->setModuleIntegracion();
             
     //Al instalar el plugin se guardan en BD los datos iniciales de integracion.
     Configuration::updateValue('WEBPAY_STOREID', $this->storeID_init);
     Configuration::updateValue('WEBPAY_SECRETCODE', str_replace("<br/>", "\n", $this->secretCode_init));
     Configuration::updateValue('WEBPAY_CERTIFICATE', str_replace("<br/>", "\n", $this->certificate_init));
     Configuration::updateValue('WEBPAY_CERTIFICATETRANSBANK', str_replace("<br/>", "\n", $this->certificateTransbank_init));
     Configuration::updateValue('WEBPAY_AMBIENT', "INTEGRACION"); 
     Configuration::updateValue('WEBPAY_NOTIFYURL', Context::getContext()->link->getModuleLink($this->name, 'validate', array(), true));       
     Configuration::updateValue('WEBPAY_POSTBACKURL', Context::getContext()->link->getModuleLink($this->name, 'validate', array(), true)); 
   
    }
    
    
    private function setModuleIntegracion() {
        $this->storeID_init = "597020000541";
        
        $this->secretCode_init = "-----BEGIN RSA PRIVATE KEY-----"
    . "<br/>MIIEpQIBAAKCAQEA0ClVcH8RC1u+KpCPUnzYSIcmyXI87REsBkQzaA1QJe4w/B7g"
    . "<br/>6KvKV9DaqfnNhMvd9/ypmGf0RDQPhlBbGlzymKz1xh0lQBD+9MZrg8Ju8/d1k0pI"
    . "<br/>b1QLQDnhRgR2T14ngXpP4PIQKtq7DsdHBybFU5vvAKVqdHvImZFzqexbZjXWxxhT"
    . "<br/>+/sGcD4Vs673fc6B+Xj2UrKF7QyV5pMDq0HCCLTMmafWAmNrHyl6imQM+bqC12gn"
    . "<br/>EEAEkrJiSO6P/21m9iDJs5KQanpJby0aGW8mocYRHDMHZjtTiIP0+JAJgL9KsH+r"
    . "<br/>Xdk2bT7aere7TzOK/bEwhkYEXnMMt/65vV6AfwIDAQABAoIBAHnIlOn6DTi99eXl"
    . "<br/>KVSzIb5dA747jZWMxFruL70ifM+UKSh30FGPoBP8ZtGnCiw1ManSMk6uEuSMKMEF"
    . "<br/>5iboVi4okqnTh2WSC/ec1m4BpPQqxKjlfrdTTjnHIxrZpXYNucMwkeci93569ZFR"
    . "<br/>2SY/8pZV1mBkZoG7ocLmq+qwE1EaBEL/sXMvuF/h08nJ71I4zcclpB8kN0yFrBCW"
    . "<br/>7scqOwTLiob2mmU2bFHOyyjTkGOlEsBQxhtVwVEt/0AFH/ucmMTP0vrKOA0HkhxM"
    . "<br/>oeR4k2z0qwTzZKXuEZtsau8a/9B3S3YcgoSOhRP/VdY1WL5hWDHeK8q1Nfq2eETX"
    . "<br/>jnQ4zjECgYEA7z2/biWe9nDyYDZM7SfHy1xF5Q3ocmv14NhTbt8iDlz2LsZ2JcPn"
    . "<br/>EMV++m88F3PYdFUOp4Zuw+eLJSrBqfuPYrTVNH0v/HdTqTS70R2YZCFb9g0ryaHV"
    . "<br/>TRwYovu/oQMV4LBSzrwdtCrcfUZDtqMYmmZfEkdjCWCEpEi36nlG0JMCgYEA3r49"
    . "<br/>o+soFIpDqLMei1tF+Ah/rm8oY5f4Wc82kmSgoPFCWnQEIW36i/GRaoQYsBp4loue"
    . "<br/>vyPuW+BzoZpVcJDuBmHY3UOLKr4ZldOn2KIj6sCQZ1mNKo5WuZ4YFeL5uyp9Hvio"
    . "<br/>TCPGeXghG0uIk4emSwolJVSbKSRi6SPsiANff+UCgYEAvNMRmlAbLQtsYb+565xw"
    . "<br/>NvO3PthBVL4dLL/Q6js21/tLWxPNAHWklDosxGCzHxeSCg9wJ40VM4425rjebdld"
    . "<br/>DF0Jwgnkq/FKmMxESQKA2tbxjDxNCTGv9tJsJ4dnch/LTrIcSYt0LlV9/WpN24LS"
    . "<br/>0lpmQzkQ07/YMQosDuZ1m/0CgYEAu9oHlEHTmJcO/qypmu/ML6XDQPKARpY5Hkzy"
    . "<br/>gj4ZdgJianSjsynUfsepUwK663I3twdjR2JfON8vxd+qJPgltf45bknziYWvgDtz"
    . "<br/>t/Duh6IFZxQQSQ6oN30MZRD6eo4X3dHp5eTaE0Fr8mAefAWQCoMw1q3m+ai1PlhM"
    . "<br/>uFzX4r0CgYEArx4TAq+Z4crVCdABBzAZ7GvvAXdxvBo0AhD9IddSWVTCza972wta"
    . "<br/>5J2rrS/ye9Tfu5j2IbTHaLDz14mwMXr1S4L39UX/NifLc93KHie/yjycCuu4uqNo"
    . "<br/>MtdweTnQt73lN2cnYedRUhw9UTfPzYu7jdXCUAyAD4IEjFQrswk2x04="
    . "<br/>-----END RSA PRIVATE KEY-----";
        
        
        $this->certificate_init = "-----BEGIN CERTIFICATE-----"
    . "<br/>MIIDujCCAqICCQCZ42cY33KRTzANBgkqhkiG9w0BAQsFADCBnjELMAkGA1UEBhMC"
    . "<br/>Q0wxETAPBgNVBAgMCFNhbnRpYWdvMRIwEAYDVQQKDAlUcmFuc2JhbmsxETAPBgNV"
    . "<br/>BAcMCFNhbnRpYWdvMRUwEwYDVQQDDAw1OTcwMjAwMDA1NDExFzAVBgNVBAsMDkNh"
    . "<br/>bmFsZXNSZW1vdG9zMSUwIwYJKoZIhvcNAQkBFhZpbnRlZ3JhZG9yZXNAdmFyaW9z"
    . "<br/>LmNsMB4XDTE2MDYyMjIxMDkyN1oXDTI0MDYyMDIxMDkyN1owgZ4xCzAJBgNVBAYT"
    . "<br/>AkNMMREwDwYDVQQIDAhTYW50aWFnbzESMBAGA1UECgwJVHJhbnNiYW5rMREwDwYD"
    . "<br/>VQQHDAhTYW50aWFnbzEVMBMGA1UEAwwMNTk3MDIwMDAwNTQxMRcwFQYDVQQLDA5D"
    . "<br/>YW5hbGVzUmVtb3RvczElMCMGCSqGSIb3DQEJARYWaW50ZWdyYWRvcmVzQHZhcmlv"
    . "<br/>cy5jbDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBANApVXB/EQtbviqQ"
    . "<br/>j1J82EiHJslyPO0RLAZEM2gNUCXuMPwe4OirylfQ2qn5zYTL3ff8qZhn9EQ0D4ZQ"
    . "<br/>Wxpc8pis9cYdJUAQ/vTGa4PCbvP3dZNKSG9UC0A54UYEdk9eJ4F6T+DyECrauw7H"
    . "<br/>RwcmxVOb7wClanR7yJmRc6nsW2Y11scYU/v7BnA+FbOu933Ogfl49lKyhe0MleaT"
    . "<br/>A6tBwgi0zJmn1gJjax8peopkDPm6gtdoJxBABJKyYkjuj/9tZvYgybOSkGp6SW8t"
    . "<br/>GhlvJqHGERwzB2Y7U4iD9PiQCYC/SrB/q13ZNm0+2nq3u08ziv2xMIZGBF5zDLf+"
    . "<br/>ub1egH8CAwEAATANBgkqhkiG9w0BAQsFAAOCAQEAdgNpIS2NZFx5PoYwJZf8faze"
    . "<br/>NmKQg73seDGuP8d8w/CZf1Py/gsJFNbh4CEySWZRCzlOKxzmtPTmyPdyhObjMA8E"
    . "<br/>Adps9DtgiN2ITSF1HUFmhMjI5V7U2L9LyEdpUaieYyPBfxiicdWz2YULVuOYDJHR"
    . "<br/>n05jlj/EjYa5bLKs/yggYiqMkZdIX8NiLL6ZTERIvBa6azDKs6yDsCsnE1M5tzQI"
    . "<br/>VVEkZtEfil6E1tz8v3yLZapLt+8jmPq1RCSx3Zh4fUkxBTpUW/9SWUNEXbKK7bB3"
    . "<br/>zfB3kGE55K5nxHKfQlrqdHLcIo+vdShATwYnmhUkGxUnM9qoCDlB8lYu3rFi9w=="
    . "<br/>-----END CERTIFICATE-----";
        
        
        $this->certificateTransbank_init = "-----BEGIN CERTIFICATE-----"
    . "<br/>MIIDKTCCAhECBFZl7uIwDQYJKoZIhvcNAQEFBQAwWTELMAkGA1UEBhMCQ0wxDjAM"
    . "<br/>BgNVBAgMBUNoaWxlMREwDwYDVQQHDAhTYW50aWFnbzEMMAoGA1UECgwDa2R1MQww"
    . "<br/>CgYDVQQLDANrZHUxCzAJBgNVBAMMAjEwMB4XDTE1MTIwNzIwNDEwNloXDTE4MDkw"
    . "<br/>MjIwNDEwNlowWTELMAkGA1UEBhMCQ0wxDjAMBgNVBAgMBUNoaWxlMREwDwYDVQQH"
    . "<br/>DAhTYW50aWFnbzEMMAoGA1UECgwDa2R1MQwwCgYDVQQLDANrZHUxCzAJBgNVBAMM"
    . "<br/>AjEwMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAizJUWTDC7nfP3jmZ"
    . "<br/>pWXFdG9oKyBrU0Bdl6fKif9a1GrwevThsU5Dq3wiRfYvomStNjFDYFXOs9pRIxqX"
    . "<br/>2AWDybjAX/+bdDTVbM+xXllA9stJY8s7hxAvwwO7IEuOmYDpmLKP7J+4KkNH7yxs"
    . "<br/>KZyLL9trG3iSjV6Y6SO5EEhUsdxoJFAow/h7qizJW0kOaWRcljf7kpqJAL3AadIu"
    . "<br/>qV+hlf+Ts/64aMsfSJJA6xdbdp9ddgVFoqUl1M8vpmd4glxlSrYmEkbYwdI9uF2d"
    . "<br/>6bAeaneBPJFZr6KQqlbbrVyeJZqmMlEPy0qPco1TIxrdEHlXgIFJLyyMRAyjX9i4"
    . "<br/>l70xjwIDAQABMA0GCSqGSIb3DQEBBQUAA4IBAQBn3tUPS6e2USgMrPKpsxU4OTfW"
    . "<br/>64+mfD6QrVeBOh81f6aGHa67sMJn8FE/cG6jrUmX/FP1/Cpbpvkm5UUlFKpgaFfH"
    . "<br/>v+KgCpEvgcRIv/OeIi6Jbuu3NrPdGPwzYkzlOQnmgio5RGb6GSs+OQ0mUWZ9J1+Y"
    . "<br/>tdZc+xTga0x7nsCT5xNcUXsZKhyjoKhXtxJm3eyB3ysLNyuL/RHy/EyNEWiUhvt1"
    . "<br/>SIePnW+Y4/cjQWYwNqSqMzTSW9TP2QR2bX/W2H6ktRcLsgBK9mq7lE36p3q6c9Dt"
    . "<br/>ZJE+xfA4NGCYWM9hd8pbusnoNO7AFxJZOuuvLZI7JvD7YLhPvCYKry7N6x3l"
    . "<br/>-----END CERTIFICATE-----";
 
     
        
     $this->ambient = Configuration::get('WEBPAY_AMBIENT');
     $this->title = Context::getContext()->cookie->WEBPAY_TITLE;
     $this->webpay_notify_url = Configuration::get('WEBPAY_NOTIFYURL');
     $this->webpay_postback_url = Configuration::get('WEBPAY_POSTBACKURL');
        
    }
    
    
    
}


