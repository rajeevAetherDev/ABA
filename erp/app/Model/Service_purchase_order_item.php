<?php
class Service_purchase_order_item extends AppModel {
    public $useTable   = "service_purchase_order_items";
    public $primaryKey = "id";
    public $belongsTo  = array(
        "Service_purchase_order" => array("foreignKey" => "spo_id")
    );
}
