<?php
App::uses('AppModel', 'Model');

class Inventory_semifinished extends AppModel {

    public $useDbConfig = 'default';
    public $useTable = 'inventory_semifinished';

    // ✅ New function to get low stock data
    public function getLowStockData($storeId) {
        
        $query = "SELECT 
                s.id,
                s.name,
                s.stock AS required_stock,
                sl.actual_balance AS available_stock
            FROM inventory_semifinished s
            JOIN stock_ledger sl 
                ON sl.sku_id = s.id
                AND sl.id = (
                    SELECT MAX(id) 
                    FROM stock_ledger 
                    WHERE store_id = $storeId
                      AND item_type = 'Semifinished'
                      AND type = 'OUTLET'
                      ANd alert_flag = 1
                      AND sku_id = s.id
                )
            WHERE sl.actual_balance < s.stock";
        
        
    $results = $this->query($query);
    // echo "<pre/>";
    // print_r($results);exit;
    // if (!empty($results)) {
    //     $skuIds = array_map(function($row) {
    //         return (int)$row['s']['id'];
    //     }, $results);
    
    //     $skuIds = array_unique(array_filter($skuIds));
    //     $skuIdsStr = implode(',', $skuIds);
    //     if (!empty($skuIdsStr)) {
    //         $StockLedger = ClassRegistry::init('Stock_ledger');
    //         $StockLedger->query("UPDATE stock_ledger
    //             SET alert_flag = 0
    //             WHERE store_id IN ($storeId)
    //               AND item_type = 'Semifinished'
    //               AND type = 'OUTLET'
    //               AND sku_id IN ($skuIdsStr)");
    //     }
    // }
    return $results;
    }

    public function markAlertsAsViewed($storeId, $skuIds) {
        if (empty($skuIds)) {
            return false;
        }

        $skuIdsStr = implode(',', array_map('intval', $skuIds));
        
        $StockLedger = ClassRegistry::init('Stock_ledger');
        $StockLedger->query("UPDATE stock_ledger
            SET alert_flag = 0
            WHERE store_id = $storeId
              AND item_type = 'Semifinished'
              AND type = 'OUTLET'
              AND sku_id IN ($skuIdsStr)");
        
        return true;
    }
}
