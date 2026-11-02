<?php
App::uses('AppModel', 'Model');
//App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
/**
 * User Model
 *
 * @property Foodcourt $Foodcourt
 */
class Inventory_sku extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'inventory_skus';

/**
 * Get Low Stock SKUs For Single / Multiple Stores
 */
public function getLowStockSkus($storeIds = [])
{
    if (empty($storeIds)) {
        return [];
    }

    // Convert array → comma-separated values
    if (is_array($storeIds)) {
        $storeIds = implode(',', array_map('intval', $storeIds));
    }

    $query = "
        SELECT 
            s.id AS id,
            s.name AS name,
            s.stock AS required_stock,
            l.actual_balance AS last_balance,
            l.store_id,
            l.sku_id
        FROM inventory_skus s
        JOIN (
            SELECT 
                l.store_id,
                l.sku_id,
                l.actual_balance
            FROM stock_ledger l
            INNER JOIN (
                SELECT 
                    store_id,
                    sku_id,
                    MAX(id) AS max_id
                FROM stock_ledger
                WHERE store_id IN ($storeIds)
                  AND item_type = 'SKU'
                  AND type = 'DC'
                  AND alert_flag = 1
                GROUP BY store_id, sku_id
            ) last ON last.max_id = l.id
        ) l ON l.sku_id = s.id
        WHERE l.actual_balance < s.stock
        ORDER BY s.name
    ";

    $results = $this->query($query);
    // if (!empty($results)) {
    //     $skuIds = array_map(function($row) {
    //         return (int)$row['l']['sku_id'];
    //     }, $results);
    
    //     $skuIds = array_unique(array_filter($skuIds));
    //     $skuIdsStr = implode(',', $skuIds);
    //     if (!empty($skuIdsStr)) {
    //         $StockLedger = ClassRegistry::init('Stock_ledger');
           
    //         $StockLedger->query("
    //             UPDATE stock_ledger
    //             SET alert_flag = 0
    //             WHERE store_id IN ($storeIds)
    //               AND item_type = 'SKU'
    //               AND type = 'DC'
    //               AND sku_id IN ($skuIdsStr)
    //         ");
    //     }
    // }
    return $results;
}


 public function markAlertsAsViewed($storeIds, $skuIds) {
    if (empty($skuIds) || empty($storeIds)) {
        return false;
    }

    if (is_array($storeIds)) {
        $storeIds = implode(',', array_map('intval', $storeIds));
    }

    $skuIdsStr = implode(',', array_map('intval', $skuIds));
    
    $StockLedger = ClassRegistry::init('Stock_ledger');
    $StockLedger->query("
        UPDATE stock_ledger
        SET alert_flag = 0
        WHERE store_id IN ($storeIds)
            AND item_type = 'SKU'
            AND type = 'DC'
            AND sku_id IN ($skuIdsStr)
    ");
    
    return true;
}

}
