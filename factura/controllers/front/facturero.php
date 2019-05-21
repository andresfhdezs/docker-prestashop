<?php
include(_PS_MODULE_DIR_ . 'factura/controllers/front/httpful.phar');
require_once(_PS_MODULE_DIR_ . 'factura/classes/ClassFactura.php');

class FacturaFactureroModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
        parent::initContent();

        if (Tools::getValue('id_order'))
        {
            $id_order = Tools::getValue('id_order');
            $order = new Order($id_order);

            ##
            #$json = $this->jsonFactura($order);
            #var_dump($json);
            #die();
            ##

            # Validá si el servicio fue enviado con codigo de estado '200'
            $results = $this->selectSQL($id_order, 200);
            
            # Sí la consulta retorna 1 en la columna 'send' de la tabla 'ps_servicio_factura'
            # Valida que ya fue enviado el servicio.
            if ($results['send']) {
                $this->messageGenerator(0);
            }
            else
            {
                if (Configuration::get('PS_INVOICE') && $order->invoice_number != 0)
                {
                    # Envia el servicio con 'httpful.phar' con metodo POST al servicio
                    # y se le pasa como parametro la clase '$order' instanciada.
                    $response = $this->servicioFactura($order);
                    
                    # Sí el servicio responde estado '200' guardara en columna 'send' 'true' si no 'false'
                    # id_order , status, send ? true : false
                    $send = ($response->code == 200) ? true : false ;

                    # Consulta para saber si ya este servicio habia sido enviado anteriormente
                    $results_exists = $this->selectSQLExists($id_order);
                    
                    # Si existia y el estado es distinto a '200' el valor de 'send' es 'falso'
                    # Se actualizara y afectara la columna 'send' y 'status' por si cambia de valor
                    if ($results_exists) {
                        $this->updateSQL($id_order, $response->code, $send);
                        # Enviara un mensaje al usuario dependiendo si fue enviado o no el servicio 
                        $var = ($send) ? 5 : 1 ;
                        $this->messageGenerator($var);
                    } else {
                        # Si no existia lo guardara 
                        $this->insertSQL($id_order, $response->code, $send);
                        # pregunto a base de datos si el servicio fue enviado correctamente
                        $results = $this->selectSQL($id_order, 200);
                        $send = ($results['send']) ? true : false ;

                        $var = ($send) ? 2 : 1 ;
                        $this->messageGenerator($var);
                    }
                    
                }else{
                    $this->messageGenerator(3);
                }
            }
            
        }else{
            $this->messageGenerator(4);
        }
    }

    # Envio del servicio 
    public function servicioFactura($order)
    {
        $jsonBE = $this->jsonFactura($order);
        $url = Configuration::get('FACTURA_REST_URL');
                    
        try {
            $response = \Httpful\Request::post($url)
                ->addHeaders(array(
                    'Empresa_ID'=> Configuration::get('FACTURA_EMPRESA_ID'),
                    'Usuario'   => Configuration::get('FACTURA_USUARIO_ID'), 
                    'Token'     => Configuration::get('FACTURA_CLAVE'),
                ))
                ->body($jsonBE)
                ->sendsJson()
                ->send();
        } catch (\Throwable $th) {
            throw $th;
        }

        return $response;
    }

    # Creación de Json para el servicio
	public function itemDetalle($cart)
    {
        $json = json_decode(json_encode($cart));   
        $itemDetalle;
        $c = 0;
        $n = 1;
        foreach ($json as $obj) {
            $itemDetalle[$c++] = array(
                    "Nrolinea"=>$n++,
                    "TipoCodigo"=> $code_product = ($obj->product_ean13 == "") ? "" : "EAN-13",
                    "CodigoProducto"=> $this->dataValidation($obj->product_ean13),
                    "DescripcionProducto"=> $obj->product_name,
                    "Cantidad"=>$obj->product_quantity,
                    "UnidadMedida"=> "UN",
                    "PrecioUnitario"=> $obj->unit_price_tax_incl,
                    "PorcentajeDescuento"=> $obj->reduction_percent,
                    "MontoDescuento"=> $total_discount = ($obj->unit_price_tax_incl * $obj->reduction_percent / 100),
                    "PorcentajeRecargo"=> 0,
                    "MontoRecargo"=> 0,
                    "Total"=> $obj->total_price_tax_incl,
                    "IndicadorExepcionTotal"=> 0,
                    "DescripcionExtendida"=> $obj->product_name 
            );
        }
        return $itemDetalle;
    }

    public function itemMedioPago($id_cart, $id_order)
    {
        $webpay = $this->consultaPaymentOrder($id_cart, $id_order);

        $n = 1;
        $webpay = Tools::jsonDecode(Tools::jsonEncode($webpay));
        foreach ($webpay as $obj) {
            $itemMedioPago = array(
                "Nrolinea"=> $n++,
                "CodMedioPago"=> $code = ($obj->payment_type == "Venta Debito") ? 5 : 4 ,
                "CodTarjeta"=> "",
                "NroCuotas"=> $code = ($obj->payment_type == "Venta Debito") ? 0 : $obj->card_fee ,
                "CodOperacion"=> $obj->reference,
                "NumTarjeta"=> $obj->card_number,
                "CodAutorizacion"=>$obj->auth_code
            );
        }
        return $itemMedioPago;
    }

    public function itemDescuentoRecargo($order_list)
    {
        $json = Tools::jsonDecode(Tools::jsonEncode($order_list));
        $c = 0;
        $n = 1;
        
        foreach ($json as $obj) {

            if ($obj->reduction_amount > 0 ) {
                $tipo_valor = "$";
                $valor = $obj->reduction_amount;
                $glosa = "Descuento comercial";
            } else {
                if ($obj->reduction_percent > 0 ) {
                    $tipo_valor = "%";
                    $valor = $obj->reduction_percent;
                    $glosa = "Descuento promocional";
                } else {
                    $tipo_valor = "";
                    $valor = 0.00;
                    $glosa = "";
                }
            }
            
            $itemDescuentoRecargo[$c++] = array(
                "linea"=> $n++,
                "TipoMovimiento"=> "D",
                "Glosa"=> $glosa,
                "TipoValor"=> $tipo_valor,
                "Valor"=> $valor,
                "IndicadorExepcion"=>0      
            );
        }
        return $itemDescuentoRecargo;
    }

    public function jsonFactura($order)
    {
        $order_list = $order->getOrderDetailList();
        $cart = $order->getCartProducts();
        $customer = $order->getCustomer();
       
        $itemDetalle = $this->itemDetalle($order_list);
        $itemDescuentoRecargo = $this->itemDescuentoRecargo($order_list);
        $itemMedioPago = $this->itemMedioPago($order->id_cart, $order->id);
        
        $boletaElectronica = array(
            "RutEmpresa"=> $this->dataValidation(Configuration::get('FACTURA_RUT_COMPANIA')),
            "TipoDocumento"=> $this->dataValidation(Configuration::get('FACTURA_TIPO_DOCUMENTO')),
            "Numero"=>$order->id,
            "FechaEmision"=> date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)),
            "FechaVencimiento"=> date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)),
            "IndicadorServicio"=> $this->dataValidation(Configuration::get('FACTURA_INDICADOR_SERVICIO')),
            "PorcentajeImpuesto"=> $this->dataValidation($order->carrier_tax_rate),
            "ExentoFuncional"=> 0,
            "AfectoFuncional"=> $order->total_paid_tax_excl,
            "ImpuestoFuncional"=> $tax = $order->total_paid_tax_incl - $order->total_paid_tax_excl,
            "TotalFuncional"=> $order->total_paid_tax_incl,
            "MontoNoFActurable"=> 0,
            "CodigoSucursal"=> $this->dataValidation(Configuration::get('EMISOR_SUCURSAL')),
            "DireccionSucursal"=> $this->dataValidation(Configuration::get('EMISOR_DIRECCION')),
            "ComunaSucursal"=> $this->dataValidation(Configuration::get('EMISOR_COMUNA')),
            "CiudadSucursal"=> $this->dataValidation(Configuration::get('EMISOR_CIUDAD')),
            "RutCliente"=> "66666666-6",
            "CodigoInternoCliente"=> "66666666-6",
            "RazonSocial"=> $this->dataValidation($customer->firstname) . " " . $this->dataValidation($customer->lastname),
            "DireccionCliente"=> "San Nicolas #600",
            "ComunaCliente"=> "San Miguel",
            "CiudadCliente"=> $this->dataValidation($customer->geoloc_id_country),
            "DireccionPostal"=> "",
            "ContactoCliente"=> "",
            "Estado"=> $customer->active,
            "ItemDetalle" => $itemDetalle,
            "ItemDescuentoRecargo" => $itemDescuentoRecargo,
            "ItemMedioPago" => $itemMedioPago,
        );
        
        return Tools::jsonEncode($boletaElectronica);
    }

    # Validación de la data del json
    public function dataValidation($data)
    {
        $data = ($data == null) ? "" : $data ;
        return $data;
    }

    # Generador de Mensajes en pantalla
    public function messageGenerator($code)
    {
        switch ($code) {
            case 0:
                $message = "¡Ups! Ya fue enviado, intente con otro pedido.";
                break;
            case 1:
                $message = "¡Ups! Algo ha salido mal, intentelo de nuevo mas tarde.";
                break;
            case 2:
                $message = "¡Éxito! Servicio enviado correctamente.";
                break;
            case 3:
                $message = "¡Alerta! A este pedido aún no se le ha hecho el pagó.";
                break;
            case 4:
                $message = "¡Error! Ingrese un pedido correcto.";
                break;
            case 5:
                $message = "¡Éxito! Estado del servicio actualizado a Enviado.";
                break;
        }
        echo "<script languaje='javascript' type='text/javascript'>window.alert('$message');</script>";
        echo "<script languaje='javascript' type='text/javascript'>window.close();</script>";
    }

    # Consultas, inserciones y actualizaciones a Base de datos
    public function consultaPaymentOrder($id_cart, $id_order)
    {
        $sql = '
        SELECT * FROM `'._DB_PREFIX_.'xpec_webpay_payment` as w
        INNER JOIN `'._DB_PREFIX_.'orders` as o 
        WHERE w.id_cart = '. (int)$id_cart .' 
        AND o.id_order = '.(int)$id_order;

        return Db::getInstance()->executeS($sql);
    }

    public function insertSQL($id_order, $resp, $send)
    {
        $ServicioFactura = new ClassFactura();
        $ServicioFactura->id_order = (int)$id_order;
        $ServicioFactura->resp = (int)$resp;
        $ServicioFactura->send = (bool)$send;
        $ServicioFactura->date_add = date('Y-m-d H:i:s');
        $ServicioFactura->add();
        
    }

    public function updateSQL($id_order, $resp, $send)
    {
        Db::getInstance()->update('servicio_factura', array(
                'resp'      => (int)$resp,
                'send'      => (bool)$send,
                'date_add'  => date('Y-m-d H:i:s'),
            ), 
            'id_order = '.$id_order);
    }

    public function selectSQL($id_order, $resp)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'servicio_factura
            WHERE id_order = '.$id_order.' AND resp = '.$resp.';';
        $results = Db::getInstance()->getRow($sql, $use_cache = 0);
        return $results;
    }

    public function selectSQLExists($id_order)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'servicio_factura
            WHERE id_order = '.$id_order.';';
        $results = Db::getInstance()->getRow($sql, $use_cache = 0);
        return $results;
    }
}