<?php
class ClassFactura extends ObjectModel
{
    public $id_servicio_factura;
    public $id_order;
    public $resp;
    public $send;
    public $date_add;

    /**
    * @see ObjectModel::$definition
    */

    public static $definition = array(
        'table' => 'servicio_factura',
        'primary' => 'id_servicio_factura',
        'multilang' => false,
        'fields' => array(
            'id_order'    => array('type' => self::TYPE_INT, 
                'validate' => 'isUnsignedId', 
                'required' => true),
            'resp'  => array('type' => self::TYPE_INT, 
                'validate' => 'isUnsignedId', 
                'size' => 11),
            'send'  => array('type' => self::TYPE_BOOL, 
                'validate' => 'isBool', 
                'size' => 1),
            'date_add' => array('type' => self::TYPE_DATE, 
                'validate' => 'isDate', 
                'copy_post' => false),
        ),
    );

    public function selectSQLExists($id_order)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'servicio_factura
            WHERE id_order = '.$id_order.';';
        $results = Db::getInstance()->getRow($sql, $use_cache = 0);
        return $results;
    }

    public function selectService($id_servicio_factura)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'servicio_factura
            WHERE id_servicio_factura = '.$id_servicio_factura.';';
        $results = Db::getInstance()->ExecuteS($sql);
        return Tools::jsonDecode(Tools::jsonEncode($results[0]));
    }
}