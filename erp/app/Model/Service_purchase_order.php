<?php
class Service_purchase_order extends AppModel {
    public $useTable   = "service_purchase_orders";
    public $primaryKey = "id";
    public $hasMany    = array(
        "Service_purchase_order_item" => array(
            "foreignKey" => "spo_id",
            "dependent"  => true,
        )
    );
}
