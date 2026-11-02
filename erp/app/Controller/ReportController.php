<?php
App::uses('AppController', 'Controller');

class ReportController extends AppController {
    public $uses = array(
        'User', 'Location', 'Order_transaction', 'Order_orderhistory',
        'Order_payment', 'Order_tax', 'Catalog_product', 'Stock_damage',
        'Purchaseorder', 'Po_item', 'Purchase_indent', 'Vendor', 'Sku',
        'Direct_purchase', 'Audit_stock', 'Service_charge_log', 'User_track',
        'Outlet_tax_mapping', 'Unit_mapping', 'Catalog_category', 'Catalog_subcategory',
        'Semifinished_sku', 'Po_grn', 'Po_grn_item', 'Service_po', 'Service_po_item',
        'Order_table_tracking', 'Order_split_transaction', 'Order_split_orderhistory',
        'Order_split_payment', 'Order_split_tax'
    );
    public $components = array('Paginator', 'Flash', 'Session');
    public $helpers = array('Html', 'Form', 'Js');

    public function beforeFilter() {
        parent::beforeFilter();
        if (!$this->Session->check('Auth.User')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->layout = 'dashboard';
    }
    
    private function _dsrSqlStoreFilter($cashierFilter, $alias) {
        if ($cashierFilter === '') {
            return '';
        }
        return preg_replace('/\bstore_id\b/', $alias . '.store_id', $cashierFilter);
    }

    private function _dsrKdsSalesSql($dayStart, $dayEnd, $cashierFilter, $nc = false) {
        $chargeClause = $nc ? 'IN' : 'NOT IN';
        $otKdsFilter = $this->_dsrSqlStoreFilter($cashierFilter, 'order_tax');
        $oohFilter   = $this->_dsrSqlStoreFilter($cashierFilter, 'ooh');
        $ot2Filter   = $this->_dsrSqlStoreFilter($cashierFilter, 'ot2');
        $opNcFilter  = $this->_dsrSqlStoreFilter($cashierFilter, 'op_nc');
        $opTxFilter  = $this->_dsrSqlStoreFilter($cashierFilter, 'op_tx');
        $kdsPrinterCond = !empty($cashierFilter)
            ? "pos_printers.kds = ia.kds AND pos_printers.store_id = ia.store_id"
            : "pos_printers.kds = ia.kds";
        return "
            WITH item_amounts AS (
                SELECT
                    ooh.reference_id,
                    ooh.store_id,
                    ooh.quantity,
                    ooh.total_tax_percentage AS tax_percentage,
                    IFNULL(ot_kds.kds, 0) AS kds,
                    (ooh.price * ooh.quantity)
                        * (1 - IFNULL(ot_kds.discount_percentage, 0) / 100)
                        AS item_amount
                FROM order_orderhistories ooh
                LEFT JOIN (
                    SELECT orderhistory_id, store_id,
                           MAX(kds) AS kds,
                           MAX(discount_percentage) AS discount_percentage
                    FROM order_tax
                    WHERE status != 0 $otKdsFilter
                    GROUP BY orderhistory_id, store_id
                ) ot_kds
                    ON ot_kds.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id)
                    AND ot_kds.store_id = ooh.store_id
                WHERE ooh.created BETWEEN '$dayStart' AND '$dayEnd'
                  AND ooh.status = 2
                  $oohFilter
                  AND ooh.reference_id $chargeClause (
                      SELECT op_nc.reference_id FROM order_payments op_nc
                      WHERE op_nc.type = 7 AND op_nc.status = 2 $opNcFilter
                  )
            ),
            bill_totals AS (
                SELECT
                    ot2.reference_id,
                    ot2.store_id,
                    (ot2.price - ot2.discount) AS bill_net,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot2
                INNER JOIN item_amounts ia
                    ON ot2.reference_id = ia.reference_id
                    AND ot2.store_id = ia.store_id
                WHERE ot2.status = 2
                  AND ot2.created BETWEEN '$dayStart' AND '$dayEnd'
                  $ot2Filter
                  AND ot2.reference_id $chargeClause (
                      SELECT op_tx.reference_id FROM order_payments op_tx
                      WHERE op_tx.type = 7 AND op_tx.status = 2 $opTxFilter
                  )
                GROUP BY ot2.reference_id, ot2.store_id, ot2.price, ot2.discount
            )
            SELECT kds, kds_name, tax_percentage, value FROM (
                SELECT
                    ia.kds,
                    IFNULL((SELECT name FROM pos_printers
                            WHERE $kdsPrinterCond AND pos_printers.status = 1 LIMIT 1), '') AS kds_name,
                    ia.tax_percentage,
                    ROUND(SUM(
                        CASE WHEN bt.bill_item_sum > 0
                             THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net
                             ELSE 0
                        END
                    ), 2) AS value
                FROM item_amounts ia
                INNER JOIN bill_totals bt
                    ON ia.reference_id = bt.reference_id
                    AND ia.store_id = bt.store_id
                GROUP BY ia.kds, ia.tax_percentage
            ) AS final
        ";
    }

    private function _dsrAggregateKdsSales($startdate, $enddate, $cashierFilter, $nc = false, $location_id = null) {
        $salesMap = [];
        $period = new DatePeriod(
            new DateTime($startdate),
            new DateInterval('P1D'),
            (new DateTime($enddate))->modify('+1 day')
        );
        foreach ($period as $dateObj) {
            $day = $dateObj->format('Y-m-d');
            $_dayShift = $this->getShiftDateRange($day, $day, $location_id);
            $dayStart = $_dayShift['startDatetime'];
            $dayEnd   = $_dayShift['endDatetime'];
            $daySales = $this->Order_tax->query(
                $this->_dsrKdsSalesSql($dayStart, $dayEnd, $cashierFilter, $nc)
            );
            foreach ($daySales as $s) {
                $row = isset($s['final']) ? $s['final'] : $s[0];
                $key = ($row['kds_name'] ?? '') . '|' . ($row['tax_percentage'] ?? '');
                if (!isset($salesMap[$key])) {
                    $salesMap[$key] = [
                        'final' => [
                            'kds'            => $row['kds'] ?? null,
                            'kds_name'       => $row['kds_name'] ?? '',
                            'tax_percentage' => $row['tax_percentage'] ?? 0,
                            'value'          => 0,
                        ],
                    ];
                }
                $salesMap[$key]['final']['value'] += (float)($row['value'] ?? 0);
            }
        }
        $sales = array_values($salesMap);
        foreach ($sales as &$saleRow) {
            $saleRow['final']['value'] = round($saleRow['final']['value'], 2);
        }
        unset($saleRow);
        return $sales;
    }

    public function dsr_report() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('0 days'));
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {
            $daterange  = $this->request->data['daterange'];
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $end_date   = trim($dates[1]);
            $endDate    = date('m/d/Y', strtotime($end_date));
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }

        $loc_id = !empty($this->request->data['Search']['cashier'])
            ? (int)$this->request->data['Search']['cashier']
            : 0;

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $loc_id ?: null);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = ['status' => 1, 'type' => 'Outlet'];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }

        // ── Build all filter variables upfront ───────────────────────────────
        if ($loc_id) {
            $cashiernames  = $this->Location->find('first', ['conditions' => ['id' => $loc_id]]);
            $cashiername   = $cashiernames['Location']['name'];
            $cashierFilter = " AND store_id=$loc_id";
            $oohFilter     = " AND ooh.store_id=$loc_id";
            $otTaxFilter   = " AND ot_tax.store_id=$loc_id";
            $otDFilter     = " AND ot_d.store_id=$loc_id";
            $otFilter      = " AND ot.store_id=$loc_id";
            $orderTaxFilter = " AND order_tax.store_id=$loc_id";
            $opFilter      = " AND op_d.store_id=$loc_id";
            $opdwFilter    = " AND op_dw.store_id=$loc_id";
            $opn1Filter    = " AND op_n1.store_id=$loc_id";
            $opn2Filter    = " AND op_n2.store_id=$loc_id";
            $opscFilter    = " AND op_sc.store_id=$loc_id";
            $opsc2Filter   = " AND op_sc2.store_id=$loc_id";
            $op_ncFilter    = " AND op_nc.store_id=$loc_id";
            $oponlyFilter   = " AND op.store_id=$loc_id";
        } elseif ($userRole != 'admin') {
            $cashiername   = 'All Stores';
            $cashierFilter = " AND store_id IN ($userLocationId)";
            $oohFilter     = " AND ooh.store_id IN ($userLocationId)";
            $otTaxFilter   = " AND ot_tax.store_id IN ($userLocationId)";
            $otDFilter     = " AND ot_d.store_id IN ($userLocationId)";
            $otFilter      = " AND ot.store_id IN ($userLocationId)";
            $orderTaxFilter = " AND order_tax.store_id IN ($userLocationId)";
            $opFilter      = " AND op_d.store_id IN ($userLocationId)";
            $opdwFilter    = " AND op_dw.store_id IN ($userLocationId)";
            $opn1Filter    = " AND op_n1.store_id IN ($userLocationId)";
            $opn2Filter    = " AND op_n2.store_id IN ($userLocationId)";
            $opscFilter    = " AND op_sc.store_id IN ($userLocationId)";
            $opsc2Filter   = " AND op_sc2.store_id IN ($userLocationId)";
            $op_ncFilter    = " AND op_nc.store_id IN ($userLocationId)";
            $oponlyFilter   = " AND op.store_id IN ($userLocationId)";
        } else {
            $cashiername   = 'All Stores';
            $cashierFilter = '';
            $oohFilter     = '';
            $otTaxFilter   = '';
            $otDFilter     = '';
            $otFilter      = '';
            $orderTaxFilter = '';
            $opFilter      = '';
            $opdwFilter    = '';
            $opn1Filter    = '';
            $opn2Filter    = '';
            $opscFilter    = '';
            $opsc2Filter   = '';
            $op_ncFilter    = '';
            $oponlyFilter   = '';
        }

        // ── Payments ──────────────────────────────────────────────────────────
        $payments = $this->Order_payment->query("
            SELECT
                SUM(amount) AS total_amount,
                SUM(IF(type=1,  amount, 0)) AS cash,
                SUM(IF(type=10, amount, 0)) AS hold,
                SUM(IF(type=2,  amount, 0)) AS card,
                SUM(IF(type=6,  amount, 0)) AS upi,
                SUM(IF(type=9,  amount, 0)) AS online,
                SUM(IF(type=12,  amount, 0)) AS reelo_wallet,
                SUM(IF(type=7,  amount, 0)) AS no_charge_with_tax,
                SUM(IF(type=7, ROUND(amount - (
                    SELECT SUM(ot_sub.total_tax)
                    FROM order_transactions ot_sub
                    WHERE ot_sub.status != 0
                      AND ot_sub.reference_id = order_payments.reference_id
                      AND ot_sub.store_id = order_payments.store_id
                ), 2), 0)) AS no_charge,
                SUM(IF(type=8, amount, 0)) AS storecard
            FROM order_payments
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
        ");

        $onlinepayments = $this->Order_payment->query("
            SELECT type_value, SUM(amount) AS total_amount
            FROM order_payments
            WHERE type = 11 AND status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
            GROUP BY type_value
        ");
        
        // Platform tax: 5% GST deposited by Zomato/Swiggy on behalf of restaurant
        $platform_tax_data = $this->Order_payment->query("
            SELECT type_value,
                   ROUND(SUM(amount) * 5/105, 2) AS platform_tax,
                   ROUND(SUM(amount) - SUM(amount)*5/105, 2) AS net_amount
            FROM order_payments
            WHERE type=11 AND status=2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
              AND type_value IN ('swiggy', 'zomato')
            GROUP BY type_value
        ");
        $platform_tax_by_channel = [];
        $platform_tax_total = 0;
        foreach ($platform_tax_data as $p) {
            $tv = $p['order_payments']['type_value'];
            $platform_tax_by_channel[$tv] = (float)$p[0]['platform_tax'];
            $platform_tax_total += (float)$p[0]['platform_tax'];
        }
        $platform_tax_total = round($platform_tax_total, 2);

        // Aliases used by view
        $platform_gst_total  = $platform_tax_total;
        $platform_gst_map    = $platform_tax_by_channel;
        $platformGstChannels = array_keys($platform_tax_by_channel);

        // ── Online Payment Net Sale per channel (dynamic — all type=11 channels) ──
        $online_net_sales = $this->Order_payment->query("
            SELECT op.type_value,
                ROUND(SUM(ot.price - ot.discount), 2) AS net_sale
            FROM order_payments op
            INNER JOIN order_transactions ot
                ON ot.reference_id = op.reference_id
                AND ot.store_id    = op.store_id
                AND ot.status      = 2
            WHERE op.type   = 11
              AND op.status = 2
              AND op.created BETWEEN '$startDatetime' AND '$endDatetime'
              $oponlyFilter
              AND ot.reference_id NOT IN (
                  SELECT op_nc.reference_id FROM order_payments op_nc
                  WHERE op_nc.type = 7 AND op_nc.status = 2 $op_ncFilter
              )
            GROUP BY op.type_value
            ORDER BY op.type_value
        ");

        // ── Transactions ──────────────────────────────────────────────────────
        $transactions = $this->Order_transaction->query("
            SELECT
                COUNT(order_transactions.id)            AS total_count,
                SUM(order_transactions.pax)             AS pax,
                SUM(order_transactions.total_tax)       AS total_tax,
                SUM(order_transactions.servicecharge)   AS servicecharge,
                SUM(order_transactions.discount)        AS discount,
                SUM(order_transactions.delivery_charge) AS delivery_charge,
                SUM(order_transactions.packing_charge)  AS packing_charge,
                SUM(order_transactions.amount)          AS amount,
                SUM(order_transactions.price)           AS price,
                SUM(order_transactions.round)           AS round,
                SUM(order_transactions.other_taxes)     AS other_taxes
            FROM order_transactions
            WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_transactions.status = 2
              $cashierFilter
              AND order_transactions.reference_id NOT IN (
                  SELECT op_nc.reference_id FROM order_payments op_nc
                  WHERE op_nc.type = 7 AND op_nc.status = 2 $cashierFilter
              )
        ");

        $transactionsnc = $this->Order_transaction->query("
            SELECT SUM(order_transactions.total_tax) AS total_tax
            FROM order_transactions
            WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_transactions.status = 2
              $cashierFilter
              AND order_transactions.reference_id IN (
                  SELECT op_nc2.reference_id FROM order_payments op_nc2
                  WHERE op_nc2.type = 7 AND op_nc2.status = 2 $cashierFilter
              )
        ");

        $transactionsubtotal = $this->Order_transaction->query("
            SELECT SUM(order_transactions.price)    AS price,
                   SUM(order_transactions.discount) AS discount
            FROM order_transactions
            WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_transactions.status = 2
              $cashierFilter
        ");

        // ── Taxes ─────────────────────────────────────────────────────────────
        $taxes = $this->Order_tax->query("
            SELECT order_tax.tax_id, order_tax.tax_name, order_tax.tax_percentage,
                   SUM(order_tax.tax_value) AS tax_value
            FROM order_tax
            WHERE order_tax.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_tax.status = 2
              $cashierFilter
              AND order_tax.reference_id NOT IN (
                  SELECT op_t.reference_id FROM order_payments op_t
                  WHERE op_t.type = 7 AND op_t.status = 2 $cashierFilter
              )
            GROUP BY order_tax.tax_id
        ");
        
        $charges_taxes = $this->Order_payment->query("
            SELECT tax_id, tax_name, tax_percentage, SUM(tax_value) AS tax_value
            FROM tax_charges
            WHERE status = 1
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
            GROUP BY tax_id
        ");

        // ── Net amounts ───────────────────────────────────────────────────────
        $net_amount_query = $this->Order_transaction->query("
            SELECT ROUND(SUM(ot.price) - IFNULL(SUM(ot.discount), 0), 2) AS net_amount
            FROM order_transactions ot
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $otFilter
              AND ot.reference_id NOT IN (
                  SELECT op_n1.reference_id FROM order_payments op_n1
                  WHERE op_n1.type = 7 AND op_n1.status = 2 $opn1Filter
              )
        ");

        $ncnet_amount_query = $this->Order_transaction->query("
            SELECT ROUND(SUM(ot.price) - IFNULL(SUM(ot.discount), 0), 2) AS net_amount
            FROM order_transactions ot
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $otFilter
              AND ot.reference_id IN (
                  SELECT op_n2.reference_id FROM order_payments op_n2
                  WHERE op_n2.type = 7 AND op_n2.status = 2 $opn2Filter
              )
        ");

        // ── Dish sales ────────────────────────────────────────────────────────
        // ✅ store_id filter moved from ON to WHERE using $otTaxFilter
        $dish_sales = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT 
                    ooh.id,
                    ooh.local_id,
                    ooh.reference_id,
                    ooh.store_id,
                    ooh.product_id,
                    ooh.quantity,
                    cp.dish_type,
                    ot_tax.taxable_price AS item_amount
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                INNER JOIN (
                    SELECT orderhistory_id, MAX(taxable_price) AS taxable_price, store_id
                    FROM order_tax
                    WHERE order_tax.status != 0 $orderTaxFilter
                    GROUP BY orderhistory_id, store_id
                ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND ooh.status = 2
                  $oohFilter
                  AND ooh.reference_id NOT IN (
                      SELECT op_d.reference_id FROM order_payments op_d
                      WHERE op_d.type = 7 AND op_d.status = 2 $opFilter
                  )
            ),
            bill_totals AS (
                SELECT 
                    ot.reference_id,
                    ot.store_id,
                    (ot.price - ot.discount + ot.servicecharge) AS bill_net,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $otFilter
                  AND ot.reference_id NOT IN (
                      SELECT op_d.reference_id FROM order_payments op_d
                      WHERE op_d.type = 7 AND op_d.status = 2 $opFilter
                  )
                GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount, ot.servicecharge
            )
            SELECT 
                cp.dish_type,
                SUM(ia.quantity) AS total_qty,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            INNER JOIN catalog_product cp ON cp.id = ia.product_id
            GROUP BY cp.dish_type
            ORDER BY cp.dish_type DESC
        ");

        // ✅ alias ot — uses $otFilter
        $dish_saleswithoutservice = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT 
                    ooh.id,
                    ooh.local_id,
                    ooh.reference_id,
                    ooh.store_id,
                    ooh.product_id,
                    ooh.quantity,
                    cp.dish_type,
                    (ooh.price * ooh.quantity) * (1 - IFNULL(ot.discount_percentage, 0) / 100) AS item_amount
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                LEFT JOIN (
                    SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                    FROM order_tax
                    WHERE order_tax.status != 0 $orderTaxFilter
                    GROUP BY orderhistory_id, store_id
                ) ot ON ot.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot.store_id = ooh.store_id
                WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND ooh.status = 2
                  $oohFilter
                  AND ooh.reference_id NOT IN (
                      SELECT op_dw.reference_id FROM order_payments op_dw
                      WHERE op_dw.type = 7 AND op_dw.status = 2 $opdwFilter
                  )
            ),
            bill_totals AS (
                SELECT 
                    ot.reference_id,
                    ot.store_id,
                    (ot.price - ot.discount) AS bill_net,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $otFilter
                  AND ot.reference_id NOT IN (
                      SELECT op_dw.reference_id FROM order_payments op_dw
                      WHERE op_dw.type = 7 AND op_dw.status = 2 $opdwFilter
                  )
                GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount
            )
            SELECT 
                cp.dish_type,
                SUM(ia.quantity) AS total_qty,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            INNER JOIN catalog_product cp ON cp.id = ia.product_id
            GROUP BY cp.dish_type
            ORDER BY cp.dish_type DESC
        ");

        // ✅ alias ot_d — uses $otDFilter
        $sc_base_sales = $this->Order_orderhistory->query("
            SELECT cp.dish_type,
                   ROUND(SUM(ooh.price * ooh.quantity * (1 - IFNULL(ot_d.discount_percentage, 0) / 100)), 2) AS base_amount
            FROM order_orderhistories ooh
            INNER JOIN catalog_product cp ON cp.id = ooh.product_id
            LEFT JOIN (
                SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                FROM order_tax
                WHERE order_tax.status != 0 $orderTaxFilter
                GROUP BY orderhistory_id, store_id
            ) ot_d ON ot_d.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_d.store_id = ooh.store_id
            WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND ooh.status = 2
              $oohFilter
              AND ooh.reference_id NOT IN (
                  SELECT op_sc.reference_id FROM order_payments op_sc
                  WHERE op_sc.type = 7 AND op_sc.status = 2 $oohFilter
              )
            GROUP BY cp.dish_type
        ");

            // Extract VAT rates dynamically
            $vat_rates = [];
            if (isset($taxes)) {
                foreach ($taxes as $tax) {
                    $tax_name = $tax['order_tax']['tax_name'];
                    if (stripos($tax_name, 'VAT') !== false) {
                        $vat_rates[] = (float)$tax['order_tax']['tax_percentage'];
                    }
                }
            }
            $vat_rates = array_unique($vat_rates);
            sort($vat_rates);
            if (empty($vat_rates)) {
                $vat_rate = "18.9";
            } else {
                $vat_rate = implode("/", $vat_rates);
            }

            // ── Service Charge Breakup (Proportional allocation per transaction) ──
            $sc_data = $this->Order_orderhistory->query("
                WITH item_amounts AS (
                    SELECT 
                        ooh.id,
                        ooh.local_id,
                        ooh.reference_id,
                        ooh.store_id,
                        ooh.product_id,
                        ooh.quantity,
                        cp.dish_type,
                        ot_tax.tax_percentage,
                        ot_tax.taxable_price AS item_amount
                    FROM order_transactions ot
                    INNER JOIN order_orderhistories ooh ON ooh.reference_id = ot.reference_id AND ooh.store_id = ot.store_id
                    INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                    INNER JOIN (
                        SELECT orderhistory_id, store_id, MAX(taxable_price) AS taxable_price, MAX(tax_percentage) AS tax_percentage
                        FROM order_tax
                        WHERE order_tax.status != 0 $orderTaxFilter
                        GROUP BY orderhistory_id, store_id
                    ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                    WHERE ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                      AND ot.status = 2
                      $otFilter
                      AND ooh.status = 2
                      AND ot.reference_id NOT IN (
                          SELECT op_sc2.reference_id FROM order_payments op_sc2
                          WHERE op_sc2.type = 7 AND op_sc2.status = 2 $cashierFilter
                      )
                ),
                bill_totals AS (
                    SELECT 
                        ot.reference_id,
                        ot.store_id,
                        ot.servicecharge,
                        SUM(ia.item_amount) AS bill_item_sum
                    FROM order_transactions ot
                    INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                    WHERE ot.status = 2
                      AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                      $otFilter
                      AND ot.reference_id NOT IN (
                          SELECT op_sc2.reference_id FROM order_payments op_sc2
                          WHERE op_sc2.type = 7 AND op_sc2.status = 2 $cashierFilter
                      )
                    GROUP BY ot.reference_id, ot.store_id, ot.servicecharge
                )
                SELECT 
                    cp.dish_type,
                    ia.tax_percentage,
                    ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.servicecharge ELSE 0 END), 2) AS sc_amount
                FROM item_amounts ia
                INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
                INNER JOIN catalog_product cp ON cp.id = ia.product_id
                GROUP BY cp.dish_type, ia.tax_percentage
            ");

            $total_sc_from_transactions = isset($transactions[0][0]['servicecharge']) ? (float)$transactions[0][0]['servicecharge'] : 0.0;
            $sc_gst_raw = 0.0;
            $sc_vat_raw = 0.0;
            $vat_on_sc_raw = 0.0;
            foreach ($sc_data as $row) {
                $dt = (int)$row['cp']['dish_type'];
                $amt = (float)$row[0]['sc_amount'];
                $pct = isset($row['ia']['tax_percentage']) ? (float)$row['ia']['tax_percentage'] : (!empty($vat_rates) ? $vat_rates[0] : 18.9);
                if ($dt == 2) {
                    $sc_vat_raw += $amt;
                    $vat_on_sc_raw += $amt * ($pct / 100);
                } else {
                    $sc_gst_raw += $amt;
                }
            }
            $diff = $total_sc_from_transactions - ($sc_gst_raw + $sc_vat_raw);
            if (abs($diff) > 0.001) {
                $sc_gst_raw += $diff;
            }
            $sc_gst = round($sc_gst_raw, 2);
            $sc_vat = round($sc_vat_raw, 2);
            $sc_total = round($total_sc_from_transactions, 2);
            $vat_on_sc = round($vat_on_sc_raw, 2);

            $scbreakuparr = [
                'sc_total'   => $sc_total,
                'sc_gst'     => $sc_gst,
                'sc_vat'     => $sc_vat,
                'sgst_on_sc' => round($sc_gst * 0.025, 2),
                'cgst_on_sc' => round($sc_gst * 0.025, 2),
                'vat_on_sc'  => $vat_on_sc,
            ];
        // ── Taxable / Non-taxable ─────────────────────────────────────────────
        $taxable = $this->Order_orderhistory->query("
            SELECT SUM(total_price) AS total_taxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage != 0
              $cashierFilter
              AND reference_id NOT IN (
                  SELECT op_tx1.reference_id FROM order_payments op_tx1
                  WHERE op_tx1.type = 7 AND op_tx1.status = 2 $cashierFilter
              )
        ");

        $nontaxable = $this->Order_orderhistory->query("
            SELECT SUM(total_price) AS total_nontaxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage = 0
              $cashierFilter
              AND reference_id NOT IN (
                  SELECT op_tx2.reference_id FROM order_payments op_tx2
                  WHERE op_tx2.type = 7 AND op_tx2.status = 2 $cashierFilter
              )
        ");

        $nctaxable = $this->Order_orderhistory->query("
            SELECT SUM(total_price) AS total_taxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage != 0
              $cashierFilter
              AND reference_id IN (
                  SELECT op_tx3.reference_id FROM order_payments op_tx3
                  WHERE op_tx3.type = 7 AND op_tx3.status = 2 $cashierFilter
              )
        ");

        $ncnontaxable = $this->Order_orderhistory->query("
            SELECT SUM(total_price) AS total_nontaxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage = 0
              $cashierFilter
              AND reference_id IN (
                  SELECT op_tx4.reference_id FROM order_payments op_tx4
                  WHERE op_tx4.type = 7 AND op_tx4.status = 2 $cashierFilter
              )
        ");

        // ── KDS sales (per-day, deduped per item — sum minus service charge = net sale)
        $sales   = $this->_dsrAggregateKdsSales($startdate, $enddate, $cashierFilter, false, $loc_id ?: null);
        $ncsales = $this->_dsrAggregateKdsSales($startdate, $enddate, $cashierFilter, true, $loc_id ?: null);

        // ── View variables ────────────────────────────────────────────────────
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $users = $this->Location->find('all', ['conditions' => $conditions]);
        $this->set(compact('users'));

        $lastSyncData   = $this->_getLastSyncTimes();
        $lastPushTime   = $lastSyncData['push']['time']         ?? 'Never';
        $lastPullTime   = $lastSyncData['pull']['time']         ?? 'Never';
        $lastPushBills  = $lastSyncData['push']['bills_synced'] ?? 0;
        $lastPushFailed = $lastSyncData['push']['bills_failed'] ?? 0;

        // ✅ Guard against undefined $transactions on GET request
        $other_taxes_sum = isset($transactions[0][0]['other_taxes'])
            ? round($transactions[0][0]['other_taxes'], 2)
            : 0;

        $this->set(compact(
            'onlinepayments', 'online_net_sales', 'ncnet_amount_query', 'nctaxable', 'ncnontaxable',
            'ncsales', 'payments', 'cashiername', 'transactions', 'taxes',
            'sales', 'transactionsubtotal', 'transactionsnc', 'dish_sales',
            'sc_base_sales', 'net_amount_query', 'taxable', 'nontaxable',
            'lastPushTime', 'lastPullTime', 'lastPushBills', 'lastPushFailed',
            'dish_saleswithoutservice','other_taxes_sum','charges_taxes','platform_tax_by_channel', 'platform_tax_total',
            'platform_gst_total', 'platform_gst_map', 'platformGstChannels',
                'scbreakuparr', 'vat_rate'
            // ✅ removed undefined $taxable_price
        ));
    }
    
    public function dsr_reportexport() {
        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));

        $cashier_id = $_GET['cashier'];
        $cashiername = 'All Stores';

        if ($cashier_id) {
            $cashiernames = $this->Location->find('first', array('conditions' => array('id' => $cashier_id)));
            $cashiername = $cashiernames['Location']['name'];
        }
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $datas = array();

        $allTaxTypes = [
            'CGST(2.5%)' => true,
            'SGST(2.5%)' => true,
        ];
        $allSalesTypes = [];
        $allonlinepayTypes = [
            'swiggy' => true,
            'zomato' => true,
            'eazydiner mislleneous' => true,
        ];
        $allOnlineNetSalesTypes = [
            'swiggy' => true,
            'zomato' => true,
            'eazydiner mislleneous' => true,
        ];
        $alldishSalesTypes = [
            0 => true,
            1 => true,
            2 => true,
            3 => true,
        ];
        $ncallSalesTypes = [];
        $alldishSalesWithoutTypes = [
            0 => true,
            1 => true,
            2 => true,
            3 => true,
        ];
        $allChargesTaxTypes = [
            'Charges Tax-SGST(2.5%)' => true,
            'Charges Tax-CGST(2.5%)' => true,
        ];

        $vat_rate = 18.9; // fallback default, overwritten by the range-wide block below

        $dishTypeMap = [
            0 => 'Food',
            1 => 'Beverage',
            2 => 'Liquor',
            3 => 'Others'
        ];

        // ------------------------------------------------------------------
        // Filters do NOT depend on the day being iterated - compute ONCE,
        // instead of recomputing identically on every pass of the day loop.
        // ------------------------------------------------------------------
        if ($userRole != 'admin' && !empty($userLocationId)) {
            $cashierFilter     = $cashier_id ? " AND store_id=$cashier_id"         : " AND store_id IN ($userLocationId)";
            $oohFilter         = $cashier_id ? " AND ooh.store_id=$cashier_id"     : " AND ooh.store_id IN ($userLocationId)";
            $otTaxFilter       = $cashier_id ? " AND ot_tax.store_id=$cashier_id"  : " AND ot_tax.store_id IN ($userLocationId)";
            $otDFilter         = $cashier_id ? " AND ot_d.store_id=$cashier_id"    : " AND ot_d.store_id IN ($userLocationId)";
            $otFilter          = $cashier_id ? " AND ot.store_id=$cashier_id"      : " AND ot.store_id IN ($userLocationId)";
            $orderTaxFilter    = $cashier_id ? " AND order_tax.store_id=$cashier_id" : " AND order_tax.store_id IN ($userLocationId)";
            $opFilter          = $cashier_id ? " AND op_d.store_id=$cashier_id"    : " AND op_d.store_id IN ($userLocationId)";
            $opdwFilter        = $cashier_id ? " AND op_dw.store_id=$cashier_id"   : " AND op_dw.store_id IN ($userLocationId)";
            $op_ncFilter       = $cashier_id ? " AND op_nc.store_id=$cashier_id"   : " AND op_nc.store_id IN ($userLocationId)";
            $oponlyFilter      = $cashier_id ? " AND op.store_id=$cashier_id"      : " AND op.store_id IN ($userLocationId)";
        } else {
            $cashierFilter     = $cashier_id ? " AND store_id=$cashier_id"         : '';
            $oohFilter         = $cashier_id ? " AND ooh.store_id=$cashier_id"     : '';
            $otTaxFilter       = $cashier_id ? " AND ot_tax.store_id=$cashier_id"  : '';
            $otDFilter         = $cashier_id ? " AND ot_d.store_id=$cashier_id"    : '';
            $otFilter          = $cashier_id ? " AND ot.store_id=$cashier_id"      : '';
            $orderTaxFilter    = $cashier_id ? " AND order_tax.store_id=$cashier_id" : '';
            $opFilter          = $cashier_id ? " AND op_d.store_id=$cashier_id"    : '';
            $opdwFilter        = $cashier_id ? " AND op_dw.store_id=$cashier_id"   : '';
            $op_ncFilter       = $cashier_id ? " AND op_nc.store_id=$cashier_id"   : '';
            $oponlyFilter      = $cashier_id ? " AND op.store_id=$cashier_id"      : '';
        }

        // moved up (was previously declared right before the per-day
        // loop) so the new range-wide block below can reuse it.
        $noChargeFilter = "reference_id NOT IN (SELECT reference_id from order_payments WHERE type=7 and status=2 $cashierFilter)";

        // ------------------------------------------------------------------
        // Whole-range window (was per-day $dayStart/$dayEnd, now the full span)
        // ------------------------------------------------------------------
        $loc_id = !empty($cashier_id) ? (int)$cashier_id : null;
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $loc_id);
        $rangeStart    = $_shiftRange['startDatetime'];
        $rangeEnd      = $_shiftRange['endDatetime'];
        $shiftStartTime = date('H:i:s', strtotime($rangeStart));

        // ------------------------------------------------------------------
        // RANGE-WIDE DISCOVERY FOR UNIFORM EXCEL COLUMNS ACROSS ALL STORES
        // ------------------------------------------------------------------

        // 1. Online payment channels (type = 11) across all stores
        $rangeOnlineTypes = $this->Order_payment->query("
            SELECT DISTINCT type_value
            FROM order_payments
            WHERE type = 11 AND status = 2
              AND created BETWEEN '$rangeStart' AND '$rangeEnd'
        ");
        if (!empty($rangeOnlineTypes)) {
            foreach ($rangeOnlineTypes as $rot) {
                $tv = strtolower(trim($rot['order_payments']['type_value'] ?? ''));
                if ($tv !== '') {
                    $allonlinepayTypes[$tv] = true;
                    $allOnlineNetSalesTypes[$tv] = true;
                }
            }
        }

        // 2. Discover all taxes (tax_name, tax_percentage) across all stores for this date range
        $rangeAllStoreTaxes = $this->Order_tax->query("
            SELECT DISTINCT tax_name, tax_percentage
            FROM order_tax
            WHERE status = 2
              AND created BETWEEN '$rangeStart' AND '$rangeEnd'
              AND reference_id NOT IN (
                  SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2
              )
        ");
        if (!empty($rangeAllStoreTaxes)) {
            foreach ($rangeAllStoreTaxes as $rat) {
                $tName = trim($rat['order_tax']['tax_name'] ?? '');
                $tPerc = trim($rat['order_tax']['tax_percentage'] ?? '');
                if ($tName !== '') {
                    $key = $tName . '(' . $tPerc . '%)';
                    $allTaxTypes[$key] = true;
                }
            }
        }

        // 3. Discover all charges taxes across all stores for this date range
        $rangeAllStoreChargesTaxes = $this->Order_payment->query("
            SELECT DISTINCT tax_name, tax_percentage
            FROM tax_charges
            WHERE status = 1
              AND created BETWEEN '$rangeStart' AND '$rangeEnd'
        ");
        if (!empty($rangeAllStoreChargesTaxes)) {
            foreach ($rangeAllStoreChargesTaxes as $rct) {
                $ctName = trim($rct['tax_charges']['tax_name'] ?? $rct[0]['tax_name'] ?? '');
                $ctPerc = trim($rct['tax_charges']['tax_percentage'] ?? $rct[0]['tax_percentage'] ?? '');
                if ($ctName !== '') {
                    $key = 'Charges Tax-' . $ctName . '(' . $ctPerc . '%)';
                    $allChargesTaxTypes[$key] = true;
                }
            }
        }

        // 4. Discover all KDS / station sales and NC sales across all stores for this date range
        $rangeSalesAllStores = $this->Order_tax->query(
            $this->_dsrKdsSalesSql($rangeStart, $rangeEnd, '', false)
        );
        if (!empty($rangeSalesAllStores)) {
            foreach ($rangeSalesAllStores as $s) {
                $kName = trim($s['final']['kds_name'] ?? '');
                if ($kName !== '') {
                    $key = $kName . '(' . $s['final']['tax_percentage'] . '%)';
                    $allSalesTypes[$key] = true;
                }
            }
        }

        $rangeNcSalesAllStores = $this->Order_tax->query(
            $this->_dsrKdsSalesSql($rangeStart, $rangeEnd, '', true)
        );
        if (!empty($rangeNcSalesAllStores)) {
            foreach ($rangeNcSalesAllStores as $s) {
                $kName = trim($s['final']['kds_name'] ?? '');
                if ($kName !== '') {
                    $key = 'NC - ' . $kName . '(' . $s['final']['tax_percentage'] . '%)';
                    $ncallSalesTypes[$key] = true;
                }
            }
        }

        // "Business day" bucket: reproduces the original per-day windowing
        // [D + shiftstart, D+1 + shiftend] as a SQL expression.
        $bizDayFromOoh    = "CASE WHEN TIME(ooh.created) >= '" . $shiftStartTime . "' THEN DATE(ooh.created) ELSE DATE(DATE_SUB(ooh.created, INTERVAL 1 DAY)) END";
        $bizDayFromBucket = "CASE WHEN TIME(ot_bucket.created) >= '" . $shiftStartTime . "' THEN DATE(ot_bucket.created) ELSE DATE(DATE_SUB(ot_bucket.created, INTERVAL 1 DAY)) END";

        // ==================================================================
        // BULK: dish_sales  (was: 1 heavy query PER DAY -> now 1 total)
        // ==================================================================
        $dish_sales_bulk = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT
                    ooh.id, ooh.local_id, ooh.reference_id, ooh.store_id, ooh.product_id, ooh.quantity,
                    cp.dish_type,
                    ot_tax.taxable_price AS item_amount,
                    $bizDayFromBucket AS business_day
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                INNER JOIN order_transactions ot_bucket
                    ON ot_bucket.reference_id = ooh.reference_id
                    AND ot_bucket.store_id = ooh.store_id
                    AND ot_bucket.status = 2
                INNER JOIN (
                    SELECT orderhistory_id, MAX(taxable_price) AS taxable_price, store_id
                    FROM order_tax
                    WHERE status != 0 $orderTaxFilter
                    GROUP BY orderhistory_id, store_id
                ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                WHERE ot_bucket.created BETWEEN '$rangeStart' AND '$rangeEnd'
                AND ooh.status = 2 $oohFilter
                AND ooh.reference_id NOT IN (
                    SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                )
            ),
            bill_totals AS (
                SELECT
                    ot.reference_id, ot.store_id,
                    (ot.price - ot.discount + ot.servicecharge) AS bill_net,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                AND ot.created BETWEEN '$rangeStart' AND '$rangeEnd' $otFilter
                AND ot.reference_id NOT IN (
                    SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                )
                GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount, ot.servicecharge
            )
            SELECT
                ia.business_day,
                cp.dish_type,
                SUM(ia.quantity) AS total_qty,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            INNER JOIN catalog_product cp ON cp.id = ia.product_id
            GROUP BY ia.business_day, cp.dish_type
            ORDER BY ia.business_day, cp.dish_type DESC
        ");

        $dishSalesByDay = [];
        foreach ($dish_sales_bulk as $row) {
            $day = $row['ia']['business_day'];
            $dishSalesByDay[$day][] = $row;
            $alldishSalesTypes[$row['cp']['dish_type']] = true;
        }

        // ==================================================================
        // BULK: dish_saleswithoutservice  (was: 1 heavy query PER DAY)
        // ==================================================================
        $dish_saleswithoutservice_bulk = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT
                    ooh.id, ooh.local_id, ooh.reference_id, ooh.store_id, ooh.product_id, ooh.quantity,
                    cp.dish_type,
                    (ooh.price * ooh.quantity) * (1 - IFNULL(ot.discount_percentage, 0) / 100) AS item_amount,
                    $bizDayFromBucket AS business_day
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                INNER JOIN order_transactions ot_bucket
                    ON ot_bucket.reference_id = ooh.reference_id
                    AND ot_bucket.store_id = ooh.store_id
                    AND ot_bucket.status = 2
                LEFT JOIN (
                    SELECT
                        orderhistory_id,
                        MAX(discount_percentage) AS discount_percentage,
                        store_id
                    FROM order_tax
                    WHERE status != 0 $orderTaxFilter
                    GROUP BY orderhistory_id, store_id
                ) ot ON ot.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot.store_id = ooh.store_id
                WHERE ot_bucket.created BETWEEN '$rangeStart' AND '$rangeEnd'
                AND ooh.status = 2 $oohFilter
                AND ooh.reference_id NOT IN (
                    SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                )
            ),
            bill_totals AS (
                SELECT
                    ot.reference_id, ot.store_id,
                    (ot.price - ot.discount) AS bill_net,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                AND ot.created BETWEEN '$rangeStart' AND '$rangeEnd' $otFilter
                AND ot.reference_id NOT IN (
                    SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                )
                GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount
            )
            SELECT
                ia.business_day,
                cp.dish_type,
                SUM(ia.quantity) AS total_qty,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            INNER JOIN catalog_product cp ON cp.id = ia.product_id
            GROUP BY ia.business_day, cp.dish_type
            ORDER BY ia.business_day, cp.dish_type DESC
        ");

        $dishSalesWithoutByDay = [];
        foreach ($dish_saleswithoutservice_bulk as $row) {
            $day = $row['ia']['business_day'];
            $dishSalesWithoutByDay[$day][] = $row;
            $alldishSalesWithoutTypes[$row['cp']['dish_type']] = true;
        }

        // ==================================================================
        // BULK: sc_base_sales  (was: 1 query PER DAY)
        // ==================================================================
        $sc_base_sales_bulk = $this->Order_orderhistory->query("
            SELECT
                $bizDayFromOoh AS business_day,
                cp.dish_type,
                ROUND(SUM(ooh.price * ooh.quantity * (1 - IFNULL(ot_d.discount_percentage, 0) / 100)), 2) AS base_amount
            FROM order_orderhistories ooh
            INNER JOIN catalog_product cp ON cp.id = ooh.product_id
            LEFT JOIN (
                SELECT orderhistory_id, store_id, MAX(discount_percentage) AS discount_percentage
                FROM order_tax
                WHERE status != 0 $orderTaxFilter
                GROUP BY orderhistory_id, store_id
            ) ot_d ON ot_d.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_d.store_id = ooh.store_id
            WHERE ooh.created BETWEEN '$rangeStart' AND '$rangeEnd'
            AND ooh.status = 2 $oohFilter
            AND ooh.reference_id NOT IN (
                SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
            )
            GROUP BY business_day, cp.dish_type
        ");

        $scBaseSalesByDay = [];
        foreach ($sc_base_sales_bulk as $row) {
            $day = $row[0]['business_day'];
            $scBaseSalesByDay[$day][] = $row;
        }

        // ==================================================================
        // BULK: sc_data (service-charge breakup) (Proportional allocation)
        // ==================================================================
        $sc_data_bulk = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT 
                    ooh.id,
                    ooh.local_id,
                    ooh.reference_id,
                    ooh.store_id,
                    ooh.product_id,
                    ooh.quantity,
                    cp.dish_type,
                    ot_tax.tax_percentage,
                    ot_tax.taxable_price AS item_amount,
                    $bizDayFromBucket AS business_day
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                INNER JOIN order_transactions ot_bucket
                    ON ot_bucket.reference_id = ooh.reference_id
                    AND ot_bucket.store_id = ooh.store_id
                    AND ot_bucket.status = 2
                INNER JOIN (
                    SELECT orderhistory_id, store_id, MAX(taxable_price) AS taxable_price, MAX(tax_percentage) AS tax_percentage
                    FROM order_tax
                    WHERE order_tax.status != 0 $orderTaxFilter
                    GROUP BY orderhistory_id, store_id
                ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                WHERE ot_bucket.created BETWEEN '$rangeStart' AND '$rangeEnd'
                  AND ooh.status = 2
                  $oohFilter
                  AND ooh.reference_id NOT IN (
                      SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                  )
            ),
            bill_totals AS (
                SELECT 
                    ot.reference_id,
                    ot.store_id,
                    ot.servicecharge,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$rangeStart' AND '$rangeEnd'
                  $otFilter
                  AND ot.reference_id NOT IN (
                      SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                  )
                GROUP BY ot.reference_id, ot.store_id, ot.servicecharge
            )
            SELECT 
                ia.business_day,
                cp.dish_type,
                ia.tax_percentage,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.servicecharge ELSE 0 END), 2) AS sc_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            INNER JOIN catalog_product cp ON cp.id = ia.product_id
            GROUP BY ia.business_day, cp.dish_type, ia.tax_percentage
        ");

        $scDataByDay = [];
        foreach ($sc_data_bulk as $row) {
            $day = $row[0]['business_day'] ?? $row['ia']['business_day'] ?? '';
            if ($day) {
                $scDataByDay[$day][] = $row;
            }
        }

        // ==================================================================
        // RANGE-WIDE "Other Charges Tax Breakup" — computed with the exact
        // same queries and formulas dsr_report() uses, run ONCE over the
        // whole export date range as a single window (never aggregated
        // from per-day splits). dsr_report() itself never loops by day —
        // it runs one query over [startDatetime, endDatetime] — so this is
        // the only way to guarantee the Total row matches the view exactly.
        // (Previously this was approximated by summing/ratio-splitting the
        // per-day bulk $sc_data_bulk data, which used a different join path
        // than the view's own $sc_data query and drifted from it.)
        // ==================================================================

        // 1) Range-wide taxes: unified VAT-rate label + tax segregation total
        //    — identical query/logic to dsr_report()'s $taxes + $vat_rate block.
        $range_taxes = $this->Order_tax->query("
            SELECT order_tax.tax_id, order_tax.tax_name, order_tax.tax_percentage,
                   SUM(order_tax.tax_value) AS tax_value
            FROM order_tax
            WHERE order_tax.created BETWEEN '$rangeStart' AND '$rangeEnd'
              AND order_tax.status = 2
              $cashierFilter
              AND order_tax.$noChargeFilter
            GROUP BY order_tax.tax_id
        ");
        $range_vat_rates = [];
        $range_tax_segregation_total = 0.0;
        foreach ($range_taxes as $rt) {
            $range_tax_segregation_total += round($rt[0]['tax_value'], 2);
            if (stripos($rt['order_tax']['tax_name'], 'VAT') !== false) {
                $pct = (float)$rt['order_tax']['tax_percentage'];
                if ($pct > 0) {
                    $range_vat_rates[] = $pct;
                }
            }
        }
        $range_vat_rates = array_unique($range_vat_rates);
        sort($range_vat_rates);

        // Discover global range-wide VAT rate across all stores so all store sheets have the exact same rate label
        $global_vat_taxes = $this->Order_tax->query("
            SELECT DISTINCT order_tax.tax_percentage
            FROM order_tax
            WHERE order_tax.created BETWEEN '$rangeStart' AND '$rangeEnd'
              AND order_tax.status = 2
              AND order_tax.tax_name LIKE '%VAT%'
              AND order_tax.tax_percentage > 0
        ");
        $global_vat_rates = [];
        if (!empty($global_vat_taxes)) {
            foreach ($global_vat_taxes as $gvt) {
                $pct = (float)$gvt['order_tax']['tax_percentage'];
                if ($pct > 0) {
                    $global_vat_rates[] = $pct;
                }
            }
        }
        $global_vat_rates = array_unique($global_vat_rates);
        sort($global_vat_rates);
        $global_vat_rate = empty($global_vat_rates) ? "18.9" : implode("/", $global_vat_rates);

        $vat_rate = !empty($global_vat_rate) ? $global_vat_rate : (empty($range_vat_rates) ? "18.9" : implode("/", $range_vat_rates));

        // Ensure VAT on SC column is registered uniformly
        $allChargesTaxTypes["Charges Tax-VAT on SC({$vat_rate}%)"] = true;

        // 2) Range-wide transaction totals (total_tax rounded BEFORE
        //    subtracting, exactly like dsr_report(): $total_tax_val = round(...);
        //    $computed_other_tax = round($total_tax_val - $tax_segregation_total, 2);)
        $range_transactions = $this->Order_transaction->query("
            SELECT SUM(total_tax) AS total_tax, SUM(servicecharge) AS servicecharge
            FROM order_transactions
            WHERE status = 2
              AND created BETWEEN '$rangeStart' AND '$rangeEnd'
              $cashierFilter
              AND $noChargeFilter
        ");
        $range_total_tax_val = isset($range_transactions[0][0]['total_tax'])
            ? round((float)$range_transactions[0][0]['total_tax'], 2)
            : 0.0;
        $range_total_sc = isset($range_transactions[0][0]['servicecharge'])
            ? (float)$range_transactions[0][0]['servicecharge']
            : 0.0;
        $range_computed_other_tax = round($range_total_tax_val - $range_tax_segregation_total, 2);

        // 3) Range-wide known "charges_taxes" (e.g. platform packing GST) —
        //    identical query to dsr_report()'s $charges_taxes.
        $range_charges_taxes = $this->Order_payment->query("
            SELECT tax_id, tax_name, tax_percentage, SUM(tax_value) AS tax_value
            FROM tax_charges
            WHERE status = 1
              AND created BETWEEN '$rangeStart' AND '$rangeEnd'
              $cashierFilter
            GROUP BY tax_id
        ");
        $range_charges_taxes_sum = 0.0;
        // Captures the exact range-wide total PER real tax_charges row (not
        // just the grand sum), so every real "Charges Tax-<name>" column in
        // the Total row can be forced to the exact range value too.
        $range_real_charges_totals = [];
        foreach ($range_charges_taxes as $ct) {
            $ctax_value = (float)($ct[0]['tax_value'] ?? 0);
            $range_charges_taxes_sum += $ctax_value;

            $ctax_name       = $ct['tax_charges']['tax_name']       ?? $ct[0]['tax_name']       ?? '';
            $ctax_percentage = $ct['tax_charges']['tax_percentage'] ?? $ct[0]['tax_percentage'] ?? '';
            $key = 'Charges Tax-' . htmlspecialchars($ctax_name) . '(' . htmlspecialchars($ctax_percentage) . '%)';
            $range_real_charges_totals[$key] = round(($range_real_charges_totals[$key] ?? 0) + $ctax_value, 2);
        }

        // 4) Range-wide $scbreakuparr — identical query/formula to
        //    dsr_report()'s $sc_data + the SC-split loop that builds
        //    $scbreakuparr, run as ONE query over the whole range (not
        //    per-day, not from the per-day $sc_data_bulk join path).
        $range_sc_data = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT 
                    ooh.id,
                    ooh.local_id,
                    ooh.reference_id,
                    ooh.store_id,
                    ooh.product_id,
                    ooh.quantity,
                    cp.dish_type,
                    ot_tax.tax_percentage,
                    ot_tax.taxable_price AS item_amount
                FROM order_transactions ot
                INNER JOIN order_orderhistories ooh ON ooh.reference_id = ot.reference_id AND ooh.store_id = ot.store_id
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                INNER JOIN (
                    SELECT orderhistory_id, store_id, MAX(taxable_price) AS taxable_price, MAX(tax_percentage) AS tax_percentage
                    FROM order_tax
                    WHERE order_tax.status != 0 $orderTaxFilter
                    GROUP BY orderhistory_id, store_id
                ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                WHERE ot.created BETWEEN '$rangeStart' AND '$rangeEnd'
                  AND ot.status = 2
                  $otFilter
                  AND ooh.status = 2
                  AND ot.reference_id NOT IN (
                      SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                  )
            ),
            bill_totals AS (
                SELECT 
                    ot.reference_id,
                    ot.store_id,
                    ot.servicecharge,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$rangeStart' AND '$rangeEnd'
                  $otFilter
                  AND ot.reference_id NOT IN (
                      SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $cashierFilter
                  )
                GROUP BY ot.reference_id, ot.store_id, ot.servicecharge
            )
            SELECT 
                cp.dish_type,
                ia.tax_percentage,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.servicecharge ELSE 0 END), 2) AS sc_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            INNER JOIN catalog_product cp ON cp.id = ia.product_id
            GROUP BY cp.dish_type, ia.tax_percentage
        ");

        $range_sc_gst_raw    = 0.0;
        $range_sc_vat_raw    = 0.0;
        $range_vat_on_sc_raw = 0.0;
        foreach ($range_sc_data as $row) {
            $dt  = (int)$row['cp']['dish_type'];
            $amt = (float)$row[0]['sc_amount'];
            $pct = isset($row['ia']['tax_percentage']) ? (float)$row['ia']['tax_percentage'] : 18.9;
            if ($dt == 2) {
                $range_sc_vat_raw    += $amt;
                $range_vat_on_sc_raw += $amt * ($pct / 100);
            } else {
                $range_sc_gst_raw += $amt;
            }
        }
        $range_sc_diff = $range_total_sc - ($range_sc_gst_raw + $range_sc_vat_raw);
        if (abs($range_sc_diff) > 0.001) {
            $range_sc_gst_raw += $range_sc_diff;
        }

        // These feed both the "Service Charge Breakup" Total row columns
        // AND the ratio used to split "Other Charges Tax" below — exactly
        // matching dsr_report()'s $scbreakuparr for the same date window.
        $agg_sc_gst     = round($range_sc_gst_raw, 2);
        $agg_sc_vat     = round($range_sc_vat_raw, 2);
        $agg_vat_on_sc  = round($range_vat_on_sc_raw, 2);
        $agg_sgst_on_sc = round($agg_sc_gst * 0.025, 2);
        $agg_cgst_on_sc = round($agg_sc_gst * 0.025, 2);
        $agg_sc_total_tax = $agg_sgst_on_sc + $agg_cgst_on_sc + $agg_vat_on_sc;

        // Fixed ratios reused for EVERY day below (per-day drill-down rows
        // only — the Total row uses the exact range figures directly).
        $agg_sgst_ratio = $agg_sc_total_tax > 0 ? ($agg_sgst_on_sc / $agg_sc_total_tax) : 0.5;
        $agg_cgst_ratio = $agg_sc_total_tax > 0 ? ($agg_cgst_on_sc / $agg_sc_total_tax) : 0.5;

        // 5) The EXACT "Other Charges Tax Breakup" figures for the whole
        //    range — identical formula to the view's PHP block — this is
        //    what the Excel Total row will be forced to equal below.
        $range_unaccounted = round($range_computed_other_tax - $range_charges_taxes_sum, 2);
        if ($agg_sc_total_tax > 0) {
            $range_derived_sgst = round($range_unaccounted * $agg_sgst_ratio, 2);
            $range_derived_cgst = round($range_unaccounted * $agg_cgst_ratio, 2);
            $range_derived_vat  = round($range_unaccounted - $range_derived_sgst - $range_derived_cgst, 2);
        } else {
            $range_derived_sgst = round($range_unaccounted / 2, 2);
            $range_derived_cgst = round($range_unaccounted - $range_derived_sgst, 2);
            $range_derived_vat  = 0.0;
        }

        // Make sure these columns exist as headers for all stores even if a store had no SC in this range
        $allChargesTaxTypes['Charges Tax-SGST(2.5%)'] = true;
        $allChargesTaxTypes['Charges Tax-CGST(2.5%)'] = true;
        $allChargesTaxTypes["Charges Tax-VAT on SC({$vat_rate}%)"] = true;

        // ==================================================================
        // Main per-day loop.
        // Dish-type queries are now O(1) array lookups instead of 4 heavy
        // queries per day. Payments/transactions/taxes/sales/ncsales stay
        // per-day exactly as before.
        // ==================================================================
        $period = new DatePeriod(
            new DateTime($startdate),
            new DateInterval('P1D'),
            (new DateTime($enddate))->modify('+1 day')
        );

        foreach ($period as $dateObj) {
            $day = $dateObj->format('Y-m-d');

            $_dayShift = $this->getShiftDateRange($day, $day, !empty($cashier_id) ? (int)$cashier_id : null);
            $dayStart  = $_dayShift['startDatetime'];
            $dayEnd    = $_dayShift['endDatetime'];

            $payments = $this->Order_payment->query("SELECT SUM(amount) as total_amount,
                SUM(IF(type=1,amount,0)) AS cash,SUM(IF(type=10,amount,0)) AS hold,
                SUM(IF(type=2,amount,0)) AS card,
                SUM(IF(type=6,amount,0)) AS upi,
                SUM(IF(type=9,amount,0)) AS online,
                SUM(IF(type=12,amount, 0)) AS reelo_wallet,
                SUM(IF(type=7,amount,0)) AS no_charge_with_tax,
                SUM(IF(type=7,round(amount-(SELECT total_tax from order_transactions WHERE status!=0 and reference_id=order_payments.reference_id $cashierFilter limit 1),2),0)) AS no_charge,
                SUM(IF(type=8,amount,0)) AS storecard
                FROM order_payments
                WHERE status=2 and created BETWEEN '$dayStart' AND '$dayEnd' $cashierFilter");

            $onlinepayments = $this->Order_payment->query("
                SELECT type_value, SUM(amount) AS total_amount
                FROM order_payments
                WHERE type = 11
                AND status = 2
                AND created BETWEEN '$dayStart' AND '$dayEnd' $cashierFilter
                GROUP BY type_value
            ");

            foreach ($onlinepayments as $op) {
                $key = strtolower(trim($op['order_payments']['type_value'] ?? ''));
                if ($key !== '') {
                    $allonlinepayTypes[$key] = true;
                }
            }

            // ── Online Payment Net Sale per channel (per-day) ── ← NEW BLOCK
            $online_net_sales = $this->Order_payment->query("
                SELECT op.type_value,
                    ROUND(SUM(ot.price - ot.discount), 2) AS net_sale
                FROM order_payments op
                INNER JOIN order_transactions ot
                    ON ot.reference_id = op.reference_id
                    AND ot.store_id    = op.store_id
                    AND ot.status      = 2
                WHERE op.type   = 11
                  AND op.status = 2
                  AND op.created BETWEEN '$dayStart' AND '$dayEnd'
                  $oponlyFilter
                  AND ot.reference_id NOT IN (
                      SELECT op_nc.reference_id FROM order_payments op_nc
                      WHERE op_nc.type = 7 AND op_nc.status = 2 $op_ncFilter
                  )
                GROUP BY op.type_value
                ORDER BY op.type_value
            ");

            foreach ($online_net_sales as $ons) {
                $key = strtolower(trim($ons['op']['type_value'] ?? ''));
                if ($key !== '') {
                    $allOnlineNetSalesTypes[$key] = true;
                }
            }
            // ── END NEW BLOCK ──

            $exp_platform_tax_data = $this->Order_payment->query("
                SELECT type_value,
                    ROUND(SUM(amount) * 5/105, 2) AS platform_tax
                FROM order_payments
                WHERE type=11 AND status=2
                AND created BETWEEN '$dayStart' AND '$dayEnd' $cashierFilter
                AND type_value IN ('swiggy', 'zomato')
                GROUP BY type_value
            ");
            $exp_platform_by_channel = [];
            $exp_platform_tax_total  = 0;
            foreach ($exp_platform_tax_data as $ep) {
                $tv = strtolower(trim($ep['order_payments']['type_value'] ?? ''));
                $exp_platform_by_channel[$tv] = (float)$ep[0]['platform_tax'];
                $exp_platform_tax_total += (float)$ep[0]['platform_tax'];
            }
            $exp_platform_tax_total = round($exp_platform_tax_total, 2);

            $transactions = $this->Order_transaction->query("SELECT COUNT(id) as total_count,SUM(pax) AS pax,
                SUM(total_tax) AS total_tax,
                SUM(servicecharge) AS servicecharge,
                SUM(discount) AS discount,
                SUM(delivery_charge) AS delivery_charge,
                SUM(packing_charge) AS packing_charge,
                SUM(amount) AS amount,
                SUM(price) AS price,
                SUM(round) AS roundamount,
                SUM(other_taxes) AS other_taxes
                FROM order_transactions
                WHERE created BETWEEN '$dayStart' AND '$dayEnd' AND status=2 $cashierFilter AND $noChargeFilter");

            $transactionsnc = $this->Order_transaction->query("SELECT
                SUM(total_tax) AS total_tax
                FROM order_transactions
                WHERE created BETWEEN '$dayStart' AND '$dayEnd' AND status=2 $cashierFilter AND reference_id IN (SELECT reference_id from order_payments WHERE type=7 and status=2 $cashierFilter)");

            $transactionsubtotal = $this->Order_transaction->query("SELECT SUM(price) AS price,
                SUM(discount) AS discount FROM order_transactions
                WHERE created BETWEEN '$dayStart' AND '$dayEnd' AND status=2 $cashierFilter");

            $taxes = $this->Order_tax->query("SELECT tax_id,tax_name,tax_percentage,sum(tax_value) as tax_value FROM `order_tax` WHERE  created BETWEEN '$dayStart' and '$dayEnd' and status=2 $cashierFilter and reference_id NOT IN (SELECT reference_id from order_payments WHERE type=7 and status=2 $cashierFilter)  GROUP by tax_id");
            foreach ($taxes as $t) {
                $key = $t['order_tax']['tax_name'] . "(" . $t['order_tax']['tax_percentage'] . "%)";
                $allTaxTypes[$key] = true;
            }

            $charges_taxes = $this->Order_payment->query("
                SELECT tax_id, tax_name, tax_percentage, SUM(tax_value) AS tax_value
                FROM tax_charges
                WHERE status = 1
                AND created BETWEEN '$dayStart' AND '$dayEnd'
                $cashierFilter
                GROUP BY tax_id
            ");
            foreach ($charges_taxes as $ct) {
                $key = 'Charges Tax-'. $ct['tax_charges']['tax_name'] . '(' . $ct['tax_charges']['tax_percentage'] . '%)';
                $allChargesTaxTypes[$key] = true;
            }

            $net_amount_query = $this->Order_transaction->query("
                SELECT
                    SUM(ot.price)
                    - IFNULL(SUM(ot.discount), 0) AS net_amount
                FROM order_transactions ot
                WHERE
                    ot.status = 2
                    AND ot.created BETWEEN '$dayStart' AND '$dayEnd' $otFilter
                    AND ot.reference_id NOT IN (
                        SELECT reference_id
                        FROM order_payments
                        WHERE type = 7
                        AND status = 2 $otFilter
                    )
            ");

            $ncnet_amount_query = $this->Order_transaction->query("
                SELECT
                    SUM(ot.price)
                    - IFNULL(SUM(ot.discount), 0) AS net_amount
                FROM order_transactions ot
                WHERE
                    ot.status = 2
                    AND ot.created BETWEEN '$dayStart' AND '$dayEnd' $otFilter
                    AND ot.reference_id IN (
                        SELECT reference_id
                        FROM order_payments
                        WHERE type = 7
                        AND status = 2 $otFilter
                    )
            ");

            // Dish-type data: array lookups, no queries
            $dish_sales               = $dishSalesByDay[$day]        ?? [];
            $dish_saleswithoutservice = $dishSalesWithoutByDay[$day] ?? [];
            $sc_base_sales            = $scBaseSalesByDay[$day]      ?? [];
            $sc_data                  = $scDataByDay[$day]           ?? [];

            $taxable = $this->Order_orderhistory->query("SELECT sum(total_price) as total_taxable FROM `order_orderhistories` WHERE  status = 2 and created BETWEEN '$dayStart' AND '$dayEnd' and total_tax_percentage != 0 $cashierFilter AND
                    reference_id NOT IN (
                        SELECT reference_id
                        FROM order_payments
                        WHERE type = 7
                        AND status = 2 $cashierFilter
                    )");

            $nontaxable = $this->Order_orderhistory->query("SELECT sum(total_price) as total_nontaxable FROM `order_orderhistories` WHERE  status = 2 and created BETWEEN '$dayStart' AND '$dayEnd' and total_tax_percentage = 0 $cashierFilter AND
                    reference_id NOT IN (
                        SELECT reference_id
                        FROM order_payments
                        WHERE type = 7
                        AND status = 2 $cashierFilter
                    )");

            $nctaxable = $this->Order_orderhistory->query("SELECT sum(total_price) as total_taxable FROM `order_orderhistories` WHERE  status = 2 and created BETWEEN '$dayStart' AND '$dayEnd' and total_tax_percentage != 0 $cashierFilter AND
                    reference_id IN (
                        SELECT reference_id
                        FROM order_payments
                        WHERE type = 7
                        AND status = 2 $cashierFilter
                    )");

            $ncnontaxable = $this->Order_orderhistory->query("SELECT sum(total_price) as total_nontaxable FROM `order_orderhistories` WHERE  status = 2 and created BETWEEN '$dayStart' AND '$dayEnd' and total_tax_percentage = 0 $cashierFilter AND
                    reference_id IN (
                        SELECT reference_id
                        FROM order_payments
                        WHERE type = 7
                        AND status = 2 $cashierFilter
                    )");

            $sales = $this->Order_tax->query(
                $this->_dsrKdsSalesSql($dayStart, $dayEnd, $cashierFilter, false)
            );
            foreach ($sales as $s) {
                $key = $s['final']['kds_name'] . "(" . $s['final']['tax_percentage'] . "%)";
                $allSalesTypes[$key] = true;
            }

            $ncsales = $this->Order_tax->query(
                $this->_dsrKdsSalesSql($dayStart, $dayEnd, $cashierFilter, true)
            );
            foreach ($ncsales as $s) {
                $key = 'NC - '. $s['final']['kds_name'] . "(" . $s['final']['tax_percentage'] . "%)";
                $ncallSalesTypes[$key] = true;
            }

            // Day-level VAT rate is still used as a fallback percentage for the
            // per-day Service Charge Breakup columns (sc_vat/vat_on_sc), which
            // is unrelated to the "Other Charges Tax Breakup" columns fixed
            // above — those now use the single, unified $vat_rate label.
            $day_vat_rates = [];
            if (isset($taxes)) {
                foreach ($taxes as $tax) {
                    $tax_name = $tax['order_tax']['tax_name'];
                    if (stripos($tax_name, 'VAT') !== false) {
                        $pct = (float)$tax['order_tax']['tax_percentage'];
                        if ($pct > 0) {
                            $day_vat_rates[] = $pct;
                        }
                    }
                }
            }
            $day_vat_rates = array_unique($day_vat_rates);
            sort($day_vat_rates);
            $day_vat_rate_val = empty($day_vat_rates) ? 18.9 : $day_vat_rates[0];

            $total_sc_from_transactions = isset($transactions[0][0]['servicecharge']) ? (float)$transactions[0][0]['servicecharge'] : 0.0;
            $sc_gst_raw = 0.0;
            $sc_vat_raw = 0.0;
            $vat_on_sc_raw = 0.0;
            foreach ($sc_data as $row) {
                $dt = (int)$row['cp']['dish_type'];
                $amt = (float)$row[0]['sc_amount'];
                $pct = isset($row['ia']['tax_percentage']) ? (float)$row['ia']['tax_percentage'] : $day_vat_rate_val;
                if ($dt == 2) {
                    $sc_vat_raw += $amt;
                    $vat_on_sc_raw += $amt * ($pct / 100);
                } else {
                    $sc_gst_raw += $amt;
                }
            }
            $diff = $total_sc_from_transactions - ($sc_gst_raw + $sc_vat_raw);
            if (abs($diff) > 0.001) {
                $sc_gst_raw += $diff;
            }
            $sc_gst = round($sc_gst_raw, 2);
            $sc_vat = round($sc_vat_raw, 2);
            $sc_total = round($total_sc_from_transactions, 2);
            $vat_on_sc = round($vat_on_sc_raw, 2);

            // --- Charges taxes (per day, for reference / drill-down rows) ---
            $tax_segregation_total = 0;
            if (!empty($taxes)) {
                foreach ($taxes as $t) {
                    $tax_segregation_total += round($t[0]['tax_value'], 2);
                }
            }
            $total_tax_for_day = round($transactions[0][0]['total_tax'], 2);
            $computed_other_tax = round($total_tax_for_day - $tax_segregation_total, 2);

            $ctax_totals = [];
            $charges_taxes_sum = 0;
            if (!empty($charges_taxes)) {
                foreach ($charges_taxes as $ctax) {
                    $ctax_name       = $ctax['tax_charges']['tax_name']       ?? $ctax[0]['tax_name']       ?? '';
                    $ctax_percentage = $ctax['tax_charges']['tax_percentage']  ?? $ctax[0]['tax_percentage']  ?? '';
                    $ctax_value      = $ctax['tax_charges']['tax_value']       ?? $ctax[0]['tax_value']       ?? 0;

                    $key = htmlspecialchars($ctax_name) . '(' . htmlspecialchars($ctax_percentage) . '%)';
                    $ctax_totals[$key] = ($ctax_totals[$key] ?? 0) + $ctax_value;
                    $charges_taxes_sum += $ctax_value;
                }
            }

            // Uses the range-wide aggregate ratio ($agg_sgst_ratio /
            // $agg_cgst_ratio, diff-adjusted) AND the single, unified
            // $vat_rate label — instead of a day-specific ratio and a
            // day-specific rate label. Per-day figures here are still just a
            // proportional split for drill-down purposes; the Total row below
            // is forced to the exact range-wide figure regardless.
            $unaccounted = round($computed_other_tax - $charges_taxes_sum, 2);
            if (abs($unaccounted) > 0.01) {
                if ($agg_sc_total_tax > 0) {
                    $derived_sgst = round($unaccounted * $agg_sgst_ratio, 2);
                    $derived_cgst = round($unaccounted * $agg_cgst_ratio, 2);
                    $derived_vat  = round($unaccounted - $derived_sgst - $derived_cgst, 2);
                } else {
                    $derived_sgst = round($unaccounted / 2, 2);
                    $derived_cgst = round($unaccounted - $derived_sgst, 2);
                    $derived_vat  = 0;
                }

                if (abs($derived_sgst) > 0.01) {
                    $ctax_totals['SGST(2.5%)'] = ($ctax_totals['SGST(2.5%)'] ?? 0) + $derived_sgst;
                }
                if (abs($derived_cgst) > 0.01) {
                    $ctax_totals['CGST(2.5%)'] = ($ctax_totals['CGST(2.5%)'] ?? 0) + $derived_cgst;
                }
                if (abs($derived_vat) > 0.01) {
                    // unified $vat_rate label (was $day_vat_rate_val)
                    $ctax_totals["VAT on SC({$vat_rate}%)"] = ($ctax_totals["VAT on SC({$vat_rate}%)"] ?? 0) + $derived_vat;
                }
            }

            // Re-populate allChargesTaxTypes so Excel makes columns for them!
            foreach ($ctax_totals as $k => $v) {
                if (abs($v) > 0.001) {
                    $allChargesTaxTypes['Charges Tax-' . $k] = true;
                }
            }
            // --- END charges taxes ---

            $datas[] = array(
                'date' => $day,
                'cashier' => $cashiername,
                'total_collection' => round($payments[0][0]['total_amount'] - $payments[0][0]['no_charge_with_tax'] - $exp_platform_tax_total, 2),
                'cash' => round($payments[0][0]['cash'], 2),
                'card' => round($payments[0][0]['card'], 2),
                'online' => round($payments[0][0]['online'], 2),
                'online_payments' => $onlinepayments,
                'online_net_sales' => $online_net_sales, // ← NEW
                'upi' => round($payments[0][0]['upi'], 2),
                'reelo_wallet' => round($payments[0][0]['reelo_wallet'], 2),
                'hold' => round($payments[0][0]['hold'], 2),
                'storecard' => round($payments[0][0]['storecard'], 2),
                'no_charge' => round($payments[0][0]['no_charge_with_tax']-$transactionsnc[0][0]['total_tax'], 2),
                'no_charge_with_tax' => round($payments[0][0]['no_charge_with_tax'], 2),
                'subtotal' => round($transactionsubtotal[0][0]['price'], 2),
                'discount' => round($transactionsubtotal[0][0]['discount'], 2),
                'taxable_amount' => round($taxable[0][0]['total_taxable']-$transactionsubtotal[0][0]['discount'], 2),
                'nontaxable_amount' => round($nontaxable[0][0]['total_nontaxable'], 2),
                'nctaxable_amount' => round($nctaxable[0][0]['total_taxable'], 2),
                'ncnontaxable_amount' => round($ncnontaxable[0][0]['total_nontaxable'], 2),
                'service_charge' => round($transactions[0][0]['servicecharge'], 2),
                'delivery_charge' => round($transactions[0][0]['delivery_charge'], 2),
                'packing_charge' => round($transactions[0][0]['packing_charge'], 2),
                'total_tax' => round($transactions[0][0]['total_tax'], 2),
                'nctotal_tax' => round($transactionsnc[0][0]['total_tax'], 2),
                'bill_count' => round($transactions[0][0]['total_count'], 2),
                'net_amount' => round($transactions[0][0]['amount'] - $exp_platform_tax_total, 2),
                'round' => $transactions[0][0]['roundamount'],
                'taxes' => $taxes,
                'sales' => $sales,
                'ncsales' => $ncsales,
                'dish_saleswithoutservice' => $dish_saleswithoutservice,
                'dish_sales' => $dish_sales,
                'net_sale' =>$net_amount_query[0][0]['net_amount'],
                'ncnet_sale' =>$ncnet_amount_query[0][0]['net_amount'],
                'pax' => $transactions[0][0]['pax'],
                'sc_base_sales' => $sc_base_sales,
                'other_taxes' => $computed_other_tax,
                'charges_taxes' => $ctax_totals,
                'platform_tax_total' => $exp_platform_tax_total,
                'platform_gst_map'   => $exp_platform_by_channel,
                'sc_total'           => $sc_total,
                'sc_gst'             => $sc_gst,
                'sc_vat'             => $sc_vat,
                'sgst_on_sc'         => round($sc_gst * 0.025, 2),
                'cgst_on_sc'         => round($sc_gst * 0.025, 2),
                'vat_on_sc'          => $vat_on_sc
            );
        }

        // $vat_rate is already correct (computed once, range-wide, before
        // the day loop) — no post-loop recomputation needed.

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("DSR Report Export");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('DSR Report');

        $taxColumns = array_keys($allTaxTypes);
        $salesColumns = array_keys($allSalesTypes);
        $ncsalesColumns = array_keys($ncallSalesTypes);

        sort($taxColumns);
        sort($salesColumns);
        sort($ncsalesColumns);
        $chargesTaxColumns = array_keys($allChargesTaxTypes);
        sort($chargesTaxColumns);

        // Use mapped names as columns
        $dishsalesColumns = [];
        foreach (array_keys($alldishSalesTypes) as $type) {
            $dishsalesColumns[] = $dishTypeMap[$type] ?? $type;
        }
        sort($dishsalesColumns);

        $dishsalesWithoutColumns = [];

        foreach (array_keys($alldishSalesWithoutTypes) as $type) {
            $dishsalesWithoutColumns[] = 'W/O SC - ' . ($dishTypeMap[$type] ?? $type);
        }
        sort($dishsalesWithoutColumns);

        $online_payments = array_keys($allonlinepayTypes);
        sort($online_payments);
        $onlinePaymentDisplayNames = [
            'swiggy' => 'Swiggy home delivery',
            'zomato' => 'Zomato home delivery',
            'eazydiner mislleneous' => 'EazyDiner Miscellaneous',
        ];
        $online_payments_display = array_map(function ($k) use ($onlinePaymentDisplayNames) {
            return $onlinePaymentDisplayNames[strtolower($k)] ?? ucfirst($k);
        }, $online_payments);

        // ── Online Payment Net Sale columns ── ← NEW BLOCK
        $online_net_sales_columns = array_keys($allOnlineNetSalesTypes);
        sort($online_net_sales_columns);
        $onlineNetSaleDisplayNames = [
            'swiggy' => 'Swiggy Home Delivery',
            'zomato' => 'Zomato Home Delivery',
            'eazydiner mislleneous' => 'EazyDiner Miscellaneous',
        ];
        $online_net_sales_display = array_map(function ($k) use ($onlineNetSaleDisplayNames) {
            $label = $onlineNetSaleDisplayNames[strtolower($k)] ?? ucfirst($k);
            return $label . ' Net Sale';
        }, $online_net_sales_columns);
        // ── END NEW BLOCK ──

        $baseHeaders = [
            'Date','Store','Total Collection','Cash','Card','Upi','Online',
        ];

        $afterOnlineHeaders = [
            'Storecard','Hold Bill','Reelo Wallet','No Charge','No Charge With Tax',
            'Subtotal','Discount','Taxable','Non Taxable','NC Taxable','NC Non Taxable',
            'NC Net Sale','NC Total Tax','No Charge','No Charge With Tax',
            'Service','Delivery','Packing','Net Sale','Total Tax','Platform Tax Deposited', 'Net Tax Liability','Net Amount',
            'Round','Bill Count','PAX'
        ];

        $headers = array_merge(
            $baseHeaders,
            $online_payments_display,
            $online_net_sales_display, // ← NEW
            $afterOnlineHeaders,
            $taxColumns,
            ['Other Charges Tax'],
            $chargesTaxColumns,
            $salesColumns,
            $ncsalesColumns,
            $dishsalesColumns,
            $dishsalesWithoutColumns,
            ['SC Total','SC on GST Items (5%)','SC on VAT Items (' . $vat_rate . '%)','SGST on SC (2.5%)','CGST on SC (2.5%)','VAT on SC (' . $vat_rate . '%)']
        );

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($datas as $data) {
            $colIndex = 0; // RESET EVERY ROW

            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['date']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['cashier']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['total_collection']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['cash']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['card']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['upi']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['online']);

            $onlinepaymentValues = array_fill_keys($online_payments, 0);
            foreach ($data['online_payments'] as $t) {
                $key = strtolower(trim($t['order_payments']['type_value'] ?? ''));
                $amt = round($t[0]['total_amount'], 2);
                if (isset($data['platform_gst_map'][$key])) {
                    $amt = round($amt - $data['platform_gst_map'][$key], 2);
                }
                $onlinepaymentValues[$key] = $amt;

            }

            foreach ($online_payments as $op) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $onlinepaymentValues[$op] ?? 0);
            }

            // ── Online Payment Net Sale cell values ── ← NEW BLOCK
            $onlineNetSaleValues = array_fill_keys($online_net_sales_columns, 0);
            foreach ($data['online_net_sales'] as $ons) {
                $key = strtolower(trim($ons['op']['type_value'] ?? ''));
                $onlineNetSaleValues[$key] = round($ons[0]['net_sale'], 2);
            }
            foreach ($online_net_sales_columns as $col) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $onlineNetSaleValues[$col] ?? 0);
            }
            // ── END NEW BLOCK ──

            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['storecard']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['hold']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['reelo_wallet']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['no_charge']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['no_charge_with_tax']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['subtotal']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['discount']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['taxable_amount']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['nontaxable_amount']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['nctaxable_amount']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['ncnontaxable_amount']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['ncnet_sale']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['nctotal_tax']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['no_charge']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['no_charge_with_tax']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['service_charge']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['delivery_charge']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['packing_charge']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['net_sale']);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['total_tax']);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['platform_tax_total'] ?? 0);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, round($data['total_tax'] - ($data['platform_tax_total'] ?? 0), 2));
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['net_amount']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['round']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['bill_count']);
            $sheet->setCellValueByColumnAndRow($colIndex++,$row, $data['pax']);

            $taxValues = array_fill_keys($taxColumns, 0);
            foreach ($data['taxes'] as $t) {
                $key = $t['order_tax']['tax_name'] . "(" . $t['order_tax']['tax_percentage'] . "%)";
                $taxValues[$key] = round($t['0']['tax_value'], 2);
            }

            if (isset($data['other_taxes']) && abs($data['other_taxes']) > 0.01) {
                $taxValues['Other Charges Taxes'] = $data['other_taxes'];
            }

            foreach ($taxColumns as $taxCol) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $taxValues[$taxCol] ?? 0);
            }


            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $taxValues['Other Charges Taxes'] ?? 0);

            $chargesTaxValues = array_fill_keys($chargesTaxColumns, 0);
            foreach ($data['charges_taxes'] as $ct_name => $ct_val) {
                $key = 'Charges Tax-' . $ct_name;

                if (array_key_exists($key, $chargesTaxValues)) {
                    $chargesTaxValues[$key] = round($ct_val, 2);
                }
            }

            foreach ($chargesTaxColumns as $ctCol) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $chargesTaxValues[$ctCol] ?? 0);
            }

            $salesValues = array_fill_keys($salesColumns, 0);
            foreach ($data['sales'] as $s) {
                $key = $s['final']['kds_name'] . "(" . $s['final']['tax_percentage'] . "%)";
                $salesValues[$key] = round($s['final']['value'], 2);
            }
            foreach ($salesColumns as $salesCol) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $salesValues[$salesCol] ?? 0);
            }

            $ncsalesValues = array_fill_keys($ncsalesColumns, 0);
            foreach ($data['ncsales'] as $s) {
                $key = 'NC - '. $s['final']['kds_name'] . "(" . $s['final']['tax_percentage'] . "%)";
                $ncsalesValues[$key] = round($s['final']['value'], 2);
            }
            foreach ($ncsalesColumns as $ncsalesCol) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $ncsalesValues[$ncsalesCol] ?? 0);
            }

            $dishsalesValues = array_fill_keys($dishsalesColumns, 0);

            foreach ($data['dish_sales'] as $ds) {

                if (!isset($ds['cp']['dish_type'])) {
                    continue;
                }

                $type = (int)$ds['cp']['dish_type'];
                $key  = $dishTypeMap[$type] ?? $type;

                if (array_key_exists($key, $dishsalesValues)) {
                    $dishsalesValues[$key] = round((float)$ds[0]['total_amount'], 2);
                }
            }


            // Write to Excel
            foreach ($dishsalesColumns as $dishsalesCol) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $dishsalesValues[$dishsalesCol] ?? 0);
            }

            $dishsalesWithoutValues = array_fill_keys($dishsalesWithoutColumns, 0);

            foreach ($data['dish_saleswithoutservice'] as $dsw) {

                if (!isset($dsw['cp']['dish_type'])) continue;

                $type = (int)$dsw['cp']['dish_type'];
                $key  = 'W/O SC - ' . ($dishTypeMap[$type] ?? $type);

                if (array_key_exists($key, $dishsalesWithoutValues)) {
                    $dishsalesWithoutValues[$key] = round((float)$dsw[0]['total_amount'], 2);
                }
            }

            foreach ($dishsalesWithoutColumns as $col) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $row, $dishsalesWithoutValues[$col] ?? 0);
            }
            // SC breakup columns
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['sc_total']);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['sc_gst']);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['sc_vat']);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['sgst_on_sc']);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['cgst_on_sc']);
            $sheet->setCellValueByColumnAndRow($colIndex++, $row, $data['vat_on_sc']);

            $row++;
        }

        // --- ADD TOTAL ROW ---
        $lastDataRow = $row - 1;
        if ($lastDataRow >= 2) {
            $sheet->setCellValueByColumnAndRow(0, $row, 'Total');
            $sheet->setCellValueByColumnAndRow(1, $row, '');

            $totalColumns = count($headers);

            for ($c = 2; $c < $totalColumns; $c++) {
                $colLetter = PHPExcel_Cell::stringFromColumnIndex($c);
                $sheet->setCellValueByColumnAndRow($c, $row, "=SUM({$colLetter}2:{$colLetter}{$lastDataRow})");
            }

            // Make the total row bold
            $lastColLetter = PHPExcel_Cell::stringFromColumnIndex($totalColumns - 1);
            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->getFont()->setBold(true);
        }
        // --- END TOTAL ROW ---

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="DSR_REPORT.xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }
    
    public function dsr_reportpdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $this->layout = "login";

        if ($this->request->is('post', 'put')) {
            $date_range = $this->request->data['sdate'];
            $dates      = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date   = trim($dates[1]);
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
            $cashier_id = !empty($this->request->data['cashier']) ? (int)$this->request->data['cashier'] : null;

            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $cashier_id);
            $startDatetime = $_shiftRange['startDatetime'];
            $endDatetime   = $_shiftRange['endDatetime'];

            // ── Build all filter variables based on role + cashier ────────────
            if ($cashier_id) {
                $cashiername   = '';
                $cashiernames  = $this->Location->find('first', ['conditions' => ['id' => $cashier_id]]);
                $cashiername   = $cashiernames['Location']['name'];

                // All filters scoped to specific store
                $cashierFilter = " AND store_id=$cashier_id";
                $oohFilter     = " AND ooh.store_id=$cashier_id";
                $otTaxFilter   = " AND ot_tax.store_id=$cashier_id";
                $otDFilter     = " AND ot_d.store_id=$cashier_id";
                $otFilter      = " AND ot.store_id=$cashier_id";
                $orderTaxFilter = " AND order_tax.store_id=$cashier_id";
                $op_ncFilter      = " AND op_nc.store_id=$cashier_id";
                $oponlyFilter = " AND op.store_id=$cashier_id";

            } elseif ($userRole != 'admin') {
                $cashiername   = 'All Stores';

                $cashierFilter = " AND store_id IN ($userLocationId)";
                $oohFilter     = " AND ooh.store_id IN ($userLocationId)";
                $otTaxFilter   = " AND ot_tax.store_id IN ($userLocationId)";
                $otDFilter     = " AND ot_d.store_id IN ($userLocationId)";
                $otFilter      = " AND ot.store_id IN ($userLocationId)";
                $orderTaxFilter = " AND order_tax.store_id IN ($userLocationId)";
                $op_ncFilter      = " AND op_nc.store_id IN ($userLocationId)";
                $oponlyFilter = " AND op.store_id IN ($userLocationId)";
                
            } else {
                // Admin, no cashier selected — no filter
                $cashiername   = 'All Stores';
                $cashierFilter = '';
                $oohFilter     = '';
                $otTaxFilter   = '';
                $otDFilter     = '';
                $otFilter      = '';
                $orderTaxFilter = '';
                $op_ncFilter      = '';
                $oponlyFilter = '';
            }

            // ── Payments ──────────────────────────────────────────────────────
            $payments = $this->Order_payment->query("
                SELECT
                    SUM(amount) AS total_amount,
                    SUM(IF(type=1,  amount, 0)) AS cash,
                    SUM(IF(type=10, amount, 0)) AS hold,
                    SUM(IF(type=2,  amount, 0)) AS card,
                    SUM(IF(type=9,  amount, 0)) AS online,
                    SUM(IF(type=12, amount, 0)) AS reelo_wallet,
                    SUM(IF(type=6,  amount, 0)) AS upi,
                    SUM(IF(type=7,  amount, 0)) AS no_charge_with_tax,
                    SUM(IF(type=7, ROUND(amount - (
                        SELECT SUM(ot_sub.total_tax)
                        FROM order_transactions ot_sub
                        WHERE ot_sub.status != 0
                          AND ot_sub.reference_id = order_payments.reference_id
                          $cashierFilter
                    ), 2), 0)) AS no_charge,
                    SUM(IF(type=8, amount, 0)) AS storecard
                FROM order_payments
                WHERE status = 2
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  $cashierFilter
            ");

            $onlinepayments = $this->Order_payment->query("
                SELECT type_value, SUM(amount) AS total_amount
                FROM order_payments
                WHERE type = 11
                  AND status = 2
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  $cashierFilter
                GROUP BY type_value
            ");

            // ── Online Payment Net Sale per channel (PDF) ──
            $pdf_online_net_sales_raw = $this->Order_payment->query("
                SELECT op.type_value,
                    ROUND(SUM(ot.price - ot.discount), 2) AS net_sale
                FROM order_payments op
                INNER JOIN order_transactions ot
                    ON ot.reference_id = op.reference_id
                    AND ot.store_id    = op.store_id
                    AND ot.status      = 2
                WHERE op.type   = 11
                  AND op.status = 2
                  AND op.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $oponlyFilter
                  AND ot.reference_id NOT IN (
                      SELECT op_nc.reference_id FROM order_payments op_nc
                      WHERE op_nc.type = 7 AND op_nc.status = 2 $op_ncFilter
                  )
                GROUP BY op.type_value
                ORDER BY op.type_value
            ");
            $online_net_sales_arr = [];
            foreach ($pdf_online_net_sales_raw as $ons) {
                $online_net_sales_arr[] = [
                    'type_value' => trim($ons['op']['type_value'] ?? $ons[0]['type_value'] ?? ''),
                    'net_sale'   => round((float)($ons[0]['net_sale'] ?? 0), 2),
                ];
            }

            $pdf_platform_tax_data = $this->Order_payment->query("
                SELECT type_value,
                       ROUND(SUM(amount) * 5/105, 2) AS platform_tax
                FROM order_payments
                WHERE type=11 AND status=2
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  $cashierFilter
                  AND type_value IN ('swiggy', 'zomato')
                GROUP BY type_value
            ");
            $pdf_platform_by_channel = [];
            $pdf_platform_tax_total  = 0;
            foreach ($pdf_platform_tax_data as $pp) {
                $tv = $pp['order_payments']['type_value'];
                $pdf_platform_by_channel[$tv] = (float)$pp[0]['platform_tax'];
                $pdf_platform_tax_total += (float)$pp[0]['platform_tax'];
            }
            $pdf_platform_tax_total = round($pdf_platform_tax_total, 2);

            $platform_gst_total  = $pdf_platform_by_channel;
            $platform_gst_map    = $pdf_platform_by_channel;
            $platformGstChannels = array_keys($pdf_platform_by_channel);
            

            // ── Transactions ──────────────────────────────────────────────────
            $transactions = $this->Order_transaction->query("
                SELECT
                    COUNT(order_transactions.id)            AS total_count,
                    SUM(order_transactions.pax)             AS pax,
                    SUM(order_transactions.total_tax)       AS total_tax,
                    SUM(order_transactions.servicecharge)   AS servicecharge,
                    SUM(order_transactions.discount)        AS discount,
                    SUM(order_transactions.delivery_charge) AS delivery_charge,
                    SUM(order_transactions.packing_charge)  AS packing_charge,
                    SUM(order_transactions.amount)          AS amount,
                    SUM(order_transactions.price)           AS price,
                    SUM(order_transactions.round)           AS round,
                    SUM(order_transactions.other_taxes)     AS other_taxes
                FROM order_transactions
                WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND order_transactions.status = 2
                  $cashierFilter
                  AND order_transactions.reference_id NOT IN (
                      SELECT op_nc.reference_id FROM order_payments op_nc
                      WHERE op_nc.type = 7 AND op_nc.status = 2 $cashierFilter
                  )
            ");

            $transactionsnc = $this->Order_transaction->query("
                SELECT SUM(order_transactions.total_tax) AS total_tax
                FROM order_transactions
                WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND order_transactions.status = 2
                  $cashierFilter
                  AND order_transactions.reference_id IN (
                      SELECT op_nc2.reference_id FROM order_payments op_nc2
                      WHERE op_nc2.type = 7 AND op_nc2.status = 2 $cashierFilter
                  )
            ");

            $transactionsubtotal = $this->Order_transaction->query("
                SELECT SUM(order_transactions.price)    AS price,
                       SUM(order_transactions.discount) AS discount
                FROM order_transactions
                WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND order_transactions.status = 2
                  $cashierFilter
            ");

            // ── Taxes ─────────────────────────────────────────────────────────
            $taxes = $this->Order_tax->query("
                SELECT order_tax.tax_id, order_tax.tax_name, order_tax.tax_percentage,
                       SUM(order_tax.tax_value) AS tax_value
                FROM order_tax
                WHERE order_tax.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND order_tax.status = 2
                  $cashierFilter
                  AND order_tax.reference_id NOT IN (
                      SELECT op_t.reference_id FROM order_payments op_t
                      WHERE op_t.type = 7 AND op_t.status = 2 $cashierFilter
                  )
                GROUP BY order_tax.tax_id
            ");
            
            
            // ── Other Taxes ─────────────────────────────────────────────────────────
            $charges_taxes = $this->Order_payment->query("
                SELECT tax_id, tax_name, tax_percentage, SUM(tax_value) AS tax_value
                FROM tax_charges
                WHERE status = 1
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  $cashierFilter
                GROUP BY tax_id
            ");

            // ── Net amounts ───────────────────────────────────────────────────
            $net_amount_query = $this->Order_transaction->query("
                SELECT ROUND(SUM(ot.price) - IFNULL(SUM(ot.discount), 0), 2) AS net_amount
                FROM order_transactions ot
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $cashierFilter
                  AND ot.reference_id NOT IN (
                      SELECT op_n1.reference_id FROM order_payments op_n1
                      WHERE op_n1.type = 7 AND op_n1.status = 2 $cashierFilter
                  )
            ");

            $ncnet_amount_query = $this->Order_transaction->query("
                SELECT ROUND(SUM(ot.price) - IFNULL(SUM(ot.discount), 0), 2) AS net_amount
                FROM order_transactions ot
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $cashierFilter
                  AND ot.reference_id IN (
                      SELECT op_n2.reference_id FROM order_payments op_n2
                      WHERE op_n2.type = 7 AND op_n2.status = 2 $cashierFilter
                  )
            ");

            // ── Dish sales ────────────────────────────────────────────────────
            // ✅ $where_location removed from ON clause; ot_tax filter in WHERE
            $dish_sales = $this->Order_orderhistory->query("
                WITH item_amounts AS (
                    SELECT
                        ooh.id,
                        ooh.local_id,
                        ooh.reference_id,
                        ooh.store_id,
                        ooh.product_id,
                        ooh.quantity,
                        cp.dish_type,
                        ot_tax.taxable_price AS item_amount
                    FROM order_orderhistories ooh
                    INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                    INNER JOIN (
                        SELECT orderhistory_id, MAX(taxable_price) AS taxable_price, store_id
                        FROM order_tax
                        WHERE order_tax.status != 0 $orderTaxFilter
                        GROUP BY orderhistory_id, store_id
                    ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                    WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
                      AND ooh.status = 2
                      $oohFilter
                      AND ooh.reference_id NOT IN (
                          SELECT op_d.reference_id FROM order_payments op_d
                          WHERE op_d.type = 7 AND op_d.status = 2 $oohFilter
                      )
                ),
                bill_totals AS (
                    SELECT
                        ot.reference_id,
                        ot.store_id,
                        (ot.price - ot.discount + ot.servicecharge) AS bill_net,
                        SUM(ia.item_amount) AS bill_item_sum
                    FROM order_transactions ot
                    INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                    WHERE ot.status = 2
                      AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                      $otFilter
                      AND ot.reference_id NOT IN (
                          SELECT op_d.reference_id FROM order_payments op_d
                          WHERE op_d.type = 7 AND op_d.status = 2 $otFilter
                      )
                    GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount, ot.servicecharge
                )
                SELECT
                    cp.dish_type,
                    SUM(ia.quantity) AS total_qty,
                    ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
                FROM item_amounts ia
                INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
                INNER JOIN catalog_product cp ON cp.id = ia.product_id
                GROUP BY cp.dish_type
                ORDER BY cp.dish_type DESC
            ");

            // ── Dish sales without service charge ─────────────────────────────
            // ✅ alias is ot — uses $otFilter
            $dish_saleswithoutservice = $this->Order_orderhistory->query("
                WITH item_amounts AS (
                    SELECT
                        ooh.id,
                        ooh.local_id,
                        ooh.reference_id,
                        ooh.store_id,
                        ooh.product_id,
                        ooh.quantity,
                        cp.dish_type,
                        (ooh.price * ooh.quantity) * (1 - IFNULL(ot.discount_percentage, 0) / 100) AS item_amount
                    FROM order_orderhistories ooh
                    INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                    LEFT JOIN (
                        SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                        FROM order_tax
                        WHERE order_tax.status != 0 $orderTaxFilter
                        GROUP BY orderhistory_id, store_id
                    ) ot ON ot.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot.store_id = ooh.store_id
                    WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
                      AND ooh.status = 2
                      $oohFilter
                      AND ooh.reference_id NOT IN (
                          SELECT op_dw.reference_id FROM order_payments op_dw
                          WHERE op_dw.type = 7 AND op_dw.status = 2 $oohFilter
                      )
                ),
                bill_totals AS (
                    SELECT
                        ot.reference_id,
                        ot.store_id,
                        (ot.price - ot.discount) AS bill_net,
                        SUM(ia.item_amount) AS bill_item_sum
                    FROM order_transactions ot
                    INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                    WHERE ot.status = 2
                      AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                      $otFilter
                      AND ot.reference_id NOT IN (
                          SELECT op_dw.reference_id FROM order_payments op_dw
                          WHERE op_dw.type = 7 AND op_dw.status = 2 $otFilter
                      )
                    GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount
                )
                SELECT
                    cp.dish_type,
                    SUM(ia.quantity) AS total_qty,
                    ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
                FROM item_amounts ia
                INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
                INNER JOIN catalog_product cp ON cp.id = ia.product_id
                GROUP BY cp.dish_type
                ORDER BY cp.dish_type DESC
            ");

            // ── SC base sales ─────────────────────────────────────────────────
            // ✅ alias is ot_d — uses $otDFilter
            $sc_base_sales = $this->Order_orderhistory->query("
                SELECT cp.dish_type,
                       ROUND(SUM(ooh.price * ooh.quantity * (1 - IFNULL(ot_d.discount_percentage, 0) / 100)), 2) AS base_amount
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                LEFT JOIN (
                    SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                    FROM order_tax
                    WHERE order_tax.status != 0 $orderTaxFilter
                    GROUP BY orderhistory_id, store_id
                ) ot_d ON ot_d.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_d.store_id = ooh.store_id
                WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND ooh.status = 2
                  $oohFilter
                  AND ooh.reference_id NOT IN (
                      SELECT op_sc.reference_id FROM order_payments op_sc
                      WHERE op_sc.type = 7 AND op_sc.status = 2 $oohFilter
                  )
                GROUP BY cp.dish_type
            ");

            // ── Taxable / Non-taxable ─────────────────────────────────────────
            $taxable = $this->Order_orderhistory->query("
                SELECT SUM(total_price) AS total_taxable
                FROM order_orderhistories
                WHERE status = 2
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND total_tax_percentage != 0
                  $cashierFilter
                  AND reference_id NOT IN (
                      SELECT op_tx1.reference_id FROM order_payments op_tx1
                      WHERE op_tx1.type = 7 AND op_tx1.status = 2 $cashierFilter
                  )
            ");

            $nontaxable = $this->Order_orderhistory->query("
                SELECT SUM(total_price) AS total_nontaxable
                FROM order_orderhistories
                WHERE status = 2
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND total_tax_percentage = 0
                  $cashierFilter
                  AND reference_id NOT IN (
                      SELECT op_tx2.reference_id FROM order_payments op_tx2
                      WHERE op_tx2.type = 7 AND op_tx2.status = 2 $cashierFilter
                  )
            ");

            $nctaxable = $this->Order_orderhistory->query("
                SELECT SUM(total_price) AS total_taxable
                FROM order_orderhistories
                WHERE status = 2
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND total_tax_percentage != 0
                  $cashierFilter
                  AND reference_id IN (
                      SELECT op_tx3.reference_id FROM order_payments op_tx3
                      WHERE op_tx3.type = 7 AND op_tx3.status = 2 $cashierFilter
                  )
            ");

            $ncnontaxable = $this->Order_orderhistory->query("
                SELECT SUM(total_price) AS total_nontaxable
                FROM order_orderhistories
                WHERE status = 2
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND total_tax_percentage = 0
                  $cashierFilter
                  AND reference_id IN (
                      SELECT op_tx4.reference_id FROM order_payments op_tx4
                      WHERE op_tx4.type = 7 AND op_tx4.status = 2 $cashierFilter
                  )
            ");

            // ── KDS sales (per-day, deduped per item — sum minus service charge = net sale)
            $sales   = $this->_dsrAggregateKdsSales($startdate, $enddate, $cashierFilter, false, $cashier_id);
            $ncsales = $this->_dsrAggregateKdsSales($startdate, $enddate, $cashierFilter, true, $cashier_id);

            // ── Build data arrays ─────────────────────────────────────────────
            $datas               = [];
            $taxesarr            = [];
            $salesarr            = [];
            $ncsalesarr          = [];
            $dishsalesarr        = [];
            $onlinepaymentsarr   = [];

            $datas[] = [
                'cashier'             => $cashiername,
                'total_collection'    => round($payments[0][0]['total_amount'] - $payments[0][0]['no_charge_with_tax'] - $exp_platform_tax_total, 2),
                'cash'                => round($payments[0][0]['cash'], 2),
                'card'                => round($payments[0][0]['card'], 2),
                'online'              => round($payments[0][0]['online'], 2),
                'upi'                 => round($payments[0][0]['upi'], 2),
                'hold'                => round($payments[0][0]['hold'], 2),
                'reelo_wallet'        => round($payments[0][0]['reelo_wallet'], 2),
                'storecard'           => round($payments[0][0]['storecard'], 2),
                'no_charge'           => round($payments[0][0]['no_charge_with_tax'] - $transactionsnc[0][0]['total_tax'], 2),
                'no_charge_with_tax'  => round($payments[0][0]['no_charge_with_tax'], 2),
                'subtotal'            => round($transactionsubtotal[0][0]['price'], 2),
                'discount'            => round($transactionsubtotal[0][0]['discount'], 2),
                'taxable_amount'      => round($taxable[0][0]['total_taxable'], 2),
                'nontaxable_amount'   => round($nontaxable[0][0]['total_nontaxable'], 2),
                'nctaxable_amount'    => round($nctaxable[0][0]['total_taxable'], 2),
                'ncnontaxable_amount' => round($ncnontaxable[0][0]['total_nontaxable'], 2),
                'service_charge'      => round($transactions[0][0]['servicecharge'], 2),
                'delivery_charge'     => round($transactions[0][0]['delivery_charge'], 2),
                'packing_charge'      => round($transactions[0][0]['packing_charge'], 2),
                'net_sale'            => round($net_amount_query[0][0]['net_amount'], 2),
                'ncnet_sale'          => round($ncnet_amount_query[0][0]['net_amount'], 2),
                'total_tax'           => round($transactions[0][0]['total_tax'], 2),
                'nctotal_tax'         => round($transactionsnc[0][0]['total_tax'], 2),
                'bill_count'          => round($transactions[0][0]['total_count'], 2),
                'net_amount'          => round($transactions[0][0]['amount'], 2),
                'round'               => round($transactions[0][0]['round'], 2),
                'pax'                 => round($transactions[0][0]['pax'], 2),
                'other_taxes'         => round($transactions[0][0]['other_taxes'], 2),
                'platform_tax_total'  => $exp_platform_tax_total,
                'platform_gst_map'    => $exp_platform_by_channel,
            ];

            foreach ($onlinepayments as $onlinepayment) {
                $onlinepaymentsarr[] = [
                    'type'   => $onlinepayment['order_payments']['type_value'],
                    'amount' => $onlinepayment[0]['total_amount'],
                ];
            }

            foreach ($taxes as $tax) {
                $taxesarr[] = [
                    'tax_name'  => $tax['order_tax']['tax_name'] . '(' . $tax['order_tax']['tax_percentage'] . '%)',
                    'tax_value' => $tax[0]['tax_value'],
                ];
            }


            foreach ($sales as $sale) {
                $salesarr[] = [
                    'kds'            => $sale['final']['kds_name'],
                    'kds_value'      => round($sale['final']['value'], 2),
                    'tax_percentage' => $sale['final']['tax_percentage'],
                ];
            }

            foreach ($ncsales as $ncsale) {
                $ncsalesarr[] = [
                    'kds'            => $ncsale['final']['kds_name'],
                    'kds_value'      => round($ncsale['final']['value'], 2),
                    'tax_percentage' => $ncsale['final']['tax_percentage'],
                ];
            }

            foreach ($dish_sales as $dish_sale) {
                $dishsalesarr[] = [
                    'dish_type'    => $dish_sale['cp']['dish_type'],
                    'total_amount' => round($dish_sale['0']['total_amount'], 2),
                ];
            }

            $dishsaleswithoutservicearr = [];
            foreach ($dish_saleswithoutservice as $dish_sale) {
                $dishsaleswithoutservicearr[] = [
                    'dish_type'    => $dish_sale['cp']['dish_type'],
                    'total_amount' => round($dish_sale[0]['total_amount'], 2),
                ];
            }

           // Extract VAT rates dynamically
                $vat_rates = [];
                if (isset($taxes)) {
                    foreach ($taxes as $tax) {
                        $tax_name = $tax['order_tax']['tax_name'];
                        if (stripos($tax_name, 'VAT') !== false) {
                            $vat_rates[] = (float)$tax['order_tax']['tax_percentage'];
                        }
                    }
                }
                $vat_rates = array_unique($vat_rates);
                sort($vat_rates);
                if (empty($vat_rates)) {
                    $vat_rate = "18.9";
                } else {
                    $vat_rate = implode("/", $vat_rates);
                }

                // ── Service Charge Breakup (Proportional allocation per transaction) ──
                $sc_data = $this->Order_orderhistory->query("
                    WITH item_amounts AS (
                        SELECT 
                            ooh.id,
                            ooh.local_id,
                            ooh.reference_id,
                            ooh.store_id,
                            ooh.product_id,
                            ooh.quantity,
                            cp.dish_type,
                            ot_tax.tax_percentage,
                            ot_tax.taxable_price AS item_amount
                        FROM order_transactions ot
                        INNER JOIN order_orderhistories ooh ON ooh.reference_id = ot.reference_id AND ooh.store_id = ot.store_id
                        INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                        INNER JOIN (
                            SELECT orderhistory_id, store_id, MAX(taxable_price) AS taxable_price, MAX(tax_percentage) AS tax_percentage
                            FROM order_tax
                            WHERE order_tax.status != 0 $orderTaxFilter
                            GROUP BY orderhistory_id, store_id
                        ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                        WHERE ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                          AND ot.status = 2
                          $otFilter
                          AND ooh.status = 2
                          AND ot.reference_id NOT IN (
                              SELECT op_sc2.reference_id FROM order_payments op_sc2
                              WHERE op_sc2.type = 7 AND op_sc2.status = 2 $cashierFilter
                          )
                    ),
                    bill_totals AS (
                        SELECT 
                            ot.reference_id,
                            ot.store_id,
                            ot.servicecharge,
                            SUM(ia.item_amount) AS bill_item_sum
                        FROM order_transactions ot
                        INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                        WHERE ot.status = 2
                          AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                          $otFilter
                          AND ot.reference_id NOT IN (
                              SELECT op_sc2.reference_id FROM order_payments op_sc2
                              WHERE op_sc2.type = 7 AND op_sc2.status = 2 $cashierFilter
                          )
                        GROUP BY ot.reference_id, ot.store_id, ot.servicecharge
                    )
                    SELECT 
                        cp.dish_type,
                        ia.tax_percentage,
                        ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.servicecharge ELSE 0 END), 2) AS sc_amount
                    FROM item_amounts ia
                    INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
                    INNER JOIN catalog_product cp ON cp.id = ia.product_id
                    GROUP BY cp.dish_type, ia.tax_percentage
                ");

                $total_sc_from_transactions = isset($transactions[0][0]['servicecharge']) ? (float)$transactions[0][0]['servicecharge'] : 0.0;
                $sc_gst_raw = 0.0;
                $sc_vat_raw = 0.0;
                $vat_on_sc_raw = 0.0;
                foreach ($sc_data as $row) {
                    $dt = (int)$row['cp']['dish_type'];
                    $amt = (float)$row[0]['sc_amount'];
                    $pct = isset($row['ia']['tax_percentage']) ? (float)$row['ia']['tax_percentage'] : (!empty($vat_rates) ? $vat_rates[0] : 18.9);
                    if ($dt == 2) {
                        $sc_vat_raw += $amt;
                        $vat_on_sc_raw += $amt * ($pct / 100);
                    } else {
                        $sc_gst_raw += $amt;
                    }
                }
                $diff = $total_sc_from_transactions - ($sc_gst_raw + $sc_vat_raw);
                if (abs($diff) > 0.001) {
                    $sc_gst_raw += $diff;
                }
                $sc_gst = round($sc_gst_raw, 2);
                $sc_vat = round($sc_vat_raw, 2);
                $sc_total = round($total_sc_from_transactions, 2);
                $vat_on_sc = round($vat_on_sc_raw, 2);

                $scbreakuparr = [
                    'sc_total'   => $sc_total,
                    'sc_gst'     => $sc_gst,
                    'sc_vat'     => $sc_vat,
                    'sgst_on_sc' => round($sc_gst * 0.025, 2),
                    'cgst_on_sc' => round($sc_gst * 0.025, 2),
                    'vat_on_sc'  => $vat_on_sc,
                ];
            
            $this->set('platform_gst_total', $pdf_platform_tax_total);
            $this->set(compact(
                'startdate', 'enddate', 'datas', 'taxesarr', 'salesarr',
                'ncsalesarr', 'dishsalesarr', 'dishsaleswithoutservicearr',
                'scbreakuparr', 'onlinepaymentsarr', 'charges_taxes', 'platformGstChannels',
                'platform_gst_map', 'onlinepayments', 'online_net_sales_arr', 'vat_rate'
            ));
        }
    }
    
    private function buildLowStockList($data, $limit = 4, $type = 'sku')
    {
        if (empty($data)) {
            return '';
        }

        $count = count($data);
        $html  = "<ul style='margin:0; padding-left:15px;'>";

        for ($i = 0; $i < min($limit, $count); $i++) {
            $item = $data[$i];
           
            $name = h($item['s']['name']);
            $requiredStock = (float)$item['s']['required_stock'];
            $availableStock = (float)$item['l']['last_balance'];
            
            $html .= "
                <li>
                    <span style='color:red; font-weight:bold;'>{$name}</span>
                    <span> / Required: {$requiredStock}</span>
                    <span> / Available: {$availableStock}</span>
                </li>
            ";
        }

        $html .= "</ul>";

        if ($count > $limit) {
            $html .= "<div style='font-weight:bold; margin-top:4px;'>... more</div>";
        }

        return $html;
    }
    
    public function markAlertViewed() {
        $this->autoRender = false;
        $this->layout = false;
        if ($this->request->is('post')) {
            $type = $this->request->data('type');
            $skuIds = $this->request->data('sku_ids');
            $storeId = $this->request->data('store_id');

            if (empty($skuIds) || empty($storeId)) {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
                return;
            }

            try {
                $this->loadModel('Inventory_sku');
                $this->Inventory_sku->markAlertsAsViewed([$storeId], $skuIds);
                
                echo json_encode(['success' => true, 'message' => 'Alerts marked as viewed']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
    }

    public function wallet_report() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "users";
        
        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        
        if($this->request->is('post', 'put'))
        {
            $daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }    

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $card = $this->Card->query("SELECT SUM(IF(status IN (2,1),1,0)) AS activecard, SUM(IF(status=0,1,0)) AS inactivecard FROM cards WHERE created between '$startDatetime' and '$endDatetime'");
        $activecards=$card[0][0]['activecard'];
        $inactivecards=$card[0][0]['inactivecard'];
        
        $cardtracking = $this->Cardtracking->query("SELECT SUM(IF(type=1 and purpose='add',amount,0)) AS totadd, SUM(IF(type=2 and purpose='withdrawal',amount,0)) AS totused, SUM(IF(type=3,amount,0)) AS totsec, SUM(IF(type=4,amount,0)) AS totref,COUNT(DISTINCT(customerphone)) as customers FROM cardtracking WHERE created between '$startDatetime' and '$endDatetime'");
        $totaladded=$cardtracking[0][0]['totadd'];
        $totalused=$cardtracking[0][0]['totused'];
        $totalsecurity=$cardtracking[0][0]['totsec'];
        $totalrefund=$cardtracking[0][0]['totref'];
     
        
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
    
        $this->set(compact('activecards','inactivecards','totaladded','totalused','totalsecurity','totalrefund'));
    }

    public function wallet_reportexport() {
        $date_range= $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate= date('Y-m-d', strtotime($start_date));
        $enddate= date('Y-m-d', strtotime($end_date));
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $card = $this->Card->query("SELECT SUM(IF(status IN (2,1),1,0)) AS activecard, SUM(IF(status=0,1,0)) AS inactivecard FROM cards WHERE created between '$startDatetime' and '$endDatetime'");
        $activecards=$card[0][0]['activecard'];
        $inactivecards=$card[0][0]['inactivecard'];
        
        $cardtracking = $this->Cardtracking->query("SELECT SUM(IF(type=1 and purpose='add',amount,0)) AS totadd, SUM(IF(type=2 and purpose='withdrawal',amount,0)) AS totused, SUM(IF(type=3,amount,0)) AS totsec, SUM(IF(type=4,amount,0)) AS totref,COUNT(DISTINCT(customerphone)) as customers FROM cardtracking WHERE created between '$startDatetime' and '$endDatetime'");
        $totaladded=$cardtracking[0][0]['totadd'];
        $totalused=$cardtracking[0][0]['totused'];
        $totalsecurity=$cardtracking[0][0]['totsec'];
        $totalrefund=$cardtracking[0][0]['totref'];
        
        
        App::import('Vendor', 'PHPExcel/PHPExcel');
           error_reporting(0);
           ini_set('display_errors', TRUE);
           ini_set('display_startup_errors', TRUE);
           if (PHP_SAPI == 'cli')
                die('This example should only be run from a Web Browser');
           $objPHPExcel = new PHPExcel();
           // Set document properties
           $objPHPExcel->getProperties()->setCreator("Aether")
           ->setLastModifiedBy("Aether")
           ->setTitle("Office 2007 XLSX Aether Document")
           ->setSubject("Office 2007 XLSX Aether Document")
           ->setDescription("Aether Export Data File.")
           ->setKeywords("office 2007 openxml php")
           ->setCategory("AetherPos Export Data File");
           // Add some data
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '   ' .'WALLET REPORT FROM '.$startdate.' TO '.$enddate);
           
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', '   ' .'Card Data');
            
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A4', 'Active Cards');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B4', $activecards);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A5', 'Inactive Cards');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B5', $inactivecards);
            
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A7', '   ' .'Wallet Data');
            
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A8', 'Total Balance Added');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B8', round($totaladded,2));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A9', 'Total Used Amount');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B9', round($totalused,2));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A10', 'Refunded Amount');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B10', round($totalrefund,2));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A11', 'Security Amount');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B11', round($totalsecurity,2));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A12', 'Net Balance');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B12', round(($totaladded-$totalused),2));
            
            $objPHPExcel->getActiveSheet()->getStyle("A1:B3")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle("A7:B7")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A1:B1');
            
            $baseRow = 3;
            $r=1;
        
            $objPHPExcel->getActiveSheet()->setTitle('Export Data');
            $objPHPExcel->setActiveSheetIndex(0);
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.'WALLET REPORT.xls"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
            header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header ('Pragma: public'); // HTTP/1.0
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
               
    }

    public function wallet_reportpdf() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "login";
        if($this->request->is('post', 'put'))
        {
            $date_range=  $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
            
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

            $card = $this->Card->query("SELECT SUM(IF(status IN (1,2),1,0)) AS activecard, SUM(IF(status=0,1,0)) AS inactivecard FROM cards WHERE created between '$startDatetime' and '$endDatetime'");
            $activecards=$card[0][0]['activecard'];
            $inactivecards=$card[0][0]['inactivecard'];
            
            $cardtracking = $this->Cardtracking->query("SELECT SUM(IF(type=1 and purpose='add',amount,0)) AS totadd, SUM(IF(type=2 and purpose='withdrawal',amount,0)) AS totused, SUM(IF(type=3,amount,0)) AS totsec, SUM(IF(type=4,amount,0)) AS totref,COUNT(DISTINCT(customerphone)) as customers FROM cardtracking WHERE created between '$startDatetime' and '$endDatetime'");
            $totaladded=$cardtracking[0][0]['totadd'];
            $totalused=$cardtracking[0][0]['totused'];
            $totalsecurity=$cardtracking[0][0]['totsec'];
            $totalrefund=$cardtracking[0][0]['totref'];
            
            
            $this->set(compact('startdate','enddate','activecards','inactivecards','totaladded','totalused','totalsecurity','totalrefund'));
        }
    }

    public function wallet_history() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        $this->layout = "users";
        
        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        
        if($this->request->is('post', 'put'))
        {
            $daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }   

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cards = $this->Card->query("SELECT cards.id,cards.cardnumber,cards.cardbalance,cards.name,cards.mobile,cardtracking.txnid,cardtracking.customername,cardtracking.cashierName,cardtracking.type,cardtracking.amount,cardtracking.payment_mode as mop,cards.created FROM `cards` LEFT JOIN `cardtracking` ON cardtracking.cardnumber = cards.cardnumber where cards.created BETWEEN '$startDatetime' and '$endDatetime' ORDER BY cardtracking.id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        
        $this->set(compact('cards'));
    }

    public function wallet_historyexport() {
        $date_range= $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate= date('Y-m-d', strtotime($start_date));
        $enddate= date('Y-m-d', strtotime($end_date));
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cards = $this->Card->query("SELECT cards.id,cards.cardnumber,cards.cardbalance,cards.name,cards.mobile,cardtracking.txnid,cardtracking.customername,cardtracking.cashierName,cardtracking.type,cardtracking.amount,cardtracking.payment_mode,cards.created FROM `cards` LEFT JOIN `cardtracking` ON cardtracking.cardnumber = cards.cardnumber where cards.created BETWEEN '$startDatetime' and '$endDatetime'  ORDER BY cardtracking.id desc");
        $datas=array();
        foreach($cards as $card){
            if(isset($card['cardtracking']['txnid'])){
                $datas[]=array(
                    'txnid'=>$card['cardtracking']['txnid'],
                    'cashierName'=>$card['cardtracking']['cashierName'],
                    'cardnumber'=>$card['cards']['cardnumber'],
                    'mobile'=>$card['cards']['mobile'],
                    'name'=>$card['cards']['name'],
                    'type'=>$card['cardtracking']['type'],
                    'payment_mode'=>$card['cardtracking']['payment_mode'],
                    'amount'=>$card['cardtracking']['amount'],
                    'created'=>$card['cards']['created']
                );
            }
        }
        
        App::import('Vendor', 'PHPExcel/PHPExcel');
           error_reporting(0);
           ini_set('display_errors', TRUE);
           ini_set('display_startup_errors', TRUE);
           if (PHP_SAPI == 'cli')
                die('This example should only be run from a Web Browser');
           $objPHPExcel = new PHPExcel();
           // Set document properties
           $objPHPExcel->getProperties()->setCreator("Aether")
           ->setLastModifiedBy("Aether")
           ->setTitle("Office 2007 XLSX Aether Document")
           ->setSubject("Office 2007 XLSX Aether Document")
           ->setDescription("Aether Export Data File.")
           ->setKeywords("office 2007 openxml php")
           ->setCategory("AetherPos Export Data File");
           // Add some data
           $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '   ' .'WALLET HISTORY FROM '.$startdate.' TO '.$enddate);
           
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'SR. NO.');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Txn id');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Cashier Name');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Card Number');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'Phone');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'Customer');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G3', 'Type');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H3', 'Payment Mode');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I3', 'Amount');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J3', 'Created');
            $objPHPExcel->getActiveSheet()->getStyle("A1:J3")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');
            
            $baseRow = 3;
            $r=1;
            
            foreach($datas as $dat){
                  
                if($dat['type']==1){
                    $type='credit';
                }elseif($dat['type']==2){
                    $type='debit';
                }elseif($dat['type']==3){
                    $type='security';
                }else{
                    $type='refund';
                }
                
                if($dat['payment_mode']=='card_charge'){
                    $purpose='Card Charge';
                }else{
                    $purpose=$dat['payment_mode'];
                }
                
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($baseRow + $r),$r);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($baseRow + $r),$dat['txnid']);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($baseRow + $r),$dat['cashierName']);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.($baseRow + $r),$dat['cardnumber']);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.($baseRow + $r),$dat['mobile']);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.($baseRow + $r),$dat['name']);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.($baseRow + $r),$type);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.($baseRow + $r),$purpose);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.($baseRow + $r),$dat['amount']);
              $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J'.($baseRow + $r),$dat['created']);
                              
                       $r++;
               
            }
            $objPHPExcel->getActiveSheet()->setTitle('Export Data');
            $objPHPExcel->setActiveSheetIndex(0);
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.'WALLET HISTORY.xls"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
            header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header ('Pragma: public'); // HTTP/1.0
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
               
    }

    public function wallet_historypdf() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "login";
        if($this->request->is('post', 'put'))
        {
            $date_range=  $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
            
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

            $cards = $this->Card->query("SELECT cards.id,cards.cardnumber,cards.cardbalance,cards.name,cards.mobile,cardtracking.txnid,cardtracking.customername,cardtracking.cashierName,cardtracking.type,cardtracking.amount,cardtracking.payment_mode as mop,cards.created FROM `cards` LEFT JOIN `cardtracking` ON cardtracking.cardnumber = cards.cardnumber where cards.created BETWEEN '$startDatetime' and '$endDatetime'  ORDER BY cardtracking.id desc");
            $datas=array();
            foreach($cards as $card){
                if(isset($card['cardtracking']['txnid'])){
                    $datas[]=array(
                        'txnid'=>$card['cardtracking']['txnid'],
                        'cashierName'=>$card['cardtracking']['cashierName'],
                        'cardnumber'=>$card['cards']['cardnumber'],
                        'mobile'=>$card['cards']['mobile'],
                        'name'=>$card['cards']['name'],
                        'type'=>$card['cardtracking']['type'],
                        'payment_mode'=>$card['cardtracking']['mop'],
                        'amount'=>$card['cardtracking']['amount'],
                        'created'=>$card['cards']['created']
                    );
                }
            }
            $this->set(compact('startdate','enddate','datas'));
        }
    }

    public function employee_sales_report() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->layout = "users";
        $data = $this->_getEmployeeSalesData();
        $this->set($data);
    }

    public function employee_sales_report_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->layout = null;
        $data = $this->_getEmployeeSalesData();
        $this->set($data);
    }

    public function employee_sales_report_export() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->autoRender = false;
        $data = $this->_getEmployeeSalesData();

        $filename = "employee_sales_report_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');

        // Title Header
        fputcsv($out, ['Employee / Cashier Sales Report']);
        fputcsv($out, ['Store:', $data['storeName'], 'Date Range:', $data['daterangeValue']]);
        fputcsv($out, []); // blank line

        // Section 1: Financial Summary
        fputcsv($out, ['--- 1. Cashier Sales & Financial Summary (DSR Style) ---']);
        fputcsv($out, ['#', 'Employee / Cashier', 'Bills Count', 'Qty Sold', 'Subtotal (INR)', 'Discount (INR)', 'Net Sales (INR)', 'Tax (INR)', 'Total Collection (INR)', 'NC Sales (INR)', 'Payment Breakdown']);

        $i = 1;
        foreach ($data['cashierSalesList'] as $cs) {
            $pmParts = [];
            if ($cs['payments']['cash'] > 0)      $pmParts[] = 'Cash: ' . number_format($cs['payments']['cash'], 2);
            if ($cs['payments']['card'] > 0)      $pmParts[] = 'Card: ' . number_format($cs['payments']['card'], 2);
            if ($cs['payments']['upi'] > 0)       $pmParts[] = 'UPI: ' . number_format($cs['payments']['upi'], 2);
            if ($cs['payments']['online'] > 0)    $pmParts[] = 'Online: ' . number_format($cs['payments']['online'], 2);
            if ($cs['payments']['storecard'] > 0) $pmParts[] = 'Storecard: ' . number_format($cs['payments']['storecard'], 2);
            if ($cs['payments']['nc'] > 0)        $pmParts[] = 'NC: ' . number_format($cs['payments']['nc'], 2);

            if (!empty($cs['payments']['custom'])) {
                foreach ($cs['payments']['custom'] as $cName => $cAmt) {
                    if ($cAmt > 0) {
                        $pmParts[] = $cName . ': ' . number_format($cAmt, 2);
                    }
                }
            }
            $paymentBreakdown = !empty($pmParts) ? implode(' | ', $pmParts) : 'No payments recorded';

            fputcsv($out, [
                $i++,
                $cs['cashier_name'],
                $cs['bills_count'],
                number_format($cs['qty_sold'], 2, '.', ''),
                number_format($cs['gross_sales'], 2, '.', ''),
                number_format($cs['discount'], 2, '.', ''),
                number_format($cs['net_sales'], 2, '.', ''),
                number_format($cs['tax'], 2, '.', ''),
                number_format($cs['net_payable'], 2, '.', ''),
                number_format($cs['nc_sales'], 2, '.', ''),
                $paymentBreakdown,
            ]);
        }
        $gt = $data['grandTotals'];
        fputcsv($out, [
            'TOTAL',
            '-',
            $gt['bills_count'],
            number_format($gt['qty_sold'], 2, '.', ''),
            number_format($gt['gross_sales'], 2, '.', ''),
            number_format($gt['discount'], 2, '.', ''),
            number_format($gt['net_sales'], 2, '.', ''),
            number_format($gt['tax'], 2, '.', ''),
            number_format($gt['net_payable'], 2, '.', ''),
            number_format($gt['nc_sales'], 2, '.', ''),
            '-',
        ]);

        fputcsv($out, []); // blank line

        // Section 2: Dish Type Sales Without Service Charge
        fputcsv($out, ['--- 2. Dish Type Sales Without Service Charge ---']);
        $dtNoHeader = ['#', 'Employee / Cashier'];
        foreach ($data['allDishTypesNoSc'] as $dtKey) {
            $dtNoHeader[] = $dtKey . ' (INR)';
        }
        $dtNoHeader[] = 'Total Without SC (INR)';
        fputcsv($out, $dtNoHeader);

        $n = 1;
        foreach ($data['cashierSalesList'] as $cs) {
            $rRow = [$n++, $cs['cashier_name']];
            $rSum = 0;
            foreach ($data['allDishTypesNoSc'] as $dtKey) {
                $val = floatval($cs['dish_type_no_sc'][$dtKey] ?? 0);
                $rSum += $val;
                $rRow[] = number_format($val, 2, '.', '');
            }
            $rRow[] = number_format($rSum, 2, '.', '');
            fputcsv($out, $rRow);
        }
        $dtNoTotRow = ['TOTAL WITHOUT SC', '-'];
        $totNoScSum = 0;
        foreach ($data['allDishTypesNoSc'] as $dtKey) {
            $val = floatval($data['grandTotalsDtNoSc'][$dtKey] ?? 0);
            $totNoScSum += $val;
            $dtNoTotRow[] = number_format($val, 2, '.', '');
        }
        $dtNoTotRow[] = number_format($totNoScSum, 2, '.', '');
        fputcsv($out, $dtNoTotRow);

        fclose($out);
        exit;
    }

    public function item_sale_report() { 
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime('-7 days'));
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {
            $daterange  = $this->request->data['daterange'];
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $end_date   = trim($dates[1]);
            $endDate    = date('m/d/Y', strtotime($end_date)); 
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }
        $cashier_id       = $this->request->data['Search']['cashier'] ?? null;
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cashierCondition = $cashier_id ? "AND o.store_id = " . (int)$cashier_id : "";

        $branchJoin = $cashier_id
            ? "LEFT JOIN catalog_product_branches cpb
                   ON  cpb.product_id  = o.product_id
                   AND cpb.status      = 1
                   AND cpb.location_id = " . (int)$cashier_id
            : "";

        $sellingPrice = $cashier_id
            ? "COALESCE(cpb.price, p.price)"
            : "p.price";

        // ── Query 1: Regular orders (type != 7) ──────────────────────────────────
        // Uses EXISTS instead of IN — same logic, but optimized
        $orderhistories = $this->Order_orderhistory->query("
            SELECT
                o.product_id,
                o.product_name,
                o.category_name                 AS subcat_name,
                p.unit_name,
                p.cat_name,
                CASE p.dish_type
                    WHEN 0 THEN 'Food'
                    WHEN 1 THEN 'Beverage'
                    WHEN 2 THEN 'Liquor'
                    WHEN 3 THEN 'Others'
                    ELSE 'NA'
                END                             AS dish_type,
                SUM(o.quantity)                 AS totalquantity,
                COALESCE(
                    SUM(o.price * o.quantity * td.discount_percentage / 100),
                    0
                )                               AS totaldiscount,
                ROUND(
                    SUM(
                        (o.price * o.quantity)
                        * (1 - IFNULL(td.discount_percentage, 0) / 100)
                    ) / NULLIF(SUM(o.quantity), 0),
                    2
                )                               AS final_price,
                $sellingPrice                   AS selling_price,
                p.food_cost                     AS food_cost

            FROM order_orderhistories o
            LEFT JOIN catalog_product p
                ON  p.id     = o.product_id
                AND p.status = 1
            $branchJoin
            LEFT JOIN (
                SELECT
                    orderhistory_id,
                    MAX(discount_percentage) AS discount_percentage,store_id
                FROM order_tax
                WHERE status = 2
                GROUP BY orderhistory_id, store_id
            ) td
                ON td.orderhistory_id = COALESCE(NULLIF(o.local_id, ''), o.id) and td.store_id=o.store_id
            WHERE o.status = 2
              AND o.created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierCondition
              AND EXISTS (
                  SELECT 1
                  FROM order_payments op
                  WHERE op.reference_id = o.reference_id
                    AND op.store_id     = o.store_id
                    AND op.status       = 2
                    AND op.type        != 7
              )
            GROUP BY o.product_id
        ");

        // ── Query 2: NC orders (type = 7) ─────────────────────────────────────────
        $ncorderhistories = $this->Order_orderhistory->query("
            SELECT
                o.product_id,
                o.product_name,
                o.category_name                 AS subcat_name,
                p.unit_name,
                p.cat_name,
                CASE p.dish_type
                    WHEN 0 THEN 'Food'
                    WHEN 1 THEN 'Beverage'
                    WHEN 2 THEN 'Liquor'
                    WHEN 3 THEN 'Others'
                    ELSE 'NA'
                END                             AS dish_type,
                SUM(o.quantity)                 AS totalquantity,
                COALESCE(
                    SUM(o.price * o.quantity * td.discount_percentage / 100),
                    0
                )                               AS totaldiscount,
                ROUND(
                    SUM(
                        (o.price * o.quantity)
                        * (1 - IFNULL(td.discount_percentage, 0) / 100)
                    ) / NULLIF(SUM(o.quantity), 0),
                    2
                )                               AS final_price,
                $sellingPrice                   AS selling_price,
                p.food_cost                     AS food_cost

            FROM order_orderhistories o
            LEFT JOIN catalog_product p
                ON  p.id     = o.product_id
                AND p.status = 1
            $branchJoin
            LEFT JOIN (
                SELECT
                    orderhistory_id,
                    MAX(discount_percentage) AS discount_percentage,store_id
                FROM order_tax
                WHERE status != 0
                GROUP BY orderhistory_id, store_id
            ) td
                ON td.orderhistory_id = COALESCE(NULLIF(o.local_id, ''), o.id) and td.store_id=o.store_id
            WHERE o.status = 2
              AND o.created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierCondition
              AND EXISTS (
                  SELECT 1
                  FROM order_payments op
                  WHERE op.reference_id = o.reference_id
                    AND op.store_id     = o.store_id
                    AND op.status       = 2
                    AND op.type         = 7
              )
            GROUP BY o.product_id
        ");

        // ── Bulk fetch all recipe costs in ONE query ───────────────────────────────
        $allHistories = array_merge($orderhistories, $ncorderhistories);
        $productIds   = array_unique(array_map(function($h) {
            return (int) $h['o']['product_id'];
        }, $allHistories));

        $costMap = [];
        if (!empty($productIds)) {
            $idList   = implode(',', $productIds);
            $costRows = $this->Inventory_linking->query("
                SELECT id, recipe_cost
                FROM v_product_recipe_cost
                WHERE id IN ($idList)
            ");
            foreach ($costRows as $row) {
                $costMap[(int)$row['v_product_recipe_cost']['id']] =
                    (float)($row['v_product_recipe_cost']['recipe_cost'] ?? 0);
            }
        }

        // ── Build productcosts — exactly matching original logic ──────────────────
        $productcosts = [];

        foreach ([true => $ncorderhistories, false => $orderhistories] as $isNc => $histories) {
            foreach ($histories as $history) {
            // print_r($history);exit;
                $data          = $history['o'];
                $productDetail = $history['p'];
                $calc          = $history[0];
                
                $product_id    = (int) $data['product_id'];
                $quantity      = $calc['totalquantity'];
                $totaldiscount = $calc['totaldiscount'];
                $final_price   = $calc['final_price'];

                // ✅ selling_price is a computed alias — lives in $history[0]
                
                $selling_price = $productDetail['selling_price'] ?? $calc['selling_price'];

                $unit_cost  = $costMap[$product_id] ?? 0.0;
                $total_cost = round($unit_cost * $quantity, 2);

                if (!isset($productcosts[$product_id])) {
                    $productcosts[$product_id] = [
                        'product_id'      => $product_id,
                        'product_name'    => $data['product_name'],
                        'category_name'   => $productDetail['cat_name'],
                        'subcat_name'     => $data['subcat_name'],
                        'dish_type'       => $calc['dish_type'],
                        'unit_name'       => $productDetail['unit_name'],
                        'selling_price'   => $selling_price,
                        'unit_cost'       => 0,
                        'totalquantity'   => 0,
                        'final_price'     => 0,
                        'total_cost'      => 0,
                        'totaldiscount'   => 0,
                        'nctotalquantity' => 0,
                        'ncfinal_price'   => 0,
                        'nctotal_cost'    => 0,
                        'food_cost'       => $productDetail['food_cost'] ?? 0,
                        'total_food_cost' => 0,
                        'margin'          => 0,
                    ];
                }

                if ($isNc) {
                    $productcosts[$product_id]['nctotalquantity'] += $quantity;
                    $productcosts[$product_id]['ncfinal_price']   += $final_price;
                    $productcosts[$product_id]['nctotal_cost']    += $total_cost;
                } else {
                    $productcosts[$product_id]['totalquantity']  += $quantity;
                    $productcosts[$product_id]['totaldiscount']  += $totaldiscount;
                    $productcosts[$product_id]['final_price']    += $final_price;
                    $productcosts[$product_id]['unit_cost']       = $unit_cost;
                    $productcosts[$product_id]['total_cost']     += $total_cost;
                }
            }
        }
        // echo "<pre/>";
        // print_r($productcosts);exit;

        // ── Auth / location filtering (unchanged) ─────────────────────────────────
        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        
        // Normalize location IDs from both fields
        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        // Build conditions
        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];

        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $conditions['id'] = -1; // No locations assigned — return nothing
            } else {
                $conditions['id'] = $userLocationIds; // CakePHP handles IN() automatically
            }
        }

        $users = $this->Location->find('all', ['conditions' => $conditions]);
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users', 'productcosts', 'orderhistories', 'cashier_id'));
    }
    
    public function item_sale_reportexport() {
        $date_range = $_GET['sdate'] ?? '';
        $cashier_id = $_GET['cashier'] ?? null;

        $dates      = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date   = trim($dates[1]);

        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate   = date('Y-m-d', strtotime($end_date));
        
        // ── Store name ────────────────────────────────────────────────────────────
        if ($cashier_id) {
            $storenames = $this->Location->find('first', ['conditions' => ['id' => $cashier_id]]);
            $storename  = $storenames['Location']['name'];
        } else {
            $storename = 'All Stores';
        }
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cashierCondition = $cashier_id ? "AND o.store_id = " . (int)$cashier_id : "";

        $branchJoin = $cashier_id
            ? "LEFT JOIN catalog_product_branches cpb
                   ON  cpb.product_id  = o.product_id
                   AND cpb.status      = 1
                   AND cpb.location_id = " . (int)$cashier_id
            : "";

        $sellingPrice = $cashier_id
            ? "COALESCE(cpb.price, p.price)"
            : "p.price";

        // ── Query 1: Regular orders (type != 7) ───────────────────────────────────
        $orderhistories = $this->Order_orderhistory->query("
            SELECT
                o.product_id,
                o.product_name,
                o.category_name                 AS subcat_name,
                p.unit_name,
                p.cat_name,
                CASE p.dish_type
                    WHEN 0 THEN 'Food'
                    WHEN 1 THEN 'Beverage'
                    WHEN 2 THEN 'Liquor'
                    WHEN 3 THEN 'Others'
                    ELSE 'NA'
                END                             AS dish_type,
                SUM(o.quantity)                 AS totalquantity,
                COALESCE(
                    SUM(o.price * o.quantity * td.discount_percentage / 100),
                    0
                )                               AS totaldiscount,
                ROUND(
                    SUM(
                        (o.price * o.quantity)
                        * (1 - IFNULL(td.discount_percentage, 0) / 100)
                    ) / NULLIF(SUM(o.quantity), 0),
                    2
                )                               AS final_price,
                $sellingPrice                   AS selling_price,
                p.food_cost                     AS food_cost
            FROM order_orderhistories o
            LEFT JOIN catalog_product p
                ON  p.id     = o.product_id
                AND p.status = 1
            $branchJoin
            LEFT JOIN (
                SELECT
                    orderhistory_id,
                    MAX(discount_percentage) AS discount_percentage,store_id
                FROM order_tax
                WHERE status = 2
                GROUP BY orderhistory_id, store_id
            ) td
                ON td.orderhistory_id = COALESCE(NULLIF(o.local_id, ''), o.id) and td.store_id=o.store_id
            WHERE o.status = 2
              AND o.created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierCondition
              AND EXISTS (
                  SELECT 1
                  FROM order_payments op
                  WHERE op.reference_id = o.reference_id
                    AND op.store_id     = o.store_id
                    AND op.status       = 2
                    AND op.type        != 7
              )
            GROUP BY o.product_id
        ");

        // ── Query 2: NC orders (type = 7) ─────────────────────────────────────────
        $ncorderhistories = $this->Order_orderhistory->query("
            SELECT
                o.product_id,
                o.product_name,
                o.category_name                 AS subcat_name,
                p.unit_name,
                p.cat_name,
                CASE p.dish_type
                    WHEN 0 THEN 'Food'
                    WHEN 1 THEN 'Beverage'
                    WHEN 2 THEN 'Liquor'
                    WHEN 3 THEN 'Others'
                    ELSE 'NA'
                END                             AS dish_type,
                SUM(o.quantity)                 AS totalquantity,
                COALESCE(
                    SUM(o.price * o.quantity * td.discount_percentage / 100),
                    0
                )                               AS totaldiscount,
                ROUND(
                    SUM(
                        (o.price * o.quantity)
                        * (1 - IFNULL(td.discount_percentage, 0) / 100)
                    ) / NULLIF(SUM(o.quantity), 0),
                    2
                )                               AS final_price,
                $sellingPrice                   AS selling_price,
                p.food_cost                     AS food_cost
            FROM order_orderhistories o
            LEFT JOIN catalog_product p
                ON  p.id     = o.product_id
                AND p.status = 1
            $branchJoin
            LEFT JOIN (
                SELECT
                    orderhistory_id,
                    MAX(discount_percentage) AS discount_percentage,store_id
                FROM order_tax
                WHERE status != 0
                GROUP BY orderhistory_id, store_id
            ) td
                ON td.orderhistory_id = COALESCE(NULLIF(o.local_id, ''), o.id) and td.store_id=o.store_id
            WHERE o.status = 2
              AND o.created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierCondition
              AND EXISTS (
                  SELECT 1
                  FROM order_payments op
                  WHERE op.reference_id = o.reference_id
                    AND op.store_id     = o.store_id
                    AND op.status       = 2
                    AND op.type         = 7
              )
            GROUP BY o.product_id
        ");

        // ── Bulk fetch all recipe costs in ONE query ───────────────────────────────
        $allHistories = array_merge($orderhistories, $ncorderhistories);
        $productIds   = array_unique(array_map(function($h) {
            return (int) $h['o']['product_id'];
        }, $allHistories));

        $costMap = [];
        if (!empty($productIds)) {
            $idList   = implode(',', $productIds);
            $costRows = $this->Inventory_linking->query("
                SELECT id, recipe_cost
                FROM v_product_recipe_cost
                WHERE id IN ($idList)
            ");
            foreach ($costRows as $row) {
                $costMap[(int)$row['v_product_recipe_cost']['id']] =
                    (float)($row['v_product_recipe_cost']['recipe_cost'] ?? 0);
            }
        }

        // ── Build productcosts ────────────────────────────────────────────────────
        $productcosts = [];

        foreach ([true => $ncorderhistories, false => $orderhistories] as $isNc => $histories) {
            foreach ($histories as $history) {
                $data          = $history['o'];
                $productDetail = $history['p'];
                $calc          = $history[0];

                $product_id    = (int) $data['product_id'];
                $quantity      = $calc['totalquantity'];
                $totaldiscount = $calc['totaldiscount'];
                $final_price   = $calc['final_price'];
                
                $selling_price = $productDetail['selling_price'] ?? $calc['selling_price'];

                $unit_cost  = $costMap[$product_id] ?? 0.0;
                $total_cost = round($unit_cost * $quantity, 2);

                if (!isset($productcosts[$product_id])) {
                    $productcosts[$product_id] = [
                        'product_id'      => $product_id,
                        'product_name'    => $data['product_name'],
                        'category_name'   => $productDetail['cat_name'],
                        'subcat_name'     => $data['subcat_name'],
                        'dish_type'       => $calc['dish_type'],
                        'unit_name'       => $productDetail['unit_name'],
                        'selling_price'   => $selling_price,
                        'unit_cost'       => 0,
                        'totalquantity'   => 0,
                        'final_price'     => 0,
                        'total_cost'      => 0,
                        'totaldiscount'   => 0,
                        'nctotalquantity' => 0,
                        'ncfinal_price'   => 0,
                        'nctotal_cost'    => 0,
                        'storename'       => $storename,  // ✅ export-specific field preserved
                        'food_cost'       => $productDetail['food_cost'] ?? 0,
                    ];
                }

                if ($isNc) {
                    $productcosts[$product_id]['nctotalquantity'] += $quantity;
                    $productcosts[$product_id]['ncfinal_price']   += $final_price;
                    $productcosts[$product_id]['nctotal_cost']    += $total_cost;
                } else {
                    $productcosts[$product_id]['totalquantity']  += $quantity;
                    $productcosts[$product_id]['totaldiscount']  += $totaldiscount;
                    $productcosts[$product_id]['final_price']    += $final_price;
                    $productcosts[$product_id]['unit_cost']       = $unit_cost;
                    $productcosts[$product_id]['total_cost']     += $total_cost;
                }
            }
        }

        // ── Excel export (unchanged) ───────────────────────────────────────────────
        App::import('Vendor', 'PHPExcel/PHPExcel');

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("POS Export")
            ->setTitle("Item Sale Report");

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ITEM SALE REPORT FROM ' . $startDatetime . ' TO ' . $endDatetime);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:R1');
        $objPHPExcel->getActiveSheet()->getStyle("A1:R1")->getFont()->setBold(true);

        $headers = [
            'SR. NO.', 'Store Name', 'Item ID', 'Item Name', 'Dish Type', 'Category', 'Sub Category',
            'Unit', 'Number Sold(Non-Comp Qty + Comp Qty)', 'Cost Price (Recipe Cost of the product)', 'Selling Price(Price on the menu)', 'Sold Price(Total Net Sales / Non-Comp Qty)',
            'Non-Comp Qty(Regular paid qty only)', 'Net Sales(Non-Comp Qty × Sold Price)', 'Billed Cost(Non-Comp Qty * Cost Price)', 'Comp Qty(Complimentary orders qty)', 'NC Amount(Average price of the comp order)',
            'Amount(Net Sales + Comp Amount)', 'Comp Amount(Comp Qty × NC Amount)', 'Total Discount(Sum of all discounts applied on paid orders)', 'NC Cost(Comp Qty × Cost Price)', 'Profit %(( Net Sales - Billed Cost ) / Net Sale * 100)',
            'Food Cost(Recipe cost of the product × Total Qty Sold)', 'Margin(Selling Price × Total Qty − Food Cost)', 'Food Cost %(Food Cost / Amount × 100)', 'Net Food Cost %(Billed Cost / Net Sales × 100)'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $objPHPExcel->getActiveSheet()->setCellValue($col . '3', $header);
            $col++;
        }

        $objPHPExcel->getActiveSheet()->getStyle("A3:Y3")->getFont()->setBold(true);

        $row = 4;
        $sr  = 1;
        $totalFoodCost = 0;
        $totalMargin   = 0;

        foreach ($productcosts as $product) {
            $sp            = $product['final_price']     ?? 0;
            $ncsp          = $product['ncfinal_price']   ?? 0;
            $billed_amount = $product['totalquantity'] * $sp;
            $billed_cost   = $product['total_cost'];
            $selling_price = $product['selling_price'];
            $ncbilled_cost = $product['nctotal_cost'];
            $contribution  = ($billed_amount > 0)
                ? round((($billed_amount - $billed_cost) / $billed_amount) * 100, 2)
                : 0;

            $total_sold   = $product['nctotalquantity'] + $product['totalquantity'];
            $comp_amount  = $ncsp * $product['nctotalquantity'];
            $total_amount = $comp_amount + $billed_amount;
            

                                              
            $food_cost_total = round((float)($product['food_cost'] ?? 0) * $total_sold, 2);
            $margin          = round(((float)$product['selling_price'] * $total_sold) - $food_cost_total, 2);
            
            $food_cost_pct     = ($total_amount > 0) ? round(($food_cost_total / $total_amount) * 100, 2) : 0;
            $net_food_cost_pct = ($billed_amount > 0) ? round(($billed_cost / $billed_amount) * 100, 2) : 0;            
            
            $totalFoodCost += $food_cost_total;
            $totalMargin += $margin;
            

            $objPHPExcel->getActiveSheet()->fromArray([
                $sr++,
                $product['storename'],
                $product['product_id']    ?? '',
                $product['product_name']    ?? '',
                $product['dish_type']       ?? '',
                $product['category_name']   ?? '',
                $product['subcat_name']     ?? '',
                $product['unit_name']       ?? '',
                number_format((float)$total_sold,                        2),
                number_format((float)($product['unit_cost'] ?? 0),       2),
                number_format((float)$selling_price,                      2),
                number_format((float)$sp,                                 2),
                number_format((float)($product['totalquantity'] ?? 0),    2),
                number_format((float)$billed_amount,                      2),
                number_format((float)$billed_cost,                        2),
                number_format((float)($product['nctotalquantity'] ?? 0),  2),
                number_format((float)$ncsp,                               2),
                number_format((float)$total_amount,                       2),
                number_format((float)$comp_amount,                        2),
                number_format((float)$product['totaldiscount'],           2),
                number_format((float)$ncbilled_cost,                      2),
                number_format((float)$contribution,                       2) . '%',
                number_format((float)$food_cost_total,                      2),
                number_format((float)$margin,                      2),
                number_format((float)$food_cost_pct,                      2) . '%',  
                number_format((float)$net_food_cost_pct,                  2) . '%',
            ], null, 'A' . $row);

            $row++;
        }
            
        $objPHPExcel->getActiveSheet()->setCellValue('U' . $row, 'Total');
        $objPHPExcel->getActiveSheet()->setCellValue('V' . $row, number_format($totalFoodCost, 2));
        $objPHPExcel->getActiveSheet()->setCellValue('W' . $row, number_format($totalMargin,   2));
        $objPHPExcel->getActiveSheet()->getStyle('U' . $row . ':W' . $row)->getFont()->setBold(true);
        
        
        /* Summary Start */

        $row++;
        $summaryStartRow = $row;

        // Build category and dish type summary with matching calculations
        $categoryGroup = [];
        $dishTypeGroup = [];

        foreach ($productcosts as $product) {
            $sp         = $product['final_price']   ?? 0;
            $ncsp       = $product['ncfinal_price'] ?? 0;

            $billed_amt = $product['totalquantity'] * $sp;
            $comp_amt   = $ncsp * $product['nctotalquantity'];
            $total_amt  = $billed_amt + $comp_amt;

            $total_qty  = $product['totalquantity'] + $product['nctotalquantity'];
            $food_cost  = round((float)($product['food_cost'] ?? 0) * $total_qty, 2);
            $billed_cost = $product['total_cost']; // recipe cost from costMap (matches main table)

            // Group by category
            $cat = $product['category_name'];
            if (!isset($categoryGroup[$cat])) {
                $categoryGroup[$cat] = [
                    'total_amt'   => 0,
                    'billed_amt'  => 0,
                    'food_cost'   => 0,
                    'billed_cost' => 0,
                ];
            }
            $categoryGroup[$cat]['total_amt']   += $total_amt;
            $categoryGroup[$cat]['billed_amt']  += $billed_amt;
            $categoryGroup[$cat]['food_cost']   += $food_cost;
            $categoryGroup[$cat]['billed_cost'] += $billed_cost;

            // Group by dish type
            $dish = $product['dish_type'];
            if (!isset($dishTypeGroup[$dish])) {
                $dishTypeGroup[$dish] = [
                    'total_amt'   => 0,
                    'billed_amt'  => 0,
                    'food_cost'   => 0,
                    'billed_cost' => 0,
                ];
            }
            $dishTypeGroup[$dish]['total_amt']   += $total_amt;
            $dishTypeGroup[$dish]['billed_amt']  += $billed_amt;
            $dishTypeGroup[$dish]['food_cost']   += $food_cost;
            $dishTypeGroup[$dish]['billed_cost'] += $billed_cost;
        }

        $grand_total_amt = array_sum(array_column($categoryGroup, 'total_amt'));

        // Section title
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, 'Food Cost Summary');
        $objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        // Headers for both tables on same row
        $objPHPExcel->getActiveSheet()->fromArray([
            'Category', 'Food Cost %(SUM(Food Cost for Category)/SUM(Amount for Category) × 100)', 'Net Food Cost %(SUM(Billed Cost for Category) / SUM(Net Sales for Category) × 100)',   // A B C
            '',                                              // D gap
            'Dish Type', 'Food Cost %(SUM(Food Cost for dish type)/SUM(Amount for dish type) × 100)', 'Net Food Cost %(SUM(Billed Cost for dish type) / SUM(Net Sales for dish type) × 100)',  // E F G
        ], null, 'A' . $row);
        $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
        $row++;

        // Build category rows
        // food_cost_pct     = (food_cost / total_amt) * 100       → matches main table col X
        // net_food_cost_pct = (billed_cost / billed_amt) * 100    → matches main table col Y
        $catRows = [];
        foreach ($categoryGroup as $catName => $c) {
            $food_cost_pct     = ($c['total_amt'] > 0)
                ? round(($c['food_cost']   / $c['total_amt'])  * 100, 2) : 0;
            $net_food_cost_pct = ($c['billed_amt'] > 0)
                ? round(($c['billed_cost'] / $c['billed_amt']) * 100, 2) : 0;
            $catRows[] = [
                $catName,
                number_format($food_cost_pct,     2) . '%',
                number_format($net_food_cost_pct, 2) . '%',
            ];
        }

        // Build dish type rows
        $dishRows = [];
        foreach ($dishTypeGroup as $dishName => $d) {
            $food_cost_pct     = ($d['total_amt'] > 0)
                ? round(($d['food_cost']   / $d['total_amt'])  * 100, 2) : 0;
            $net_food_cost_pct = ($d['billed_amt'] > 0)
                ? round(($d['billed_cost'] / $d['billed_amt']) * 100, 2) : 0;
            $dishRows[] = [
                $dishName,
                number_format($food_cost_pct,     2) . '%',
                number_format($net_food_cost_pct, 2) . '%',
            ];
        }

        // Write both tables side by side
        $maxRows = max(count($catRows), count($dishRows));
        for ($i = 0; $i < $maxRows; $i++) {
            $catCols  = $catRows[$i]  ?? ['', '', ''];
            $dishCols = $dishRows[$i] ?? ['', '', ''];
            $objPHPExcel->getActiveSheet()->fromArray([
                $catCols[0],  $catCols[1],  $catCols[2],   
                '',                                        
                $dishCols[0], $dishCols[1], $dishCols[2],  
            ], null, 'A' . $row);
            $row++;
        }

        $objPHPExcel->getActiveSheet()->getStyle('A' . $summaryStartRow . ':G' . ($row - 1))->getFont()->setSize(10);

        /* Summary END */

        $objPHPExcel->getActiveSheet()->setTitle('ItemSaleReport');
        $objPHPExcel->setActiveSheetIndex(0);


        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="ITEM_SALE_REPORT.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function item_sale_reportpdf() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "login";
        if($this->request->is('post', 'put'))
        {
            $date_range=  $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
            
            $cashier_id=  $this->request->data['cashier'];
            
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
            $startDatetime = $_shiftRange['startDatetime'];
            $endDatetime   = $_shiftRange['endDatetime'];

            if($cashier_id){
                $orderhistories = $this->Order_orderhistory->query("SELECT product_id,product_name,category_name,SUM(quantity) as totalquantity,price FROM `order_orderhistories` WHERE status=2 and created between '$startDatetime' and '$endDatetime' and store_id=$cashier_id GROUP BY product_id");
            }else{
                $orderhistories = $this->Order_orderhistory->query("SELECT product_id,product_name,category_name,SUM(quantity) as totalquantity,price FROM `order_orderhistories` WHERE status=2 and created between '$startDatetime' and '$endDatetime' GROUP BY product_id");            
            }
            $datas=array();
            foreach($orderhistories as $orderhistory){
                $datas[]=array(
                    'product_id'=>$orderhistory['order_orderhistories']['product_id'],
                    'product_name'=>$orderhistory['order_orderhistories']['product_name'],
                    'category_name'=>$orderhistory['order_orderhistories']['category_name'],
                    'totalquantity'=>$orderhistory[0]['totalquantity'],
                    'price'=>$orderhistory['order_orderhistories']['price']
                );
            }
            
            $this->set(compact('startdate','enddate','datas'));
        }
    }
    
    public function discount_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime('-7 days'));
        $endDate = date('m/d/Y');
        
        if($this->request->is('post', 'put'))
        {
            $daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }
        
        $cashier_id = $this->request->data['Search']['cashier'] ?? null;
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cashierCondition = $cashier_id ? "AND t.store_id = $cashier_id" : "";
        
        $transactions = $this->Order_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashier,
                (
                    SELECT SUM(quantity) 
                    FROM order_orderhistories 
                    WHERE reference_id = t.reference_id AND status = 2
                ) AS quantity,
                SUM(t.discount) AS tdiscount,
                t.orderno,
                t.discount,
                t.price,
                t.discount_type,
                t.discount_type_value,
                t.name,
                t.phone,
                t.address,
                (
                    SELECT remarks 
                    FROM user_tracks 
                    WHERE reference_id = t.reference_id and status!=0 and type='discount_apply'
                    LIMIT 1
                ) AS remarks,
                (
                 SELECT u.username 
                 FROM user_tracks ut
                 LEFT JOIN users u ON u.id = ut.user_id
                 WHERE ut.reference_id = t.reference_id  
                 AND ut.status != 0 LIMIT 1
                ) AS managername,
                t.reference_id,
                t.amount,
                (
                    SELECT GROUP_CONCAT(type_value) 
                    FROM order_payments 
                    WHERE reference_id = t.reference_id and status!=0
                ) AS type,
                p.created
            FROM 
                order_transactions t
            INNER JOIN 
                order_payments p ON p.reference_id = t.reference_id
            WHERE 
                p.status = 2 
                AND t.discount != 0 
                AND t.status = 2 
                $cashierCondition
                AND p.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
            GROUP BY 
                t.reference_id
        ");
            
        
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        // Normalize location IDs from both fields
        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        // Build conditions
        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];

        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $conditions['id'] = -1; // No locations assigned — return nothing
            } else {
                $conditions['id'] = $userLocationIds; // CakePHP handles IN() automatically
            }
        }

        $users = $this->Location->find('all', [
            'conditions' => $conditions
        ]);
        
        
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users', 'transactions'));
    }

    public function discount_reportexport() {

        $this->autoRender = false;
    
        $date_range = $_GET['sdate'] ?? '';
        $cashier_id = $_GET['cashier'] ?? null;
    
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date   = trim($dates[1]);
    
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate   = date('Y-m-d', strtotime($end_date));
    
        $cashierCondition = $cashier_id ? "AND t.store_id = $cashier_id" : "";
    
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $transactions = $this->Order_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashiername,
                (SELECT SUM(quantity) FROM order_orderhistories WHERE reference_id = t.reference_id AND status = 2) AS quantity,
                t.discount,
                t.price,
                t.discount_type,
                t.discount_type_value,
                t.orderno,
                t.name,
                t.phone,
                t.address,
                (SELECT remarks FROM user_tracks WHERE reference_id = t.reference_id AND status!=0 AND type='discount_apply' LIMIT 1) AS remarks,
                t.reference_id,
                t.amount,
                (SELECT GROUP_CONCAT(type_value) FROM order_payments WHERE reference_id = t.reference_id AND status!=0) AS type,
                p.created,
                (
                    SELECT u.username 
                    FROM user_tracks ut
                    LEFT JOIN users u ON u.id = ut.user_id
                    WHERE ut.reference_id = t.reference_id 
                    AND ut.status != 0
                    LIMIT 1
                ) AS managername
            FROM 
                order_transactions t
            INNER JOIN 
                order_payments p ON p.reference_id = t.reference_id
            WHERE 
                p.status = 2
                AND t.discount != 0
                AND t.status = 2
                $cashierCondition
                AND p.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
            GROUP BY 
                t.reference_id
        ");
    
        App::import('Vendor', 'PHPExcel/PHPExcel');
    
        $objPHPExcel = new PHPExcel();
    
        $objPHPExcel->getProperties()
            ->setCreator("POS Export")
            ->setTitle("Discount Report");
    
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'DISCOUNT REPORT FROM ' . $startDatetime . ' TO ' . $endDatetime);
    
        $objPHPExcel->getActiveSheet()->mergeCells('A1:N1');
        $objPHPExcel->getActiveSheet()->getStyle("A1:N1")->getFont()->setBold(true);
    
        $headers = [
            'SR. NO.',
            'Bill No',
            'Payment Type',
            'Date',
            'Time',
            'Total Discount',
            'Discount Type',
            'Coupon',
            'Amount',
            'Remarks',
            'Customer Name',
            'Manager Name',
            'Phone',
            'Address',
            'Store Name'
        ];
    
        $col = 'A';
        foreach ($headers as $header) {
            $objPHPExcel->getActiveSheet()->setCellValue($col . '3', $header);
            $col++;
        }
    
        $objPHPExcel->getActiveSheet()->getStyle("A3:O3")->getFont()->setBold(true);
    
        $row = 4;
        $sr = 1;
    
        foreach ($transactions as $tr) {
    
            $t = $tr['t'];
    
            $dtype    = $t['discount_type'] ?? '';
            $price    = $t['price'] ?? 0;
            $discount = $t['discount'] ?? 0;
    
            if ($dtype == '0') {
    
                $discountTypes = 'Coupon';
    
            } elseif ($dtype == '1') {
    
                $rate = $price > 0 ? round(($discount / $price) * 100, 2) : 0;
                $discountTypes = 'Percentage (' . $rate . '%)';
    
            } elseif ($dtype == '2') {
    
                $discountTypes = 'Flat (₹' . number_format($discount, 2) . ')';
    
            } else {
    
                $discountTypes = '-';
            }
    
            $created = $tr['p']['created'] ?? '';
    
            $objPHPExcel->getActiveSheet()->fromArray([
                $sr++,
                $t['orderno'] ?? '',
                $tr[0]['type'] ?? '',
                $created ? date('d-m-Y', strtotime($created)) : '',
                $created ? date('h:i A', strtotime($created)) : '',
                number_format((float)$discount, 2),
                $discountTypes,
                '-',
                number_format((float)($t['amount'] ?? 0), 2),
                $tr[0]['remarks'] ?? '',
                $t['name'] ?? '',
                $tr[0]['managername'] ?? '',
                $t['phone'] ?? '',
                $t['address'] ?? '',
                $tr[0]['cashiername'] ?? ''
            ], null, 'A' . $row);
    
            $row++;
        }
    
        $objPHPExcel->getActiveSheet()->setTitle('DiscountReport');
        $objPHPExcel->setActiveSheetIndex(0);
    
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="DISCOUNT_REPORT.xls"');
        header('Cache-Control: max-age=0');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    
        exit;
    }
    
    public function bill_wise_summary() {

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-7 days'));
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {

            $daterange  = $this->request->data['daterange'];
            $dates      = explode(" - ", $daterange);

            $start_date = trim($dates[0]);
            $end_date   = trim($dates[1]);

            $startDate  = date('m/d/Y', strtotime($start_date));
            $endDate    = date('m/d/Y', strtotime($end_date));

            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));

        } else {

            $startdate  = date('Y-m-d', strtotime($startDate));
            $enddate    = date('Y-m-d', strtotime($endDate));
        }

        $cashier_id        = $this->request->data['Search']['cashier'] ?? null;
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cashierCondition  = $cashier_id ? " AND t.store_id = $cashier_id" : "";

        $splitTransactions = $this->Order_split_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashier,

                (
                    SELECT SUM(quantity) 
                    FROM order_split_orderhistories 
                    WHERE reference_id = t.reference_id
                    AND split_reference_id = t.split_reference_id
                    AND store_id = t.store_id
                    AND status != 0
                ) AS quantity,

                t.orderno,
                t.reference_id,
                t.split_reference_id,
                t.discount,
                t.total_tax,
                t.price,
                t.amount,
                t.status,  

                (
                    SELECT GROUP_CONCAT(type_value)
                    FROM order_split_payments
                    WHERE reference_id = t.reference_id
                    AND split_reference_id = t.split_reference_id
                    AND store_id = t.store_id
                    AND status = 2
                ) AS type,

                t.created

            FROM order_split_transactions t

            WHERE t.split_reference_id NOT IN (
                SELECT split_reference_id
                FROM order_split_payments
                WHERE type = 7
            )
            $cashierCondition
            AND t.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'

            ORDER BY t.id DESC
        ");

        $normalTransactions = $this->Order_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashier,

                (
                    SELECT SUM(quantity) 
                    FROM order_orderhistories 
                    WHERE reference_id = t.reference_id
                    AND store_id = t.store_id
                    AND status != 0
                ) AS quantity,

                t.orderno,
                t.reference_id,
                NULL AS split_reference_id,
                t.discount,
                t.total_tax,
                t.price,
                t.amount,
                t.status,  

                (
                    SELECT GROUP_CONCAT(type_value)
                    FROM order_payments
                    WHERE reference_id = t.reference_id
                    AND store_id = t.store_id
                    AND status = 2
                ) AS type,

                t.created

            FROM order_transactions t

            WHERE t.is_split = 0
            $cashierCondition

            AND t.reference_id NOT IN (
                SELECT reference_id
                FROM order_payments
                WHERE type = 7
            )

            AND t.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'

            ORDER BY t.id DESC
        ");


        $transactions = array_merge($splitTransactions, $normalTransactions);


        usort($transactions, function ($a, $b) {
            return strtotime($b[0]['created']) - strtotime($a[0]['created']);
        });


        $authUser        = $this->Session->read('Auth.User');
        $userRole        = $authUser['role'] ?? '';
        // Normalize location IDs from both fields
        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        // Build conditions
        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];

        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $conditions['id'] = -1;
            } else {
                $conditions['id'] = $userLocationIds;
            }
        }

        $users = $this->Location->find('all', [
            'conditions' => $conditions
        ]);

        $yourStartDateValue = $startDate . ' - ' . $endDate;

        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users', 'transactions'));
    }
    
    public function bill_wise_summary_export() {
        $this->autoRender = false;

        $date_range = $_GET['sdate'] ?? '';
        $cashier_id = $_GET['cashier'] ?? null;

        $dates      = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date   = trim($dates[1]);

        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate   = date('Y-m-d', strtotime($end_date));

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cashierCondition = $cashier_id ? " AND t.store_id = $cashier_id" : "";

        /* ===================== SPLIT TRANSACTIONS ===================== */
        $splitTransactions = $this->Order_split_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashier,
                (
                    SELECT SUM(quantity) 
                    FROM order_split_orderhistories 
                    WHERE reference_id = t.reference_id
                    AND split_reference_id = t.split_reference_id
                    AND store_id = t.store_id
                    AND status != 0
                ) AS quantity,
                t.orderno,
                t.reference_id,
                t.split_reference_id,
                t.discount,
                t.total_tax,
                t.price,
                t.amount,
                t.status,
                (
                    SELECT GROUP_CONCAT(type_value)
                    FROM order_split_payments
                    WHERE reference_id = t.reference_id
                    AND split_reference_id = t.split_reference_id
                    AND store_id = t.store_id
                    AND status = 2
                ) AS type,
                t.created
            FROM order_split_transactions t
            WHERE t.split_reference_id NOT IN (
                SELECT split_reference_id FROM order_split_payments WHERE type = 7
            )
            $cashierCondition
            AND t.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
            ORDER BY t.id DESC
        ");

        /* ===================== NORMAL TRANSACTIONS ===================== */
        $normalTransactions = $this->Order_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashier,
                (
                    SELECT SUM(quantity) 
                    FROM order_orderhistories 
                    WHERE reference_id = t.reference_id
                    AND store_id = t.store_id
                    AND status != 0
                ) AS quantity,
                t.orderno,
                t.reference_id,
                NULL AS split_reference_id,
                t.discount,
                t.total_tax,
                t.price,
                t.amount,
                t.status,
                (
                    SELECT GROUP_CONCAT(type_value)
                    FROM order_payments
                    WHERE reference_id = t.reference_id
                    AND store_id = t.store_id
                    AND status = 2
                ) AS type,
                t.created
            FROM order_transactions t
            WHERE t.is_split = 0
            $cashierCondition
            AND t.reference_id NOT IN (
                SELECT reference_id FROM order_payments WHERE type = 7
            )
            AND t.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
            ORDER BY t.id DESC
        ");

        /* ===================== MERGE & SORT ===================== */
        $transactions = array_merge($splitTransactions, $normalTransactions);

        // ✅ Match view sort exactly — always $tr['t']['created']
        usort($transactions, function ($a, $b) {
            $createdA = $a['t']['created'] ?? '1970-01-01';
            $createdB = $b['t']['created'] ?? '1970-01-01';
            return strtotime($createdB) - strtotime($createdA);
        });

        /* ===================== EXCEL EXPORT ===================== */
        App::import('Vendor', 'PHPExcel/PHPExcel');

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("POS Export")->setTitle("Bill Wise Report");

        // Title row
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'BILL WISE REPORT FROM ' . $startDatetime . ' TO ' . $endDatetime);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setBold(true);

        // Headers
        $headers = [
            'SR. NO.', 'Bill No', 'Store', 'Payment Type',
            'Date', 'Time', 'Item Total', 'Bill Price',
            'Bill Discount', 'Bill Tax', 'Bill Amount', 'Status'
        ];
        $col = 'A';
        foreach ($headers as $header) {
            $objPHPExcel->getActiveSheet()->setCellValue($col . '3', $header);
            $col++;
        }
        $objPHPExcel->getActiveSheet()->getStyle('A3:L3')->getFont()->setBold(true);

        // ✅ Status maps
        $statusMap = [
            0 => 'Cancelled',
            1 => 'Pending',
            2 => 'Completed',
        ];
        $statusColorMap = [
            0 => 'FF0000',  // Red
            1 => 'FF8C00',  // Orange
            2 => '008000',  // Green
        ];

        $row = 4;
        $sr  = 1;

        foreach ($transactions as $tr) {

            // ✅ Exactly matching view — always use $tr['t'] and $tr[0]
            $created     = $tr['t']['created'] ?? '';
            $status      = (int)($tr['t']['status'] ?? 2);
            $statusLabel = $statusMap[$status]      ?? 'Completed';
            $statusColor = $statusColorMap[$status] ?? '008000';

            $objPHPExcel->getActiveSheet()->fromArray([
                $sr++,
                $tr['t']['orderno']   ?? '',
                $tr[0]['cashier']     ?? '',
                $tr[0]['type']        ?? '',
                !empty($created) ? date('d-m-Y', strtotime($created)) : '',
                !empty($created) ? date('h:i A', strtotime($created)) : '',
                number_format((float)($tr[0]['quantity']    ?? 0), 2),
                number_format((float)($tr['t']['price']     ?? 0), 2),
                number_format((float)($tr['t']['discount']  ?? 0), 2),
                number_format((float)($tr['t']['total_tax'] ?? 0), 2),
                number_format((float)($tr['t']['amount']    ?? 0), 2),
                $statusLabel,
            ], null, 'A' . $row);

            // ✅ Color status cell (column L)
            $objPHPExcel->getActiveSheet()
                ->getStyle('L' . $row)
                ->getFont()
                ->setBold(true)
                ->getColor()
                ->setRGB($statusColor);

            $row++;
        }

        $objPHPExcel->getActiveSheet()->setTitle('Billwise');
        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Billwise_REPORT.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function hourly_sales() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $startDate = date('m/d/Y');
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {
            $daterange  = $this->request->data['daterange'] ?? '';
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0] ?? $startDate);
            $end_date   = trim($dates[1] ?? $endDate);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $endDate    = date('m/d/Y', strtotime($end_date));
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }

        $cashier_id = $this->request->data['Search']['cashier'] ?? null;

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        
        // Normalize location IDs from both fields
        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        // Build raw SQL location condition (for raw queries)
        if ($cashier_id) {
            $where_location = " AND store_id = " . (int)$cashier_id;
        } else {
            if ($userRole !== 'admin') {
                if (empty($userLocationIds)) {
                    $where_location = " AND store_id IN (-1)"; // No locations — return nothing
                } else {
                    $where_location = " AND store_id IN (" . implode(',', $userLocationIds) . ")";
                }
            } else {
                $where_location = '';
            }
        }

        $noChargeSubquery = "SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $where_location";

        $datearray = [];
        $loopdate  = $startdate;
        while (strtotime($loopdate) <= strtotime($enddate)) {
            $datearray[] = $loopdate;
            $loopdate    = date('Y-m-d', strtotime('+1 day', strtotime($loopdate)));
        }

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : null));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        $startHour = (int)date('G', strtotime($_shiftRange['shift_start_time']));

        $shiftHours = [];
        for ($i = 0; $i < 24; $i++) {
            $shiftHours[] = ($startHour + $i) % 24;
        }

        $itemArray = [];
        // Initialize itemArray in the desired shift order dynamically
        foreach ($shiftHours as $h) {
            $dummyFrom     = date('Y-m-d') . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
            $label         = date('ga', strtotime($dummyFrom)) . '-' . date('ga', strtotime($dummyFrom . ' +1 hour'));
            $itemArray[$h] = [
                'label'          => $label,
                'orders'         => 0,
                'pax'            => 0,
                'basic_sale'     => 0.0,
                'discount'       => 0.0,
                'net_sale'       => 0.0,
                'tax'            => 0.0,
                'service_charge' => 0.0,
            ];
        }

        foreach ($datearray as $date) {
            $_dayRange = $this->getShiftDateRange($date, $date, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : null));
            $dayStart  = $_dayRange['startDatetime'];
            $dayEnd    = $_dayRange['endDatetime'];


            foreach ($shiftHours as $h) {
                // Hours less than shift start hour belong to the next calendar day in the shift
                $targetDate = ($h < $startHour) ? date('Y-m-d', strtotime($date . ' +1 day')) : $date;
                $hourStr   = str_pad($h, 2, '0', STR_PAD_LEFT);
                $from      = $targetDate . ' ' . $hourStr . ':00:00';
                $to        = $targetDate . ' ' . $hourStr . ':59:59';
                $queryFrom = ($from < $dayStart) ? $dayStart : $from;
                $queryTo   = ($to   > $dayEnd)   ? $dayEnd   : $to;

                if (strtotime($queryFrom) > strtotime($queryTo)) {
                    continue;
                }

                $sql = "
                    SELECT
                        COUNT(DISTINCT CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN reference_id END)                                             AS orders,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN pax END), 0)                                                  AS pax,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN price END), 0)                                                AS basic_sale,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN IFNULL(discount, 0) END), 0)                                  AS discount,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN price - IFNULL(discount, 0) END), 0)                         AS net_sale,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN IFNULL(total_tax, 0) END), 0)                                AS tax,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN IFNULL(servicecharge, 0) END), 0)                            AS service_charge
                    FROM order_transactions
                    WHERE status = 2
                      AND created BETWEEN '$queryFrom' AND '$queryTo'
                      $where_location
                ";

                $result = $this->Order_transaction->query($sql);
                $row    = $result[0][0] ?? [];

                $itemArray[$h]['orders']         += (int)($row['orders']         ?? 0);
                $itemArray[$h]['pax']            += (int)($row['pax']            ?? 0);
                $itemArray[$h]['basic_sale']     += (float)($row['basic_sale']   ?? 0);
                $itemArray[$h]['discount']       += (float)($row['discount']     ?? 0);
                $itemArray[$h]['net_sale']       += (float)($row['net_sale']     ?? 0);
                $itemArray[$h]['tax']            += (float)($row['tax']          ?? 0);
                $itemArray[$h]['service_charge'] += (float)($row['service_charge'] ?? 0);
            }
        }

        ksort($itemArray);

        $shiftSql = "
            SELECT
                COUNT(DISTINCT CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN reference_id END)                                             AS totalinvoice,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN pax END), 0)                                                  AS pax,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN price END), 0)                                                AS basic_sale,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN IFNULL(discount, 0) END), 0)                                  AS discount,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN price - IFNULL(discount, 0) END), 0)                         AS netsale,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN IFNULL(total_tax, 0) END), 0)                                AS shifttax,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN IFNULL(servicecharge, 0) END), 0)                            AS servicecharge
            FROM order_transactions
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $where_location
        ";

        $shiftResult              = $this->Order_transaction->query($shiftSql);
        $shiftTotals              = $shiftResult[0][0] ?? [];
        // ✅ totalsale now includes service charge
        $shiftTotals['totalsale'] = round(
            (float)($shiftTotals['netsale']      ?? 0) +
            (float)($shiftTotals['shifttax']     ?? 0) +
            (float)($shiftTotals['servicecharge'] ?? 0), 2
        );

        // $conditions = ['status' => 1, 'type' => 'Outlet'];
        // if ($userRole !== 'admin' && !empty($userLocationId)) {
        //     $ids = is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId);
        //     $conditions['id IN'] = array_map('trim', $ids);
        // }
        // $users = $this->Location->find('all', ['conditions' => $conditions]);

        // Build ORM conditions (for CakePHP find)
        $conditions = ['status' => 1, 'type' => 'Outlet'];

        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $conditions['id'] = -1; // No locations — return nothing
            } else {
                $conditions['id'] = $userLocationIds; // CakePHP handles IN() automatically
            }
        }

        $users = $this->Location->find('all', ['conditions' => $conditions]);

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users', 'datearray', 'itemArray', 'shiftTotals'));
    }

    public function hourly_sales_export() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $date_range = $_GET['sdate'] ?? '';
        $dates      = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date   = trim($dates[1] ?? $dates[0]);
        $startdate  = date('Y-m-d', strtotime($start_date));
        $enddate    = date('Y-m-d', strtotime($end_date));
        $cashier_id = $_GET['cashier'] ?? null;

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        
        // Normalize location IDs from both fields
        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        // Build raw SQL location condition
        if ($cashier_id) {
            $where_location = " AND store_id = " . $cashier_id; // already (int) cast above
        } else {
            if ($userRole !== 'admin') {
                if (empty($userLocationIds)) {
                    $where_location = " AND store_id IN (-1)"; // No locations — return nothing
                } else {
                    $where_location = " AND store_id IN (" . implode(',', $userLocationIds) . ")";
                }
            } else {
                $where_location = '';
            }
        }

        $noChargeSubquery = "SELECT reference_id FROM order_payments WHERE type = 7 AND status = 2 $where_location";

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : null));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $datearray = [];
        $loopdate  = $startdate;
        while (strtotime($loopdate) <= strtotime($enddate)) {
            $datearray[] = $loopdate;
            $loopdate    = date('Y-m-d', strtotime('+1 day', strtotime($loopdate)));
        }

        $startHour = (int)date('G', strtotime($_shiftRange['shift_start_time']));
        $shiftHours = [];
        for ($i = 0; $i < 24; $i++) {
            $shiftHours[] = ($startHour + $i) % 24;
        }

        $itemArray = [];
        // Initialize itemArray in the desired shift order dynamically
        foreach ($shiftHours as $h) {
            $dummyFrom     = date('Y-m-d') . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
            $label         = date('ga', strtotime($dummyFrom)) . '-' . date('ga', strtotime($dummyFrom . ' +1 hour'));
            $itemArray[$h] = [
                'label'          => $label,
                'orders'         => 0,
                'pax'            => 0,
                'basic_sale'     => 0.0,
                'discount'       => 0.0,
                'net_sale'       => 0.0,
                'tax'            => 0.0,
                'service_charge' => 0.0,
            ];
        }

        foreach ($datearray as $date) {
            $_dayRange = $this->getShiftDateRange($date, $date, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : null));
            $dayStart  = $_dayRange['startDatetime'];
            $dayEnd    = $_dayRange['endDatetime'];

            foreach ($shiftHours as $h) {
                // Hours less than shift start hour belong to the next calendar day in the shift
                $targetDate = ($h < $startHour) ? date('Y-m-d', strtotime($date . ' +1 day')) : $date;
                $hourStr   = str_pad($h, 2, '0', STR_PAD_LEFT);
                $from      = $targetDate . ' ' . $hourStr . ':00:00';
                $to        = $targetDate . ' ' . $hourStr . ':59:59';
                $queryFrom = ($from < $dayStart) ? $dayStart : $from;
                $queryTo   = ($to   > $dayEnd)   ? $dayEnd   : $to;

                if (strtotime($queryFrom) > strtotime($queryTo)) {
                    continue;
                }

                $sql = "
                    SELECT
                        COUNT(DISTINCT CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN reference_id END)                                             AS orders,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN pax END), 0)                                                  AS pax,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN price END), 0)                                                AS basic_sale,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN IFNULL(discount, 0) END), 0)                                  AS discount,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN price - IFNULL(discount, 0) END), 0)                         AS net_sale,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN IFNULL(total_tax, 0) END), 0)                                AS tax,
                        IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                            THEN IFNULL(servicecharge, 0) END), 0)                            AS service_charge
                    FROM order_transactions
                    WHERE status = 2
                      AND created BETWEEN '$queryFrom' AND '$queryTo'
                      $where_location
                ";

                $result = $this->Order_transaction->query($sql);
                $row    = $result[0][0] ?? [];

                $itemArray[$h]['orders']         += (int)($row['orders']           ?? 0);
                $itemArray[$h]['pax']            += (int)($row['pax']              ?? 0);
                $itemArray[$h]['basic_sale']     += (float)($row['basic_sale']     ?? 0);
                $itemArray[$h]['discount']       += (float)($row['discount']       ?? 0);
                $itemArray[$h]['net_sale']       += (float)($row['net_sale']       ?? 0);
                $itemArray[$h]['tax']            += (float)($row['tax']            ?? 0);
                $itemArray[$h]['service_charge'] += (float)($row['service_charge'] ?? 0);
            }
        }

        ksort($itemArray);

        $shiftSql = "
            SELECT
                COUNT(DISTINCT CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN reference_id END)                                             AS totalinvoice,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN pax END), 0)                                                  AS pax,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN price END), 0)                                                AS basic_sale,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN IFNULL(discount, 0) END), 0)                                  AS discount,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN price - IFNULL(discount, 0) END), 0)                         AS netsale,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN IFNULL(total_tax, 0) END), 0)                                AS shifttax,
                IFNULL(SUM(CASE WHEN reference_id NOT IN ($noChargeSubquery)
                    THEN IFNULL(servicecharge, 0) END), 0)                            AS servicecharge
            FROM order_transactions
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $where_location
        ";

        $shiftResult              = $this->Order_transaction->query($shiftSql);
        $shiftTotals              = $shiftResult[0][0] ?? [];
        $shiftTotals['totalsale'] = round(
            (float)($shiftTotals['netsale']       ?? 0) +
            (float)($shiftTotals['shifttax']      ?? 0) +
            (float)($shiftTotals['servicecharge'] ?? 0), 2
        );

        // ✅ Build Excel
        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Hourly Sales Export");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Hourly Sales');

        // ✅ 10 columns now — Service Charge added before Total Sale
        $headers = ['#', 'Time Slot', 'Orders', 'PAX', 'Basic Sale', 'Discount', 'Net Sale', 'Tax', 'Service Charge', 'Total Sale'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
        ]);

        // Data rows
        $row = 2;
        $idx = 1;
        foreach ($itemArray as $h => $data) {
            $totalSale = round(
                (float)($data['net_sale']       ?? 0) +
                (float)($data['tax']            ?? 0) +
                (float)($data['service_charge'] ?? 0), 2
            );

            $sheet->setCellValueByColumnAndRow(0, $row, $idx++);
            $sheet->setCellValueByColumnAndRow(1, $row, $data['label']);
            $sheet->setCellValueByColumnAndRow(2, $row, (int)$data['orders']);
            $sheet->setCellValueByColumnAndRow(3, $row, (int)$data['pax']);
            $sheet->setCellValueByColumnAndRow(4, $row, round((float)$data['basic_sale'],     2));
            $sheet->setCellValueByColumnAndRow(5, $row, round((float)$data['discount'],       2));
            $sheet->setCellValueByColumnAndRow(6, $row, round((float)$data['net_sale'],       2));
            $sheet->setCellValueByColumnAndRow(7, $row, round((float)$data['tax'],            2));
            $sheet->setCellValueByColumnAndRow(8, $row, round((float)$data['service_charge'], 2));  // ✅
            $sheet->setCellValueByColumnAndRow(9, $row, $totalSale);
            $row++;
        }

        // ✅ Only ONE shift totals row (green) — blue row removed matching view
        $sheet->setCellValueByColumnAndRow(0, $row, 'Shift Totals (Total Sale)');
        $sheet->mergeCellsByColumnAndRow(0, $row, 1, $row);
        $sheet->setCellValueByColumnAndRow(2, $row, (int)($shiftTotals['totalinvoice']  ?? 0));
        $sheet->setCellValueByColumnAndRow(3, $row, (int)($shiftTotals['pax']           ?? 0));
        $sheet->setCellValueByColumnAndRow(4, $row, round((float)($shiftTotals['basic_sale']    ?? 0), 2));
        $sheet->setCellValueByColumnAndRow(5, $row, round((float)($shiftTotals['discount']      ?? 0), 2));
        $sheet->setCellValueByColumnAndRow(6, $row, round((float)($shiftTotals['netsale']       ?? 0), 2));
        $sheet->setCellValueByColumnAndRow(7, $row, round((float)($shiftTotals['shifttax']      ?? 0), 2));
        $sheet->setCellValueByColumnAndRow(8, $row, round((float)($shiftTotals['servicecharge'] ?? 0), 2)); // ✅
        $sheet->setCellValueByColumnAndRow(9, $row, round((float)($shiftTotals['totalsale']     ?? 0), 2));
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => '538135']],
        ]);

        // ✅ Auto-width — now J
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Hourly_Sales_Report.xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function bill_cancel() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime('0 days'));
        $endDate = date('m/d/Y');
        
        if($this->request->is('post', 'put'))
        {
            $daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }
        
        $cashier_id = $this->request->data['Search']['cashier'] ?? null;
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cashierCondition = $cashier_id ? " and t.store_id = $cashier_id" : "";
    
        $transactions = $this->Order_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashier,
                t.orderno,
                t.reference_id,
                t.created,
                (
                    SELECT remarks 
                    FROM user_tracks 
                    WHERE reference_id = t.reference_id and type='unbill'
                    LIMIT 1
                ) AS remarks,
                MAX(t.price) AS price,
                MAX(t.amount) AS amount,
                (
                    SELECT GROUP_CONCAT(type_value) 
                    FROM order_payments 
                    WHERE reference_id = t.reference_id
                ) AS type
            FROM 
                order_transactions t
            WHERE 
                t.status = 0
                $cashierCondition
                AND t.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
            GROUP BY 
                t.reference_id
        ");
    
        
        
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
    
        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];
        
        // Add location filter for non-admins
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }
        
        $users = $this->Location->find('all', [
            'conditions' => $conditions
        ]);
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users', 'transactions'));
    }

    public function bill_cancel_export() {
        $date_range = $_GET['sdate'] ?? '';
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
    
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        
        $cashier_id = $_GET['cashier'] ?? null;
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
    
        if ($cashier_id) {
            $cashierData = $this->Location->find('first', ['conditions' => ['Location.id' => $cashier_id]]);
            $cashiername = $cashierData['Location']['name'] ?? 'Unknown';
            $cashierCondition = " and t.store_id = " . (int)$cashier_id;
        } else {
            $cashiername = 'All Stores';
            $cashierCondition = '';
        }
    
        $transactions = $this->Order_transaction->query("
            SELECT 
                (SELECT name FROM locations WHERE id = t.store_id) AS cashier,
                t.orderno,
                t.reference_id,
                t.created,
                (
                    SELECT remarks 
                    FROM user_tracks 
                    WHERE reference_id = t.reference_id  and type='unbill'
                    LIMIT 1
                ) AS remarks,
                MAX(t.price) AS price,
                MAX(t.amount) AS amount,
                (
                    SELECT GROUP_CONCAT(type_value) 
                    FROM order_payments 
                    WHERE reference_id = t.reference_id
                ) AS type
            FROM 
                order_transactions t
            WHERE 
                t.status = 0
                $cashierCondition
                AND t.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
            GROUP BY 
                t.reference_id
        ");
        
        $datas = [];
        foreach ($transactions as $transaction) {
            $t = $transaction;
            
            $datas[] = [
                'bill_no' => $t['t']['orderno'],
                'cashierName' => $t[0]['cashier'],
                'type' => $t[0]['type'],
                'amount' => round($t[0]['amount'], 2),
                'remarks' => $t[0]['remarks'] ?? '',
                'created' => date('d-m-Y h:i A', strtotime($t['t']['created'])),
            ];
        }
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")
            ->setTitle("UNBills Report")
            ->setDescription("Auto-generated Un Bill Report");
    
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'UNBILL REPORT FROM ' . $startDatetime . ' TO ' . $endDatetime);
    
        // Column Headers
        $headers = [
            'A3' => 'SR. NO.', 'B3' => 'Bill No', 'C3' => 'Store Name',
            'D3' => 'Payment Type', 'E3' => 'Date', 'F3' => 'Remarks','G3' => 'Amount'
        ];
    
        foreach ($headers as $cell => $value) {
            $objPHPExcel->getActiveSheet()->setCellValue($cell, $value);
        }
    
        $objPHPExcel->getActiveSheet()->mergeCells('A1:G1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:G3')->getFont()->setBold(true);
    
        $baseRow = 3;
        $r = 1;
        foreach ($datas as $dat) {
            $rowNum = $baseRow + $r;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue("A{$rowNum}", $r)
                ->setCellValue("B{$rowNum}", $dat['bill_no'])
                ->setCellValue("C{$rowNum}", $dat['cashierName'])
                ->setCellValue("D{$rowNum}", $dat['type'])
                ->setCellValue("E{$rowNum}", $dat['amount'])
                ->setCellValue("F{$rowNum}", $dat['remarks'])
                ->setCellValue("G{$rowNum}", $dat['created']);
            $r++;
        }
    
        // Finalize export
        $objPHPExcel->getActiveSheet()->setTitle('UNBills');
        $objPHPExcel->setActiveSheetIndex(0);
    
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="UN_BILL_REPORT_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function void_order() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $startDate      = date('m/d/Y', strtotime('0 days'));
        $endDate        = date('m/d/Y');
        $orderhistories = [];

        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));

        if ($this->request->is('post', 'put')) {
            $daterange  = $this->request->data['daterange'];
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $end_date   = trim($dates[1]);
            $endDate    = date('m/d/Y', strtotime($end_date));
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));

            $cashier_id       = $this->request->data['Search']['cashier'] ?? null;
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
            $startDatetime = $_shiftRange['startDatetime'];
            $endDatetime   = $_shiftRange['endDatetime'];

            $cashierCondition = $cashier_id ? "AND o.store_id = " . (int)$cashier_id : "";

            $orderhistories = $this->Order_orderhistory->query("
                SELECT
                    o.store_id,
                    o.reference_id,
                    o.product_id,
                    o.product_name,
                    o.quantity,
                    o.price,
                    o.remarks,
                    o.kot_number,
                    o.created,
                    l.name AS location_name,
                    (
                        SELECT u.name
                        FROM user_tracks ut
                        JOIN users u ON u.id = ut.user_id
                        WHERE (
                                    ut.reference_id = o.reference_id
                                 OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id)  
                            )
                          AND ut.type IN ('deleteitem','deletemultipe')
                          AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                            AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
                        ORDER BY ut.created DESC              -- 🔥 ADDED (latest manager)
                        LIMIT 1
                    ) AS manager_name,
                    (
                        SELECT ut.remarks
                        FROM user_tracks ut
                        WHERE (
                                    ut.reference_id = o.reference_id
                                 OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id)   -- 🔥 ADDED
                            )
                          AND ut.type IN ('deleteitem','deletemultipe')
                          AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                            AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
                        ORDER BY ut.created DESC              -- 🔥 ADDED
                        LIMIT 1
                    ) AS manager_remarks,
                    (
                        SELECT ut.modified
                        FROM user_tracks ut
                        WHERE (
                                    ut.reference_id = o.reference_id
                                 OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id)
                            )
                          AND ut.type IN ('deleteitem','deletemultipe')
                          AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                            AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
                        ORDER BY ut.created DESC
                        LIMIT 1
                    ) AS void_datetime
                FROM order_orderhistories o
                LEFT JOIN locations l ON l.id = o.store_id
                WHERE o.created >= '$startDatetime'
                  AND o.created <  '$endDatetime'
                  AND o.status  =  0
                  AND EXISTS (
                      SELECT 1 FROM user_tracks ut
                      WHERE (
                                  ut.reference_id = o.reference_id
                               OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id)   -- 🔥 ADDED
                          )
                        AND ut.type IN ('deleteitem','deletemultipe')
                        AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                          AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
                  )
                  $cashierCondition
                ORDER BY o.id DESC
            ");
        }

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = ['status' => 1, 'type' => 'Outlet'];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId)
                    ? $userLocationId
                    : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }

        $users = $this->Location->find('all', ['conditions' => $conditions]);

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users', 'orderhistories'));
    }

    public function void_order_export() {
        $date_range = $_GET['sdate'] ?? '';
        $cashier_id = $_GET['cashier'] ?? null;

        $dates      = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date   = trim($dates[1]);

        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate   = date('Y-m-d', strtotime($end_date));

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cashierCondition = $cashier_id ? "AND o.store_id = " . (int)$cashier_id : "";

        // ── Identical query to void_order() ───────────────────────────────────────
        $transactions = $this->Order_orderhistory->query("
            SELECT
                o.store_id,
                o.reference_id,
                o.product_id,
                o.product_name,
                o.quantity,
                o.price,
                o.remarks,
                o.kot_number,
                o.created,
                l.name AS location_name,
                (
                    SELECT u.name
                    FROM user_tracks ut
                    JOIN users u ON u.id = ut.user_id
                    WHERE (
                                ut.reference_id = o.reference_id
                             OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id) 
                        )
                      AND ut.type IN ('deleteitem', 'deletemultipe')
                      AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                        AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
                        ORDER BY ut.created DESC              -- 🔥 ADDED (latest manager)       
                    LIMIT 1
                ) AS manager_name,
                (
                    SELECT ut.remarks
                    FROM user_tracks ut
                    WHERE (
                            ut.reference_id = o.reference_id
                         OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id)   -- 🔥 ADDED
                    )
                      AND ut.type IN ('deleteitem', 'deletemultipe')
                      AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                        AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
                        ORDER BY ut.created DESC              -- 🔥 ADDED
                    LIMIT 1
                ) AS manager_remarks,
                (
                    SELECT ut.modified
                    FROM user_tracks ut
                    WHERE (
                                ut.reference_id = o.reference_id
                             OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id)
                        )
                      AND ut.type IN ('deleteitem','deletemultipe')
                      AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                        AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
                    ORDER BY ut.created DESC
                    LIMIT 1
                ) AS void_datetime
            FROM order_orderhistories o
            LEFT JOIN locations l ON l.id = o.store_id
            WHERE o.created >= '$startDatetime'
              AND o.created <  '$endDatetime'
              AND o.status  =  0
              AND EXISTS (
                  SELECT 1 FROM user_tracks ut
                  WHERE (
                          ut.reference_id = o.reference_id
                       OR ut.reference_id = COALESCE(NULLIF(o.local_id, ''), o.id)   -- 🔥 ADDED
                  )
                    AND ut.type IN ('deleteitem','deletemultipe')
                    AND ut.created BETWEEN DATE_SUB(o.created, INTERVAL 1 MINUTE)
                                      AND DATE_ADD(o.created, INTERVAL 60 MINUTE)
              )
              $cashierCondition
            ORDER BY o.id DESC
        ");

        $datas = [];
        foreach ($transactions as $row) {
            $o = $row['o'];
            $l = $row['l'];
            $c = $row[0];

            $managerRemarks = $c['manager_remarks'] ?? '';
            json_decode($managerRemarks, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $managerRemarks = '';
            }

            $datas[] = [
                'store'           => $l['location_name'],
                'product'         => $o['product_name'],
                'quantity'        => $o['quantity'],
                'price'           => $o['price'],
                'remarks'         => $o['remarks'],
                'kot_number'      => $o['kot_number'] ?? '',
                'manager_name'    => $c['manager_name'] ?? '',
                'manager_remarks' => $managerRemarks,
                'created'         => date('d-m-Y h:i A', strtotime($o['created'])),
                'void_datetime' => !empty($c['void_datetime']) ? date('d-m-Y h:i A', strtotime($c['void_datetime'])) : ''
            ];
        }

        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")
            ->setTitle("Void Orders Report")
            ->setDescription("Auto-generated Void Orders Report");

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'VOID ORDER REPORT FROM ' . $startDatetime . ' TO ' . $endDatetime);

        $headers = [
            'A3' => 'SR. NO.',       'B3' => 'Store',           'C3' => 'Product',
            'D3' => 'Quantity',      'E3' => 'Price',            'F3' => 'Remarks',
            'G3' => 'Manager Name',  'H3' => 'Manager Remarks',  'I3' => 'KOT Number',
            'J3' => 'Punch DateTime','K3' => 'Void DateTime'
        ];

        foreach ($headers as $cell => $value) {
            $objPHPExcel->getActiveSheet()->setCellValue($cell, $value);
        }

        $objPHPExcel->getActiveSheet()->mergeCells('A1:K1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:K3')->getFont()->setBold(true);

        $baseRow = 3;
        $sr      = 1;
        foreach ($datas as $dat) {
            $rowNum = $baseRow + $sr;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue("A{$rowNum}", $sr)
                ->setCellValue("B{$rowNum}", $dat['store'])
                ->setCellValue("C{$rowNum}", $dat['product'])
                ->setCellValue("D{$rowNum}", $dat['quantity'])
                ->setCellValue("E{$rowNum}", $dat['price'])
                ->setCellValue("F{$rowNum}", $dat['remarks'])
                ->setCellValue("G{$rowNum}", $dat['manager_name'])
                ->setCellValue("H{$rowNum}", $dat['manager_remarks'])
                ->setCellValue("I{$rowNum}", $dat['kot_number'])
                ->setCellValue("J{$rowNum}", $dat['created'])
                ->setCellValue("K{$rowNum}", $dat['void_datetime']);
            $sr++;
        }

        $objPHPExcel->getActiveSheet()->setTitle('Void Order');
        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="VOID_ORDER_REPORT_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function transaction_report() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        $this->layout = "users";
        
        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        
        if($this->request->is('post', 'put'))
        {
            $daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }   
        
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        
        if (!empty($this->request->data['Search']['cashier'])) {
            $cashier_id = $this->request->data['Search']['cashier'];
        } else {
            $cashier_id = null; 
        }

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        if($cashier_id){
            $cashiernames=$this->Location->find('first',array('conditions'=>array('id'=>$cashier_id)));

            $cashiername=$cashiernames['Location']['name'];

            $where_location = " and store_id = " . (int)$cashier_id;
            
            $transactions = $this->Order_transaction->query("SELECT order_transactions.*,(select name from locations where id=order_transactions.store_id) as store_name,(SELECT GROUP_CONCAT(type_value) FROM `order_payments` WHERE reference_id=order_transactions.reference_id and store_id=order_transactions.store_id) as mode  FROM `order_transactions`  WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime' $where_location /* T12-fix: show ALL transactions (no dedup) to match bill-wise summary */ ORDER BY order_transactions.id DESC");
        }else{
            if($userRole!='admin'){
                $where_location = "AND store_id IN (" . $userLocationId . ")";
            
                $transactions = $this->Order_transaction->query("SELECT order_transactions.*,(select name from locations where id=order_transactions.store_id) as store_name,(SELECT GROUP_CONCAT(type_value) FROM `order_payments` WHERE reference_id=order_transactions.reference_id and store_id=order_transactions.store_id) as mode  FROM `order_transactions`  WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime' $where_location /* T12-fix: show ALL transactions (no dedup) to match bill-wise summary */ ORDER BY order_transactions.id DESC"); 
            }else{
                $cashiername='All Cashiers';
            
                $transactions = $this->Order_transaction->query("SELECT order_transactions.*,(select name from locations where id=order_transactions.store_id) as store_name,(SELECT GROUP_CONCAT(type_value) FROM `order_payments` WHERE reference_id=order_transactions.reference_id and store_id=order_transactions.store_id) as mode  FROM `order_transactions`  WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime' /* T12-fix: show ALL transactions (no dedup) to match bill-wise summary */ ORDER BY order_transactions.id DESC");
            }
        }
    
        
        $this->set(compact('cashiername'));
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        
        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];
        
        if ($userRole !== 'admin' && !empty($userLocationId)) {
        
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }
        
        $users = $this->Location->find('all', [
            'conditions' => $conditions
        ]);

        $this->set(compact('users'));
        
        $this->set(compact('transactions'));
    }

    public function transaction_reportexport() {
        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
    
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
    
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate   = date('Y-m-d', strtotime($end_date));
    
        $cashier_id = $_GET['cashier'] ?? null;
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
    
        if($cashier_id){
    
            $transactions = $this->Order_transaction->query("
                SELECT order_transactions.*,
                (SELECT name FROM locations WHERE id=order_transactions.store_id) AS store_name,
                (SELECT GROUP_CONCAT(type_value)
                    FROM order_payments
                    WHERE reference_id=order_transactions.reference_id
                    AND store_id=order_transactions.store_id) AS mode
                FROM order_transactions
                WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
                AND order_transactions.store_id=$cashier_id
                ORDER BY order_transactions.id DESC
            ");
    
        }else{
    
            $transactions = $this->Order_transaction->query("
                SELECT order_transactions.*,
                (SELECT name FROM locations WHERE id=order_transactions.store_id) AS store_name,
                (SELECT GROUP_CONCAT(type_value)
                    FROM order_payments
                    WHERE reference_id=order_transactions.reference_id
                    AND store_id=order_transactions.store_id) AS mode
                FROM order_transactions
                WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
                ORDER BY order_transactions.id DESC
            ");
        }
    
        $datas=array();
        $i=1;
    
        foreach($transactions as $transaction){
    
            if($transaction['order_transactions']['status']==0){
                $status='Cancelled';
            }elseif($transaction['order_transactions']['status']==1){
                $status='Pending / Unsettled';
            }elseif($transaction['order_transactions']['status']==2){
                $status='Settled';
            }else{
                $status='Unknown';
            }
    
            $datas[]=array(
                'sr'=>$i,
                'txnid'=>$transaction['order_transactions']['reference_id'],
                'bill_no'=>$transaction['order_transactions']['orderno'],
                'store'=>$transaction[0]['store_name'],
                'table_number'=>$transaction['order_transactions']['table_number'],
                'pax'=>$transaction['order_transactions']['pax'],
                'phone'=>$transaction['order_transactions']['phone'],
                'name'=>$transaction['order_transactions']['name'],
                'mode'=>$transaction[0]['mode'],
                'amount'=>round($transaction['order_transactions']['amount'],2),
                'status'=>$status,
                'created'=>$transaction['order_transactions']['created']
            );
    
            $i++;
        }
    
        App::import('Vendor','PHPExcel/PHPExcel');
    
        $objPHPExcel = new PHPExcel();
    
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1','TRANSACTION REPORT FROM '.$startDatetime.' TO '.$endDatetime);
    
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A3','SR NO')
            ->setCellValue('B3','Transaction ID')
            ->setCellValue('C3','Bill No')
            ->setCellValue('D3','Store')
            ->setCellValue('E3','Table Number')
            ->setCellValue('F3','PAX')
            ->setCellValue('G3','Phone')
            ->setCellValue('H3','Name')
            ->setCellValue('I3','Payment Mode')
            ->setCellValue('J3','Amount')
            ->setCellValue('K3','Status')
            ->setCellValue('L3','Created');
    
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');
        $objPHPExcel->getActiveSheet()->getStyle("A1:L3")->getFont()->setBold(true);
    
        $row = 4;
    
        foreach($datas as $data){
    
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$row,$data['sr'])
                ->setCellValue('B'.$row,$data['txnid'])
                ->setCellValue('C'.$row,$data['bill_no'])
                ->setCellValue('D'.$row,$data['store'])
                ->setCellValue('E'.$row,$data['table_number'])
                ->setCellValue('F'.$row,$data['pax'])
                ->setCellValue('G'.$row,$data['phone'])
                ->setCellValue('H'.$row,$data['name'])
                ->setCellValue('I'.$row,$data['mode'])
                ->setCellValue('J'.$row,$data['amount'])
                ->setCellValue('K'.$row,$data['status'])
                ->setCellValue('L'.$row,$data['created']);
    
            $row++;
        }
    
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="TRANSACTION_REPORT.xls"');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function transaction_reportpdf() {
    
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action'=>'login'));
        }
    
        $this->layout="login";
    
        if($this->request->is('post')){
    
            $date_range = $this->request->data['sdate'];
            $dates = explode(" - ",$date_range);
    
            $start_date = trim($dates[0]);
            $end_date   = trim($dates[1]);
    
            $startdate = date('Y-m-d',strtotime($start_date));
            $enddate   = date('Y-m-d',strtotime($end_date));
    
            $cashier_id = $this->request->data['cashier'] ?? null;
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
            $startDatetime = $_shiftRange['startDatetime'];
            $endDatetime   = $_shiftRange['endDatetime'];
    
            if($cashier_id){
    
                $transactions = $this->Order_transaction->query("
                    SELECT order_transactions.*,
                    (SELECT name FROM locations WHERE id=order_transactions.store_id) AS store_name,
                    (SELECT GROUP_CONCAT(type_value)
                        FROM order_payments
                        WHERE reference_id=order_transactions.reference_id
                        AND store_id=order_transactions.store_id) AS mode
                    FROM order_transactions
                    WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
                    AND store_id=$cashier_id
                    ORDER BY id DESC
                ");
    
            }else{
    
                $transactions = $this->Order_transaction->query("
                    SELECT order_transactions.*,
                    (SELECT name FROM locations WHERE id=order_transactions.store_id) AS store_name,
                    (SELECT GROUP_CONCAT(type_value)
                        FROM order_payments
                        WHERE reference_id=order_transactions.reference_id
                        AND store_id=order_transactions.store_id) AS mode
                    FROM order_transactions
                    WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
                    ORDER BY id DESC
                ");
            }
    
            $datas=array();
    
            foreach($transactions as $transaction){
    
                if($transaction['order_transactions']['status']==0){
                    $status='Cancelled';
                }elseif($transaction['order_transactions']['status']==1){
                    $status='Pending / Unsettled';
                }elseif($transaction['order_transactions']['status']==2){
                    $status='Settled';
                }else{
                    $status='Unknown';
                }
    
                $datas[]=array(
                    'txnid'=>$transaction['order_transactions']['reference_id'],
                    'bill_no'=>$transaction['order_transactions']['orderno'],
                    'store'=>$transaction[0]['store_name'],
                    'table_number'=>$transaction['order_transactions']['table_number'],
                    'pax'=>$transaction['order_transactions']['pax'],
                    'phone'=>$transaction['order_transactions']['phone'],
                    'name'=>$transaction['order_transactions']['name'],
                    'mode'=>$transaction[0]['mode'],
                    'amount'=>round($transaction['order_transactions']['amount'],2),
                    'status'=>$status,
                    'created'=>$transaction['order_transactions']['created']
                );
            }
    
            $this->set(compact('datas','startDatetime','endDatetime'));
        }
    }

    public function card_report() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "users";
    
        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        
        if($this->request->is('post', 'put'))
        {
            $daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }   
            
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        
        $card_no= $this->request->data['Search']['card_no'];
        
        $cards = $this->Cardtracking->query("SELECT txnid,type,amount,created,purpose FROM `cardtracking` WHERE cardnumber='$card_no' and created between '$startDatetime' and '$endDatetime' order by id desc");
    
        
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
    
        $this->set(compact('cards'));
        
    }

    public function card_reportexport() {
        $date_range= $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate= date('Y-m-d', strtotime($start_date));
        $enddate= date('Y-m-d', strtotime($end_date));

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        
        $card_no= $_GET['id'];
        $cards = $this->Cardtracking->query("SELECT txnid,type,amount,created,purpose FROM `cardtracking` WHERE cardnumber='$card_no' and created between '$startDatetime' and '$endDatetime' order by id desc");
        
        $datas=array();
        foreach($cards as $card){
            $datas[]=array(
                'txnid'=>$card['cardtracking']['txnid'],
                'type'=>$card['cardtracking']['type'],
                'purpose'=>$card['cardtracking']['purpose'],
                'amount'=>$card['cardtracking']['amount'],
                'created'=>$card['cardtracking']['created']
            );
        }
             
        App::import('Vendor', 'PHPExcel/PHPExcel');
           error_reporting(0);
           ini_set('display_errors', TRUE);
           ini_set('display_startup_errors', TRUE);
           if (PHP_SAPI == 'cli')
                die('This example should only be run from a Web Browser');
           $objPHPExcel = new PHPExcel();
           // Set document properties
           $objPHPExcel->getProperties()->setCreator("Aether")
           ->setLastModifiedBy("Aether")
           ->setTitle("Office 2007 XLSX Aether Document")
           ->setSubject("Office 2007 XLSX Aether Document")
           ->setDescription("Aether Export Data File.")
           ->setKeywords("office 2007 openxml php")
           ->setCategory("AetherPos Export Data File");
           // Add some data
           $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '   ' .'CARD REPORT OF '.$card_no);
           
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'SR. NO.');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Txn id');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Type');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Purpose');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'Amount');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'Created');
            $objPHPExcel->getActiveSheet()->getStyle("A1:F3")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
            
            $baseRow = 3;
            $r=1;
            
            foreach($datas as $dat){
                  
                    if($dat['type']==1){
                        $type='credit';
                    }elseif($dat['type']==2){
                        $type='debit';
                    }elseif($dat['type']==3){
                        $type='security';
                    }else{
                        $type='refund';
                    }
                    
                    if($dat['purpose']=='card_charge'){
                        $purpose='card charge';
                    }elseif($dat['purpose']=='transferfrom'){
                        $purpose='transfer from';
                    }elseif($dat['purpose']=='transferto'){
                        $purpose='transfer to';
                    }else{
                        $purpose=$dat['purpose'];
                    }
                    
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($baseRow + $r),$r);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($baseRow + $r),$dat['txnid']);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($baseRow + $r),$type);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.($baseRow + $r),$purpose);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.($baseRow + $r),abs($dat['amount']));
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.($baseRow + $r),$dat['created']);
                                  
                           $r++;
                   
                }
                
                  //$r++;
                 
               
               //$objPHPExcel->getActiveSheet()->removeRow($baseRow-1,1);
               //$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Hello');
               // Miscellaneous glyphs, UTF-8
               // Rename worksheet
               $objPHPExcel->getActiveSheet()->setTitle('Export Data');
               // Set active sheet index to the first sheet, so Excel opens this as the first sheet
               $objPHPExcel->setActiveSheetIndex(0);
               // Redirect output to a client’s web browser (Excel5)
               header('Content-Type: application/vnd.ms-excel');
               header('Content-Disposition: attachment;filename="'.'CARD REPORT.xls"');
               header('Cache-Control: max-age=0');
               // If you're serving to IE 9, then the following may be needed
               header('Cache-Control: max-age=1');
               // If you're serving to IE over SSL, then the following may be needed
               header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
               header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
               header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
               header ('Pragma: public'); // HTTP/1.0
               $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
               $objWriter->save('php://output');
               exit;
               
    }

    public function card_reportpdf()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "login";
        if($this->request->is('post', 'put'))
        {
            $card_no= $this->request->data['cardno'];
            $date_range= $this->request->data['sdate'];
            
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));

            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

            $cards = $this->Cardtracking->query("SELECT cardnumber,txnid,purpose,type,amount,created FROM `cardtracking` WHERE cardnumber='$card_no' and created between '$startDatetime' and '$endDatetime' order by id desc");
            $datas=array();
            foreach($cards as $card){
                $datas[]=array(
                    'card_no'=>$card['cardtracking']['cardnumber'],
                    'txnid'=>$card['cardtracking']['txnid'],
                    'purpose'=>$card['cardtracking']['purpose'],
                    'type'=>$card['cardtracking']['type'],
                    'amount'=>$card['cardtracking']['amount'],
                    'created'=>$card['cardtracking']['created']
                );
            }
              $this->set(compact('card_no','datas'));
        }
    }
    
    public function bill_printpdf() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "login";
        
        $id=  $_GET['id'];
        
        $transactions = $this->Order_transaction->query("
            SELECT 
                order_transactions.*, 
                locations.*,
                users.name as cashiername
            FROM 
                order_transactions
            LEFT JOIN 
                users ON users.id = order_transactions.cashier_id
            LEFT JOIN 
                locations ON locations.id = users.location_id
            WHERE 
                order_transactions.id = ".$id." and order_transactions.status!=0
        ");
        
        $reference_id=$transactions[0]['order_transactions']['reference_id'];
        $storeId=$transactions[0]['order_transactions']['store_id'];
        $cashier_id=$transactions[0]['order_transactions']['cashier_id'];
        $orderno=$transactions[0]['order_transactions']['orderno'];
        $discount=$transactions[0]['order_transactions']['discount'];
        $servicecharge=$transactions[0]['order_transactions']['servicecharge'];
        $delivery_charge=$transactions[0]['order_transactions']['delivery_charge'];
        $packing_charge=$transactions[0]['order_transactions']['packing_charge'];
        $amount=$transactions[0]['order_transactions']['amount'];
        $created=$transactions[0]['order_transactions']['created'];
        $table_number=$transactions[0]['order_transactions']['table_number'];
        $pax=$transactions[0]['order_transactions']['pax'];
        $price=$transactions[0]['order_transactions']['price'];
        $today= date('Y-m-d H:i:s');
        
        $name=$transactions[0]['locations']['name'];
        $print_line1=$transactions[0]['locations']['print_line1'];
        $print_line2=$transactions[0]['locations']['print_line2'];
        $print_line3=$transactions[0]['locations']['print_line3'];
        $print_line4=$transactions[0]['locations']['print_line4'];
        $print_line5=$transactions[0]['locations']['print_line5'];
        $print_line6=$transactions[0]['locations']['print_line6'];
        $print_line7=$transactions[0]['locations']['print_line7'];
        $print_line8=$transactions[0]['locations']['print_line8'];
        $print_line9=$transactions[0]['locations']['print_line9'];
        $print_line10=$transactions[0]['locations']['print_line10'];
        $footer_msg=$transactions[0]['locations']['footer_msg'];
        $currency_symbol=$transactions[0]['locations']['currency_symbol'];
        $cashiername=$transactions[0]['users']['cashiername'];
        
        $orderss = $this->Order_orderhistory->query("SELECT id,product_name,SUM(quantity) as quantity,sum(total_price) as total_price FROM `order_orderhistories` WHERE reference_id='$reference_id' and store_id = '$storeId' and status!=0 group BY product_id");
        $taxes = $this->Order_tax->query("SELECT tax_id,tax_name,tax_percentage,sum(tax_value) as tax_value FROM `order_tax` WHERE reference_id='$reference_id' and status!=0 GROUP by tax_id");
        $discper = $this->Order_tax->query("SELECT discount_percentage FROM `order_tax` WHERE reference_id='$reference_id' and discount_percentage!=0 and status!=0 limit 1");
        if(!empty($discper))
        {
            $discount_percentage=$discper[0]['order_tax']['discount_percentage'];
        }
        if(!isset($discount_percentage)){
            $discount_percentage="0";
        }
        
        $payments = $this->Order_payment->find('all', array('conditions'=>array('reference_id'=>$reference_id,'status !='=>0)));
        $mode=   $payments[0]['Order_payment']['type_value'];
                    
        $this->set(compact('pax','currency_symbol','delivery_charge','packing_charge','mode','price','table_number','cashiername','servicecharge','amount','today','created','name','print_line1','print_line2','print_line3','print_line4','print_line5','print_line6','print_line7','print_line8','print_line9','print_line10','orderno','orderss','taxes','footer_msg','discount_percentage','discount'));
        
    }
    
    public function stock_level() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $today = date("Y-m-d");
        $currentTime = date("H:i", strtotime("+1 minutes"));
 
        $sel_date = $today;      // default
        $sel_time = $currentTime; // default

        $skuledger_startdate = date('Y-m-d', strtotime('-10 days'));
        $stocklevel_enddate   = $skuledger_enddate   = date('Y-m-d');
        $sku_id = null;
        
        $stocklevel_startdate = $sel_date.' '.$sel_time.':00';
        
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->data['submit_stocklevel'])) {
                
                $sel_date=$this->request->data['sel_date'];
                $sel_time=$this->request->data['sel_time'];
            
                $stocklevel_startdate = $sel_date.' '.$sel_time.':00';
            }
    
            if (isset($this->request->data['submit_skutransactions'])) {
                $daterange = $this->request->data['daterange'] ?? '';
                $dates = explode(" - ", $daterange);
                if (count($dates) === 2) {
                    $skuledger_startdate = date('Y-m-d', strtotime(trim($dates[0])));
                    $skuledger_enddate = date('Y-m-d', strtotime(trim($dates[1])));
                }
                $sku_id = $this->request->data['SkuLedger']['sku'] ?? null;
            }
            
        }
        
        $this->set(compact('sel_date', 'sel_time'));

        $id = $this->Session->read('Auth.User.id');
        
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $conditions = [
            'status' => 1,
            'type'   => 'DC'
        ];
        
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
            $store_id = implode(',', $userLocationIds);
        }
        else{
            $username = $this->User->query("SELECT location_id FROM users WHERE id = $id");
            $store_id = $username[0]['users']['location_id'];
        }

        if (!empty($this->request->data['StockLevel']['cashier'])) {
            $store_id = $this->request->data['StockLevel']['cashier'];
            $transactions = $this->Stock_ledger->query("
                SELECT sl1.sku_id,sl1.sku_price,sl1.sku_name,s.stock,sl1.unit_name, sl1.actual_balance
                FROM stock_ledger sl1
                INNER JOIN (
                    SELECT sku_id, MAX(id) AS max_id
                    FROM stock_ledger
                    WHERE type = 'dc'  AND item_type = 'SKU' 
                      AND store_id IN ($store_id)
                      AND created < '$stocklevel_startdate'
                    GROUP BY sku_id
                ) latest ON sl1.id = latest.max_id
                LEFT  JOIN inventory_skus s ON s.id = sl1.sku_id
                WHERE sl1.type = 'DC' AND sl1.item_type = 'SKU' order by s.name asc
            ");
        } 
        // else{
            // $transactions = $this->Stock_ledger->query("
            // SELECT sl1.sku_id,sl1.sku_price,sl1.sku_name,sl1.unit_name, sl1.actual_balance
            // FROM stock_ledger sl1
            // INNER JOIN (
                // SELECT sku_id, MAX(id) AS max_id
                // FROM stock_ledger
                // WHERE type = 'dc'  AND item_type = 'SKU' 
                  // AND created BETWEEN '$stocklevel_startdate 00:00:01' AND '$stocklevel_enddate 23:59:59'
                // GROUP BY sku_id
            // ) latest ON sl1.id = latest.max_id
            // WHERE sl1.type = 'DC' AND sl1.item_type = 'SKU' 
        // ");
        // }
        
        
        $skus = $this->Inventory_sku->query("SELECT * FROM inventory_skus WHERE status=1 order by name asc");
        
        $skutransactions = [];
        if (!empty($sku_id)) {
            if($store_id){
                $_shiftRange   = $this->getShiftDateRange($skuledger_startdate, $skuledger_enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
               
                $skutransactions = $this->Stock_ledger->query("
                    SELECT sl.*, ipo.vendor_name,ii.location_name
                        FROM stock_ledger sl 
                        LEFT JOIN inventory_purchase_order ipo
                            ON ipo.stockin_id = sl.record_id
                        LEFT JOIN inventory_indent ii ON ii.indent_id = sl.record_id
                        WHERE sl.created BETWEEN '$startDatetime' AND '$endDatetime'
                        AND sl.type = 'DC'
                        AND sl.sku_id = $sku_id
                        AND sl.store_id IN ($store_id)
                        AND sl.item_type = 'SKU'
                        ORDER BY sl.id DESC
                ");
            }
            // else{
            //     $skutransactions = $this->Stock_ledger->query("
            //         SELECT * FROM stock_ledger 
            //         WHERE created BETWEEN '$skuledger_startdate 00:00:01' AND '$skuledger_enddate 23:59:59'
            //         AND type='DC' AND sku_id = $sku_id and item_type = 'SKU'
            //         ORDER BY id DESC
            //                         ");
            // }
        }
        
        $this->set('stocklevel_daterange', date('m/d/Y', strtotime($stocklevel_startdate)) . ' - ' . date('m/d/Y', strtotime($stocklevel_enddate)));
        $this->set('skuledger_daterange', date('m/d/Y', strtotime($skuledger_startdate)) . ' - ' . date('m/d/Y', strtotime($skuledger_enddate)));

            
        $users=$this->Location->find('all',array('conditions'=>$conditions));
                
        $this->set(compact('users'));
    
        $this->set(compact('transactions', 'skus', 'skutransactions'));
    }

    public function stock_levelexport()
    {
        $date_range = $_GET['sdate'] ?? '';
    
        if (!$date_range) {
            throw new BadRequestException('Invalid date range.');
        }
    
        $id = $this->Session->read('Auth.User.id');
        $username = $this->User->query("SELECT location_id FROM users WHERE id = $id");
        $store_id = $username[0]['users']['location_id'];
    
        // Fetch store name
        $store_data = $this->Location->find('first', [
            'conditions' => ['Location.id' => $store_id],
            'fields' => ['Location.name']
        ]);
        $store_name = $store_data['Location']['name'] ?? 'Unknown Store';
    
        // ===============================
        // Fetch Stock Ledger Data (Same as View)
        // ===============================
        $transactions = $this->Stock_ledger->query("
            SELECT sl1.sku_id, sl1.sku_price, sl1.sku_name, sl1.unit_name,
                   s.stock, sl1.actual_balance
            FROM stock_ledger sl1
            INNER JOIN (
                SELECT sku_id, MAX(id) AS max_id
                FROM stock_ledger
                WHERE type = 'DC' 
                  AND item_type = 'SKU' 
                  AND store_id = $store_id
                  AND created < '$date_range'
                GROUP BY sku_id
            ) latest ON sl1.id = latest.max_id
            LEFT JOIN inventory_skus s ON s.id = sl1.sku_id
            WHERE sl1.type = 'DC' AND sl1.item_type = 'SKU'
            ORDER BY s.name ASC
        ");
    
        // ===============================
        // Prepare Excel Export
        // ===============================
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
    
        if (PHP_SAPI == 'cli') {
            die('This example should only be run from a Web Browser');
        }
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("DC Stock Level Report")
            ->setSubject("Stock Report")
            ->setDescription("Exported DC stock level data.")
            ->setKeywords("stock excel")
            ->setCategory("Export File");
    
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
    
        // ===============================
        // Header Section
        // ===============================
        $sheet->setCellValue('A1', "DC STOCK LEVEL REPORT");
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(14);
    
        $sheet->setCellValue('A2', "Store: $store_name");
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2:G2')->getFont()->setBold(true)->setSize(12);
    
        $sheet->setCellValue('A3', "Date $date_range");
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true)->setSize(11);
    
        // ===============================
        // Column Headers
        // ===============================
        $headers = ['Sr No.', 'SKU Name', 'Unit Name', 'Price', 'Required Qty', 'Available Qty', 'Value'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
    
        // ===============================
        // Fill Data Rows
        // ===============================
        $row = 6;
        $count = 1;
        foreach ($transactions as $txn) {
            $data  = $txn['sl1'];
            $stock = $txn['s']['stock'] ?? 0;
            $value = round($data['sku_price'] * $data['actual_balance'], 2);
    
            $sheet->setCellValue("A$row", $count);
            $sheet->setCellValue("B$row", $data['sku_name']);
            $sheet->setCellValue("C$row", $data['unit_name']);
            $sheet->setCellValue("D$row", (float)$data['sku_price']);
            $sheet->setCellValue("E$row", (float)$stock);
            $sheet->setCellValue("F$row", (float)$data['actual_balance']);
            $sheet->setCellValue("G$row", $value);
    
            $row++;
            $count++;
        }
    
        // ===============================
        // Style and Output
        // ===============================
        $sheet->getStyle("A5:G5")->getFont()->setBold(true);
        $sheet->setTitle('DC Stock Level');
        $objPHPExcel->setActiveSheetIndex(0);
    
        // Output headers
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="DC_Stock_Level_Report.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function kitchen_stock_level() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $stocklevel_startdate = $stocklevel_enddate = $skuledger_startdate = $skuledger_enddate = date('Y-m-d');
        $sku_id = null;
    
        $id = $this->Session->read('Auth.User.id');
        
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        
        $conditions = [
            'status' => 1,
            'type'   => 'kitchen'
        ];
        
        if ($userRole !== 'admin' && !empty($userLocationId)) {
             $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
            $store_id = implode(',', $userLocationIds);
        }
        else{
            $username = $this->User->query("SELECT location_id FROM users WHERE id = $id");
            $store_id = $username[0]['users']['location_id'];
        }

        if (!empty($this->request->data['StockLevel']['cashier'])) {
            $store_id = $this->request->data['StockLevel']['cashier'];
        } 

        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->data['submit_stocklevel'])) {
                $daterange = $this->request->data['daterange'] ?? '';
                $dates = explode(" - ", $daterange);
                if (count($dates) === 2) {
                    $stocklevel_startdate = date('Y-m-d', strtotime(trim($dates[0])));
                    $stocklevel_enddate = date('Y-m-d', strtotime(trim($dates[1])));
                }
                $_shiftRange = $this->getShiftDateRange($stocklevel_startdate, $stocklevel_enddate, $store_id ?: null);
                $startDatetime = $_shiftRange['startDatetime'];
                $endDatetime   = $_shiftRange['endDatetime'];
            }
    
            if (isset($this->request->data['submit_skutransactions'])) {
                $daterange = $this->request->data['daterange'] ?? '';
                $dates = explode(" - ", $daterange);
                if (count($dates) === 2) {
                    $skuledger_startdate = date('Y-m-d', strtotime(trim($dates[0])));
                    $skuledger_enddate = date('Y-m-d', strtotime(trim($dates[1])));
                }
                $sku_id = $this->request->data['SkuLedger']['sku'] ?? null;
                $_shiftRange = $this->getShiftDateRange($skuledger_startdate, $skuledger_enddate, $store_id ?: null);
                $startDatetime = $_shiftRange['startDatetime'];
                $endDatetime   = $_shiftRange['endDatetime'];
            
            }
            
        }
        
        $transactions = $this->Stock_ledger->query("
            SELECT sl1.sku_id,sl1.sku_price,sl1.sku_name,sl1.unit_name, sl1.actual_balance
            FROM stock_ledger sl1
            INNER JOIN (
                SELECT sku_id, MAX(id) AS max_id
                FROM stock_ledger
                WHERE type = 'KITCHEN' 
                  AND store_id IN($store_id)
                  AND created BETWEEN '$startDatetime' AND '$endDatetime'
                GROUP BY sku_id
            ) latest ON sl1.id = latest.max_id
            WHERE sl1.type = 'KITCHEN' 
              AND sl1.store_id IN($store_id)
        ");
        
        $skus = $this->Inventory_sku->query("SELECT * FROM inventory_skus WHERE status=1");
        
        $skutransactions = [];
        if (!empty($sku_id)) {
            $skutransactions = $this->Stock_ledger->query("
                SELECT * FROM stock_ledger 
                WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
                AND type='KITCHEN' AND sku_id = $sku_id and store_id IN($store_id)
                ORDER BY id DESC
            ");
        }
        
        $this->set('stocklevel_daterange', date('m/d/Y', strtotime($stocklevel_startdate)) . ' - ' . date('m/d/Y', strtotime($stocklevel_enddate)));
        $this->set('skuledger_daterange', date('m/d/Y', strtotime($skuledger_startdate)) . ' - ' . date('m/d/Y', strtotime($skuledger_enddate)));

        $users=$this->Location->find('all',array('conditions'=>$conditions));
                
        $this->set(compact('users'));
    
        $this->set(compact('transactions', 'skus', 'skutransactions'));
    }

    public function kitchen_stock_levelexport() {
        $date_range = $_GET['sdate'] ?? '';
    
        $dates = explode(" - ", $date_range);
        if (count($dates) !== 2) {
            throw new BadRequestException('Invalid date range.');
        }
        $id = $this->Session->read('Auth.User.id');
        $username = $this->User->query("SELECT location_id FROM users WHERE id = $id");
        $store_id = $username[0]['users']['location_id'];
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        
        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // Fetch stock ledger data
        $transactions = $this->Stock_ledger->query("
            SELECT sl1.sku_id,sl1.sku_price,sl1.sku_name,sl1.unit_name, sl1.actual_balance
            FROM stock_ledger sl1
            INNER JOIN (
                SELECT sku_id, MAX(id) AS max_id
                FROM stock_ledger
                WHERE type = 'KITCHEN' 
                  AND store_id = $store_id
                  AND created BETWEEN '$startDatetime' and  '$endDatetime'
                GROUP BY sku_id
            ) latest ON sl1.id = latest.max_id
            WHERE sl1.type = 'KITCHEN' 
              AND sl1.store_id = $store_id
        ");
        
        
        // Prepare for Excel export
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
    
        if (PHP_SAPI == 'cli') {
            die('This example should only be run from a Web Browser');
        }
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("Stock Level Report")
            ->setSubject("Stock Report")
            ->setDescription("Exported stock level data.")
            ->setKeywords("stock excel")
            ->setCategory("Export File");
    
        // Header and formatting
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'STOCK LEVEL REPORT FROM '.$startdate.' TO '.$enddate);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true)->setSize(14);
    
        // Column headers
        $headers = ['Sr No.', 'SKU Name', 'Unit Name', 'Price', 'Quantity', 'Value'];
        $col = 'A';
        foreach ($headers as $header) {
            $objPHPExcel->getActiveSheet()->setCellValue($col . '3', $header);
            $objPHPExcel->getActiveSheet()->getStyle($col . '3')->getFont()->setBold(true);
            $col++;
        }
    
        // Fill data
        $row = 4;
        $count = 1;
        foreach ($transactions as $txn) {
            $data = $txn['sl1'];
            $value = round($data['sku_price'] * $data['actual_balance'], 2);
    
            $objPHPExcel->getActiveSheet()
                ->setCellValue("A$row", $count)
                ->setCellValue("B$row", $data['sku_name'])
                ->setCellValue("C$row", $data['unit_name'])
                ->setCellValue("D$row", (float) $data['sku_price'])
                ->setCellValue("E$row", (float) $data['actual_balance'])
                ->setCellValue("F$row", $value);
    
            $row++;
            $count++;
        }
    
        // Finalize
        $objPHPExcel->getActiveSheet()->setTitle('Stock Level');
        $objPHPExcel->setActiveSheetIndex(0);
    
        // Output headers
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Stock_Level_Report.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function semi_stock_level() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $stocklevel_startdate = $stocklevel_enddate = $skuledger_startdate = $skuledger_enddate = date('Y-m-d');
        $sku_id = null;
    
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->data['submit_stocklevel'])) {
                $daterange = $this->request->data['stocklevel_daterange'] ?? '';
                $dates = explode(" - ", $daterange);
                if (count($dates) === 2) {
                    $stocklevel_startdate = date('Y-m-d', strtotime(trim($dates[0])));
                    $stocklevel_enddate = date('Y-m-d', strtotime(trim($dates[1])));
                }
                $_shiftRange = $this->getShiftDateRange($stocklevel_startdate, $stocklevel_enddate);
                $startDatetime = $_shiftRange['startDatetime'];
                $endDatetime   = $_shiftRange['endDatetime'];
            }
    
            if (isset($this->request->data['submit_skutransactions'])) {
                $daterange = $this->request->data['daterange'] ?? '';
                $dates = explode(" - ", $daterange);
                if (count($dates) === 2) {
                    $skuledger_startdate = date('Y-m-d', strtotime(trim($dates[0])));
                    $skuledger_enddate = date('Y-m-d', strtotime(trim($dates[1])));
                }
                $sku_id = $this->request->data['SkuLedger']['sku'] ?? null;
            }
            $_shiftRange = $this->getShiftDateRange($skuledger_startdate, $skuledger_enddate);
            $startDatetime = $_shiftRange['startDatetime'];
            $endDatetime   = $_shiftRange['endDatetime'];
            
        }
    
        $transactions = $this->Stock_ledger->query("
            SELECT * FROM stock_ledger 
            WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
            AND type='OUTLET' and item_type='SKU'
            ORDER BY id DESC
        ");
    
        $skus = $this->Inventory_semifinished->query("SELECT * FROM inventory_semifinished WHERE status=1");
        
        $skutransactions = [];
        if (!empty($sku_id)) {
            $skutransactions = $this->Stock_ledger->query("
                SELECT * FROM stock_ledger 
                WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
                AND type='OUTLET' AND sku_id = $sku_id
                ORDER BY id DESC
            ");
        }
        
        $this->set('stocklevel_daterange', date('m/d/Y', strtotime($stocklevel_startdate)) . ' - ' . date('m/d/Y', strtotime($stocklevel_enddate)));
        $this->set('skuledger_daterange', date('m/d/Y', strtotime($skuledger_startdate)) . ' - ' . date('m/d/Y', strtotime($skuledger_enddate)));
    
        $this->set(compact('transactions', 'skus', 'skutransactions'));
    }

    public function semi_stock_levelexport() {
        $date_range = $_GET['sdate'] ?? '';
    
        $dates = explode(" - ", $date_range);
        if (count($dates) !== 2) {
            throw new BadRequestException('Invalid date range.');
        }
    
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
    
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        
        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        
        // Fetch stock ledger data
        $transactions = $this->Stock_ledger->query("
            SELECT * FROM stock_ledger 
            WHERE created BETWEEN '$startDatetime' AND '$endDatetime' and type='OUTLET' and item_type='SKU'
            ORDER BY id DESC
        ");
    
        // Prepare for Excel export
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
    
        if (PHP_SAPI == 'cli') {
            die('This example should only be run from a Web Browser');
        }
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("Stock Level Report")
            ->setSubject("Stock Report")
            ->setDescription("Exported stock level data.")
            ->setKeywords("stock excel")
            ->setCategory("Export File");
    
        // Header and formatting
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'STOCK LEVEL REPORT FROM '.$startdate.' TO '.$enddate);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true)->setSize(14);
    
        // Column headers
        $headers = ['Sr No.', 'SKU Name', 'Unit Name', 'Price', 'Quantity', 'Value'];
        $col = 'A';
        foreach ($headers as $header) {
            $objPHPExcel->getActiveSheet()->setCellValue($col . '3', $header);
            $objPHPExcel->getActiveSheet()->getStyle($col . '3')->getFont()->setBold(true);
            $col++;
        }
    
        // Fill data
        $row = 4;
        $count = 1;
        foreach ($transactions as $txn) {
            $data = $txn['stock_ledger'];
            $value = round($data['sku_price'] * $data['quantity'], 2);
    
            $objPHPExcel->getActiveSheet()
                ->setCellValue("A$row", $count)
                ->setCellValue("B$row", $data['sku_name'])
                ->setCellValue("C$row", $data['unit_name'])
                ->setCellValue("D$row", (float) $data['sku_price'])
                ->setCellValue("E$row", (float) $data['quantity'])
                ->setCellValue("F$row", $value);
    
            $row++;
            $count++;
        }
    
        // Finalize
        $objPHPExcel->getActiveSheet()->setTitle('Stock Level');
        $objPHPExcel->setActiveSheetIndex(0);
    
        // Output headers
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Stock_Level_Report.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function purchaseorder_report()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $store_id  = !empty($this->request->data['Search']['store']) ? $this->request->data['Search']['store'] : null;
        $vendor_id = !empty($this->request->data['Search']['vendor']) ? $this->request->data['Search']['vendor'] : null;
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $where = " WHERE ipoi.status!=0
                AND ipo.po_date BETWEEN '$startdate' and  '$enddate'";

        if ($store_id) {
            $where .= " AND ipo.location_id = " . (int)$store_id;
        } elseif ($userRole != 'admin') {
            $where .= " AND ipo.location_id IN (" . $userLocationId . ")";
        }

        if ($vendor_id) {
            $where .= " AND ipo.vendor_id = " . (int)$vendor_id;
        }

        // $purchaseorders = $this->Inventory_purchase_order->query("
        //     SELECT 
        //         ipo.id,
        //         ipo.p_o_id,
        //         ipo.vendor_name AS vendor,
        //         ipo.location_name AS store_name,
        //         ipoi.sku_name AS name,
        //         ipoi.sku_price AS price,
        //         ipoi.sku_quantity AS quantity,
        //         ipoi.sku_unit_name AS unit,
        //         ipoi.sku_amount AS total_amount,
        //         ipo.created
        //     FROM inventory_purchase_order ipo
        //     LEFT JOIN inventory_purchase_order_items ipoi 
        //         ON ipo.p_o_id = ipoi.p_o_id
        //     $where
        //     ORDER BY ipo.id DESC
        // ");
        $purchaseorders = $this->Inventory_purchase_order->query("
            SELECT 
                    ipo.location_name AS store_name,
                    ipo.vendor_name AS vendor,
                    ipoi.sku_name AS name,
                    ipoi.sku_unit_name AS unit,
                    SUM(ipoi.sku_quantity) AS sku_total_qty,
                    SUM(ipoi.sku_amount) AS sku_total_amnt,
                    SUM(ipoi.approved_quantity) AS approved_total_qty,
                    SUM(ipoi.approved_amount) AS approved_total_amnt,
                    SUM(ipoi.processed_quantity) AS processed_total_qty,
                    SUM(ipoi.processed_amount) AS processed_total_amnt,
                    SUM(ipoi.stockin_quantity) AS stockin_total_qty,
                    SUM(ipoi.stockin_amount) AS stockin_total_amnt,
                    SUM(ipoi.excise_duty_amt) AS excise_total,
                    SUM(ipoi.addl_excise_duty_amt) AS addl_excise_total,
                    SUM(ipoi.vat_amt) AS vat_total,
                    SUM(ipoi.tcs_amt) AS tcs_total
                FROM inventory_purchase_order ipo
                LEFT JOIN inventory_purchase_order_items ipoi 
                    ON ipo.p_o_id = ipoi.p_o_id
                $where
                GROUP BY ipo.location_name, ipo.vendor_name, ipoi.sku_name, ipoi.sku_unit_name
                ORDER BY ipo.location_name ASC, ipo.vendor_name ASC, ipoi.sku_name ASC
        ");

        $conditions = [
            'status' => 1
        ];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }

        $stores = $this->Location->find('all', ['conditions' => $conditions]);
        $vendors = $this->Inventory_vendor->find('list', [
            'conditions' => [
                'Inventory_vendor.status' => 1
            ],
            'fields' => ['Inventory_vendor.id', 'Inventory_vendor.company'],
            'order' => ['Inventory_vendor.company ASC']
        ]);

        $yourStartDateValue = $startDate . ' - ' . $endDate;

        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('stores', 'vendors', 'purchaseorders'));
    }

    public function purchaseorder_reportexport() {
        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        $store_id = $_GET['store'];
        $vendor_id = $_GET['vendor'];
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        
        $where = " WHERE ipo.po_date BETWEEN '$startdate' and  '$enddate' and ipoi.status!=0";

        if ($store_id) {
            $storeData = $this->Location->find('first', ['conditions' => ['id' => $store_id]]);
            $store_name = $storeData['Location']['name'];
            $where .= " AND ipo.location_id = " . $store_id;
        } else {
            $store_name = 'All Stores';
        }
        

        if ($vendor_id) {
            $vendorData = $this->Inventory_vendor->find('first', ['conditions' => ['id' => $vendor_id]]);
            $vendor_name = $vendorData['Inventory_vendor']['company'];
            $where .= " AND ipo.vendor_id = " . (int)$vendor_id;
        } else {
            $vendor_name = 'All Vendors';
        }
        
        $purchaseorders = $this->Inventory_purchase_order->query("
            SELECT 
                    ipo.location_name AS store_name,
                    ipo.vendor_name AS vendor,
                    ipoi.sku_name AS name,
                    ipoi.sku_unit_name AS unit,
                    SUM(ipoi.sku_quantity) AS sku_total_qty,
                    SUM(ipoi.sku_amount) AS sku_total_amnt,
                    SUM(ipoi.approved_quantity) AS approved_total_qty,
                    SUM(ipoi.approved_amount) AS approved_total_amnt,
                    SUM(ipoi.processed_quantity) AS processed_total_qty,
                    SUM(ipoi.processed_amount) AS processed_total_amnt,
                    SUM(ipoi.stockin_quantity) AS stockin_total_qty,
                    SUM(ipoi.stockin_amount) AS stockin_total_amnt,
                    SUM(ipoi.excise_duty_amt) AS excise_total,
                    SUM(ipoi.addl_excise_duty_amt) AS addl_excise_total,
                    SUM(ipoi.vat_amt) AS vat_total,
                    SUM(ipoi.tcs_amt) AS tcs_total
                FROM inventory_purchase_order ipo
                LEFT JOIN inventory_purchase_order_items ipoi 
                    ON ipo.p_o_id = ipoi.p_o_id
                $where
                GROUP BY ipo.location_name, ipo.vendor_name, ipoi.sku_name, ipoi.sku_unit_name
                ORDER BY ipo.location_name ASC, ipo.vendor_name ASC, ipoi.sku_name ASC
        ");


        $datas = [];
        foreach ($purchaseorders as $po) {
            $datas[] = [
                'name' => $po['ipoi']['name'],
                'store_name' => $po['ipo']['store_name'],
                'vendor' => $po['ipo']['vendor'],
                'unit' => $po['ipoi']['unit'],
                'sku_total_qty' => $po[0]['sku_total_qty'],
                'sku_total_amnt' => $po[0]['sku_total_amnt'],
                'approved_total_qty' => $po[0]['approved_total_qty'],
                'approved_total_amnt' => $po[0]['approved_total_amnt'],
                'processed_total_qty' => $po[0]['processed_total_qty'],
                'processed_total_amnt' => $po[0]['processed_total_amnt'],
                'stockin_total_qty' => $po[0]['stockin_total_qty'],
                'stockin_total_amnt' => $po[0]['stockin_total_amnt'],
                'excise_total' => $po[0]['excise_total'],
                'addl_excise_total' => $po[0]['addl_excise_total'],
                'vat_total' => $po[0]['vat_total'],
                'tcs_total' => $po[0]['tcs_total']
            ];
        }

        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);

        if (PHP_SAPI == 'cli')
            die('This example should only be run from a Web Browser');

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("Office 2007 XLSX Aether Document")
            ->setSubject("Office 2007 XLSX Aether Document")
            ->setDescription("Aether Export Data File.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("AetherPos Export Data File");

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'PURCHASE ORDER REPORT FROM ' . $startdate . ' TO ' . $enddate);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'SR. NO.');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Store');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Name');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Vendor');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'Unit');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'Initial Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G3', 'Initial Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H3', 'Approved Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I3', 'Approved Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J3', 'Processed Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K3', 'Processed Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L3', 'Stockin Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M3', 'Stockin Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N3', 'Excise');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O3', 'Addl. Excise');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P3', 'VAT');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('Q3', 'TCS');

        $objPHPExcel->getActiveSheet()->getStyle("A1:Q3")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:Q1');

        $baseRow = 3;
        $r = 1;
        foreach ($datas as $dat) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . ($baseRow + $r), $r);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . ($baseRow + $r), $dat['store_name']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . ($baseRow + $r), $dat['name']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . ($baseRow + $r), $dat['vendor']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . ($baseRow + $r), $dat['unit']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . ($baseRow + $r), $dat['sku_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . ($baseRow + $r), $dat['sku_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . ($baseRow + $r), $dat['approved_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . ($baseRow + $r), $dat['approved_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J' . ($baseRow + $r), $dat['processed_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K' . ($baseRow + $r), $dat['processed_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L' . ($baseRow + $r), $dat['stockin_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M' . ($baseRow + $r), $dat['stockin_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N' . ($baseRow + $r), $dat['excise_total']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O' . ($baseRow + $r), $dat['addl_excise_total']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P' . ($baseRow + $r), $dat['vat_total']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('Q' . ($baseRow + $r), $dat['tcs_total']);
            $r++;
        }

        $objPHPExcel->getActiveSheet()->setTitle('Purchase Orders');
        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="PURCHASE ORDER REPORT.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function purchaseorder_reportpdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            $date_range = $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $store_id = $this->request->data['store'];
            $vendor_id = $this->request->data['vendor'];
            
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
            
            $where = " WHERE ipo.po_date BETWEEN '$startdate' and  '$enddate' and ipoi.status!=0";

            if ($store_id) {
                $storeData = $this->Location->find('first', ['conditions' => ['id' => $store_id]]);
                $store_name = $storeData['Location']['name'];
                $where .= " AND ipo.location_id = " . (int)$store_id;
            } else {
                $store_name = 'All Stores';
            }

            if ($vendor_id) {
                $vendorData = $this->Inventory_vendor->find('first', ['conditions' => ['id' => $vendor_id]]);
                $vendor_name = $vendorData['Inventory_vendor']['company'];
                $where .= " AND ipo.vendor_id = " . (int)$vendor_id;
            } else {
                $vendor_name = 'All Vendors';
            }

            
            $purchaseorders = $this->Inventory_purchase_order->query("
                SELECT 
                        ipo.location_name AS store_name,
                        ipo.vendor_name AS company,
                        ipoi.sku_name AS name,
                        ipoi.sku_unit_name AS unit,
                        SUM(ipoi.sku_quantity) AS sku_total_qty,
                        SUM(ipoi.sku_amount) AS sku_total_amnt,
                        SUM(ipoi.approved_quantity) AS approved_total_qty,
                        SUM(ipoi.approved_amount) AS approved_total_amnt,
                        SUM(ipoi.processed_quantity) AS processed_total_qty,
                        SUM(ipoi.processed_amount) AS processed_total_amnt,
                        SUM(ipoi.stockin_quantity) AS stockin_total_qty,
                        SUM(ipoi.stockin_amount) AS stockin_total_amnt,
                        SUM(ipoi.excise_duty_amt) AS excise_total,
                        SUM(ipoi.addl_excise_duty_amt) AS addl_excise_total,
                        SUM(ipoi.vat_amt) AS vat_total,
                        SUM(ipoi.tcs_amt) AS tcs_total
                    FROM inventory_purchase_order ipo
                    LEFT JOIN inventory_purchase_order_items ipoi 
                        ON ipo.p_o_id = ipoi.p_o_id
                    $where
                    GROUP BY ipo.location_name, ipo.vendor_name, ipoi.sku_name, ipoi.sku_unit_name
                    ORDER BY ipo.location_name ASC, ipo.vendor_name ASC, ipoi.sku_name ASC
            ");


            $datas = [];
            foreach ($purchaseorders as $po) {
                $datas[] = [
                    'name' => $po['ipoi']['name'],
                    'store_name' => $po['ipo']['store_name'],
                    'vendor' => $po['ipo']['company'],
                    'unit' => $po['ipoi']['unit'],
                    'sku_total_qty' => $po[0]['sku_total_qty'],
                    'sku_total_amnt' => $po[0]['sku_total_amnt'],
                    'approved_total_qty' => $po[0]['approved_total_qty'],
                    'approved_total_amnt' => $po[0]['approved_total_amnt'],
                    'processed_total_qty' => $po[0]['processed_total_qty'],
                    'processed_total_amnt' => $po[0]['processed_total_amnt'],
                    'stockin_total_qty' => $po[0]['stockin_total_qty'],
                    'stockin_total_amnt' => $po[0]['stockin_total_amnt'],
                    'excise_total' => $po[0]['excise_total'],
                    'addl_excise_total' => $po[0]['addl_excise_total'],
                    'vat_total' => $po[0]['vat_total'],
                    'tcs_total' => $po[0]['tcs_total']
                ];
            }

            $this->set(compact('startdate', 'enddate', 'datas', 'store_name', 'vendor_name'));
        }
    }

    public function purchase_item_ledger(){
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $item_id  = !empty($this->request->data['Search']['item']) ? $this->request->data['Search']['item'] : null;
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $where = " WHERE ipoi.status!=0
                AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";

        if ($item_id) {
            $where .= " AND ipoi.sku_id = " . $item_id;
        }


        
        $items = $this->Inventory_sku->query("SELECT id,name FROM inventory_skus WHERE status = 1 order by name ASC");

        
        $purchaseorders = $this->Inventory_purchase_order->query("
            SELECT 
                ipoi.sku_name AS name,
                ipoi.sku_unit_name AS unit,
                SUM(ipoi.sku_quantity) AS sku_total_qty,
                SUM(ipoi.sku_amount) AS sku_total_amnt,
                SUM(ipoi.approved_quantity) AS approved_total_qty,
                SUM(ipoi.approved_amount) AS approved_total_amnt,
                SUM(ipoi.processed_quantity) AS processed_total_qty,
                SUM(ipoi.processed_amount) AS processed_total_amnt,
                SUM(ipoi.stockin_quantity) AS stockin_total_qty,
                SUM(ipoi.stockin_amount) AS stockin_total_amnt,
                SUM(ipoi.excise_duty_amt) AS excise_total,
                SUM(ipoi.addl_excise_duty_amt) AS addl_excise_total,
                SUM(ipoi.vat_amt) AS vat_total,
                SUM(ipoi.tcs_amt) AS tcs_total
                FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi 
                ON ipo.p_o_id = ipoi.p_o_id
            $where
            GROUP BY ipoi.sku_name, ipoi.sku_unit_name
            ORDER BY ipoi.sku_name ASC");


        $conditions = [
            'status' => 1
        ];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }

        $stores = $this->Location->find('all', ['conditions' => $conditions]);

        $yourStartDateValue = $startDate . ' - ' . $endDate;

        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('items', 'purchaseorders'));
    }

    public function purchaseitemledger_export() {
        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        $item_id = $_GET['item'];
        $itemname = $_GET['itemname'];
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

         $where = " WHERE ipoi.status!=0
                AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";

        if ($item_id) {
            $item_name = $itemname;
            $where .= " AND ipoi.sku_id = " . $item_id;
        } else {
            $item_name = 'All Items';
        }
        
        $purchaseorders = $this->Inventory_purchase_order->query("
            SELECT 
                ipoi.sku_name AS name,
                ipoi.sku_unit_name AS unit,
                SUM(ipoi.sku_quantity) AS sku_total_qty,
                SUM(ipoi.sku_amount) AS sku_total_amnt,
                SUM(ipoi.approved_quantity) AS approved_total_qty,
                SUM(ipoi.approved_amount) AS approved_total_amnt,
                SUM(ipoi.processed_quantity) AS processed_total_qty,
                SUM(ipoi.processed_amount) AS processed_total_amnt,
                SUM(ipoi.stockin_quantity) AS stockin_total_qty,
                SUM(ipoi.stockin_amount) AS stockin_total_amnt,
                SUM(ipoi.excise_duty_amt) AS excise_total,
                SUM(ipoi.addl_excise_duty_amt) AS addl_excise_total,
                SUM(ipoi.vat_amt) AS vat_total,
                SUM(ipoi.tcs_amt) AS tcs_total
                FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi 
                ON ipo.p_o_id = ipoi.p_o_id
            $where
            GROUP BY ipoi.sku_name, ipoi.sku_unit_name
            ORDER BY ipoi.sku_name ASC");

        $datas = [];
        foreach ($purchaseorders as $po) {
            $datas[] = [
                'name' => $po['ipoi']['name'],
                'unit' => $po['ipoi']['unit'],
                'sku_total_qty' => $po[0]['sku_total_qty'],
                'sku_total_amnt' => $po[0]['sku_total_amnt'],
                'approved_total_qty' => $po[0]['approved_total_qty'],
                'approved_total_amnt' => $po[0]['approved_total_amnt'],
                'processed_total_qty' => $po[0]['processed_total_qty'],
                'processed_total_amnt' => $po[0]['processed_total_amnt'],
                'stockin_total_qty' => $po[0]['stockin_total_qty'],
                'stockin_total_amnt' => $po[0]['stockin_total_amnt'],
                'excise_total' => $po[0]['excise_total'],
                'addl_excise_total' => $po[0]['addl_excise_total'],
                'vat_total' => $po[0]['vat_total'],
                'tcs_total' => $po[0]['tcs_total']
            ];
        }
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);

        if (PHP_SAPI == 'cli')
            die('This example should only be run from a Web Browser');

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("Office 2007 XLSX Aether Document")
            ->setSubject("Office 2007 XLSX Aether Document")
            ->setDescription("Aether Export Data File.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("AetherPos Export Data File");

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'PURCHASE ORDER REPORT FROM ' . $startDatetime . ' TO ' . $endDatetime);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'SR. NO.');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Name');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Unit');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Initial Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'Initial Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'Approved Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G3', 'Approved Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H3', 'Processed Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I3', 'Processed Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J3', 'Stockin Quantity');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K3', 'Stockin Total Amount');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L3', 'Excise');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M3', 'Addl. Excise');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N3', 'VAT');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O3', 'TCS');
        

        $objPHPExcel->getActiveSheet()->getStyle("A1:O3")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:O1');

        $baseRow = 3;
        $r = 1;
        foreach ($datas as $dat) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . ($baseRow + $r), $r);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . ($baseRow + $r), $dat['name']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . ($baseRow + $r), $dat['unit']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . ($baseRow + $r), $dat['sku_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . ($baseRow + $r), $dat['sku_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . ($baseRow + $r), $dat['approved_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . ($baseRow + $r), $dat['approved_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . ($baseRow + $r), $dat['processed_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . ($baseRow + $r), $dat['processed_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J' . ($baseRow + $r), $dat['stockin_total_qty']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K' . ($baseRow + $r), $dat['stockin_total_amnt']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L' . ($baseRow + $r), $dat['excise_total']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M' . ($baseRow + $r), $dat['addl_excise_total']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N' . ($baseRow + $r), $dat['vat_total']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O' . ($baseRow + $r), $dat['tcs_total']);
            $r++;
        }

        $objPHPExcel->getActiveSheet()->setTitle('Purchase Item Ledger');
        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="PURCHASE ITEM LEDGER REPORT.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function purchaseitemledger_reportpdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            $date_range = $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $item_id = $this->request->data['item'];
            $itemname = $this->request->data['itemname'];
            
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

             $where = " WHERE ipoi.status!=0
                AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";

            if ($item_id) {
                $item_name = $itemname;
                $where .= " AND ipoi.sku_id = " . $item_id;
            } else {
                $item_name = 'All Items';
            }

            $purchaseorders = $this->Inventory_purchase_order->query("
            SELECT 
                ipoi.sku_name AS name,
                ipoi.sku_unit_name AS unit,
                SUM(ipoi.sku_quantity) AS sku_total_qty,
                SUM(ipoi.sku_amount) AS sku_total_amnt,
                SUM(ipoi.approved_quantity) AS approved_total_qty,
                SUM(ipoi.approved_amount) AS approved_total_amnt,
                SUM(ipoi.processed_quantity) AS processed_total_qty,
                SUM(ipoi.processed_amount) AS processed_total_amnt,
                SUM(ipoi.stockin_quantity) AS stockin_total_qty,
                SUM(ipoi.stockin_amount) AS stockin_total_amnt,
                SUM(ipoi.excise_duty_amt) AS excise_total,
                SUM(ipoi.addl_excise_duty_amt) AS addl_excise_total,
                SUM(ipoi.vat_amt) AS vat_total,
                SUM(ipoi.tcs_amt) AS tcs_total
                FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi 
                ON ipo.p_o_id = ipoi.p_o_id
            $where
            GROUP BY ipoi.sku_name, ipoi.sku_unit_name
            ORDER BY ipoi.sku_name ASC");


            $datas = [];
            foreach ($purchaseorders as $po) {
                $datas[] = [
                    'name' => $po['ipoi']['name'],
                    'unit' => $po['ipoi']['unit'],
                    'sku_total_qty' => $po[0]['sku_total_qty'] ?? 0,
                    'sku_total_amnt' => $po[0]['sku_total_amnt'] ?? 0,
                    'approved_total_qty' => $po[0]['approved_total_qty'] ?? 0,
                    'approved_total_amnt' => $po[0]['approved_total_amnt'] ?? 0,
                    'processed_total_qty' => $po[0]['processed_total_qty'] ?? 0,
                    'processed_total_amnt' => $po[0]['processed_total_amnt'] ?? 0,
                    'stockin_total_qty' => $po[0]['stockin_total_qty'] ?? 0,
                    'stockin_total_amnt' => $po[0]['stockin_total_amnt'] ?? 0,
                    'excise_total' => $po[0]['excise_total'] ?? 0,
                    'addl_excise_total' => $po[0]['addl_excise_total'] ?? 0,
                    'vat_total' => $po[0]['vat_total'] ?? 0,
                    'tcs_total' => $po[0]['tcs_total'] ?? 0
                ];
            }

            $this->set(compact('startdate', 'enddate', 'datas', 'item_name','startDatetime','endDatetime'));
        }
    }

    private function _format_complimentary_items($rows) {
    if (empty($rows)) {
        return [];
    }

    $items = [];
    foreach ($rows as $r) {
        $get = function($keys, $default = '') use ($r) {
            if (!is_array($keys)) $keys = [$keys];
            foreach ($keys as $k) {
                if (isset($r[$k]) && !is_array($r[$k]) && $r[$k] !== '') return $r[$k];
                foreach ($r as $bucket) {
                    if (is_array($bucket) && isset($bucket[$k]) && $bucket[$k] !== '') return $bucket[$k];
                }
            }
            return $default;
        };

        // If $r is an already grouped structure (has arrays of item_names, rates, etc.)
        if (isset($r['item_names']) && is_array($r['item_names'])) {
            $count = count($r['item_names']);
            for ($i = 0; $i < $count; $i++) {
                $rateVal = isset($r['rates'][$i]) ? (float)str_replace(',', '', $r['rates'][$i]) : 0;
                $qtyVal  = isset($r['qtys'][$i]) ? (float)$r['qtys'][$i] : 0;
                $amtVal  = isset($r['amounts'][$i]) ? (float)str_replace(',', '', $r['amounts'][$i]) : ($rateVal * $qtyVal);

                $items[] = [
                    'report_date'            => $r['report_date'] ?? '',
                    'store_name'             => $r['store_name'] ?? ($r['deployment_name'] ?? ''),
                    'deployment_name'        => $r['store_name'] ?? ($r['deployment_name'] ?? ''),
                    'bill_no'                => $r['bill_no'] ?? '',
                    'reference_id'           => $r['reference_id'] ?? '',
                    'bill_open_time'         => $r['bill_open_time'] ?? '',
                    'bill_print_time'        => $r['bill_print_time'] ?? '',
                    'bill_settle_time'       => $r['bill_settle_time'] ?? '',
                    'kot_numbers'            => $r['kot_numbers'] ?? '',
                    'kot_print_time'         => $r['kot_print_time'] ?? '',
                    'section'                => $r['sections'][$i] ?? ($r['sections'][0] ?? ''),
                    'tableno'                => $r['tableno'] ?? '',
                    'total_pax'              => $r['total_pax'] ?? '',
                    'customer_name'          => $r['customer_name'] ?? ($r['name'] ?? ''),
                    'customer_phone'         => $r['customer_phone'] ?? ($r['phone'] ?? ''),
                    'name'                   => $r['customer_name'] ?? ($r['name'] ?? ''),
                    'phone'                  => $r['customer_phone'] ?? ($r['phone'] ?? ''),
                    'user_name'              => $r['user_name'] ?? '',
                    'offer_name'             => $r['offer_name'] ?? '',
                    'complimentary_head'     => $r['complimentary_head'] ?? '',
                    'manager_remarks'        => $r['manager_remarks'] ?? '',
                    'remarks'                => $r['remarks'][$i] ?? '-',
                    'complimentary_comment'  => $r['remarks'][$i] ?? '-',
                    'category'               => $r['categories'][$i] ?? '',
                    'item_name'              => $r['item_names'][$i] ?? '',
                    'rate'                   => $rateVal,
                    'qty'                    => $qtyVal,
                    'amount'                 => $amtVal,
                ];
            }
            continue;
        }

        // Raw row from database
        $compComment = $get(['complimentary_comment']);
        $remark      = ($compComment !== '') ? $compComment : '-';
        $rateVal     = (float)$get(['rate', 'price'], 0);
        $qtyVal      = (float)$get(['qty', 'quantity'], 0);
        $amtRaw      = $get(['amount']);
        $amt         = ($amtRaw !== '') ? (float)$amtRaw : ($rateVal * $qtyVal);

        $items[] = [
            'report_date'            => $get(['report_date']),
            'store_name'             => $get(['store_name', 'deployment_name']),
            'deployment_name'        => $get(['store_name', 'deployment_name']),
            'bill_no'                => $get(['bill_no', 'orderno']),
            'reference_id'           => $get(['reference_id']),
            'bill_open_time'         => $get(['bill_open_time']),
            'bill_print_time'        => $get(['bill_print_time']),
            'bill_settle_time'       => $get(['bill_settle_time']),
            'kot_numbers'            => $get(['kot_numbers']),
            'kot_print_time'         => $get(['kot_print_time']),
            'section'                => $get(['section', 'item_section']),
            'tableno'                => $get(['tableno', 'table_number']),
            'total_pax'              => $get(['total_pax', 'pax']),
            'customer_name'          => $get(['customer_name', 'name']),
            'customer_phone'         => $get(['customer_phone', 'phone']),
            'name'                   => $get(['customer_name', 'name']),
            'phone'                  => $get(['customer_phone', 'phone']),
            'user_name'              => $get(['user_name', 'username']),
            'offer_name'             => $get(['offer_name']),
            'complimentary_head'     => $get(['complimentary_head']),
            'manager_remarks'        => $get(['manager_remarks']),
            'remarks'                => $remark,
            'complimentary_comment'  => $remark,
            'category'               => $get(['category', 'category_name']),
            'item_name'              => $get(['item_name', 'product_name']),
            'rate'                   => $rateVal,
            'qty'                    => $qtyVal,
            'amount'                 => $amt,
        ];
    }

    return $items;
}

private function _group_complimentary_bills($rows) {
    if (empty($rows)) {
        return [];
    }

    $bills = [];
    foreach ($rows as $r) {
        $get = function($keys, $default = '') use ($r) {
            if (!is_array($keys)) $keys = [$keys];
            foreach ($keys as $k) {
                if (isset($r[$k]) && !is_array($r[$k]) && $r[$k] !== '') return $r[$k];
                foreach ($r as $bucket) {
                    if (is_array($bucket) && isset($bucket[$k]) && $bucket[$k] !== '') return $bucket[$k];
                }
            }
            return $default;
        };

        // If already grouped structure with array of item_names
        if (isset($r['item_names']) && is_array($r['item_names'])) {
            $ref = $r['reference_id'] ?? ($r['bill_no'] ?? uniqid());
            if (!isset($bills[$ref])) {
                $bills[$ref] = [
                    'report_date'        => $r['report_date'] ?? '',
                    'store_name'         => $r['store_name'] ?? ($r['deployment_name'] ?? ''),
                    'bill_no'            => $r['bill_no'] ?? '',
                    'reference_id'       => $r['reference_id'] ?? '',
                    'bill_open_time'     => $r['bill_open_time'] ?? '',
                    'bill_print_time'    => $r['bill_print_time'] ?? '',
                    'bill_settle_time'   => $r['bill_settle_time'] ?? '',
                    'kot_numbers'        => $r['kot_numbers'] ?? '',
                    'kot_print_time'     => $r['kot_print_time'] ?? '',
                    'section'            => $r['sections'][0] ?? ($r['section'] ?? ''),
                    'tableno'            => $r['tableno'] ?? '',
                    'total_pax'          => $r['total_pax'] ?? '',
                    'customer_name'      => $r['customer_name'] ?? ($r['name'] ?? ''),
                    'customer_phone'     => $r['customer_phone'] ?? ($r['phone'] ?? ''),
                    'user_name'          => $r['user_name'] ?? '',
                    'offer_name'         => $r['offer_name'] ?? '',
                    'complimentary_head' => $r['complimentary_head'] ?? '',
                    'manager_remarks'    => $r['manager_remarks'] ?? '',
                    'remarks'            => $r['remarks'][0] ?? ($r['manager_remarks'] ?? ($r['complimentary_head'] ?? '-')),
                    'items'              => [],
                    'item_count'         => 0,
                    'total_qty'          => 0,
                    'total_amount'       => 0,
                ];
            }
            $count = count($r['item_names']);
            for ($i = 0; $i < $count; $i++) {
                $rateVal = isset($r['rates'][$i]) ? (float)str_replace(',', '', (string)$r['rates'][$i]) : 0;
                $qtyVal  = isset($r['qtys'][$i]) ? (float)$r['qtys'][$i] : 0;
                $amtVal  = isset($r['amounts'][$i]) ? (float)str_replace(',', '', (string)$r['amounts'][$i]) : ($rateVal * $qtyVal);
                $rem     = $r['remarks'][$i] ?? '-';
                $bills[$ref]['items'][] = [
                    'kot_no'                => $r['kot_nos'][$i] ?? ($r['kot_numbers'] ?? '-'),
                    'section'               => $r['sections'][$i] ?? ($r['section'] ?? ''),
                    'dish_type'              => $r['dish_type'][$i] ?? '',
                    'cat_name'              => $r['cat_name'][$i] ?? '',
                    'subcat_name'              => $r['subcat_name'][$i] ?? '',
                    'item_name'             => $r['item_names'][$i] ?? '',
                    'rate'                  => $rateVal,
                    'qty'                   => $qtyVal,
                    'amount'                => $amtVal,
                    'remarks'               => $rem,
                    'complimentary_comment' => ($rem === '-') ? '' : $rem
                ];
                $bills[$ref]['total_qty'] += $qtyVal;
                $bills[$ref]['total_amount'] += $amtVal;
            }
            $bills[$ref]['item_count'] = count($bills[$ref]['items']);
            continue;
        }

        // Raw row from DB
        $ref = $get(['reference_id']);
        if (empty($ref)) {
            $ref = $get(['bill_no', 'orderno']) ?: uniqid();
        }

        if (!isset($bills[$ref])) {
            $compHead = $get(['complimentary_head']);
            $mgrRem   = $get(['manager_remarks']);
            $billRem  = ($compHead !== '') ? $compHead : (($mgrRem !== '') ? $mgrRem : '-');

            $bills[$ref] = [
                'report_date'        => $get(['report_date']),
                'store_name'         => $get(['store_name', 'deployment_name']),
                'bill_no'            => $get(['bill_no', 'orderno']),
                'reference_id'       => $ref,
                'bill_open_time'     => $get(['bill_open_time']),
                'bill_print_time'    => $get(['bill_print_time']),
                'bill_settle_time'   => $get(['bill_settle_time']),
                'kot_numbers'        => $get(['kot_numbers']),
                'kot_print_time'     => $get(['kot_print_time']),
                'section'            => $get(['section', 'item_section']),
                'tableno'            => $get(['tableno', 'table_number']),
                'total_pax'          => $get(['total_pax', 'pax']),
                'customer_name'      => $get(['customer_name', 'name']),
                'customer_phone'     => $get(['customer_phone', 'phone']),
                'user_name'          => $get(['user_name', 'username']),
                'offer_name'         => $get(['offer_name']),
                'complimentary_head' => $compHead,
                'manager_remarks'    => $mgrRem,
                'remarks'            => $billRem,
                'items'              => [],
                'item_count'         => 0,
                'total_qty'          => 0,
                'total_amount'       => 0,
            ];
        }

        $rateVal = (float)str_replace(',', '', (string)$get(['rate', 'price'], 0));
        $qtyVal  = (float)$get(['qty', 'quantity'], 0);
        $amtRaw  = $get(['amount']);
        $amtVal  = ($amtRaw !== '') ? (float)str_replace(',', '', (string)$amtRaw) : ($rateVal * $qtyVal);
        $compComment = $get(['complimentary_comment']);
        $itemRem = ($compComment !== '') ? $compComment : ($get(['remarks']) ?: '-');
        $itemKot = $get(['kot_no', 'kot_number']) ?: ($get(['kot_numbers']) ?: '-');

        $bills[$ref]['items'][] = [
            'kot_no'                => $itemKot,
            'section'               => $get(['section', 'item_section']),
            'dish_type'              => $get(['dish_type', 'dish_type']),
            'cat_name'              => $get(['cat_name', 'cat_name']),
            'subcat_name'              => $get(['subcat_name', 'subcat_name']),
            'item_name'             => $get(['item_name', 'product_name']),
            'rate'                  => $rateVal,
            'qty'                   => $qtyVal,
            'amount'                => $amtVal,
            'remarks'               => $itemRem,
            'complimentary_comment' => $compComment
        ];
        $bills[$ref]['total_qty'] += $qtyVal;
        $bills[$ref]['total_amount'] += $amtVal;
        $bills[$ref]['item_count'] = count($bills[$ref]['items']);
    }

    return array_values($bills);
}
        
private function _group_complimentary_rows($rows) {
    return $this->_group_complimentary_bills($rows);
}

public function complimentary_detail() {
    if (!$this->Session->check('Auth.User.id')) {
        return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
    }

    $this->layout = "users";

    $startDate = date('m/d/Y');
    $endDate = date('m/d/Y');
    $yourStartDateValue = $startDate . ' - ' . $endDate;
    $startdate = date('Y-m-d', strtotime($startDate));
    $enddate = date('Y-m-d', strtotime($endDate));

    $authUser = $this->Session->read('Auth.User');
    $userRole = $authUser['role'] ?? '';
    $userLocationId = $authUser['location_ids'] ?? 0;

    $conditions = [
        'status' => 1,
        'type'   => 'Outlet'
    ];
    if ($userRole !== 'admin' && !empty($userLocationId)) {
        $userLocationIds = Hash::extract(
            ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
            'ids.{n}'
        );
        $conditions['id IN'] = array_map('trim', $userLocationIds);
    }
    $users = $this->Location->find('all', ['conditions' => $conditions]);

    if ($this->request->is(['post', 'put'])) {
        $daterange = $this->request->data['daterange'];
        $dates = explode(" - ", $daterange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startDate = date('m/d/Y', strtotime($start_date));
        $endDate = date('m/d/Y', strtotime($end_date));
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        $cashier_id = $this->request->data['Search']['cashier'] ?? '';
    } else {
        $cashier_id = '';
    }

    if ($userRole != 'admin') {
        $cashierFilter = $cashier_id
            ? " AND t.store_id=$cashier_id"
            : " AND t.store_id IN (" . $userLocationId . ")";
    } else {
        $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
    }

    $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
    $startDatetime = $_shiftRange['startDatetime'];
    $endDatetime   = $_shiftRange['endDatetime'];

    $kdsPrinterCond = !empty($cashier_id)
        ? "kds = h.kds AND store_id = h.store_id"
        : "kds = h.kds";

    $complimentaryData = $this->Order_orderhistory->query("
        SELECT 
            DATE(p.created) AS report_date,
            (SELECT name FROM locations WHERE id = t.store_id) AS store_name,
            t.orderno AS bill_no,
            t.reference_id AS reference_id,
            (SELECT MIN(created) FROM order_orderhistories h2 WHERE h2.reference_id = t.reference_id AND h2.status = 2) AS bill_open_time,
            t.created AS bill_print_time,
            p.created AS bill_settle_time,
            (SELECT GROUP_CONCAT(DISTINCT(kot_number)) FROM order_orderhistories h3 WHERE h3.reference_id = t.reference_id AND h3.status = 2) AS kot_numbers,
            (SELECT MIN(created) FROM order_orderhistories h4 WHERE h4.reference_id = t.reference_id AND h4.status = 2) AS kot_print_time,
            h.kot_number AS kot_no,
            (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS section,
            h.category_name AS category,
            h.product_name AS item_name,
            h.price AS rate,
            h.quantity AS qty,
            h.table_number AS tableno,
            p.remarks AS offer_name,
            ROUND(h.price * h.quantity, 2) AS amount,
            (SELECT username FROM users WHERE id = t.cashier_id) AS user_name,
            h.remarks AS complimentary_comment,
            t.remarks AS complimentary_head,
            t.name AS customer_name,
            t.phone AS customer_phone,
            t.pax AS total_pax,
            (SELECT GROUP_CONCAT(ut.remarks SEPARATOR '; ') FROM user_tracks ut WHERE ut.reference_id = t.reference_id AND ut.type IN ('nocharge','unbill') LIMIT 1) AS manager_remarks
        FROM order_orderhistories h
        INNER JOIN order_transactions t ON t.reference_id = h.reference_id AND t.status = 2
        INNER JOIN order_payments p ON p.reference_id = h.reference_id AND p.status = 2 AND p.type = 7
        WHERE h.status = 2 
          AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
          $cashierFilter
        ORDER BY p.created ASC, t.orderno ASC, h.id ASC
    ");

    $complimentaryData = $this->_group_complimentary_bills($complimentaryData);

    $this->set(compact('users', 'yourStartDateValue', 'complimentaryData', 'cashier_id'));
}

public function complimentary_detail_items()
{
    if (!$this->Session->check('Auth.User.id')) {
        return $this->response->body(json_encode(['error' => 'Unauthorized']));
    }
    $this->autoRender = false;
    $this->response->type('application/json');

    $reference_id = $this->request->query('reference_id') ?? '';
    $bill_no      = $this->request->query('bill_no') ?? '';
    if (!$reference_id && !$bill_no) {
        return $this->response->body(json_encode(['error' => 'Invalid Request']));
    }

    $where = $reference_id ? "h.reference_id = '$reference_id'" : "t.orderno = '$bill_no'";

    $rows = $this->Order_orderhistory->query("
        SELECT 
            h.kot_number AS kot_no,
            h.category_name AS category,
            h.product_name AS item_name,
            h.price AS rate,
            h.quantity AS qty,
            ROUND(h.price * h.quantity, 2) AS amount,
            COALESCE(NULLIF(h.remarks, ''), '-') AS remarks
        FROM order_orderhistories h
        INNER JOIN order_transactions t ON t.reference_id = h.reference_id AND t.status = 2
        WHERE h.status = 2 AND $where
        ORDER BY h.id ASC
    ");

    $items = [];
    foreach ($rows as $r) {
        $kot  = $r['h']['kot_no'] ?? $r['h']['kot_number'] ?? $r[0]['kot_no'] ?? '-';
        $cat  = $r['h']['category'] ?? $r['h']['category_name'] ?? $r[0]['category'] ?? '';
        $name = $r['h']['item_name'] ?? $r['h']['product_name'] ?? $r[0]['item_name'] ?? '';
        $rate = (float)($r['h']['rate'] ?? $r['h']['price'] ?? $r[0]['rate'] ?? 0);
        $qty  = (float)($r['h']['qty'] ?? $r['h']['quantity'] ?? $r[0]['qty'] ?? 0);
        $amt  = (float)($r[0]['amount'] ?? ($rate * $qty));
        $rem  = $r['h']['remarks'] ?? $r['h']['complimentary_comment'] ?? $r[0]['remarks'] ?? '-';
        $items[] = [
            'kot_no'    => $kot,
            'category'  => $cat,
            'item_name' => $name,
            'rate'      => $rate,
            'qty'       => $qty,
            'amount'    => $amt,
            'remarks'   => $rem
        ];
    }

    return $this->response->body(json_encode(['items' => $items]));
}

public function complimentary_detail_export() {
        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        $excelDateRange = $startdate. ' ' .$enddate;
        $cashier_id = $_GET['cashier'] ?? '';
    
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
    
        if($userRole!='admin' ){
            $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : " AND t.store_id IN (" . $userLocationId . ")";
        }else{
            $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }
    
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $kdsPrinterCond = !empty($cashier_id)
            ? "kds = h.kds AND store_id = h.store_id"
            : "kds = h.kds";
    
        $rows = $this->Order_orderhistory->query("
            SELECT 
                DATE(p.created) AS report_date,
                (SELECT name FROM locations WHERE id = t.store_id) AS deployment_name,
                t.orderno AS bill_no,
                t.reference_id AS reference_id,
                (SELECT MIN(created) FROM order_orderhistories h2 WHERE h2.reference_id = t.reference_id AND h2.status = 2) AS bill_open_time,
                t.created AS bill_print_time,
                p.created AS bill_settle_time,
                (SELECT GROUP_CONCAT(DISTINCT(kot_number)) FROM order_orderhistories h3 WHERE h3.reference_id = t.reference_id AND h3.status = 2) AS kot_numbers,
                (SELECT MIN(created) FROM order_orderhistories h4 WHERE h4.reference_id = t.reference_id AND h4.status = 2) AS kot_print_time,
                (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS section,
                (SELECT cat_name FROM catalog_product WHERE id = h.product_id) AS cat_name,
                (SELECT dish_type FROM catalog_product WHERE id = h.product_id) AS dish_type,
                h.category_name AS subcat_name,
                h.product_name AS item_name,
                h.price AS rate,
                h.quantity AS qty,
                h.table_number AS tableno,
                p.remarks AS offer_name,
                ROUND(h.price * h.quantity, 2) AS amount,
                (SELECT username FROM users WHERE id = t.cashier_id) AS user_name,
                h.remarks AS complimentary_comment,
                t.remarks AS complimentary_head,
                t.name AS name,
                t.phone AS phone,
                t.pax AS total_pax
            FROM order_orderhistories h
            INNER JOIN order_transactions t ON t.reference_id = h.reference_id AND t.status = 2
            INNER JOIN order_payments p ON p.reference_id = h.reference_id AND p.status = 2 AND p.type = 7
            WHERE h.status = 2 
              AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
            ORDER BY p.created ASC, t.orderno ASC, h.id ASC
        ");
      
    $rows = $this->_group_complimentary_bills($rows);
    
        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Complimentary Detail");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Complimentary Detail');
    
        $storeName = '';
        if ($cashier_id) {
            $storeData = $this->Order_orderhistory->query("SELECT name FROM locations WHERE id = $cashier_id LIMIT 1");
            $storeName = $storeData ? $storeData[0]['locations']['name'] : '';
        } 
        else{
            $storeName = 'All Stores';
        }
    
        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'Complimentary Detail (' . $excelDateRange . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));
    
        $headers = array(
            '#', 'Bill No', 'Date', 'Store Name', 'Bill Open Time', 'Bill Print Time', 'Bill Settle Time',
            'Table', 'Remarks', 'Items', 'Total Qty', 'Total Amount', 'PAX', 'Customer Name', 'Customer Phone',
            'KOT No', 'KOT Print Time', 'Section', 'Master Category', 'Category', 'Sub Category', 'Rate',
            'User Name', 'Discount User', 'Complimentary Offer', 'Compliment Comment', 'Complimentary Head'
        );
        $sheet->fromArray($headers, null, 'A5');
    
        $rowNum = 6;
        $totalQty = 0;
        $totalAmount = 0;
        $idx = 1;
        foreach($rows as $b){
            // 1. Bill Summary Row
            $sheet->setCellValue("A$rowNum", $idx++);
            $sheet->setCellValueExplicit("B$rowNum", $b['bill_no'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("C$rowNum", $b['report_date']);
            $sheet->setCellValue("D$rowNum", $b['store_name']);
            $sheet->setCellValue("E$rowNum", $b['bill_open_time']);
            $sheet->setCellValue("F$rowNum", $b['bill_print_time']);
            $sheet->setCellValue("G$rowNum", $b['bill_settle_time']);
            $sheet->setCellValue("H$rowNum", $b['tableno']);
            $sheet->setCellValue("I$rowNum", $b['remarks']);
            $sheet->setCellValue("J$rowNum", $b['item_count']);
            $sheet->setCellValue("K$rowNum", (float)$b['total_qty']);
            $sheet->setCellValue("L$rowNum", (float)$b['total_amount']);
            $sheet->setCellValue("M$rowNum", $b['total_pax']);
            $sheet->setCellValue("N$rowNum", $b['customer_name']);
            $sheet->setCellValueExplicit("O$rowNum", $b['customer_phone'], PHPExcel_Cell_DataType::TYPE_STRING);
            // New columns
            $sheet->setCellValue("P$rowNum", $b['kot_numbers'] ?? '');
            $sheet->setCellValue("Q$rowNum", $b['kot_print_time'] ?? '');
            $sheet->setCellValue("R$rowNum", '');   // Section (item level)
            $sheet->setCellValue("S$rowNum", '');   // Category (item level)
            $sheet->setCellValue("T$rowNum", '');   // Category (item level)
            $sheet->setCellValue("U$rowNum", '');   // Category (item level)
            $sheet->setCellValue("V$rowNum", '');   // Rate (item level)
            $sheet->setCellValue("W$rowNum", $b['user_name'] ?? '');
            $sheet->setCellValue("X$rowNum", $b['user_name'] ?? '');   // Discount User
            $sheet->setCellValue("Y$rowNum", $b['offer_name'] ?? '');
            $sheet->setCellValue("Z$rowNum", '');   // Compliment Comment (item level)
            $sheet->setCellValue("AA$rowNum", $b['complimentary_head'] ?? '');
    
            $sheet->getStyle("A$rowNum:Z$rowNum")->getFont()->setBold(true);
            $sheet->getStyle("K$rowNum:L$rowNum")->getNumberFormat()->setFormatCode('#,##0.00');
    
            $totalQty += (float)$b['total_qty'];
            $totalAmount += (float)$b['total_amount'];
            $rowNum++;
    
            // 2. Item Sub-Rows (indented with blank leading columns, exactly like PO Report v2)
            if (!empty($b['items']) && is_array($b['items'])) {
                
                foreach ($b['items'] as $item) {
                    
                    
                    if($item['dish_type']==0){
                        $dish_type='Food';
                    }elseif($item['dish_type']==1){
                        $dish_type='Beverage';
                    }elseif($item['dish_type']==2){
                        $dish_type='Liquor';
                    }else{
                        $dish_type='Others';
                    }
                    $sheet->setCellValue("A$rowNum", '');
                    $sheet->setCellValue("B$rowNum", '');
                    $sheet->setCellValue("C$rowNum", '');
                    $sheet->setCellValue("D$rowNum", '');
                    $sheet->setCellValue("E$rowNum", '');
                    $sheet->setCellValue("F$rowNum", '');
                    $sheet->setCellValue("G$rowNum", '');
                    $sheet->setCellValue("H$rowNum", '');
                    $sheet->setCellValue("I$rowNum", $item['remarks'] ?? '-');
                    $sheet->setCellValue("J$rowNum", $item['item_name'] ?? '');
                    $sheet->setCellValue("K$rowNum", (float)($item['qty'] ?? 0));
                    $sheet->setCellValue("L$rowNum", (float)($item['amount'] ?? 0));
                    $sheet->setCellValue("M$rowNum", '');
                    $sheet->setCellValue("N$rowNum", '');
                    $sheet->setCellValue("O$rowNum", '');
                    // New columns
                    $sheet->setCellValue("P$rowNum", '');
                    $sheet->setCellValue("Q$rowNum", '');
                    $sheet->setCellValue("R$rowNum", $item['section'] ?? '');
                    $sheet->setCellValue("S$rowNum", $dish_type ?? '');
                    $sheet->setCellValue("T$rowNum", $item['cat_name'] ?? '');
                    $sheet->setCellValue("U$rowNum", $item['subcat_name'] ?? '');
                    $sheet->setCellValue("V$rowNum", (float)($item['rate'] ?? 0));
                    $sheet->setCellValue("W$rowNum", '');
                    $sheet->setCellValue("X$rowNum", '');
                    $sheet->setCellValue("Y$rowNum", '');
                    $sheet->setCellValue("Z$rowNum", $item['complimentary_comment'] ?? '');
                    $sheet->setCellValue("AA$rowNum", '');
    
                    $rowNum++;
                }
            }
        }
    
        // Summary Total Row in Excel
        $sheet->setCellValue("J$rowNum", "Grand Total:");
        $sheet->setCellValue("K$rowNum", $totalQty);
        $sheet->setCellValue("L$rowNum", $totalAmount);
        $sheet->getStyle("J$rowNum:L$rowNum")->getFont()->setBold(true);
        $sheet->getStyle("K$rowNum:L$rowNum")->getNumberFormat()->setFormatCode('#,##0.00');
        
        $sheet->getStyle('A5:Z5')->getFont()->setBold(true);
    
        foreach(range('A','Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Complimentary_Detail.xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

public function complimentary_detail_pdf() {
    if (!$this->Session->check('Auth.User.id')) {
        $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
    }

    $this->layout = "login";

    if ($this->request->is(['post', 'put'])) {
        
        $date_range = $this->request->data['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));

        $cashier_id = $this->request->data['cashier'] ?? '';

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if ($userRole != 'admin') {
            $cashierFilter = $cashier_id
                ? " AND t.store_id=$cashier_id"
                : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        if (!empty($cashier_id)) {
            $store = $this->Location->find('first', [
                'conditions' => ['Location.id' => $cashier_id],
                'fields' => ['Location.name']
            ]);
            $store_name = $store['Location']['name'] ?? '';
        } else {
            $store_name = 'All Stores';
        }

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $kdsPrinterCond = !empty($cashier_id)
            ? "kds = h.kds AND store_id = h.store_id"
            : "kds = h.kds";

        $rows = $this->Order_orderhistory->query("
            SELECT 
                DATE(p.created) AS report_date,
                (SELECT name FROM locations WHERE id = t.store_id) AS deployment_name,
                t.orderno AS bill_no,
                t.reference_id AS reference_id,
                (SELECT MIN(created) FROM order_orderhistories h2 WHERE h2.reference_id = t.reference_id AND h2.status = 2) AS bill_open_time,
                t.created AS bill_print_time,
                p.created AS bill_settle_time,
                (SELECT GROUP_CONCAT(DISTINCT(kot_number)) FROM order_orderhistories h3 WHERE h3.reference_id = t.reference_id AND h3.status = 2) AS kot_numbers,
                (SELECT MIN(created) FROM order_orderhistories h4 WHERE h4.reference_id = t.reference_id AND h4.status = 2) AS kot_print_time,
                (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS section,
                h.category_name AS category,
                h.product_name AS item_name,
                h.price AS rate,
                h.quantity AS qty,
                h.table_number AS tableno,
                p.remarks AS offer_name,
                ROUND(h.price * h.quantity, 2) AS amount,
                (SELECT username FROM users WHERE id = t.cashier_id) AS user_name,
                h.remarks AS complimentary_comment,
                t.remarks AS complimentary_head,
                t.name AS name,
                t.phone AS phone,
				t.pax AS total_pax
            FROM order_orderhistories h
            INNER JOIN order_transactions t ON t.reference_id = h.reference_id AND t.status = 2
            INNER JOIN order_payments p ON p.reference_id = h.reference_id AND p.status = 2 AND p.type = 7
            WHERE h.status = 2 
            AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
            $cashierFilter
            ORDER BY p.created ASC, t.orderno ASC, h.id ASC
        ");

        $datas = $this->_group_complimentary_bills($rows);

        $this->set(compact('startdate', 'enddate', 'datas', 'store_name'));
        $this->render('complimentary_detail_pdf');
    }
}

    public function reprint_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate = date('Y-m-d', strtotime($endDate));

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }
        $users = $this->Location->find('all', ['conditions' => $conditions]);

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $yourStartDateValue = $startDate . ' - ' . $endDate;
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
            $cashier_id = $this->request->data['Search']['cashier'] ?? '';
        } else {
            $cashier_id = '';
        }

        if ($userRole != 'admin') {
            $cashierFilter = $cashier_id
                ? " AND t.store_id=$cashier_id"
                : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }
        
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // reprint - when payment is created after transaction creation (indicating bill was reprinted)
        $reprintData = $this->Order_transaction->query("
            SELECT 
                DATE(COALESCE(p_max.created, t.created)) AS report_date,
                (SELECT name FROM locations WHERE id = t.store_id) AS deployment_name,
                t.orderno AS bill_no,
                t.id AS transaction_id,
                t.reference_id,
                t.created AS original_print_time,
                COALESCE(p_max.created, t.created) AS reprint_time,
                t.amount AS original_bill_amount,
                COALESCE(payment_total.total_amount, t.amount) AS reprint_bill_amount,
                (SELECT username FROM users WHERE id = t.cashier_id) AS originally_printed_by,
                COALESCE((SELECT username FROM users WHERE id = p_max.cashier_id), (SELECT username FROM users WHERE id = t.cashier_id)) AS user,
                COALESCE(t.name, (SELECT customer_name FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), 'NA') AS customer_name,
                COALESCE(t.phone, (SELECT customer_phone FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), 'NA') AS customer_number,
                COALESCE(t.remarks, 'NA') AS comment
            FROM order_transactions t
            LEFT JOIN (
                SELECT p1.reference_id, p1.created, p1.cashier_id
                FROM order_payments p1
                INNER JOIN (
                    SELECT reference_id, MAX(created) AS max_created
                    FROM order_payments
                    WHERE status = 2
                    GROUP BY reference_id
                ) p2 ON p1.reference_id = p2.reference_id AND p1.created = p2.max_created
                WHERE p1.status = 2
            ) p_max ON p_max.reference_id = t.reference_id
            LEFT JOIN (
                SELECT reference_id, SUM(amount) AS total_amount
                FROM order_payments
                WHERE status = 2
                GROUP BY reference_id
            ) payment_total ON payment_total.reference_id = t.reference_id
            WHERE t.status = 2 
            AND p_max.created IS NOT NULL
            AND p_max.created > DATE_ADD(t.created, INTERVAL 2 MINUTE)
            AND DATE(p_max.created) BETWEEN '$startDatetime' AND '$endDatetime'
            $cashierFilter
            ORDER BY p_max.created DESC
        ");

        $this->set(compact('users', 'yourStartDateValue', 'reprintData', 'cashier_id'));
    }

    public function reprint_report_export() {
        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));        
        $excelDateRange = $startdate. ' ' .$enddate;

        $cashier_id = $_GET['cashier'] ?? '';

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if($userRole!='admin' ){
            $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : " AND t.store_id IN (" . $userLocationId . ")";
        }else{
            $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        $storeName = '';
        if ($cashier_id) {
            $storeData = $this->Order_orderhistory->query("SELECT name FROM locations WHERE id = $cashier_id LIMIT 1");
            $storeName = $storeData ? $storeData[0]['locations']['name'] : '';
        } 
        else{
            $storeName = 'All Stores';
        }
        
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];



        $rows = $this->Order_transaction->query("
            SELECT 
                DATE(COALESCE(p_max.created, t.created)) AS report_date,
                (SELECT name FROM locations WHERE id = t.store_id) AS deployment_name,
                t.orderno AS bill_no,
                t.created AS original_print_time,
                COALESCE(p_max.created, t.created) AS reprint_time,
                t.amount AS original_bill_amount,
                COALESCE(payment_total.total_amount, t.amount) AS reprint_bill_amount,
                (SELECT username FROM users WHERE id = t.cashier_id) AS originally_printed_by,
                COALESCE((SELECT username FROM users WHERE id = p_max.cashier_id), (SELECT username FROM users WHERE id = t.cashier_id)) AS user,
                COALESCE(t.name, (SELECT customer_name FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), 'NA') AS customer_name,
                COALESCE(t.phone, (SELECT customer_phone FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), 'NA') AS customer_number,
                COALESCE(t.remarks, 'NA') AS comment
            FROM order_transactions t
            LEFT JOIN (
                SELECT p1.reference_id, p1.created, p1.cashier_id
                FROM order_payments p1
                INNER JOIN (
                    SELECT reference_id, MAX(created) AS max_created
                    FROM order_payments
                    WHERE status = 2
                    GROUP BY reference_id
                ) p2 ON p1.reference_id = p2.reference_id AND p1.created = p2.max_created
                WHERE p1.status = 2
            ) p_max ON p_max.reference_id = t.reference_id
            LEFT JOIN (
                SELECT reference_id, SUM(amount) AS total_amount
                FROM order_payments
                WHERE status = 2
                GROUP BY reference_id
            ) payment_total ON payment_total.reference_id = t.reference_id
            WHERE t.status = 2 
            AND p_max.created IS NOT NULL
            AND p_max.created > DATE_ADD(t.created, INTERVAL 2 MINUTE)
            AND DATE(p_max.created) BETWEEN '$startDatetime' AND '$endDatetime'
            $cashierFilter
            ORDER BY p_max.created DESC
        ");

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Reprint Report");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Reprint');

        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'Reprint_Report (' . $excelDateRange . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));

        $headers = array(
            'Date','billNo','Originally Printed By','Re Print Time','Re Print Bill Amount',
            'Actual Print Time','Original Bill Amount','User','Comment','Customer','Customer Number'
        );
        $sheet->fromArray($headers, null, 'A5');

        $rowNum = 6;
        foreach($rows as $r){
            $row = $r[0];
            $t = $r['t'];
            $sheet->setCellValue("A$rowNum", $row['report_date']);
            $sheet->setCellValue("B$rowNum", $t['bill_no']);
            $sheet->setCellValue("C$rowNum", $row['originally_printed_by']);
            $sheet->setCellValue("D$rowNum", date('d-m-Y H:i:s A', strtotime($row['reprint_time'])));
            $sheet->setCellValue("E$rowNum", number_format($row['reprint_bill_amount'], 2));
            $sheet->setCellValue("F$rowNum", date('d-m-Y H:i:s A', strtotime($t['original_print_time'])));
            $sheet->setCellValue("G$rowNum", number_format($t['original_bill_amount'], 2));
            $sheet->setCellValue("H$rowNum", $row['user']);
            $sheet->setCellValue("I$rowNum", $row['comment']);
            $sheet->setCellValue("J$rowNum", $row['customer_name']);
            $sheet->setCellValue("K$rowNum", $row['customer_number']);
            $rowNum++;
        }

        
        $sheet->getStyle('A5:K5')->getFont()->setBold(true);

        foreach(range('A','K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Reprint_Report.xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function reprint_report_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            
            $date_range = $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $cashier_id = $this->request->data['cashier'] ?? '';

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            if ($userRole != 'admin') {
                $cashierFilter = $cashier_id
                    ? " AND t.store_id=$cashier_id"
                    : " AND t.store_id IN (" . $userLocationId . ")";
            } else {
                $cashierFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
            }

            if (!empty($cashier_id)) {
                $store = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $cashier_id],
                    'fields' => ['Location.name']
                ]);
                $store_name = $store['Location']['name'] ?? '';
            } else {
                $store_name = 'All Stores';
            }
            
                
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

            $rows = $this->Order_transaction->query("
                SELECT 
                    DATE(COALESCE(p_max.created, t.created)) AS report_date,
                    (SELECT name FROM locations WHERE id = t.store_id) AS deployment_name,
                    t.orderno AS bill_no,
                    t.created AS original_print_time,
                    COALESCE(p_max.created, t.created) AS reprint_time,
                    t.amount AS original_bill_amount,
                    COALESCE(payment_total.total_amount, t.amount) AS reprint_bill_amount,
                    (SELECT username FROM users WHERE id = t.cashier_id) AS originally_printed_by,
                    COALESCE((SELECT username FROM users WHERE id = p_max.cashier_id), (SELECT username FROM users WHERE id = t.cashier_id)) AS user,
                    COALESCE(t.name, (SELECT customer_name FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), 'NA') AS customer_name,
                    COALESCE(t.phone, (SELECT customer_phone FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), 'NA') AS customer_number,
                    COALESCE(t.remarks, 'NA') AS comment
                FROM order_transactions t
                LEFT JOIN (
                    SELECT reference_id, MAX(created) AS created, cashier_id
                    FROM order_payments
                    WHERE status = 2
                    GROUP BY reference_id, cashier_id
                ) p_max ON p_max.reference_id = t.reference_id
                LEFT JOIN (
                    SELECT reference_id, SUM(amount) AS total_amount
                    FROM order_payments
                    WHERE status = 2
                    GROUP BY reference_id
                ) payment_total ON payment_total.reference_id = t.reference_id
                WHERE t.status = 2 
                AND p_max.created IS NOT NULL
                AND p_max.created > DATE_ADD(t.created, INTERVAL 2 MINUTE)
                AND DATE(p_max.created) BETWEEN '$startDatetime' AND '$endDatetime'
                $cashierFilter
                ORDER BY p_max.created DESC
            ");

            $datas = [];
            foreach ($rows as $r) {
                $row = $r[0];
                $t = $r['t'];
                $datas[] = [
                    'report_date' => $row['report_date'],
                    'deployment_name' => $row['deployment_name'],
                    'bill_no' => $t['bill_no'],
                    'originally_printed_by' => $row['originally_printed_by'],
                    'reprint_time' => $row['reprint_time'],
                    'reprint_bill_amount' => $row['reprint_bill_amount'],
                    'original_print_time' => $t['original_print_time'],
                    'original_bill_amount' => $t['original_bill_amount'],
                    'user' => $row['user'],
                    'comment' => $row['comment'],
                    'customer_name' => $row['customer_name'],
                    'customer_number' => $row['customer_number']
                ];
            }

            $this->set(compact('startdate', 'enddate', 'datas', 'store_name'));
            $this->render('reprint_report_pdf');
        }
    }

    public function bill_kot_delete_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate = date('Y-m-d', strtotime($endDate));

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        // Allowed stores
        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }
        $users = $this->Location->find('all', ['conditions' => $conditions]);

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $yourStartDateValue = $startDate . ' - ' . $endDate;
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
            $cashier_id = $this->request->data['Search']['cashier'] ?? '';
        } else {
            $cashier_id = '';
        }

        if ($userRole != 'admin') {
            $storeFilter = $cashier_id
                ? " AND t.store_id=$cashier_id"
                : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];

        // Item (KOT) delete rows
        $kotDeleted = $this->Order_orderhistory->query("
            SELECT 
                DATE(h.created) AS report_date,
                t.orderno AS bill_no,
                h.kot_number AS kot_no,
                h.product_name AS item_name,
                h.price AS rate,
                h.quantity AS qty,
                ROUND(h.total_tax,2) AS total_tax_value,
                h.table_number AS table_no,
                h.remarks AS comment,
                (SELECT username FROM users WHERE id = h.cashier_id) AS username,
                (SELECT username FROM users WHERE id = h.cashier_id) AS delete_user,
                (SELECT username FROM users WHERE id = t.cashier_id) AS settle_user,
                (SELECT name FROM locations WHERE id = t.store_id) AS server,
                COALESCE(t.name,'') AS customer_name,
                COALESCE(t.phone,'') AS customer_mobile,
                '' AS customer_address,
                'Item Delete' AS delete_type,
                CASE WHEN EXISTS (SELECT 1 FROM order_payments p WHERE p.reference_id=t.reference_id AND p.status=2) THEN 'After Settlement' ELSE 'Before Settlement' END AS bill_settlement_status,
                (SELECT MIN(created) FROM order_orderhistories h2 WHERE h2.reference_id=t.reference_id AND h2.status IN (0,1,2)) AS order_time,
                h.created AS delete_time
            FROM order_orderhistories h
            INNER JOIN order_transactions t ON t.reference_id = h.reference_id
            WHERE h.status = 0
              AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilter
        ");
        

        // Bill delete rows
        $billDeleted = $this->Order_transaction->query("
            SELECT 
                DATE(t.created) AS report_date,
                t.orderno AS bill_no,
                '' AS kot_no,
                '' AS item_name,
                0 AS rate,
                0 AS qty,
                ROUND(t.total_tax,2) AS total_tax_value,
                t.table_number AS table_no,
                t.remarks AS comment,
                (SELECT username FROM users WHERE id = t.cashier_id) AS username,
                (SELECT username FROM users WHERE id = t.cashier_id) AS delete_user,
                '' AS settle_user,
                (SELECT name FROM locations WHERE id = t.store_id) AS server,
                COALESCE(t.name,'') AS customer_name,
                COALESCE(t.phone,'') AS customer_mobile,
                '' AS customer_address,
                'Bill Delete' AS delete_type,
                'Before Settlement' AS bill_settlement_status,
                t.created AS order_time,
                t.created AS delete_time
            FROM order_transactions t
            WHERE t.status = 0
              AND t.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilter
        ");

        $rows = array_merge($kotDeleted, $billDeleted);

        $this->set(compact('users', 'yourStartDateValue', 'rows', 'cashier_id'));
    }

    public function bill_kot_delete_report_export() {
        $date_range = $_GET['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        
        $excelDateRange = $startdate. ' ' .$enddate;

        $cashier_id = $_GET['cashier'] ?? '';

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if($userRole!='admin' ){
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : " AND t.store_id IN (" . $userLocationId . ")";
        }else{
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        $storeName = '';
        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];

        if ($cashier_id) {
            $storeData = $this->Order_orderhistory->query("SELECT name FROM locations WHERE id = $cashier_id LIMIT 1");
            $storeName = $storeData ? $storeData[0]['locations']['name'] : '';
        } 
        else{
            $storeName = 'All Stores';
        }
        
        $kotDeleted = $this->Order_orderhistory->query("
            SELECT 
                DATE(h.created) AS report_date,
                t.orderno AS bill_no,
                h.kot_number AS kot_no,
                h.product_name AS item_name,
                h.price AS rate,
                h.quantity AS qty,
                ROUND(h.price * h.quantity, 2) AS total,
                h.table_number AS table_no,
                h.remarks AS comment,
                (SELECT username FROM users WHERE id = h.cashier_id) AS username,
                (SELECT username FROM users WHERE id = h.cashier_id) AS delete_user,
                (SELECT username FROM users WHERE id = t.cashier_id) AS settle_user,
                (SELECT name FROM locations WHERE id = t.store_id) AS server,
                COALESCE(t.name,'NA') AS customer_name,
                COALESCE(t.phone,'NA') AS customer_mobile,
                COALESCE(t.address,'NA') AS customer_address,
                'Item Delete' AS delete_type,
                CASE WHEN EXISTS (SELECT 1 FROM order_payments p WHERE p.reference_id=t.reference_id AND p.status=2) THEN 'Void After Settlement' ELSE 'Void Before Settlement' END AS bill_settlement_status,
                (SELECT MIN(created) FROM order_orderhistories h2 WHERE h2.reference_id=t.reference_id AND h2.status IN (0,1,2)) AS order_time,
                h.created AS delete_time,
                COALESCE((SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2), t.created) AS bill_close_time,
                '' AS tab_name
            FROM order_orderhistories h
            INNER JOIN order_transactions t ON t.reference_id = h.reference_id
            WHERE h.status = 0
              AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilter
        ");

        $billDeleted = $this->Order_transaction->query("
            SELECT 
                DATE(t.created) AS report_date,
                t.orderno AS bill_no,
                '' AS kot_no,
                '' AS item_name,
                0 AS rate,
                0 AS qty,
                0 AS total,
                t.table_number AS table_no,
                t.remarks AS comment,
                (SELECT username FROM users WHERE id = t.cashier_id) AS username,
                (SELECT username FROM users WHERE id = t.cashier_id) AS delete_user,
                '' AS settle_user,
                (SELECT name FROM locations WHERE id = t.store_id) AS server,
                COALESCE(t.name,'NA') AS customer_name,
                COALESCE(t.phone,'NA') AS customer_mobile,
                COALESCE(t.address,'NA') AS customer_address,
                'Bill Delete' AS delete_type,
                'Void Before Settlement' AS bill_settlement_status,
                t.created AS order_time,
                t.created AS delete_time,
                t.created AS bill_close_time,
                '' AS tab_name
            FROM order_transactions t
            WHERE t.status = 0
              AND t.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilter
        ");

        // Process and group KOT deleted items
        $groupedData = [];
        foreach($kotDeleted as $r){
            $d = $r[0];
            $t = isset($r['t']) ? $r['t'] : [];
            $h = isset($r['h']) ? $r['h'] : [];
            
            $billNo = $t['bill_no'] ?? $d['bill_no'];
            $billCloseTime = $d['bill_close_time'] ?? '';
            $key = $billNo . '_' . $billCloseTime;
            
            if(!isset($groupedData[$key])){
                $groupedData[$key] = [
                    'bill_no' => $billNo,
                    'bill_close_time' => $billCloseTime,
                    'items' => []
                ];
            }
            
            $groupedData[$key]['items'][] = [
                'd' => $d,
                't' => $t,
                'h' => $h
            ];
        }
        
        // Process and group Bill deleted items
        foreach($billDeleted as $r){
            $d = $r[0];
            $t = isset($r['t']) ? $r['t'] : [];
            
            $billNo = $t['bill_no'] ?? $d['bill_no'];
            $billCloseTime = $d['bill_close_time'] ?? '';
            $key = $billNo . '_' . $billCloseTime;
            
            if(!isset($groupedData[$key])){
                $groupedData[$key] = [
                    'bill_no' => $billNo,
                    'bill_close_time' => $billCloseTime,
                    'items' => []
                ];
            }
            
            $groupedData[$key]['items'][] = [
                'd' => $d,
                't' => $t,
                'h' => []
            ];
        }
        
        // Sort grouped data by bill number and bill close time
        uasort($groupedData, function($a, $b) {
            $billCompare = strcmp($a['bill_no'], $b['bill_no']);
            if ($billCompare !== 0) {
                return $billCompare;
            }
            return strcmp($a['bill_close_time'], $b['bill_close_time']);
        });

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Bill & KOT Delete Report");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Delete Report');

        
        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'KOT_Delete_History_combined(' . $excelDateRange . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));

        $headers = array(
            'KOT Number','Item','Order Time','Delete Time','Rate','Quantity','Total',
            'Tab Name','Table No.','Comment','Username','Deletion User','Settle User','Server',
            'Customer Name','Customer Mobile','Customer Address','Type','Bill Settlement Status'
        );
        $sheet->fromArray($headers, null, 'A5');

        // Style headers
        $headerStyle = $sheet->getStyle('A5:S5');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('E0E0E0');

        $rowNum = 6;
        
        foreach($groupedData as $group){
            $billNo = $group['bill_no'];
            $billCloseTime = $group['bill_close_time'];
            $items = $group['items'];
            
            // Add bill header row
            $closeTimeFormatted = !empty($billCloseTime) ? date('h:i:s A', strtotime($billCloseTime)) : '';
            $sheet->setCellValue("A$rowNum", "Bill Number: $billNo Bill Close Time: $closeTimeFormatted");
            $sheet->mergeCells("A$rowNum:S$rowNum");
            $billHeaderStyle = $sheet->getStyle("A$rowNum");
            $billHeaderStyle->getFont()->setBold(true);
            $billHeaderStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $billHeaderStyle->getFill()->getStartColor()->setRGB('D0D0D0');
            $rowNum++;
            
            $billTotal = 0;
            
            // Add item rows
            foreach($items as $item){
                $d = $item['d'];
                $t = $item['t'];
                $h = $item['h'];
                
                $kotNo = $h['kot_no'] ?? $d['kot_no'] ?? '';
                $itemName = $h['item_name'] ?? $d['item_name'] ?? '';
                $orderTime = $d['order_time'] ?? '';
                $deleteTime = $h['delete_time'] ?? $d['delete_time'] ?? '';
                $rate = $h['rate'] ?? $d['rate'] ?? 0;
                $qty = $h['qty'] ?? $d['qty'] ?? 0;
                $total = $d['total'] ?? ($rate * $qty);
                $tabName = $d['tab_name'] ?? '';
                $tableNo = $h['table_no'] ?? $d['table_no'] ?? '';
                $comment = $h['comment'] ?? $d['comment'] ?? '';
                $username = $d['username'] ?? '';
                $deleteUser = $d['delete_user'] ?? '';
                $settleUser = $d['settle_user'] ?? '';
                $server = $d['server'] ?? '';
                $customerName = $d['customer_name'] ?? 'NA';
                $customerMobile = $d['customer_mobile'] ?? 'NA';
                $customerAddress = $d['customer_address'] ?? 'NA';
                $deleteType = $d['delete_type'] ?? '';
                $billSettlementStatus = $d['bill_settlement_status'] ?? '';
                
                $billTotal += (float)$total;
                
                $sheet->setCellValue("A$rowNum", $kotNo);
                $sheet->setCellValue("B$rowNum", $itemName);
                $sheet->setCellValue("C$rowNum", !empty($orderTime) ? date('d-m-Y h:i:s A', strtotime($orderTime)) : '');
                $sheet->setCellValue("D$rowNum", !empty($deleteTime) ? date('d-m-Y h:i:s A', strtotime($deleteTime)) : '');
                $sheet->setCellValue("E$rowNum", number_format($rate, 2));
                $sheet->setCellValue("F$rowNum", $qty);
                $sheet->setCellValue("G$rowNum", number_format($total, 2));
                $sheet->setCellValue("H$rowNum", $tabName);
                $sheet->setCellValue("I$rowNum", $tableNo);
                $sheet->setCellValue("J$rowNum", $comment);
                $sheet->setCellValue("K$rowNum", $username);
                $sheet->setCellValue("L$rowNum", $deleteUser);
                $sheet->setCellValue("M$rowNum", $settleUser);
                $sheet->setCellValue("N$rowNum", $server);
                $sheet->setCellValue("O$rowNum", $customerName);
                $sheet->setCellValue("P$rowNum", $customerMobile);
                $sheet->setCellValue("Q$rowNum", $customerAddress);
                $sheet->setCellValue("R$rowNum", $deleteType);
                $sheet->setCellValue("S$rowNum", $billSettlementStatus);
                $rowNum++;
            }
            
            // Add bill total row
            $sheet->setCellValue("A$rowNum", "Bill Total");
            $sheet->setCellValue("G$rowNum", number_format($billTotal, 2));
            $billTotalStyle = $sheet->getStyle("A$rowNum:S$rowNum");
            $billTotalStyle->getFont()->setBold(true);
            $billTotalStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $billTotalStyle->getFill()->getStartColor()->setRGB('F0F0F0');
            $rowNum++;
        }
        
        foreach(range('A','S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Bill_KOT_Delete_Report.xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function bill_kot_delete_report_pdf() {

        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            $date_range = $this->request->data['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $cashier_id = $this->request->data['cashier'] ?? '';

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            if ($userRole != 'admin') {
                $storeFilter = $cashier_id
                    ? " AND t.store_id=$cashier_id"
                    : " AND t.store_id IN (" . $userLocationId . ")";
            } else {
                $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
            }

            if (!empty($cashier_id)) {
                $store = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $cashier_id],
                    'fields' => ['Location.name']
                ]);
                $store_name = $store['Location']['name'] ?? '';
            } else {
                $store_name = 'All Stores';
            }
        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


            $kotDeleted = $this->Order_orderhistory->query("
                SELECT 
                    DATE(h.created) AS report_date,
                    t.orderno AS bill_no,
                    h.kot_number AS kot_no,
                    h.product_name AS item_name,
                    h.price AS rate,
                    h.quantity AS qty,
                    ROUND(h.price * h.quantity, 2) AS total,
                    h.table_number AS table_no,
                    h.remarks AS comment,
                    (SELECT username FROM users WHERE id = h.cashier_id) AS username,
                    (SELECT username FROM users WHERE id = h.cashier_id) AS delete_user,
                    (SELECT username FROM users WHERE id = t.cashier_id) AS settle_user,
                    (SELECT name FROM locations WHERE id = t.store_id) AS server,
                    COALESCE(t.name,'NA') AS customer_name,
                    COALESCE(t.phone,'NA') AS customer_mobile,
                    COALESCE(t.address,'NA') AS customer_address,
                    'Item Delete' AS delete_type,
                    CASE WHEN EXISTS (SELECT 1 FROM order_payments p WHERE p.reference_id=t.reference_id AND p.status=2)
                        THEN 'Void After Settlement'
                        ELSE 'Void Before Settlement' END AS bill_settlement_status,
                    (SELECT MIN(created) FROM order_orderhistories h2 WHERE h2.reference_id=t.reference_id AND h2.status IN (0,1,2)) AS order_time,
                    h.created AS delete_time,
                    COALESCE((SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2), t.created) AS bill_close_time,
                    '' AS tab_name
                FROM order_orderhistories h
                INNER JOIN order_transactions t ON t.reference_id = h.reference_id
                WHERE h.status = 0
                AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
                $storeFilter
            ");

            
            $billDeleted = $this->Order_transaction->query("
                SELECT 
                    DATE(t.created) AS report_date,
                    t.orderno AS bill_no,
                    '' AS kot_no,
                    '' AS item_name,
                    0 AS rate,
                    0 AS qty,
                    0 AS total,
                    t.table_number AS table_no,
                    t.remarks AS comment,
                    (SELECT username FROM users WHERE id = t.cashier_id) AS username,
                    (SELECT username FROM users WHERE id = t.cashier_id) AS delete_user,
                    '' AS settle_user,
                    (SELECT name FROM locations WHERE id = t.store_id) AS server,
                    COALESCE(t.name,'NA') AS customer_name,
                    COALESCE(t.phone,'NA') AS customer_mobile,
                    COALESCE(t.address,'NA') AS customer_address,
                    'Bill Delete' AS delete_type,
                    'Void Before Settlement' AS bill_settlement_status,
                    t.created AS order_time,
                    t.created AS delete_time,
                    t.created AS bill_close_time,
                    '' AS tab_name
                FROM order_transactions t
                WHERE t.status = 0
                AND t.created BETWEEN '$startDatetime' AND '$endDatetime'
                $storeFilter
            ");

            
            $datas = array_merge($kotDeleted, $billDeleted);
            $this->set(compact('startdate', 'enddate', 'datas', 'store_name'));
            $this->render('bill_kot_delete_report_pdf');
        }
    }

    public function removed_service_charges_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate = date('Y-m-d', strtotime($endDate));

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }
        $users = $this->Location->find('all', ['conditions' => $conditions]);

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $yourStartDateValue = $startDate . ' - ' . $endDate;
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
            $cashier_id = $this->request->data['Search']['cashier'] ?? '';
        } else {
            $cashier_id = '';
        }

        if ($userRole != 'admin') {
            $storeFilter = $cashier_id
                ? " AND t.store_id=$cashier_id"
                : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        // Query for removed service charges (taxes with status = 0)
        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];

        // Find taxes that were removed - taxes with status 0 that exist for transactions
        $removedChargesData = $this->Order_tax->query("
            SELECT 
                DATE(COALESCE(tax.modified, tax.created, t.modified, t.created)) AS report_date,
                t.orderno AS bill_no,
                CONCAT(COALESCE(tax.tax_name, ''), ' @ ', COALESCE(tax.tax_percentage, 0)) AS tax_name,
                ROUND(tax.tax_value, 2) AS tax_amount,
                COALESCE(tax.modified, tax.created, t.modified, t.created) AS removed_at,
                t.created AS bill_print_time,
                COALESCE((SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2), '') AS settled_time,
                CONCAT((SELECT username FROM users WHERE id = t.cashier_id), '(', (SELECT name FROM users WHERE id = t.cashier_id), ')') AS removed_by_user,
                (SELECT username FROM users WHERE id = t.cashier_id) AS username,
                COALESCE((SELECT waiter_name FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), '') AS waiter_username,
                COALESCE(t.name, '') AS customer_name,
                COALESCE(t.phone, '') AS customer_mobile,
                COALESCE(t.remarks, '') AS edit_bill_comment,
                tax.reference_id,
                tax.tax_id
            FROM order_tax tax
            INNER JOIN order_transactions t ON t.reference_id = tax.reference_id
            WHERE tax.status = 0
            AND tax.tax_name IS NOT NULL
            AND tax.tax_name != ''
            AND COALESCE(tax.modified, tax.created, t.modified, t.created) BETWEEN '$startDatetime' AND '$endDatetime'
            $storeFilter
            GROUP BY tax.id, tax.reference_id, tax.tax_id
            ORDER BY report_date DESC, removed_at DESC
        ");

        $this->set(compact('users', 'yourStartDateValue', 'removedChargesData', 'cashier_id'));
    }

    public function removed_service_charges_report_export() {
        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));        
        $excelDateRange = $startdate. ' ' .$enddate;

        $cashier_id = $_GET['cashier'] ?? '';

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if($userRole!='admin' ){
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : " AND t.store_id IN (" . $userLocationId . ")";
        }else{
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        

        $storeName = '';
        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];

        if ($cashier_id) {
            $storeData = $this->Order_orderhistory->query("SELECT name FROM locations WHERE id = $cashier_id LIMIT 1");
            $storeName = $storeData ? $storeData[0]['locations']['name'] : '';
        } 
        else{
            $storeName = 'All Stores';
        }

        $rows = $this->Order_tax->query("
            SELECT 
                DATE(COALESCE(tax.modified, tax.created, t.modified, t.created)) AS report_date,
                t.orderno AS bill_no,
                CONCAT(COALESCE(tax.tax_name, ''), ' @ ', COALESCE(tax.tax_percentage, 0)) AS tax_name,
                ROUND(tax.tax_value, 2) AS tax_amount,
                COALESCE(tax.modified, tax.created, t.modified, t.created) AS removed_at,
                t.created AS bill_print_time,
                COALESCE((SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2), '') AS settled_time,
                CONCAT((SELECT username FROM users WHERE id = t.cashier_id), '(', (SELECT name FROM users WHERE id = t.cashier_id), ')') AS removed_by_user,
                (SELECT username FROM users WHERE id = t.cashier_id) AS username,
                COALESCE((SELECT waiter_name FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), '') AS waiter_username,
                COALESCE(t.name, '') AS customer_name,
                COALESCE(t.phone, '') AS customer_mobile,
                COALESCE(t.remarks, '') AS edit_bill_comment,
                tax.reference_id,
                tax.tax_id
            FROM order_tax tax
            INNER JOIN order_transactions t ON t.reference_id = tax.reference_id
            WHERE tax.status = 0
            AND tax.tax_name IS NOT NULL
            AND tax.tax_name != ''
            AND COALESCE(tax.modified, tax.created, t.modified, t.created) BETWEEN '$startDatetime' AND '$endDatetime'
            $storeFilter
            GROUP BY tax.id, tax.reference_id, tax.tax_id
            ORDER BY report_date DESC, removed_at DESC
        ");

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Removed Service Charges Report");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Removed_Service_Charges_Report');

        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'Removed_Taxes/Charges_Report (' . $excelDateRange . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));

        // Headers
        $headers = array(
            'Date','Bill Number','Tax Name','Tax Amount','Removed At','Bill Print Time',
            'Settled Time','Removed By(User Id)','Username','Waiter Username',
            'Customer Name','Customer Mobile','Edit Bill Comment'
        );
        $sheet->fromArray($headers, null, 'A5');

        // Style headers
        $headerStyle = $sheet->getStyle('A5:M5');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('E0E0E0');

        $rowNum = 6;
        $currentDate = '';
        $dayTotalAmount = 0;
        $dayBillCount = 0;
        $processedBills = []; 

        foreach($rows as $r){
            $row = $r[0];
            $t = $r['t'];
            $billNo = $t['bill_no'];
            $reportDate = $row['report_date'];
            $billKey = $reportDate . '_' . $billNo;
            
            if($currentDate != '' && $currentDate != $reportDate) {
                
                $sheet->setCellValue("A$rowNum", "Day Total");
                $sheet->setCellValue("B$rowNum", "Bill Count: $dayBillCount");
                $sheet->setCellValue("D$rowNum", number_format($dayTotalAmount, 2));
                $dayTotalStyle = $sheet->getStyle("A$rowNum:D$rowNum");
                $dayTotalStyle->getFont()->setBold(true);
                $dayTotalStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $dayTotalStyle->getFill()->getStartColor()->setRGB('F0F0F0');
                $rowNum++;
                
                $dayTotalAmount = 0;
                $dayBillCount = 0;
                $processedBills = [];
            }
            
            $currentDate = $reportDate;
            $dayTotalAmount += (float)$row['tax_amount'];
            
            if (!in_array($billKey, $processedBills)) {
                $dayBillCount++;
                $processedBills[] = $billKey;
            }
            
            $sheet->setCellValue("A$rowNum", $reportDate);
            $sheet->setCellValue("B$rowNum", $billNo);
            $sheet->setCellValue("C$rowNum", $row['tax_name']);
            $sheet->setCellValue("D$rowNum", number_format($row['tax_amount'], 2));
            $sheet->setCellValue("E$rowNum", date('H:i:s A', strtotime($row['removed_at'])));
            $sheet->setCellValue("F$rowNum", date('H:i:s A', strtotime($t['bill_print_time'])));
            $sheet->setCellValue("G$rowNum", !empty($row['settled_time']) ? date('H:i:s A', strtotime($row['settled_time'])) : '-');
            $sheet->setCellValue("H$rowNum", $row['removed_by_user']);
            $sheet->setCellValue("I$rowNum", $row['username']);
            $sheet->setCellValue("J$rowNum", $row['waiter_username'] ?: '-');
            $sheet->setCellValue("K$rowNum", $row['customer_name'] ?: '-');
            $sheet->setCellValue("L$rowNum", $row['customer_mobile'] ?: '-');
            $sheet->setCellValue("M$rowNum", $row['edit_bill_comment'] ?: '-');
            $rowNum++;
        }
        
        if($dayBillCount > 0) {
            $sheet->setCellValue("A$rowNum", "Day Total");
            $sheet->setCellValue("B$rowNum", "Bill Count: $dayBillCount");
            $sheet->setCellValue("D$rowNum", number_format($dayTotalAmount, 2));
            $dayTotalStyle = $sheet->getStyle("A$rowNum:D$rowNum");
            $dayTotalStyle->getFont()->setBold(true);
            $dayTotalStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $dayTotalStyle->getFill()->getStartColor()->setRGB('F0F0F0');
            $rowNum++;
        }
        
        $sheet->getStyle('A5:M5')->getFont()->setBold(true);

        foreach(range('A','M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Removed_Service_Charges_Report(' . $startdate . '--' . $enddate . ').xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function removed_service_charges_report_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            
            $date_range = $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $cashier_id = $this->request->data['cashier'] ?? '';

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            if ($userRole != 'admin') {
                $storeFilter = $cashier_id
                    ? " AND t.store_id=$cashier_id"
                    : " AND t.store_id IN (" . $userLocationId . ")";
            } else {
                $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
            }

            if (!empty($cashier_id)) {
                $store = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $cashier_id],
                    'fields' => ['Location.name']
                ]);
                $store_name = $store['Location']['name'] ?? '';
            } else {
                $store_name = 'All Stores';
            }
        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


            $rows = $this->Order_tax->query("
                SELECT 
                    DATE(COALESCE(tax.modified, tax.created, t.modified, t.created)) AS report_date,
                    t.orderno AS bill_no,
                    CONCAT(COALESCE(tax.tax_name, ''), ' @ ', COALESCE(tax.tax_percentage, 0)) AS tax_name,
                    ROUND(tax.tax_value, 2) AS tax_amount,
                    COALESCE(tax.modified, tax.created, t.modified, t.created) AS removed_at,
                    t.created AS bill_print_time,
                    COALESCE((SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2), '') AS settled_time,
                    CONCAT((SELECT username FROM users WHERE id = t.cashier_id), '(', (SELECT name FROM users WHERE id = t.cashier_id), ')') AS removed_by_user,
                    (SELECT username FROM users WHERE id = t.cashier_id) AS username,
                    COALESCE((SELECT waiter_name FROM order_tables WHERE reference_id = t.reference_id LIMIT 1), '') AS waiter_username,
                    COALESCE(t.name, '') AS customer_name,
                    COALESCE(t.phone, '') AS customer_mobile,
                    COALESCE(t.remarks, '') AS edit_bill_comment,
                    tax.reference_id,
                    tax.tax_id
                FROM order_tax tax
                INNER JOIN order_transactions t ON t.reference_id = tax.reference_id
                WHERE tax.status = 0
                AND tax.tax_name IS NOT NULL
                AND tax.tax_name != ''
                AND COALESCE(tax.modified, tax.created, t.modified, t.created) BETWEEN '$startDatetime' AND '$endDatetime'
                $storeFilter
                GROUP BY tax.id, tax.reference_id, tax.tax_id
                ORDER BY report_date DESC, removed_at DESC
            ");

            $datas = [];
            $dayTotals = [];
            
            foreach ($rows as $r) {
                $row = $r[0];
                $tax = isset($r['tax']) ? $r['tax'] : [];
                $t = isset($r['t']) ? $r['t'] : [];
                $ot = isset($r['ot']) ? $r['ot'] : [];
                
                $reportDate = $row['report_date'];
                
                if (!isset($dayTotals[$reportDate])) {
                    $dayTotals[$reportDate] = ['count' => 0, 'amount' => 0];
                }
                
                $dayTotals[$reportDate]['count']++;
                $dayTotals[$reportDate]['amount'] += (float)$row['tax_amount'];
                
                $datas[] = [
                    'report_date' => $reportDate,
                    'bill_no' => $t['bill_no'] ?? $row['bill_no'],
                    'tax_name' => $row['tax_name'],
                    'tax_amount' => $row['tax_amount'],
                    'removed_at' => $row['removed_at'],
                    'bill_print_time' => $row['bill_print_time'],
                    'settled_time' => $row['settled_time'],
                    'removed_by_user' => $row['removed_by_user'],
                    'username' => $row['username'],
                    'waiter_username' => $row['waiter_username'],
                    'customer_name' => $row['customer_name'],
                    'customer_mobile' => $row['customer_mobile'],
                    'edit_bill_comment' => $row['edit_bill_comment']
                ];
            }

            $this->set(compact('startdate', 'enddate', 'datas', 'store_name', 'dayTotals'));
            $this->render('removed_service_charges_report_pdf');
        }
    }

    private function _group_kot_rows($rows) {
        $grouped = [];
        foreach ($rows as $r) {
            $d = $r[0];
            $t = $r['t'];
            $h = $r['h'];

            $billKey = $t['bill_serial_number'];

            if (!isset($grouped[$billKey])) {
                $grouped[$billKey] = [
                    'report_date'        => $d['report_date'],
                    'bill_serial_number' => $t['bill_serial_number'],
                    'edit_bill_comment'  => $t['edit_bill_comment'],
                    'kot_numbers'   => [],
                    'kot_dates'     => [],
                    'kot_times'     => [],
                    'kot_comments'  => [],
                    'item_names'    => [],
                    'rates'         => [],
                    'qtys'          => [],
                    'amounts'       => [],
                    'item_comments' => [],
                    'total_amount'  => 0,
                ];
            }

            $grouped[$billKey]['kot_numbers'][]   = $h['kot_number'];
            $grouped[$billKey]['kot_dates'][]     = $d['kot_date'];
            $grouped[$billKey]['kot_times'][]     = $d['kot_time'];
            $grouped[$billKey]['kot_comments'][]  = $d['kot_comment'];
            $grouped[$billKey]['item_names'][]    = $h['item_name'];
            $grouped[$billKey]['rates'][]         = number_format($h['rate'], 2);
            $grouped[$billKey]['qtys'][]          = $h['qty'];
            $grouped[$billKey]['amounts'][]       = number_format($d['amount'], 2);
            $grouped[$billKey]['item_comments'][] = ($d['item_comment'] !== '' ? $d['item_comment'] : '-');
            $grouped[$billKey]['total_amount']   += (float)$d['amount'];
        }
        return array_values($grouped);
    }

    public function kot_detail_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate = date('Y-m-d', strtotime($endDate));

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }
        $users = $this->Location->find('all', ['conditions' => $conditions]);

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $yourStartDateValue = $startDate . ' - ' . $endDate;
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
            $cashier_id = $this->request->data['Search']['cashier'] ?? '';
        } else {
            $cashier_id = '';
        }

        if ($userRole != 'admin') {
            $storeFilter = $cashier_id
                ? " AND t.store_id=$cashier_id"
                : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }
        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];
        // KOT line-items printed (status = 2) with transactional context
        $rows = $this->Order_orderhistory->query("
            SELECT 
                DATE(h.created) AS report_date,
                t.orderno AS bill_serial_number,
                t.remarks AS edit_bill_comment,
                h.kot_number AS kot_number,
                DATE(h.created) AS kot_date,
                TIME(h.created) AS kot_time,
                'NA' AS kot_comment,
                h.product_name AS item_name,
                h.price AS rate,
                h.quantity AS qty,
                ROUND(h.price * h.quantity, 2) AS amount,
                COALESCE(h.remarks, '') AS item_comment
            FROM order_orderhistories h
            INNER JOIN order_transactions t 
                ON t.reference_id = h.reference_id AND t.status != 0
            WHERE h.status != 0
              AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilter
            ORDER BY h.created ASC, t.orderno ASC, h.kot_number ASC, h.id ASC
        ");

        $rows = $this->_group_kot_rows($rows);

        $this->set(compact('users', 'yourStartDateValue', 'rows', 'cashier_id'));
    }

    public function kot_detail_report_export() {
        $date_range = $_GET['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        $excelDateRange = $startdate . ' ' . $enddate;
        $cashier_id = $_GET['cashier'] ?? '';

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if ($userRole != 'admin') {
            $storeFilter = $cashier_id
                ? " AND t.store_id=$cashier_id"
                : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        $storeName = '';
        if ($cashier_id) {
            $storeData = $this->Order_orderhistory->query("SELECT name FROM locations WHERE id = $cashier_id LIMIT 1");
            $storeName = $storeData ? $storeData[0]['locations']['name'] : '';
        } else {
            $storeName = 'All Stores';
        }
        $location_id = !empty($cashier_id) ? $cashier_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];

        $rows = $this->Order_orderhistory->query("
            SELECT 
                DATE(h.created) AS report_date,
                t.orderno AS bill_serial_number,
                t.remarks AS edit_bill_comment,
                h.kot_number AS kot_number,
                DATE(h.created) AS kot_date,
                TIME(h.created) AS kot_time,
                'NA' AS kot_comment,
                h.product_name AS item_name,
                h.price AS rate,
                h.quantity AS qty,
                ROUND(h.price * h.quantity, 2) AS amount,
                COALESCE(h.remarks, '') AS item_comment
            FROM order_orderhistories h
            INNER JOIN order_transactions t 
                ON t.reference_id = h.reference_id AND t.status != 0
            WHERE h.status != 0
              AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilter
            ORDER BY h.created ASC, t.orderno ASC, h.kot_number ASC, h.id ASC
        ");

        $rows = $this->_group_kot_rows($rows);

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("KOT Detail Report");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('KOT_Detail_Report');

        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'KOT Detail Report (' . $excelDateRange . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));

        $headers = array(
            'Date','Bill Serial Number','Edit Bill Comment','KOT Number','KOT Date','KOT Time',
            'KOT Comment','Item Name','Rate','Quantity','Amount','Item Comment'
        );
        $sheet->fromArray($headers, null, 'A5');

        $rowNum = 6;
        $currentDate = '';
        $dayTotalAmount = 0;
        $grandTotalAmount = 0;

        foreach ($rows as $bill) {
            $reportDate = $bill['report_date'];

            if ($currentDate !== '' && $currentDate !== $reportDate) {
                $sheet->setCellValue("A$rowNum", "Day Total");
                $sheet->setCellValue("K$rowNum", number_format($dayTotalAmount, 2));
                $dayTotalStyle = $sheet->getStyle("A$rowNum:K$rowNum");
                $dayTotalStyle->getFont()->setBold(true);
                $rowNum++;
                $dayTotalAmount = 0;
            }

            $currentDate = $reportDate;
            $dayTotalAmount   += $bill['total_amount'];
            $grandTotalAmount += $bill['total_amount'];

            $sheet->setCellValue("A$rowNum", $bill['report_date']);
            $sheet->setCellValue("B$rowNum", $bill['bill_serial_number']);
            $sheet->setCellValue("C$rowNum", $bill['edit_bill_comment']);
            $sheet->setCellValue("D$rowNum", implode("\n", $bill['kot_numbers']));
            $sheet->setCellValue("E$rowNum", implode("\n", $bill['kot_dates']));
            $sheet->setCellValue("F$rowNum", implode("\n", $bill['kot_times']));
            $sheet->setCellValue("G$rowNum", implode("\n", $bill['kot_comments']));
            $sheet->setCellValue("H$rowNum", implode("\n", $bill['item_names']));
            $sheet->setCellValue("I$rowNum", implode("\n", $bill['rates']));
            $sheet->setCellValue("J$rowNum", implode("\n", $bill['qtys']));
            $sheet->setCellValue("K$rowNum", implode("\n", $bill['amounts']) . "\nTotal: " . number_format($bill['total_amount'], 2));
            $sheet->setCellValue("L$rowNum", implode("\n", $bill['item_comments']));

            $sheet->getStyle("A$rowNum:L$rowNum")->getAlignment()->setWrapText(true);
            $rowNum++;
        }

        if ($currentDate !== '') {
            $sheet->setCellValue("A$rowNum", "Day Total");
            $sheet->setCellValue("K$rowNum", number_format($dayTotalAmount, 2));
            $dayTotalStyle = $sheet->getStyle("A$rowNum:K$rowNum");
            $dayTotalStyle->getFont()->setBold(true);
            $rowNum++;
        }

        $sheet->setCellValue("A$rowNum", "Grand Total");
        $sheet->setCellValue("K$rowNum", number_format($grandTotalAmount, 2));
        $grandTotalStyle = $sheet->getStyle("A$rowNum:K$rowNum");
        $grandTotalStyle->getFont()->setBold(true);

        $sheet->getStyle('A5:L5')->getFont()->setBold(true);
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="KOT_Detail_Report(' . $startdate . '--' . $enddate . ').xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }
    
    public function kot_detail_report_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            $date_range = $this->request->data['sdate'] ?? (date('m/d/Y') . ' - ' . date('m/d/Y'));
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $cashier_id = $this->request->data['cashier'] ?? '';

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            if ($userRole != 'admin') {
                $storeFilter = $cashier_id
                    ? " AND t.store_id=$cashier_id"
                    : " AND t.store_id IN (" . $userLocationId . ")";
            } else {
                $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
            }

            if (!empty($cashier_id)) {
                $store = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $cashier_id],
                    'fields' => ['Location.name']
                ]);
                $store_name = $store['Location']['name'] ?? '';
            } else {
                $store_name = 'All Stores';
            }
            $location_id = !empty($cashier_id) ? $cashier_id : null;
            $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
            $startDatetime = $shiftRange['startDatetime'];
            $endDatetime   = $shiftRange['endDatetime'];
            $rows = $this->Order_orderhistory->query("
                SELECT 
                    DATE(h.created) AS report_date,
                    t.orderno AS bill_serial_number,
                    t.remarks AS edit_bill_comment,
                    h.kot_number AS kot_number,
                    DATE(h.created) AS kot_date,
                    TIME(h.created) AS kot_time,
                    'NA' AS kot_comment,
                    h.product_name AS item_name,
                    h.price AS rate,
                    h.quantity AS qty,
                    ROUND(h.price * h.quantity, 2) AS amount,
                    COALESCE(h.remarks, '') AS item_comment
                FROM order_orderhistories h
                INNER JOIN order_transactions t 
                    ON t.reference_id = h.reference_id AND t.status != 0
                WHERE h.status != 0
                  AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $storeFilter
                ORDER BY h.created ASC, t.orderno ASC, h.kot_number ASC, h.id ASC
            ");

            $grouped = $this->_group_kot_rows($rows);

            $datas = [];
            foreach ($grouped as $bill) {
                $datas[] = [
                    'report_date'        => $bill['report_date'],
                    'bill_serial_number' => $bill['bill_serial_number'],
                    'edit_bill_comment'  => $bill['edit_bill_comment'],
                    'kot_number'   => implode('<br>', $bill['kot_numbers']),
                    'kot_date'     => implode('<br>', $bill['kot_dates']),
                    'kot_time'     => implode('<br>', $bill['kot_times']),
                    'kot_comment'  => implode('<br>', $bill['kot_comments']),
                    'item_name'    => implode('<br>', $bill['item_names']),
                    'rate'         => implode('<br>', $bill['rates']),
                    'qty'          => implode('<br>', $bill['qtys']),
                    'amount'       => implode('<br>', $bill['amounts']) . '<br><b>Total: ' . number_format($bill['total_amount'], 2) . '</b>',
                    'item_comment' => implode('<br>', $bill['item_comments']),
                ];
            }

            $this->set(compact('startdate', 'enddate', 'datas', 'store_name'));
            $this->render('kot_detail_report_pdf');
        }
    }

    public function bill_item_detail_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate = date('Y-m-d', strtotime($endDate));

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            if (!empty($userLocationIds)) {
                $conditions['id IN'] = array_map('trim', $userLocationIds);
            }
        }

        $users = $this->Location->find('all', ['conditions' => $conditions]);

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $yourStartDateValue = $startDate . ' - ' . $endDate;
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
            $cashier_id = $this->request->data['Search']['cashier'] ?? '';
        } else {
            $cashier_id = '';
        }

        if ($userRole != 'admin') {
            if (!empty($cashier_id)) {
                $storeFilter = " AND t.store_id=$cashier_id";
            } elseif (!empty($userLocationId)) {
                $storeFilter = " AND t.store_id IN (" . $userLocationId . ")";
            } else {
                $storeFilter = '';
            }
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // Build dynamic tax columns (same logic as export/pdf)
        $taxesDistinct = $this->Order_tax->query("
            SELECT DISTINCT tax_name, tax_percentage
            FROM order_tax ot
            INNER JOIN order_orderhistories h ON h.reference_id = ot.reference_id
            INNER JOIN order_transactions t ON t.reference_id = ot.reference_id
            WHERE h.status = 2
            AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
            AND ot.status = 2
            $storeFilter
        ");
        $taxColumns = [];
        foreach ($taxesDistinct as $td) {
            $name = $td['ot']['tax_name'] ?? $td[0]['tax_name'] ?? '';
            $perc = $td['ot']['tax_percentage'] ?? $td[0]['tax_percentage'] ?? '';
            if ($name !== '') {
                $taxColumns[] = $name . "(" . $perc . "%)";
            }
        }
        sort($taxColumns);

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $kdsPrinterCond = !empty($cashier_id)
            ? "kds = h.kds AND store_id = h.store_id"
            : "kds = h.kds";

        $rows = $this->Order_orderhistory->query("
            SELECT 
                COALESCE(NULLIF(TRIM(loc.print_line3), ''), 'NA') AS state,
                loc.name AS deployment,
                t.orderno AS bill_no,
                t.reference_id AS order_id,
                t.tokenno AS day_serial,
                DATE(t.created) AS bill_date,
                '' AS tab_name,
                h.table_number AS table_no,
                (SELECT MIN(created) FROM order_orderhistories ho WHERE ho.reference_id = t.reference_id AND ho.status IN (1,2)) AS open_time,
                COALESCE(pay.settled_time, t.created) AS close_time,
                h.category_name AS category,
                h.product_name AS item_name,
                h.product_id AS item_number,
                (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS item_section,
                CASE 
                    WHEN cp.food_type = 0 THEN 'Veg'
                    WHEN cp.food_type = 1 THEN 'Non Veg'
                    WHEN cp.food_type = 2 THEN 'Egg'
                    ELSE 'NA'
                END AS item_classification,
                ROUND(h.price, 2) AS rate,
                h.quantity AS qty,
                ROUND(h.price * h.quantity, 2) AS amount,
                ROUND(h.discount, 2) AS discount_amount,
                CASE WHEN h.price * h.quantity > 0 THEN ROUND((h.discount / (h.price * h.quantity)) * 100, 2) ELSE 0 END AS discount_percent,
                ROUND(COALESCE(tax.taxable_price, 0), 2) AS taxable_amount,
                ROUND(COALESCE(tax.total_tax_value, 0), 2) AS gst_total,
                ROUND(COALESCE(tax.sgst_amount, 0), 2) AS sgst_amount,
                ROUND(COALESCE(tax.cgst_amount, 0), 2) AS cgst_amount,
                COALESCE(ROUND(COALESCE(t.servicecharge, 0) / NULLIF(ic.item_count, 0), 2), 0) AS service_charge_per_item,
                0 AS service_charge_tax,
                ROUND(
                    (h.price * h.quantity) - h.discount
                    + COALESCE(tax.total_tax_value, 0)
                    + COALESCE(ROUND(COALESCE(t.servicecharge, 0) / NULLIF(ic.item_count, 0), 2), 0),
                    2
                ) AS net_amount,
                'POS' AS source,
                COALESCE(taxmap.tax_map, '') AS tax_map
            FROM order_orderhistories h
            INNER JOIN order_transactions t ON t.reference_id = h.reference_id AND t.status = 2
            LEFT JOIN locations loc ON loc.id = t.store_id
            LEFT JOIN catalog_product cp ON cp.id = h.product_id
            LEFT JOIN (
                SELECT 
                    reference_id,
                    product_id,
                    MAX(taxable_price) AS taxable_price,
                    SUM(tax_value) AS total_tax_value,
                    SUM(CASE WHEN tax_name LIKE 'SGST%' THEN tax_value ELSE 0 END) AS sgst_amount,
                    SUM(CASE WHEN tax_name LIKE 'CGST%' THEN tax_value ELSE 0 END) AS cgst_amount
                FROM order_tax
                WHERE status != 0
                GROUP BY reference_id, product_id
            ) tax ON tax.reference_id = h.reference_id AND tax.product_id = h.product_id
            LEFT JOIN (
                SELECT 
                    reference_id,
                    MIN(created) AS settled_time
                FROM order_payments
                WHERE status = 2
                GROUP BY reference_id
            ) pay ON pay.reference_id = h.reference_id
            LEFT JOIN (
                SELECT reference_id, COUNT(*) AS item_count
                FROM order_orderhistories
                WHERE status = 2
                GROUP BY reference_id
            ) ic ON ic.reference_id = h.reference_id
            LEFT JOIN (
                SELECT reference_id, product_id, GROUP_CONCAT(CONCAT(tax_name, '|', tax_percentage, '|', ROUND(tax_value,2)) SEPARATOR '||') AS tax_map
                FROM order_tax
                WHERE status = 2
                AND created BETWEEN '$startDatetime' AND '$endDatetime'
                GROUP BY reference_id, product_id
            ) taxmap ON taxmap.reference_id = h.reference_id AND taxmap.product_id = h.product_id
            WHERE h.status = 2
            AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
            $storeFilter
            ORDER BY t.created ASC, t.orderno ASC, h.id ASC
        ");

        $this->set(compact('users', 'yourStartDateValue', 'rows', 'cashier_id', 'taxColumns'));
    }

    public function bill_item_detail_report_export() {

        $date_range = $_GET['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        $excelDateRange = $startdate . ' ' . $enddate;

        $cashier_id = $_GET['cashier'] ?? '';

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if ($userRole != 'admin') {
            if (!empty($cashier_id)) {
                $storeFilter = " AND t.store_id=$cashier_id";
            } elseif (!empty($userLocationId)) {
                $storeFilter = " AND t.store_id IN (" . $userLocationId . ")";
            } else {
                $storeFilter = '';
            }
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        $storeName = '';
        if (!empty($cashier_id)) {
            $storeData = $this->Location->find('first', [
                'conditions' => ['Location.id' => $cashier_id],
                'fields' => ['Location.name']
            ]);
            $storeName = $storeData['Location']['name'] ?? '';
        } else {
            $storeName = 'All Stores';
        }

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $taxesDistinct = $this->Order_tax->query("
            SELECT DISTINCT tax_name, tax_percentage
            FROM order_tax ot
            INNER JOIN order_orderhistories h ON h.reference_id = ot.reference_id
            INNER JOIN order_transactions t ON t.reference_id = ot.reference_id
            WHERE h.status = 2
            AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
            AND ot.status = 2
            $storeFilter
        ");
        $taxColumns = [];
        foreach ($taxesDistinct as $td) {
            $name = $td['ot']['tax_name'] ?? $td[0]['tax_name'] ?? '';
            $perc = $td['ot']['tax_percentage'] ?? $td[0]['tax_percentage'] ?? '';
            if ($name !== '') {
                $taxColumns[] = $name . "(" . $perc . "%)";
            }
        }
        sort($taxColumns);

        $kdsPrinterCond = !empty($cashier_id)
            ? "kds = h.kds AND store_id = h.store_id"
            : "kds = h.kds";

        $rows = $this->Order_orderhistory->query("
            SELECT 
                COALESCE(NULLIF(TRIM(loc.print_line3), ''), 'NA') AS state,
                loc.name AS deployment,
                t.orderno AS bill_no,
                t.reference_id AS order_id,
                t.tokenno AS day_serial,
                DATE(t.created) AS bill_date,
                '' AS tab_name,
                t.table_number AS table_no,
                (SELECT MIN(created) FROM order_orderhistories ho WHERE ho.reference_id = t.reference_id AND ho.status IN (1,2)) AS open_time,
                COALESCE(pay.settled_time, t.created) AS close_time,
                h.category_name AS category,
                h.product_name AS item_name,
                h.product_id AS item_number,
                (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS item_section,
                CASE 
                    WHEN cp.food_type = 0 THEN 'Veg'
                    WHEN cp.food_type = 1 THEN 'Non Veg'
                    WHEN cp.food_type = 2 THEN 'Egg'
                    ELSE 'NA'
                END AS item_classification,
                ROUND(h.price, 2) AS rate,
                h.quantity AS qty,
                ROUND(h.price * h.quantity, 2) AS amount,
                ROUND(h.discount, 2) AS discount_amount,
                CASE WHEN h.price * h.quantity > 0 THEN ROUND((h.discount / (h.price * h.quantity)) * 100, 2) ELSE 0 END AS discount_percent,
                ROUND(COALESCE(tax.taxable_price, 0), 2) AS taxable_amount,
                ROUND(COALESCE(tax.total_tax_value, 0), 2) AS gst_total,
                ROUND(COALESCE(tax.sgst_amount, 0), 2) AS sgst_amount,
                ROUND(COALESCE(tax.cgst_amount, 0), 2) AS cgst_amount,
                COALESCE(ROUND(COALESCE(t.servicecharge, 0) / NULLIF(ic.item_count, 0), 2), 0) AS service_charge_per_item,
                0 AS service_charge_tax,
                ROUND(
                    (h.price * h.quantity) - h.discount
                    + COALESCE(tax.total_tax_value, 0)
                    + COALESCE(ROUND(COALESCE(t.servicecharge, 0) / NULLIF(ic.item_count, 0), 2), 0),
                    2
                ) AS net_amount,
                'POS' AS source,
                COALESCE(taxmap.tax_map, '') AS tax_map
            FROM order_orderhistories h
            INNER JOIN order_transactions t ON t.reference_id = h.reference_id AND t.status = 2
            LEFT JOIN locations loc ON loc.id = t.store_id
            LEFT JOIN catalog_product cp ON cp.id = h.product_id
            LEFT JOIN (
                SELECT 
                    reference_id,
                    product_id,
                    MAX(taxable_price) AS taxable_price,
                    SUM(tax_value) AS total_tax_value,
                    SUM(CASE WHEN tax_name LIKE 'SGST%' THEN tax_value ELSE 0 END) AS sgst_amount,
                    SUM(CASE WHEN tax_name LIKE 'CGST%' THEN tax_value ELSE 0 END) AS cgst_amount
                FROM order_tax
                WHERE status != 0
                GROUP BY reference_id, product_id
            ) tax ON tax.reference_id = h.reference_id AND tax.product_id = h.product_id
            LEFT JOIN (
                SELECT 
                    reference_id,
                    MIN(created) AS settled_time
                FROM order_payments
                WHERE status = 2
                GROUP BY reference_id
            ) pay ON pay.reference_id = h.reference_id
            LEFT JOIN (
                SELECT reference_id, COUNT(*) AS item_count
                FROM order_orderhistories
                WHERE status = 2
                GROUP BY reference_id
            ) ic ON ic.reference_id = h.reference_id
            LEFT JOIN (
                SELECT reference_id, product_id, GROUP_CONCAT(CONCAT(tax_name, '|', tax_percentage, '|', ROUND(tax_value,2)) SEPARATOR '||') AS tax_map
                FROM order_tax
                WHERE status = 2
                AND created BETWEEN '$startDatetime' AND '$endDatetime'
                GROUP BY reference_id, product_id
            ) taxmap ON taxmap.reference_id = h.reference_id AND taxmap.product_id = h.product_id
            WHERE h.status = 2
            AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
            $storeFilter
            ORDER BY t.created ASC, t.orderno ASC, h.id ASC
        ");

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Bill Item Detail Report");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Bill_Item_Detail_Report');

        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'Bill Item Detail Report (' . $excelDateRange . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));

        $baseHeaders = array(
            'State', 'Deployment', 'Bill Number', 'Order Id', 'Day Serial', 'Date',
            'Tab', 'Table No', 'Open Time', 'Close Time', 'Category', 'Item Name',
            'Item Number', 'Item Section', 'Item Classification', 'Rate', 'Qty',
            'Amount', 'Discount Amount', 'Discount %', 'Taxable Amount', 'Net Amount', 'Source'
        );

        $headers = array_merge($baseHeaders, $taxColumns);
        $sheet->fromArray($headers, null, 'A5');

        // Initialize totals for numeric columns
        $totals = [
            'amount'          => 0.0,
            'discount_amount' => 0.0,
            'taxable_amount'  => 0.0,
            'net_amount'      => 0.0,
        ];
        $taxTotals = array_fill_keys($taxColumns, 0.0);

        $startDataRow = 6;
        $rowNum = $startDataRow;
        foreach ($rows as $r) {
            $row = $r[0];
            $loc = $r['loc'];
            $t = $r['t'];
            $h = $r['h'];

            $col = 0;
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['state']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $loc['deployment']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $t['bill_no']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $t['order_id']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $t['day_serial']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['bill_date']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $r['tab_name']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $t['table_no']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, !empty($row['open_time']) ? date('H:i:s', strtotime($row['open_time'])) : '-');
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, !empty($row['close_time']) ? date('H:i:s', strtotime($row['close_time'])) : '-');
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $h['category']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $h['item_name']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $h['item_number']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['item_section']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['item_classification']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['rate'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $h['qty']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['amount'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['discount_amount'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['discount_percent'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['taxable_amount'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['net_amount'], 2));
            $sheet->setCellValueExplicitByColumnAndRow($col++, $rowNum, $row['source'], PHPExcel_Cell_DataType::TYPE_STRING);

            // Accumulate base totals
            $totals['amount']          += (float)$row['amount'];
            $totals['discount_amount'] += (float)$row['discount_amount'];
            $totals['taxable_amount']  += (float)$row['taxable_amount'];
            $totals['net_amount']      += (float)$row['net_amount'];

            // Build dynamic tax values per row
            $taxValues = array_fill_keys($taxColumns, 0.00);
            $tax_map = $row['tax_map'] ?? '';
            if ($tax_map !== '') {
                $pieces = explode('||', $tax_map);
                foreach ($pieces as $pc) {
                    if (trim($pc) === '') {
                        continue;
                    }
                    $parts = explode('|', $pc);
                    if (count($parts) >= 3) {
                        $tname = $parts[0];
                        $tperc = $parts[1];
                        $tval  = (float)$parts[2];
                        $key = $tname . "(" . $tperc . "%)";
                        if (array_key_exists($key, $taxValues)) {
                            $taxValues[$key] += round($tval, 2);
                        }
                    }
                }
            }

            // Write tax columns and accumulate totals
            foreach ($taxColumns as $tc) {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($taxValues[$tc], 2));
                $taxTotals[$tc] += (float)$taxValues[$tc];
            }

            $rowNum++;
        }

        // Header styling and autosize
        $lastColIndex = count($headers) - 1;
        $lastHeaderColumn = PHPExcel_Cell::stringFromColumnIndex($lastColIndex);
        $sheet->getStyle('A5:' . $lastHeaderColumn . '5')->getFont()->setBold(true);
        for ($i = 0; $i <= $lastColIndex; $i++) {
            $sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($i))->setAutoSize(true);
        }

        // Append grand total row if there is data
        if (!empty($rows)) {
            $totalRow = $rowNum + 1;
            $col = 0;
            // First 17 columns are descriptive (State .. Qty), align "Grand Totals" under the first numeric block
            $sheet->setCellValueByColumnAndRow($col, $totalRow, 'Grand Totals');
            // Merge from first cell through the column before Amount (index 0..16)
            $sheet->mergeCellsByColumnAndRow($col, $totalRow, 16, $totalRow);
            $col = 17; // Amount column index

            // Amount
            $sheet->setCellValueByColumnAndRow($col++, $totalRow, number_format($totals['amount'], 2));
            // Discount Amount
            $sheet->setCellValueByColumnAndRow($col++, $totalRow, number_format($totals['discount_amount'], 2));
            // Discount % (leave blank)
            $sheet->setCellValueByColumnAndRow($col++, $totalRow, '');
            // Taxable Amount
            $sheet->setCellValueByColumnAndRow($col++, $totalRow, number_format($totals['taxable_amount'], 2));
            // Net Amount
            $sheet->setCellValueByColumnAndRow($col++, $totalRow, number_format($totals['net_amount'], 2));
            // Source (blank)
            $sheet->setCellValueByColumnAndRow($col++, $totalRow, '');

            // Dynamic tax grand totals
            foreach ($taxColumns as $tc) {
                $sheet->setCellValueByColumnAndRow($col++, $totalRow, number_format($taxTotals[$tc], 2));
            }

            // Style the grand total row
            $sheet->getStyle('A' . $totalRow . ':' . $sheet->getCellByColumnAndRow($col - 1, $totalRow)->getColumn() . $totalRow)
                ->getFont()->setBold(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Bill_Item_Detail_Report(' . $startdate . '--' . $enddate . ').xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function bill_item_detail_report_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            $date_range = $this->request->data['sdate'] ?? (date('m/d/Y') . ' - ' . date('m/d/Y'));
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $cashier_id = $this->request->data['cashier'] ?? '';

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            if ($userRole != 'admin') {
                if (!empty($cashier_id)) {
                    $storeFilter = " AND t.store_id=$cashier_id";
                } elseif (!empty($userLocationId)) {
                    $storeFilter = " AND t.store_id IN (" . $userLocationId . ")";
                } else {
                    $storeFilter = '';
                }
            } else {
                $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
            }

            if (!empty($cashier_id)) {
                $store = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $cashier_id],
                    'fields' => ['Location.name']
                ]);
                $store_name = $store['Location']['name'] ?? '';
            } else {
                $store_name = 'All Stores';
            }

            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

            // Build dynamic tax columns similar to Excel export
            $taxesDistinct = $this->Order_tax->query("
                SELECT DISTINCT tax_name, tax_percentage
                FROM order_tax ot
                INNER JOIN order_orderhistories h ON h.reference_id = ot.reference_id
                INNER JOIN order_transactions t ON t.reference_id = ot.reference_id
                WHERE h.status = 2
                AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
                AND ot.status = 2
                $storeFilter
            ");
            $taxColumns = [];
            foreach ($taxesDistinct as $td) {
                $name = $td['ot']['tax_name'] ?? $td[0]['tax_name'] ?? '';
                $perc = $td['ot']['tax_percentage'] ?? $td[0]['tax_percentage'] ?? '';
                if ($name !== '') {
                    $taxColumns[] = $name . "(" . $perc . "%)";
                }
            }
            sort($taxColumns);

            $kdsPrinterCond = !empty($cashier_id)
                ? "kds = h.kds AND store_id = h.store_id"
                : "kds = h.kds";

            $rows = $this->Order_orderhistory->query("
                SELECT 
                    COALESCE(NULLIF(TRIM(loc.print_line3), ''), 'NA') AS state,
                    loc.name AS deployment,
                    t.orderno AS bill_no,
                    t.reference_id AS order_id,
                    t.tokenno AS day_serial,
                    DATE(t.created) AS bill_date,
                    '' AS tab_name,
                    h.table_number AS table_no,
                    (SELECT MIN(created) FROM order_orderhistories ho WHERE ho.reference_id = t.reference_id AND ho.status IN (1,2)) AS open_time,
                    COALESCE(pay.settled_time, t.created) AS close_time,
                    h.category_name AS category,
                    h.product_name AS item_name,
                    h.product_id AS item_number,
                    (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS item_section,
                    CASE 
                        WHEN cp.food_type = 0 THEN 'Veg'
                        WHEN cp.food_type = 1 THEN 'Non Veg'
                        WHEN cp.food_type = 2 THEN 'Egg'
                        ELSE 'NA'
                    END AS item_classification,
                    ROUND(h.price, 2) AS rate,
                    h.quantity AS qty,
                    ROUND(h.price * h.quantity, 2) AS amount,
                    ROUND(h.discount, 2) AS discount_amount,
                    CASE WHEN h.price * h.quantity > 0 THEN ROUND((h.discount / (h.price * h.quantity)) * 100, 2) ELSE 0 END AS discount_percent,
                    ROUND(COALESCE(tax.taxable_price, 0), 2) AS taxable_amount,
                    ROUND(COALESCE(tax.total_tax_value, 0), 2) AS gst_total,
                    ROUND(COALESCE(tax.sgst_amount, 0), 2) AS sgst_amount,
                    ROUND(COALESCE(tax.cgst_amount, 0), 2) AS cgst_amount,
                    COALESCE(ROUND(COALESCE(t.servicecharge, 0) / NULLIF(ic.item_count, 0), 2), 0) AS service_charge_per_item,
                    0 AS service_charge_tax,
                    ROUND(
                        (h.price * h.quantity) - h.discount
                        + COALESCE(tax.total_tax_value, 0)
                        + COALESCE(ROUND(COALESCE(t.servicecharge, 0) / NULLIF(ic.item_count, 0), 2), 0),
                        2
                    ) AS net_amount,
                    'POS' AS source,
                    COALESCE(taxmap.tax_map, '') AS tax_map
                FROM order_orderhistories h
                INNER JOIN order_transactions t ON t.reference_id = h.reference_id AND t.status = 2
                LEFT JOIN locations loc ON loc.id = t.store_id
                LEFT JOIN catalog_product cp ON cp.id = h.product_id
                LEFT JOIN (
                    SELECT 
                        reference_id,
                        product_id,
                        MAX(taxable_price) AS taxable_price,
                        SUM(tax_value) AS total_tax_value,
                        SUM(CASE WHEN tax_name LIKE 'SGST%' THEN tax_value ELSE 0 END) AS sgst_amount,
                        SUM(CASE WHEN tax_name LIKE 'CGST%' THEN tax_value ELSE 0 END) AS cgst_amount
                    FROM order_tax
                    WHERE status != 0
                    GROUP BY reference_id, product_id
                ) tax ON tax.reference_id = h.reference_id AND tax.product_id = h.product_id
                LEFT JOIN (
                    SELECT 
                        reference_id,
                        MIN(created) AS settled_time
                    FROM order_payments
                    WHERE status = 2
                    GROUP BY reference_id
                ) pay ON pay.reference_id = h.reference_id
                LEFT JOIN (
                    SELECT reference_id, COUNT(*) AS item_count
                    FROM order_orderhistories
                    WHERE status = 2
                    GROUP BY reference_id
                ) ic ON ic.reference_id = h.reference_id
                LEFT JOIN (
                    SELECT reference_id, product_id, GROUP_CONCAT(CONCAT(tax_name, '|', tax_percentage, '|', ROUND(tax_value,2)) SEPARATOR '||') AS tax_map
                    FROM order_tax
                    WHERE status = 2
                    AND created BETWEEN '$startDatetime' AND '$endDatetime'
                    GROUP BY reference_id, product_id
                ) taxmap ON taxmap.reference_id = h.reference_id AND taxmap.product_id = h.product_id
                WHERE h.status = 2
                AND h.created BETWEEN '$startDatetime' AND '$endDatetime'
                $storeFilter
                ORDER BY t.created ASC, t.orderno ASC, h.id ASC
            ");

            $this->set(compact('startdate', 'enddate', 'store_name', 'rows', 'taxColumns'));
            $this->render('bill_item_detail_report_pdf');
        }
    }

    public function bill_wise_sales_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate = date('Y-m-d', strtotime($endDate));

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }
        $users = $this->Location->find('all', ['conditions' => $conditions]);

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
            $yourStartDateValue = $startDate . ' - ' . $endDate;
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
            $cashier_id = $this->request->data['Search']['cashier'] ?? '';
        } else {
            $cashier_id = '';
        }

        if ($userRole != 'admin') {
            $storeFilter = $cashier_id
                ? " AND t.store_id=$cashier_id"
                : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }
        
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // Build dynamic tax columns
        $taxesDistinct = $this->Order_tax->query("
            SELECT DISTINCT tax_name, tax_percentage
            FROM order_tax ot
            INNER JOIN order_transactions t ON t.reference_id = ot.reference_id
            WHERE t.status = 2
            AND DATE(t.created) BETWEEN '$startDatetime' AND '$endDatetime'
            AND ot.status = 2
            $storeFilter
        ");
        $taxColumns = [];
        foreach ($taxesDistinct as $td) {
            $name = $td['ot']['tax_name'] ?? $td[0]['tax_name'] ?? '';
            $perc = $td['ot']['tax_percentage'] ?? $td[0]['tax_percentage'] ?? '';
            if ($name !== '') {
                $taxColumns[] = $name . "(" . $perc . "%)";
            }
        }
        sort($taxColumns);


        $rows = $this->Order_transaction->query("
            SELECT 
                t.orderno AS bill_number,
                t.reference_id AS order_id,
                DATE(t.created) AS bill_date,
                TIME(t.created) AS bill_time,
                t.created AS print_time,
                COALESCE(
                    (SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2),
                    t.created
                ) AS close_time,
                COALESCE(t.table_number, '') AS table_number,
                COALESCE(t.pax, 0) AS covers,
                ROUND(COALESCE(
                    (SELECT SUM(h.price * h.quantity) 
                    FROM order_orderhistories h 
                    WHERE h.reference_id = t.reference_id 
                    AND h.status = 2), 0), 2) AS total,
                ROUND(t.discount, 2) AS discount,
                ROUND(COALESCE(
                    (SELECT SUM(h.price * h.quantity) 
                    FROM order_orderhistories h 
                    WHERE h.reference_id = t.reference_id 
                    AND h.status = 2), 0) - t.discount, 2) AS net_total,
                ROUND(t.amount, 2) AS gross_bill_amount,
                ROUND(COALESCE(t.servicecharge, 0), 2) AS total_service_charge,
                COALESCE(taxmap.tax_map, '') AS tax_map
            FROM order_transactions t
            LEFT JOIN (
                SELECT 
                    reference_id,
                    GROUP_CONCAT(CONCAT(tax_name, '|', tax_percentage, '|', ROUND(tax_value,2)) SEPARATOR '||') AS tax_map
                FROM order_tax
                WHERE status = 2
                AND created BETWEEN '$startDatetime' AND '$endDatetime'
                GROUP BY reference_id
            ) taxmap ON taxmap.reference_id = t.reference_id
            WHERE t.status = 2
            AND t.created BETWEEN '$startDatetime' AND '$endDatetime'
            $storeFilter
            ORDER BY t.created ASC
        ");

        $this->set(compact('users', 'yourStartDateValue', 'rows', 'cashier_id', 'taxColumns'));
    }

    public function bill_wise_sales_report_export() {

        $date_range = $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate = date('Y-m-d', strtotime($end_date));
        $excelDateRange = $startdate . ' ' . $enddate;

        $cashier_id = $_GET['cashier'] ?? '';

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if ($userRole != 'admin') {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : " AND t.store_id IN (" . $userLocationId . ")";
        } else {
            $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
        }

        $storeName = '';
        if ($cashier_id) {
            $storeData = $this->Order_transaction->query("SELECT name FROM locations WHERE id = $cashier_id LIMIT 1");
            $storeName = $storeData ? $storeData[0]['locations']['name'] : '';
        } else {
            $storeName = 'All Stores';
        }
        
        
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // Build dynamic tax columns for export
        $taxesDistinct = $this->Order_tax->query("
            SELECT DISTINCT tax_name, tax_percentage
            FROM order_tax ot
            INNER JOIN order_transactions t ON t.reference_id = ot.reference_id
            WHERE t.status = 2
            AND DATE(t.created) BETWEEN '$startDatetime' AND '$endDatetime'
            AND ot.status = 2
            $storeFilter
        ");
        $taxColumns = [];
        foreach ($taxesDistinct as $td) {
            $name = $td['ot']['tax_name'] ?? $td[0]['tax_name'] ?? '';
            $perc = $td['ot']['tax_percentage'] ?? $td[0]['tax_percentage'] ?? '';
            if ($name !== '') {
                $taxColumns[] = $name . "(" . $perc . "%)";
            }
        }
        sort($taxColumns);

        $rows = $this->Order_transaction->query("
            SELECT 
                t.orderno AS bill_number,
                t.reference_id AS order_id,
                DATE(t.created) AS bill_date,
                TIME(t.created) AS bill_time,
                t.created AS print_time,
                COALESCE(
                    (SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2),
                    t.created
                ) AS close_time,
                COALESCE(t.table_number, '') AS table_number,
                COALESCE(t.pax, 0) AS covers,
                ROUND(COALESCE(
                    (SELECT SUM(h.price * h.quantity) 
                    FROM order_orderhistories h 
                    WHERE h.reference_id = t.reference_id 
                    AND h.status = 2), 0), 2) AS total,
                ROUND(t.discount, 2) AS discount,
                ROUND(COALESCE(
                    (SELECT SUM(h.price * h.quantity) 
                    FROM order_orderhistories h 
                    WHERE h.reference_id = t.reference_id 
                    AND h.status = 2), 0) - t.discount, 2) AS net_total,
                ROUND(t.amount, 2) AS gross_bill_amount,
                ROUND(COALESCE(t.servicecharge, 0), 2) AS total_service_charge,
                COALESCE(taxmap.tax_map, '') AS tax_map
            FROM order_transactions t
            LEFT JOIN (
                SELECT 
                    reference_id,
                    GROUP_CONCAT(CONCAT(tax_name, '|', tax_percentage, '|', ROUND(tax_value,2)) SEPARATOR '||') AS tax_map
                FROM order_tax
                WHERE status = 2
                AND created BETWEEN '$startDatetime' AND '$endDatetime'
                GROUP BY reference_id
            ) taxmap ON taxmap.reference_id = t.reference_id
            WHERE t.status = 2
            AND t.created BETWEEN '$startDatetime' AND '$endDatetime'
            $storeFilter
            ORDER BY t.created ASC
        ");

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Bill Wise Sales Report");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Bill_Wise_Sales');

        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'Bill Wise Sales Report (' . $excelDateRange . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));

        $baseHeaders = array(
            'Bill Number', 'Order ID', 'Bill Date', 'Bill Time', 'Print Time', 'Close Time',
            'Table Number', 'Covers', 'Total', 'Discount', 'Net Total', 'Gross Bill Amount', 'Total Service Charge'
        );
        $headers = array_merge($baseHeaders, $taxColumns);
        $sheet->fromArray($headers, null, 'A5');

        $rowNum = 6;

        // Grand totals for numeric columns
        $grandTotals = [
            'covers' => 0,
            'total' => 0,
            'discount' => 0,
            'net_total' => 0,
            'gross_bill_amount' => 0,
            'total_service_charge' => 0,
        ];
        $taxTotals = array_fill_keys($taxColumns, 0.0);

        foreach ($rows as $r) {
            $row = $r[0];
            $t = $r['t'];

            $grandTotals['covers'] += (float)$row['covers'];
            $grandTotals['total'] += (float)$row['total'];
            $grandTotals['discount'] += (float)$row['discount'];
            $grandTotals['net_total'] += (float)$row['net_total'];
            $grandTotals['gross_bill_amount'] += (float)$row['gross_bill_amount'];
            $grandTotals['total_service_charge'] += (float)$row['total_service_charge'];

            $col = 0;
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $t['bill_number']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $t['order_id']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['bill_date']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['bill_time']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, date('H:i:s', strtotime($row['print_time'])));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, date('H:i:s', strtotime($row['close_time'])));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['table_number']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $row['covers']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['total'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['discount'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['net_total'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['gross_bill_amount'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($row['total_service_charge'], 2));

            // Dynamic tax per bill
            $rowTaxValues = array_fill_keys($taxColumns, 0.0);
            $tax_map = isset($row['tax_map']) ? $row['tax_map'] : '';
            if (!empty($tax_map)) {
                $pieces = explode('||', $tax_map);
                foreach ($pieces as $pc) {
                    if (trim($pc) === '') {
                        continue;
                    }
                    $parts = explode('|', $pc);
                    if (count($parts) >= 3) {
                        $tname = $parts[0];
                        $tperc = $parts[1];
                        $tval  = (float)$parts[2];
                        $key   = $tname . "(" . $tperc . "%)";
                        if (array_key_exists($key, $rowTaxValues)) {
                            $rowTaxValues[$key] += round($tval, 2);
                        }
                    }
                }
            }

            foreach ($taxColumns as $tc) {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($rowTaxValues[$tc], 2));
                $taxTotals[$tc] += $rowTaxValues[$tc];
            }

            $rowNum++;
        }

        // Grand total row
        if (!empty($rows)) {
            $sheet->setCellValue("A$rowNum", "Grand Total");
            $sheet->mergeCells("A$rowNum:G$rowNum");

            $col = 7; // Covers column index
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, $grandTotals['covers']);
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($grandTotals['total'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($grandTotals['discount'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($grandTotals['net_total'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($grandTotals['gross_bill_amount'], 2));
            $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($grandTotals['total_service_charge'], 2));

            foreach ($taxColumns as $tc) {
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, number_format($taxTotals[$tc], 2));
            }

            $lastColLetter = $sheet->getCellByColumnAndRow($col - 1, $rowNum)->getColumn();
            $grandTotalStyle = $sheet->getStyle("A$rowNum:" . $lastColLetter . $rowNum);
            $grandTotalStyle->getFont()->setBold(true);
            $grandTotalStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $grandTotalStyle->getFill()->getStartColor()->setRGB('E0E0E0');
        }

        // Header style & autosize
        $lastHeaderColLetter = $sheet->getCellByColumnAndRow(count($headers) - 1, 5)->getColumn();
        $sheet->getStyle('A5:' . $lastHeaderColLetter . '5')->getFont()->setBold(true);
        foreach (range('A', $lastHeaderColLetter) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Bill_Wise_Sales_Report(' . $startdate . '--' . $enddate . ').xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function bill_wise_sales_report_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            $date_range = $this->request->data['sdate'];
            $dates = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));

            $cashier_id = $this->request->data['cashier'] ?? '';

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            if ($userRole != 'admin') {
                $storeFilter = $cashier_id
                    ? " AND t.store_id=$cashier_id"
                    : " AND t.store_id IN (" . $userLocationId . ")";
            } else {
                $storeFilter = $cashier_id ? " AND t.store_id=$cashier_id" : '';
            }

            if (!empty($cashier_id)) {
                $store = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $cashier_id],
                    'fields' => ['Location.name']
                ]);
                $store_name = $store['Location']['name'] ?? '';
            } else {
                $store_name = 'All Stores';
            }
            
            
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

            // Build dynamic tax columns for PDF
            $taxesDistinct = $this->Order_tax->query("
                SELECT DISTINCT tax_name, tax_percentage
                FROM order_tax ot
                INNER JOIN order_transactions t ON t.reference_id = ot.reference_id
                WHERE t.status = 2
                AND DATE(t.created) BETWEEN '$startDatetime' AND '$endDatetime'
                AND ot.status = 2
                $storeFilter
            ");
            $taxColumns = [];
            foreach ($taxesDistinct as $td) {
                $name = $td['ot']['tax_name'] ?? $td[0]['tax_name'] ?? '';
                $perc = $td['ot']['tax_percentage'] ?? $td[0]['tax_percentage'] ?? '';
                if ($name !== '') {
                    $taxColumns[] = $name . "(" . $perc . "%)";
                }
            }
            sort($taxColumns);

            $rows = $this->Order_transaction->query("
                SELECT 
                    t.orderno AS bill_number,
                    t.reference_id AS order_id,
                    DATE(t.created) AS bill_date,
                    TIME(t.created) AS bill_time,
                    t.created AS print_time,
                    COALESCE(
                        (SELECT MIN(created) FROM order_payments p WHERE p.reference_id = t.reference_id AND p.status = 2),
                        t.created
                    ) AS close_time,
                    COALESCE(t.table_number, '') AS table_number,
                    COALESCE(t.pax, 0) AS covers,
                    ROUND(COALESCE(
                        (SELECT SUM(h.price * h.quantity) 
                        FROM order_orderhistories h 
                        WHERE h.reference_id = t.reference_id 
                        AND h.status = 2), 0), 2) AS total,
                    ROUND(t.discount, 2) AS discount,
                    ROUND(COALESCE(
                        (SELECT SUM(h.price * h.quantity) 
                        FROM order_orderhistories h 
                        WHERE h.reference_id = t.reference_id 
                        AND h.status = 2), 0) - t.discount, 2) AS net_total,
                    ROUND(t.amount, 2) AS gross_bill_amount,
                    ROUND(COALESCE(t.servicecharge, 0), 2) AS total_service_charge,
                    COALESCE(taxmap.tax_map, '') AS tax_map
                FROM order_transactions t
                LEFT JOIN (
                    SELECT 
                        reference_id,
                        GROUP_CONCAT(CONCAT(tax_name, '|', tax_percentage, '|', ROUND(tax_value,2)) SEPARATOR '||') AS tax_map
                    FROM order_tax
                    WHERE status = 2
                    AND created BETWEEN '$startDatetime' AND '$endDatetime'
                    GROUP BY reference_id
                ) taxmap ON taxmap.reference_id = t.reference_id
                WHERE t.status = 2
                AND t.created BETWEEN '$startDatetime' AND '$endDatetime'
                $storeFilter
                ORDER BY t.created ASC
            ");

            $datas = [];
            foreach ($rows as $r) {
                $row = $r[0];
                $t = $r['t'];
                $datas[] = [
                    'bill_number' => $t['bill_number'],
                    'order_id' => $t['order_id'],
                    'bill_date' => $row['bill_date'],
                    'bill_time' => $row['bill_time'],
                    'print_time' => $row['print_time'],
                    'close_time' => $row['close_time'],
                    'table_number' => $row['table_number'],
                    'covers' => $row['covers'],
                    'total' => $row['total'],
                    'discount' => $row['discount'],
                    'net_total' => $row['net_total'],
                    'gross_bill_amount' => $row['gross_bill_amount'],
                    'total_service_charge' => $row['total_service_charge'],
                    'tax_map' => $row['tax_map'],
                ];
            }

            $this->set(compact('startdate', 'enddate', 'datas', 'store_name', 'taxColumns'));
            $this->render('bill_wise_sales_report_pdf');
        }
    }
    
    private function _getLastSyncTimes()
    {
        $filePath = TMP . 'sync_status.json';
        if (!file_exists($filePath)) {
            return ['push' => null, 'pull' => null];
        }
        return json_decode(file_get_contents($filePath), true) ?: ['push' => null, 'pull' => null];
    }
    
    public function po_report_v2()
    {
        
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime(date('Y-m-01')));
        $endDate   = date('m/d/Y');

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            $dates = explode(" - ", $daterange);
            $startDate = date('m/d/Y', strtotime(trim($dates[0])));
            $endDate   = date('m/d/Y', strtotime(trim($dates[1])));
        }
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));
        
        

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $store_id  = !empty($this->request->data['Search']['store'])  ? (int)$this->request->data['Search']['store']  : null;
        $vendor_id = !empty($this->request->data['Search']['vendor']) ? (int)$this->request->data['Search']['vendor'] : null;
        $status_f  = isset($this->request->data['Search']['status'])  ? $this->request->data['Search']['status']      : '';

        $location_id = !empty($store_id) ? $store_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


        $where = "WHERE ipo.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) {
            $where .= " AND ipo.location_id = $store_id";
        } elseif ($userRole !== 'admin') {
            $where .= " AND ipo.location_id IN ($userLocationId)";
        }
        if ($vendor_id) $where .= " AND ipo.vendor_id = $vendor_id";
        if ($status_f !== '') $where .= " AND ipo.status = " . (int)$status_f;

        $statusLabels = [1 => 'Submitted', 2 => 'Approved', 3 => 'Processed', 4 => 'Stocked In'];

        
        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipo.p_o_id,
                ipo.po_date AS po_date,
                ipo.location_name AS store,
                ipo.vendor_name AS vendor,
                ipo.status,
                COUNT(ipoi.id) AS item_count,
                SUM(ipoi.sku_quantity) AS qty_total,
                SUM(ipoi.stockin_quantity) as stockin_qty,
                SUM(ipoi.sku_price * ipoi.sku_quantity) AS gross,
                SUM(COALESCE(ipoi.sku_discount_value,0)) AS discount,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.tcs_amount,0)) AS tcs,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total,
                SUM(ipoi.approved_amount) AS approved_amt,
                SUM(ipoi.approved_total_tax_value) AS approved_tax,
                SUM(ipoi.processed_amount) AS processed_amt,
                SUM(ipoi.processed_total_tax_value) AS processed_tax,
                SUM(ipoi.stockin_amount) AS stockin_amt,
                SUM(ipoi.stockin_total_tax_value) AS stockin_tax
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id AND ipoi.status != 0
            $where
            GROUP BY ipo.p_o_id
            ORDER BY ipo.id DESC
        ");

        $pos = [];
        foreach ($rows as $r) {
            $pos[] = [
                'po_id'        => $r['ipo']['p_o_id'],
                'po_date'      => $r['ipo']['po_date'] ?? $r[0]['po_date'] ?? '',
                'store'        => $r['ipo']['store']   ?? $r[0]['store']   ?? '',
                'vendor'       => $r['ipo']['vendor']  ?? $r[0]['vendor']  ?? '',
                'status'       => $statusLabels[$r['ipo']['status'] ?? 0] ?? 'Unknown',
                'item_count'   => $r[0]['item_count']   ?? 0,
                'qty_total'    => $r[0]['qty_total']    ?? 0,
                'stockin_qty'  => $r[0]['stockin_qty']    ?? 0,
                'gross'        => $r[0]['gross']        ?? 0,
                'discount'     => $r[0]['discount']     ?? 0,
                'base'         => $r[0]['base']         ?? 0,
                'tax'          => $r[0]['tax']          ?? 0,
                'tcs'          => $r[0]['tcs']          ?? 0,
                'excise_total' => $r[0]['excise_total']      ?? 0,
                'addl_excise_total' => $r[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $r[0]['vat_total']         ?? 0,
                'tcs_total'    => $r[0]['tcs_total']         ?? 0,
                'total'        => $r[0]['total']        ?? 0,
                'approved_amt' => (float)($r[0]['approved_amt'] ?? 0),
                'processed_amt'=> (float)($r[0]['processed_amt'] ?? 0),
                'stockin_amt'  => (float)($r[0]['stockin_amt']  ?? 0)
            ];
        }

        $conditions = ['status' => 1]; // T14-fix: show all location types in purchase report store dropdown
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $conditions['id IN'] = array_map('trim', explode(',', $userLocationId));
        }
        $stores  = $this->Location->find('all', ['conditions' => $conditions]);
        $vendors = $this->Inventory_vendor->find('list', [
            'conditions' => ['Inventory_vendor.status' => 1],
            'fields' => ['Inventory_vendor.id', 'Inventory_vendor.company'],
            'order'  => ['Inventory_vendor.company ASC']
        ]);

        $this->set('startdate', $startDate . ' - ' . $endDate);
        $this->set(compact('stores', 'vendors', 'pos', 'statusLabels'));
    }

    public function po_report_v2_items()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->response->body(json_encode(['error' => 'Unauthorized']));
        }
        $this->autoRender = false;
        $this->response->type('application/json');

        $po_id = $this->request->query('po_id') ?? 0;
        if (!$po_id) {
            return $this->response->body(json_encode(['error' => 'Invalid PO']));
        }

        $items = $this->Inventory_purchase_order->query("
            SELECT
                ipoi.sku_name                                                                    AS item_name,
                ipoi.sku_quantity                                                                AS qty,
                ipoi.stockin_quantity                                                            AS stockin_qty,
                (ipoi.sku_price * ipoi.sku_quantity)                                             AS gross,
                COALESCE(ipoi.sku_discount_value, 0)                                             AS discount,
                (ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value, 0))      AS base,
                COALESCE(ipoi.sku_total_tax_value, 0)                                            AS tax,
                COALESCE(ipoi.tcs_amount, 0)                                                     AS tcs,
                COALESCE(ipoi.excise_duty_amt, 0)                                                AS excise_total,
                COALESCE(ipoi.addl_excise_duty_amt, 0)                                           AS addl_excise_total,
                COALESCE(ipoi.vat_amt, 0)                                                        AS vat_total,
                COALESCE(ipoi.tcs_amt, 0)                                                        AS tcs_total,
                ipoi.sku_amount                                                                  AS total,
                ipoi.approved_amount   AS approved_amt,
                ipoi.processed_amount  AS processed_amt,
                ipoi.stockin_amount    AS stockin_amt
            FROM inventory_purchase_order_items ipoi
            WHERE ipoi.p_o_id = $po_id AND ipoi.status != 0
            ORDER BY ipoi.id ASC
        ");

        $result = [];
        foreach ($items as $row) {
            $r  = $row['ipoi'] ?? [];   // real table columns (sku_name etc.)
            $r0 = $row[0]      ?? [];   // computed/expression columns (gross, discount, base …)

            $result[] = [
                'item_name'     => $r['item_name']             ?? $r0['item_name']     ?? '',
                'qty'           => $r['qty']                   ?? $r0['qty']           ?? 0,
                'stockin_qty'   => $r['stockin_qty']           ?? $r0['stockin_qty']           ?? 0,
                'gross'         => $r0['gross']                ?? 0,   // computed → always in [0]
                'discount'      => $r0['discount']             ?? 0,
                'base'          => $r0['base']                 ?? 0,
                'tax'           => $r['tax']                   ?? $r0['tax']           ?? 0,
                'tcs'           => $r['tcs']                   ?? $r0['tcs']           ?? 0,
                'excise_total'  => $r['excise_total']          ?? $r0['excise_total']  ?? 0,
                'addl_excise_total' => $r['addl_excise_total'] ?? $r0['addl_excise_total'] ?? 0,
                'vat_total'     => $r['vat_total']             ?? $r0['vat_total']     ?? 0,
                'tcs_total'     => $r['tcs_total']             ?? $r0['tcs_total']     ?? 0,
                'total'         => $r['total']                 ?? $r0['total']         ?? 0,
                'approved_amt'  => $r['approved_amt']          ?? $r0['approved_amt']  ?? 0,
                'processed_amt' => $r['processed_amt']         ?? $r0['processed_amt'] ?? 0,
                'stockin_amt'   => $r['stockin_amt']           ?? $r0['stockin_amt']   ?? 0,
            ];
        }

        return $this->response->body(json_encode(['items' => $result]));
    }

    public function po_report_v2_export()
    {
        $this->autoRender = false;
        $date_range = $_GET['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
        $dates      = explode(" - ", $date_range);
        $startdate  = date('Y-m-d', strtotime(trim($dates[0])));
        $enddate    = date('Y-m-d', strtotime(trim($dates[1])));
        
        
        $store_id   = !empty($_GET['store'])  ? (int)$_GET['store']  : null;
        $vendor_id  = !empty($_GET['vendor']) ? (int)$_GET['vendor'] : null;
        $status_f   = isset($_GET['status'])  ? $_GET['status']      : '';

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;


        $location_id = !empty($store_id) ? $store_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


        $where = "WHERE ipo.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id)  $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin' && !empty($userLocationId)) $where .= " AND ipo.location_id IN ($userLocationId)";
        if ($vendor_id) $where .= " AND ipo.vendor_id = $vendor_id";
        if ($status_f !== '') $where .= " AND ipo.status = " . (int)$status_f;

        $statusLabels = [1 => 'Submitted', 2 => 'Approved', 3 => 'Processed', 4 => 'Stocked In'];

        // Fetch PO-level summary
        $rows = $this->Inventory_purchase_order->query("
            SELECT ipo.p_o_id, ipo.po_date AS po_date, ipo.location_name AS store,
                ipo.vendor_name AS vendor, ipo.status,
                COUNT(ipoi.id) AS item_count, SUM(ipoi.sku_quantity) AS qty_total,
                SUM(ipoi.stockin_quantity) AS stockin_qty_total,
                SUM(ipoi.sku_price * ipoi.sku_quantity) AS gross,
                SUM(COALESCE(ipoi.sku_discount_value,0)) AS discount,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.tcs_amount,0)) AS tcs,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total,
                SUM(ipoi.approved_amount) AS approved_amt,
                SUM(COALESCE(ipoi.processed_amount,0)) AS processed_amt,
                SUM(COALESCE(ipoi.stockin_amount,0)) AS stockin_amt
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id AND ipoi.status != 0
            $where GROUP BY ipo.p_o_id ORDER BY ipo.id DESC
        ");

        // Collect all PO IDs to fetch item lines in one query
        $poIds = [];
        foreach ($rows as $r) {
            $poIds[] = $r['ipo']['p_o_id'];
        }

        // Fetch all item lines for these POs in one shot
        $itemsMap = [];
        if (!empty($poIds)) {
            $inList   = implode(',', $poIds);
            $itemRows = $this->Inventory_purchase_order->query("
                SELECT
                    ipoi.p_o_id,
                    ipoi.sku_name                                                                     AS item_name,
                    ipoi.sku_quantity                                                                  AS qty,
                    ipoi.stockin_quantity                                                             AS stockin_qty,
                    (ipoi.sku_price * ipoi.sku_quantity)                                              AS gross,
                    COALESCE(ipoi.sku_discount_value, 0)                                              AS discount,
                    (ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value, 0))       AS base,
                    COALESCE(ipoi.sku_total_tax_value, 0)                                             AS tax,
                    COALESCE(ipoi.tcs_amount, 0)                                                      AS tcs,
                    COALESCE(ipoi.excise_duty_amt, 0)                                                 AS excise_total,
                    COALESCE(ipoi.addl_excise_duty_amt, 0)                                            AS addl_excise_total,
                    COALESCE(ipoi.vat_amt, 0)                                                         AS vat_total,
                    COALESCE(ipoi.tcs_amt, 0)                                                         AS tcs_total,
                    ipoi.sku_amount                                                                    AS total,
                    ipoi.approved_amount                                                               AS approved_amt,
                    ipoi.processed_amount                                                              AS processed_amt,
                    ipoi.stockin_amount                                                                AS stockin_amt
                FROM inventory_purchase_order_items ipoi
                WHERE ipoi.p_o_id IN ($inList) AND ipoi.status != 0
                ORDER BY ipoi.p_o_id, ipoi.id ASC
            ");

            foreach ($itemRows as $ir) {
                $poId = $ir['ipoi']['p_o_id'] ?? $ir[0]['p_o_id'] ?? null;
                if (!$poId) continue;
                $itemsMap[$poId][] = [
                    'item_name'     => $ir['ipoi']['item_name']    ?? $ir[0]['item_name']    ?? '',
                    'qty'           => $ir['ipoi']['qty']          ?? $ir[0]['qty']          ?? 0,
                    'stockin_qty'   => $ir['ipoi']['stockin_qty']  ?? $ir[0]['stockin_qty']  ?? 0,
                    'gross'         => $ir[0]['gross']             ?? 0,
                    'discount'      => $ir[0]['discount']          ?? 0,
                    'base'          => $ir[0]['base']              ?? 0,
                    'tax'           => $ir[0]['tax']               ?? 0,
                    'tcs'           => $ir[0]['tcs']               ?? 0,
                    'excise_total'  => $ir['excise_total']          ?? $ir[0]['excise_total']  ?? 0,
                    'addl_excise_total' => $ir['addl_excise_total'] ?? $ir[0]['addl_excise_total'] ?? 0,
                    'vat_total'     => $ir['vat_total']             ?? $ir[0]['vat_total']     ?? 0,
                    'tcs_total'     => $ir['tcs_total']             ?? $ir[0]['tcs_total']     ?? 0,
                    'total'         => $ir['ipoi']['total']        ?? $ir[0]['total']        ?? 0,
                    'approved_amt'  => $ir['ipoi']['approved_amt'] ?? $ir[0]['approved_amt'] ?? 0,
                    'processed_amt' => $ir['ipoi']['processed_amt']?? $ir[0]['processed_amt']?? 0,
                    'stockin_amt'   => $ir['ipoi']['stockin_amt']  ?? $ir[0]['stockin_amt']  ?? 0,
                ];
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="testPO_Report_v2_' . $startdate . '_' . $enddate . '.csv"');
        $fp = fopen('php://output', 'w');

        // Header row
        fputcsv($fp, ['PO No','Date','Store','Vendor','Status','Items','Item Qty','Stockin Qty','Gross','Discount','Base','Tax','TCS','Excise','Addl. Excise','VAT','TCS','Total','Approved(incl tax)','Processed(incl tax)','StockedIn(incl tax)']);

        foreach ($rows as $r) {
            $poId = $r['ipo']['p_o_id'];

            // PO summary row
            fputcsv($fp, [
                $poId,
                $r['ipo']['po_date']  ?? '',
                $r['ipo']['store']    ?? '',
                $r['ipo']['vendor']   ?? '',
                $statusLabels[$r['ipo']['status'] ?? 0] ?? 'Unknown',
                $r[0]['item_count']   ?? 0,
                round($r[0]['qty_total']     ?? 0, 3),
                round($r[0]['stockin_qty_total']     ?? 0, 3),
                round($r[0]['gross']         ?? 0, 2),
                round($r[0]['discount']      ?? 0, 2),
                round($r[0]['base']          ?? 0, 2),
                round($r[0]['tax']           ?? 0, 2),
                round($r[0]['tcs']           ?? 0, 2),
                round($r[0]['excise_total']      ?? 0, 2),
                round($r[0]['addl_excise_total'] ?? 0, 2),
                round($r[0]['vat_total']         ?? 0, 2),
                round($r[0]['tcs_total']         ?? 0, 2),
                round($r[0]['total']         ?? 0, 2),
                round($r[0]['approved_amt']  ?? 0, 2),
                round($r[0]['processed_amt'] ?? 0, 2),
                round($r[0]['stockin_amt']   ?? 0, 2),
            ]);

            // Item detail rows — indented with 5 empty leading columns
            foreach ($itemsMap[$poId] ?? [] as $item) {
                fputcsv($fp, [
                    '',   // PO No
                    '',   // Date
                    '',   // Store
                    '',   // Vendor
                    '',   // Status
                    $item['item_name'],
                    round($item['qty'],          3),
                    round($item['stockin_qty'],  3),
                    round($item['gross'],        2),
                    round($item['discount'],     2),
                    round($item['base'],         2),
                    round($item['tax'],          2),
                    round($item['tcs'],          2),
                    round($item['excise_total'], 2),
                    round($item['addl_excise_total'], 2),
                    round($item['vat_total'],    2),
                    round($item['tcs_total'],    2),
                    round($item['total'],        2),
                    round($item['approved_amt'], 2),
                    round($item['processed_amt'],2),
                    round($item['stockin_amt'],  2),
                ]);
            }
        }

        fclose($fp);
        exit;
    }
    
    public function vendor_wise_purchase_report()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime(date('Y-m-01')));
        $endDate   = date('m/d/Y');
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            $dates = explode(" - ", $daterange);
            $startDate = date('m/d/Y', strtotime(trim($dates[0])));
            $endDate   = date('m/d/Y', strtotime(trim($dates[1])));
        }
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));
        
        

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $store_id = !empty($this->request->data['Search']['store']) ? (int)$this->request->data['Search']['store'] : null;

        $location_id = !empty($store_id) ? $store_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin') $where .= " AND ipo.location_id IN ($userLocationId)";


        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipo.vendor_id,
                ipo.vendor_name AS vendor,
                COUNT(DISTINCT ipo.p_o_id)    AS po_count,
                COUNT(DISTINCT ipoi.sku_id)   AS item_count,
                SUM(ipoi.sku_quantity)         AS qty,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0))  AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount)           AS total,
                SUM(CASE WHEN isku.expense_type='CAPEX' THEN ipoi.sku_amount ELSE 0 END) AS capex_total,
                SUM(CASE WHEN isku.expense_type='OPEX'  THEN ipoi.sku_amount ELSE 0 END) AS opex_total,
                SUM(CASE WHEN isku.expense_type NOT IN ('CAPEX','OPEX') THEN ipoi.sku_amount ELSE 0 END) AS unclassified_total
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            LEFT JOIN inventory_skus isku ON isku.id = ipoi.sku_id
            $where
            GROUP BY ipo.vendor_id, ipo.vendor_name
            ORDER BY total DESC
        ");

        $vendorData = [];
        foreach ($rows as $r) {
            $vendorData[] = [
                'vendor_id'    => $r['ipo']['vendor_id']          ?? $r[0]['vendor_id']       ?? 0,
                'vendor'       => $r['ipo']['vendor']             ?? '',
                'po_count'     => $r[0]['po_count']               ?? 0,
                'item_count'   => $r[0]['item_count']             ?? 0,
                'qty'          => $r[0]['qty']                    ?? 0,
                'base'         => $r[0]['base']                   ?? 0,
                'tax'          => $r[0]['tax']                    ?? 0,
                'excise_total' => $r[0]['excise_total']           ?? 0,
                'addl_excise_total' => $r[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $r[0]['vat_total']              ?? 0,
                'tcs_total'    => $r[0]['tcs_total']              ?? 0,
                'total'        => $r[0]['total']                  ?? 0,
                'capex_total'  => $r[0]['capex_total']            ?? 0,
                'opex_total'   => $r[0]['opex_total']             ?? 0,
                'unclassified' => $r[0]['unclassified_total']     ?? 0,
            ];
        }

        $conditions = ['status' => 1];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $conditions['id IN'] = array_map('trim', explode(',', $userLocationId));
        }
        $stores = $this->Location->find('all', ['conditions' => $conditions]);

        $this->set('startdate', $startDate . ' - ' . $endDate);
        $this->set(compact('stores', 'vendorData'));
    }
    
    public function vendor_wise_purchase_export()
    {
        $this->autoRender = false;
        $date_range = $_GET['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
        $dates      = explode(" - ", $date_range);
        $startdate  = date('Y-m-d', strtotime(trim($dates[0])));
        $enddate    = date('Y-m-d', strtotime(trim($dates[1])));
        
        
        $store_id   = !empty($_GET['store']) ? (int)$_GET['store'] : null;

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;


        $location_id = !empty($store_id) ? $store_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin' && !empty($userLocationId)) $where .= " AND ipo.location_id IN ($userLocationId)";

        // ── Vendor-level summary (same as screen query) ──
        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipo.vendor_id,
                ipo.vendor_name AS vendor,
                COUNT(DISTINCT ipo.p_o_id)    AS po_count,
                COUNT(DISTINCT ipoi.sku_id)   AS item_count,
                SUM(ipoi.sku_quantity)         AS qty,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0))  AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount)           AS total,
                SUM(CASE WHEN isku.expense_type='CAPEX' THEN ipoi.sku_amount ELSE 0 END) AS capex_total,
                SUM(CASE WHEN isku.expense_type='OPEX'  THEN ipoi.sku_amount ELSE 0 END) AS opex_total,
                SUM(CASE WHEN isku.expense_type NOT IN ('CAPEX','OPEX') THEN ipoi.sku_amount ELSE 0 END) AS unclassified_total
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            LEFT JOIN inventory_skus isku ON isku.id = ipoi.sku_id
            $where
            GROUP BY ipo.vendor_id, ipo.vendor_name
            ORDER BY total DESC
        ");

        // ── Collect vendor IDs to fetch item lines in one query ──
        $vendorIds = [];
        foreach ($rows as $r) {
            $vid = $r['ipo']['vendor_id'] ?? $r[0]['vendor_id'] ?? null;
            if ($vid) $vendorIds[] = (int)$vid;
        }

        // ── Item lines grouped by vendor_id + sku (same logic as AJAX action) ──
        $itemsMap = [];
        if (!empty($vendorIds)) {
            $inList   = implode(',', array_unique($vendorIds));
            $itemRows = $this->Inventory_purchase_order->query("
                SELECT
                    ipo.vendor_id,
                    ipoi.sku_id,
                    ipoi.sku_name,
                    SUM(ipoi.sku_quantity)                                                              AS qty,
                    SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value, 0))     AS base,
                    SUM(COALESCE(ipoi.sku_total_tax_value, 0))                                          AS tax,
                    SUM(COALESCE(ipoi.excise_duty_amt,0))                                               AS excise_total,
                    SUM(COALESCE(ipoi.addl_excise_duty_amt,0))                                          AS addl_excise_total,
                    SUM(COALESCE(ipoi.vat_amt,0))                                                       AS vat_total,
                    SUM(COALESCE(ipoi.tcs_amt,0))                                                       AS tcs_total,
                    SUM(ipoi.sku_amount)                                                                AS total,
                    SUM(CASE WHEN isku.expense_type='CAPEX' THEN ipoi.sku_amount ELSE 0 END)           AS capex_total,
                    SUM(CASE WHEN isku.expense_type='OPEX'  THEN ipoi.sku_amount ELSE 0 END)           AS opex_total,
                    SUM(CASE WHEN isku.expense_type NOT IN ('CAPEX','OPEX') THEN ipoi.sku_amount ELSE 0 END) AS unclassified_total
                FROM inventory_purchase_order ipo
                LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
                LEFT JOIN inventory_skus isku ON isku.id = ipoi.sku_id
                WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipo.vendor_id IN ($inList)
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'
                GROUP BY ipo.vendor_id, ipoi.sku_id, ipoi.sku_name
                ORDER BY ipo.vendor_id, total DESC
            ");

            foreach ($itemRows as $ir) {
                $vid = $ir['ipo']['vendor_id'] ?? $ir[0]['vendor_id'] ?? null;
                if (!$vid) continue;
                $itemsMap[$vid][] = [
                    'item_name'    => $ir['ipoi']['sku_name']          ?? $ir[0]['sku_name']          ?? '',
                    'qty'          => $ir[0]['qty']                    ?? 0,
                    'base'         => $ir[0]['base']                   ?? 0,
                    'tax'          => $ir[0]['tax']                    ?? 0,
                    'excise_total' => $ir[0]['excise_total']           ?? 0,
                    'addl_excise_total' => $ir[0]['addl_excise_total'] ?? 0,
                    'vat_total'    => $ir[0]['vat_total']              ?? 0,
                    'tcs_total'    => $ir[0]['tcs_total']              ?? 0,
                    'total'        => $ir[0]['total']                  ?? 0,
                    'capex_total'  => $ir[0]['capex_total']            ?? 0,
                    'opex_total'   => $ir[0]['opex_total']             ?? 0,
                    'unclassified' => $ir[0]['unclassified_total']     ?? 0,
                ];
            }
        }

        // ── Output CSV ──
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Vendor_Wise_Purchase_' . $startdate . '_' . $enddate . '.csv"');
        $fp = fopen('php://output', 'w');

        // Header row — columns match both vendor summary + item detail rows
        fputcsv($fp, ['Vendor','POs','Items','Qty','Base','Tax','Excise','Addl. Excise','VAT','TCS','Total','CAPEX','OPEX','Unclassified']);

        foreach ($rows as $r) {
            $vid = $r['ipo']['vendor_id'] ?? $r[0]['vendor_id'] ?? null;

            // ── Vendor summary row ──
            fputcsv($fp, [
                $r['ipo']['vendor']               ?? '',
                $r[0]['po_count']                 ?? 0,
                '',                                        // Item Name blank on summary row
                round($r[0]['qty']                ?? 0, 3),
                round($r[0]['base']               ?? 0, 2),
                round($r[0]['tax']                ?? 0, 2),
                round($r[0]['excise_total']       ?? 0, 2),
                round($r[0]['addl_excise_total']  ?? 0, 2),
                round($r[0]['vat_total']          ?? 0, 2),
                round($r[0]['tcs_total']          ?? 0, 2),
                round($r[0]['total']              ?? 0, 2),
                round($r[0]['capex_total']        ?? 0, 2),
                round($r[0]['opex_total']         ?? 0, 2),
                round($r[0]['unclassified_total'] ?? 0, 2),
            ]);

            // ── Item detail rows — vendor + POs blank, indented under summary ──
            foreach ($itemsMap[$vid] ?? [] as $item) {
                fputcsv($fp, [
                    '',   // Vendor
                    '',   // # POs
                    $item['item_name'],
                    round($item['qty'],          3),
                    round($item['base'],         2),
                    round($item['tax'],          2),
                    round($item['excise_total'], 2),
                    round($item['addl_excise_total'], 2),
                    round($item['vat_total'],    2),
                    round($item['tcs_total'],    2),
                    round($item['total'],        2),
                    round($item['capex_total'],  2),
                    round($item['opex_total'],   2),
                    round($item['unclassified'], 2),
                ]);
            }
        }

        fclose($fp);
        exit;
    }

    public function vendor_wise_purchase_items()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->response->body(json_encode(['error' => 'Unauthorized']));
        }
        $this->autoRender = false;
        $this->response->type('application/json');

        $vendor_id = (int)($this->request->query('vendor_id') ?? 0);
        $startDT   = trim($this->request->query('start') ?? '');
        $endDT     = trim($this->request->query('end')   ?? '');
        $store_id  = (int)($this->request->query('store_id') ?? 0);

        if (!$vendor_id || !$startDT || !$endDT) {
            return $this->response->body(json_encode(['error' => 'Invalid parameters']));
        }
        
        $startdate = date('Y-m-d', strtotime($startDT));
        $enddate   = date('Y-m-d', strtotime($endDT));

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $store_id ?: null);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipo.vendor_id = $vendor_id
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin') $where .= " AND ipo.location_id IN ($userLocationId)";

        
        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipoi.sku_id,
                ipoi.sku_name,
                SUM(ipoi.sku_quantity)                                                              AS qty,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value, 0))     AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value, 0))                                          AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0))                                               AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0))                                          AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0))                                                       AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0))                                                       AS tcs_total,
                SUM(ipoi.sku_amount)                                                                AS total,
                SUM(CASE WHEN isku.expense_type = 'CAPEX' THEN ipoi.sku_amount ELSE 0 END)         AS capex_total,
                SUM(CASE WHEN isku.expense_type = 'OPEX'  THEN ipoi.sku_amount ELSE 0 END)         AS opex_total,
                SUM(CASE WHEN isku.expense_type NOT IN ('CAPEX','OPEX') THEN ipoi.sku_amount ELSE 0 END) AS unclassified_total
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            LEFT JOIN inventory_skus isku ON isku.id = ipoi.sku_id
            $where
            GROUP BY ipoi.sku_id, ipoi.sku_name
            ORDER BY total DESC
        ");

        $result = [];
        foreach ($rows as $row) {
            // CakePHP puts real table columns under model alias key,
            // computed/aggregated columns under [0]
            // sku_id and sku_name are real columns on ipoi table → ['ipoi']
            // All SUM/CASE results → [0]
            $item_name = $row['ipoi']['sku_name'] ?? $row[0]['sku_name'] ?? '';

            // Fallback: if both empty, try the raw key CakePHP sometimes uses
            if ($item_name === '') {
                $item_name = $row['Inventory_purchase_order_items']['sku_name'] ?? '';
            }

            $result[] = [
                'item_name'    => $item_name,
                'qty'          => $row[0]['qty']               ?? 0,
                'base'         => $row[0]['base']              ?? 0,
                'tax'          => $row[0]['tax']               ?? 0,
                'excise_total' => $row[0]['excise_total']      ?? 0,
                'addl_excise_total' => $row[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $row[0]['vat_total']         ?? 0,
                'tcs_total'    => $row[0]['tcs_total']         ?? 0,
                'total'        => $row[0]['total']             ?? 0,
                'capex_total'  => $row[0]['capex_total']       ?? 0,
                'opex_total'   => $row[0]['opex_total']        ?? 0,
                'unclassified' => $row[0]['unclassified_total'] ?? 0,
            ];
        }

        // ── Debug: if result empty, return raw first row so you can see actual keys ──
        if (empty($result)) {
            $raw = !empty($rows) ? $rows[0] : [];
            return $this->response->body(json_encode([
                'items' => [],
                'debug_raw_first_row' => $raw,
                'debug_row_count' => count($rows)
            ]));
        }

        return $this->response->body(json_encode(['items' => $result]));
    }
    
    public function item_wise_purchase_report()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime(date('Y-m-01')));
        $endDate   = date('m/d/Y');
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            $dates = explode(" - ", $daterange);
            $startDate = date('m/d/Y', strtotime(trim($dates[0])));
            $endDate   = date('m/d/Y', strtotime(trim($dates[1])));
        }
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));
        
        

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $store_id = !empty($this->request->data['Search']['store']) ? (int)$this->request->data['Search']['store'] : null;

        $location_id = !empty($store_id) ? $store_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin') $where .= " AND ipo.location_id IN ($userLocationId)";


        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipoi.sku_id,
                ipoi.sku_name AS sku,
                ipoi.sku_unit_name AS unit,
                isku.expense_type,
                SUM(ipoi.sku_quantity) AS qty,
                ROUND(SUM(ipoi.sku_price * ipoi.sku_quantity) / NULLIF(SUM(ipoi.sku_quantity),0), 4) AS weighted_avg_rate,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total,
                COUNT(DISTINCT ipo.vendor_id) AS vendor_count
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            LEFT JOIN inventory_skus isku ON isku.id = ipoi.sku_id
            $where
            GROUP BY ipoi.sku_id, ipoi.sku_name, ipoi.sku_unit_name, isku.expense_type
            ORDER BY ipoi.sku_name ASC
        ");

        $itemData = [];
        foreach ($rows as $r) {
            $itemData[] = [
                'sku_id'        => $r['ipoi']['sku_id']        ?? $r[0]['sku_id']           ?? 0,
                'sku'           => $r['ipoi']['sku']           ?? $r[0]['sku']              ?? '',
                'unit'          => $r['ipoi']['unit']          ?? $r[0]['unit']             ?? '',
                'expense_type'  => $r['isku']['expense_type']  ?? $r[0]['expense_type']     ?? 'UNCLASSIFIED',
                'qty'           => $r[0]['qty']                ?? 0,
                'weighted_avg'  => $r[0]['weighted_avg_rate']  ?? 0,
                'base'          => $r[0]['base']               ?? 0,
                'tax'           => $r[0]['tax']                ?? 0,
                'excise_total' => $r[0]['excise_total']           ?? 0,
                'addl_excise_total' => $r[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $r[0]['vat_total']              ?? 0,
                'tcs_total'    => $r[0]['tcs_total']              ?? 0,
                'total'         => $r[0]['total']              ?? 0,
                'vendor_count'  => $r[0]['vendor_count']       ?? 0,
            ];
        }

        $conditions = ['status' => 1];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $conditions['id IN'] = array_map('trim', explode(',', $userLocationId));
        }
        $stores = $this->Location->find('all', ['conditions' => $conditions]);

        $this->set('startdate', $startDate . ' - ' . $endDate);
        $this->set(compact('stores', 'itemData'));
    }

    public function item_wise_purchase_export()
    {
        $this->autoRender = false;
        $date_range = $_GET['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
        $dates      = explode(" - ", $date_range);
        $startdate  = date('Y-m-d', strtotime(trim($dates[0])));
        $enddate    = date('Y-m-d', strtotime(trim($dates[1])));
        
        
        $store_id   = !empty($_GET['store']) ? (int)$_GET['store'] : null;

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;


        $location_id = !empty($store_id) ? $store_id : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];


        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin' && !empty($userLocationId)) $where .= " AND ipo.location_id IN ($userLocationId)";

        // ── SKU-level summary (same as screen query) ──
        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipoi.sku_id,
                ipoi.sku_name AS sku,
                ipoi.sku_unit_name AS unit,
                isku.expense_type,
                SUM(ipoi.sku_quantity) AS qty,
                ROUND(SUM(ipoi.sku_price * ipoi.sku_quantity) / NULLIF(SUM(ipoi.sku_quantity),0), 4) AS weighted_avg_rate,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total,
                COUNT(DISTINCT ipo.vendor_id) AS vendor_count
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            LEFT JOIN inventory_skus isku ON isku.id = ipoi.sku_id
            $where
            GROUP BY ipoi.sku_id, ipoi.sku_name, ipoi.sku_unit_name, isku.expense_type
            ORDER BY ipoi.sku_name ASC
        ");

        // ── Collect SKU IDs to fetch vendor lines in one query ──
        $skuIds = [];
        foreach ($rows as $r) {
            $sid = $r['ipoi']['sku_id'] ?? $r[0]['sku_id'] ?? null;
            if ($sid) $skuIds[] = (int)$sid;
        }

        // ── Vendor lines grouped by sku_id + vendor (same logic as AJAX action) ──
        $vendorsMap = [];
        if (!empty($skuIds)) {
            $inList   = implode(',', array_unique($skuIds));
            $vendorRows = $this->Inventory_purchase_order->query("
                SELECT
                    ipoi.sku_id,
                    ipo.vendor_id,
                    ipo.vendor_name AS vendor,
                    COUNT(DISTINCT ipo.p_o_id) AS po_count,
                    SUM(ipoi.sku_quantity) AS qty,
                    ROUND(SUM(ipoi.sku_price * ipoi.sku_quantity) / NULLIF(SUM(ipoi.sku_quantity),0), 4) AS weighted_avg_rate,
                    SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                    SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                    SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                    SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                    SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                    SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                    SUM(ipoi.sku_amount) AS total
                FROM inventory_purchase_order ipo
                LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
                WHERE ipo.direct_purchase = 0 AND ipo.status != 0 AND ipoi.status != 0
                    AND ipoi.sku_id IN ($inList)
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'
                GROUP BY ipoi.sku_id, ipo.vendor_id, ipo.vendor_name
                ORDER BY ipoi.sku_id, total DESC
            ");

            foreach ($vendorRows as $vr) {
                $sid = $vr['ipoi']['sku_id'] ?? $vr[0]['sku_id'] ?? null;
                if (!$sid) continue;
                $vendorsMap[$sid][] = [
                    'vendor'       => $vr['ipo']['vendor']       ?? $vr[0]['vendor']            ?? '',
                    'po_count'     => $vr[0]['po_count']         ?? 0,
                    'qty'          => $vr[0]['qty']              ?? 0,
                    'weighted_avg' => $vr[0]['weighted_avg_rate'] ?? 0,
                    'base'         => $vr[0]['base']             ?? 0,
                    'tax'          => $vr[0]['tax']              ?? 0,
                    'excise_total' => $vr[0]['excise_total']      ?? 0,
                    'addl_excise_total' => $vr[0]['addl_excise_total'] ?? 0,
                    'vat_total'    => $vr[0]['vat_total']         ?? 0,
                    'tcs_total'    => $vr[0]['tcs_total']         ?? 0,
                    'total'        => $vr[0]['total']            ?? 0,
                ];
            }
        }

        // ── Output CSV ──
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Item_Wise_Purchase_' . $startdate . '_' . $enddate . '.csv"');
        $fp = fopen('php://output', 'w');

        // Columns cover both SKU summary rows and vendor detail rows
        fputcsv($fp, ['SKU','Unit','Type','# Vendors','Vendor','# POs','Qty','Wtd Avg Rate','Base','Tax','Excise','Addl. Excise','VAT','TCS','Total']);

        foreach ($rows as $r) {
            $sid = $r['ipoi']['sku_id'] ?? $r[0]['sku_id'] ?? null;

            // ── SKU summary row ──
            fputcsv($fp, [
                $r['ipoi']['sku']              ?? '',
                $r['ipoi']['unit']             ?? '',
                $r['isku']['expense_type']     ?? 'UNCLASSIFIED',
                $r[0]['vendor_count']          ?? 0,
                '',                                        // Vendor blank on summary row
                '',                                        // # POs blank on summary row
                round($r[0]['qty']               ?? 0, 3),
                round($r[0]['weighted_avg_rate'] ?? 0, 4),
                round($r[0]['base']              ?? 0, 2),
                round($r[0]['tax']               ?? 0, 2),
                round($r[0]['excise_total']      ?? 0, 2),
                round($r[0]['addl_excise_total'] ?? 0, 2),
                round($r[0]['vat_total']         ?? 0, 2),
                round($r[0]['tcs_total']         ?? 0, 2),
                round($r[0]['total']             ?? 0, 2),
            ]);

            // ── Vendor detail rows — SKU/Unit/Type/# Vendors blank, indented under summary ──
            foreach ($vendorsMap[$sid] ?? [] as $v) {
                fputcsv($fp, [
                    '',   // SKU
                    '',   // Unit
                    '',   // Type
                    '',   // # Vendors
                    $v['vendor'],
                    $v['po_count'],
                    round($v['qty'],          3),
                    round($v['weighted_avg'], 4),
                    round($v['base'],         2),
                    round($v['tax'],          2),
                    round($v['excise_total'], 2),
                    round($v['addl_excise_total'], 2),
                    round($v['vat_total'],    2),
                    round($v['tcs_total'],    2),
                    round($v['total'],        2),
                ]);
            }
        }

        fclose($fp);
        exit;
    }
    
    public function bills_without_sc() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        // AUTH USER
        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        // DATE SETUP
        $startDate          = date('m/d/Y');
        $endDate            = date('m/d/Y');
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $startdate          = date('Y-m-d', strtotime($startDate));
        $enddate            = date('Y-m-d', strtotime($endDate));
        $cashier_id         = '';

        // POST HANDLING
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            if (!empty($daterange)) {
                $dates      = explode(" - ", $daterange);
                $start_date = trim($dates[0]);
                $end_date   = trim($dates[1] ?? $dates[0]);
                $yourStartDateValue = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
                $startdate  = date('Y-m-d', strtotime($start_date));
                $enddate    = date('Y-m-d', strtotime($end_date));
            }
            $cashier_id = $this->request->data['Search']['cashier'] ?? '';
        }

        // SHIFT TIMES
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // STORE FILTERS
        $storeFilterT   = '';
        $storeFilterO   = '';
        $storeFilterPay = '';  // ← for payment subquery (uses 'ot' alias)

        if ($userRole !== 'admin') {
            if (!empty($cashier_id)) {
                $sid            = (int)$cashier_id;
                $storeFilterT   = " AND t.store_id = $sid";
                $storeFilterO   = " AND o.store_id = $sid";
                $storeFilterPay = " AND ot.store_id = $sid";  // ← fixed alias
            } else {
                $list = is_array($userLocationId)
                    ? implode(',', array_map('intval', $userLocationId))
                    : implode(',', array_map('intval', array_map('trim', explode(',', $userLocationId))));
                $storeFilterT   = " AND t.store_id IN ($list)";
                $storeFilterO   = " AND o.store_id IN ($list)";
                $storeFilterPay = " AND ot.store_id IN ($list)";  // ← fixed alias
            }
        } elseif (!empty($cashier_id)) {
            $sid            = (int)$cashier_id;
            $storeFilterT   = " AND t.store_id = $sid";
            $storeFilterO   = " AND o.store_id = $sid";
            $storeFilterPay = " AND ot.store_id = $sid";  // ← fixed alias
        }

        // LOCATION DROPDOWN
        $conditions = ['status' => 1, 'type' => 'Outlet','service_charge_rate !='=>0];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $conditions['id IN'] = array_map('trim', is_array($userLocationId)
                ? $userLocationId
                : explode(',', $userLocationId)
            );
        }
        $users = $this->Location->find('all', [
            'conditions' => $conditions,
            'fields'     => ['id', 'name'],
            'order'      => ['name ASC']
        ]);

        // After fetching $users, build a per-store SC rate map
        $scRateMap = [];

        // Fetch SC rates from locations table (adjust field name if different)
        $locationRates = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'type' => 'Outlet','service_charge_rate !='=>0],
            'fields'     => ['id', 'name', 'service_charge_rate'], // ← adjust field name
        ]);
        foreach ($locationRates as $loc) {
            $scRateMap[$loc['Location']['id']] = (float)($loc['Location']['service_charge_rate'] ?? 0);
        }

        // MAIN QUERY
        $removedChargesData = $this->Order_transaction->query("
            SELECT
                t.created                                                      AS bill_print_time,
                pay.created                                                      AS bill_settle_time,
                DATE_SUB(t.created, INTERVAL 10 SECOND)                        AS sc_removed_time,
                t.reference_id                                                  AS bill_reference_id,
                t.orderno                                                       AS order_number,
                l.name                                                          AS location_name,
                COALESCE(l.service_charge_rate, 0)                              AS store_sc_rate,  -- ← ADD THIS
                ROUND(dedup.unique_taxable * COALESCE(l.service_charge_rate, 0) / 100, 2)    AS sc_amount,
                pay.payment_modes,
                CONCAT(
                    COALESCE(u.username, 'N/A'),
                    ' (',
                    COALESCE(u.name, 'N/A'),
                    ')'
                )                                                               AS removed_by_user,
                COALESCE(u.username, 'N/A')                                     AS cashier_username,
                COALESCE(t.waiter_name, '-')                                    AS waiter_username,
                COALESCE(t.name, '')                                            AS customer_name,
                COALESCE(t.phone, '')                                           AS customer_mobile,
                COALESCE(t.remarks, '')                                         AS edit_bill_comment,
                t.pax                                                           AS guest_count

            FROM order_transactions t

            INNER JOIN locations l
                ON l.id = t.store_id and l.service_charge_rate!=0

            LEFT JOIN users u
                ON u.id = t.cashier_id

            -- Payment modes: uses 'ot' alias inside subquery, NOT 't'
            LEFT JOIN (
                SELECT
                    op.reference_id,
                    GROUP_CONCAT(op.type_value ORDER BY op.id SEPARATOR ' | ') AS payment_modes,
                    op.created
                FROM order_payments op
                INNER JOIN order_transactions ot
                    ON  ot.reference_id = op.reference_id
                    AND ot.status       = 2
                    AND ot.modified     BETWEEN '$startDatetime' AND '$endDatetime'
                    $storeFilterPay
                WHERE op.status = 2
                GROUP BY op.reference_id
            ) pay ON pay.reference_id = t.reference_id

            -- Taxable amount scoped to store
            LEFT JOIN (
                SELECT
                    o.reference_id,
                    ROUND(
                        SUM(
                            (o.price * o.quantity)
                            * (1 - IFNULL(td.max_discount, 0) / 100)
                        ), 2
                    ) AS unique_taxable
                FROM order_orderhistories o
                LEFT JOIN (
                    SELECT
                        orderhistory_id,
                        store_id,
                        MAX(discount_percentage) AS max_discount
                    FROM order_tax
                    WHERE status = 2
                    GROUP BY orderhistory_id, store_id
                ) td ON  td.orderhistory_id = COALESCE(NULLIF(o.local_id, ''), o.id)
                     AND td.store_id        = o.store_id
                WHERE o.status = 2
                  $storeFilterO
                GROUP BY o.reference_id
            ) dedup ON dedup.reference_id = t.reference_id

            WHERE t.status      = 2
              AND t.servicecharge = 0
              AND t.created    BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilterT

            ORDER BY t.created DESC
        ");

        // SUMMARY TOTALS
        $totalSC      = 0.0;
        $totalTaxable = 0.0;
        $totalBills   = 0;

        if (!empty($removedChargesData)) {
            foreach ($removedChargesData as $row) {
                $r             = isset($row[0]) ? $row[0] : $row;
                $totalTaxable += (float)($r['total_taxable_amount'] ?? 0);
                $totalSC      += (float)($r['sc_amount']            ?? 0);
                $totalBills++;
            }
        }

        // For header display: if single store selected, show its rate; else show 'Mixed'
        if (!empty($cashier_id) && isset($scRateMap[$cashier_id])) {
            $scRate = $scRateMap[$cashier_id];
            $scRateLabel = $scRate . '%';
        } else {
            $scRate = null;
            $scRateLabel = 'Mixed'; // multiple stores with different rates
        }

        $this->set(compact(
            'users',
            'yourStartDateValue',
            'removedChargesData',
            'cashier_id',
            'totalSC',
            'totalTaxable',
            'totalBills',
            'scRate',
            'scRateLabel'   // ← new
        ));

    }

    public function bills_without_sc_export() {
        $date_range = $_GET['sdate'];
        $dates      = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date   = trim($dates[1] ?? $dates[0]);
        $startdate  = date('Y-m-d', strtotime($start_date));
        $enddate    = date('Y-m-d', strtotime($end_date));

        $cashier_id = $_GET['cashier'] ?? '';

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        // --- Store filters (same logic as main report) ---
        $storeFilterT   = '';
        $storeFilterO   = '';
        $storeFilterPay = '';

        if ($userRole !== 'admin') {
            if (!empty($cashier_id)) {
                $sid            = (int)$cashier_id;
                $storeFilterT   = " AND t.store_id = $sid";
                $storeFilterO   = " AND o.store_id = $sid";
                $storeFilterPay = " AND ot.store_id = $sid";
            } else {
                $list = is_array($userLocationId)
                    ? implode(',', array_map('intval', $userLocationId))
                    : implode(',', array_map('intval', array_map('trim', explode(',', $userLocationId))));
                $storeFilterT   = " AND t.store_id IN ($list)";
                $storeFilterO   = " AND o.store_id IN ($list)";
                $storeFilterPay = " AND ot.store_id IN ($list)";
            }
        } elseif (!empty($cashier_id)) {
            $sid            = (int)$cashier_id;
            $storeFilterT   = " AND t.store_id = $sid";
            $storeFilterO   = " AND o.store_id = $sid";
            $storeFilterPay = " AND ot.store_id = $sid";
        }

        // --- Shift times ---
        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // --- Store name for header ---
        if (!empty($cashier_id)) {
            $storeData = $this->Location->find('first', [
                'conditions' => ['Location.id' => (int)$cashier_id],
                'fields'     => ['Location.name']
            ]);
            $storeName = $storeData['Location']['name'] ?? 'Unknown';
        } else {
            $storeName = 'All Stores';
        }

        // --- Same query as main report ---
        $rows = $this->Order_transaction->query("
            SELECT
                t.created                                                       AS bill_print_time,
                pay.created                                                     AS bill_settle_time,
                DATE_SUB(t.created, INTERVAL 10 SECOND)                        AS sc_removed_time,
                t.reference_id                                                  AS bill_reference_id,
                t.orderno                                                       AS order_number,
                l.name                                                          AS location_name,
                COALESCE(l.service_charge_rate, 0)                              AS store_sc_rate,
                ROUND(dedup.unique_taxable * COALESCE(l.service_charge_rate, 0) / 100, 2) AS sc_amount,
                pay.payment_modes,
                CONCAT(
                    COALESCE(u.username, 'N/A'), ' (', COALESCE(u.name, 'N/A'), ')'
                )                                                               AS removed_by_user,
                COALESCE(u.username, 'N/A')                                     AS cashier_username,
                COALESCE(t.waiter_name, '-')                                    AS waiter_username,
                COALESCE(t.name, '')                                            AS customer_name,
                COALESCE(t.phone, '')                                           AS customer_mobile,
                COALESCE(t.remarks, '')                                         AS edit_bill_comment,
                t.pax                                                           AS guest_count

            FROM order_transactions t

            INNER JOIN locations l ON l.id = t.store_id and  l.service_charge_rate != 0

            LEFT JOIN users u ON u.id = t.cashier_id

            LEFT JOIN (
                SELECT
                    op.reference_id,
                    GROUP_CONCAT(op.type_value ORDER BY op.id SEPARATOR ' | ') AS payment_modes,op.created
                FROM order_payments op
                INNER JOIN order_transactions ot
                    ON  ot.reference_id = op.reference_id
                    AND ot.status       = 2
                    AND ot.modified     BETWEEN '$startDatetime' AND '$endDatetime'
                    $storeFilterPay
                WHERE op.status = 2
                GROUP BY op.reference_id
            ) pay ON pay.reference_id = t.reference_id

            LEFT JOIN (
                SELECT
                    o.reference_id,
                    ROUND(
                        SUM(
                            (o.price * o.quantity)
                            * (1 - IFNULL(td.max_discount, 0) / 100)
                        ), 2
                    ) AS unique_taxable
                FROM order_orderhistories o
                LEFT JOIN (
                    SELECT orderhistory_id, store_id, MAX(discount_percentage) AS max_discount
                    FROM order_tax
                    WHERE status = 2
                    GROUP BY orderhistory_id, store_id
                ) td ON  td.orderhistory_id = COALESCE(NULLIF(o.local_id, ''), o.id)
                     AND td.store_id        = o.store_id
                WHERE o.status = 2
                  $storeFilterO
                GROUP BY o.reference_id
            ) dedup ON dedup.reference_id = t.reference_id

            WHERE t.status      = 2
              AND t.servicecharge = 0
              AND t.created    BETWEEN '$startDatetime' AND '$endDatetime'
              $storeFilterT

            ORDER BY t.created DESC
        ");

        // --- Build Excel ---
        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")->setTitle("Service Charges Removal Report");
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Sc_removal_report');

        // Report header rows
        $sheet->setCellValue('A1', $storeName);
        $sheet->setCellValue('A2', 'Service Charge Removal Report (' . $startdate . ' to ' . $enddate . ')');
        $sheet->setCellValue('A3', 'Generated On: ' . date('Y-m-d H:i:s'));

        // Column headers — match view table exactly
        $headers = [
            '#',
            'Order Number',
            'Location',
            'SC Rate (%)',
            'SC Removed',
            'Payment Mode',
            'SC Removed At',
            'Bill Print Time',
            'Bill Settle Time',
            'Removed By',
            'Cashier',
            'Waiter',
            'Customer Name',
            'Customer Mobile',
            'Comment',
            'Guests',
        ];
        $sheet->fromArray($headers, null, 'A5');

        // Header style
        $headerStyle = $sheet->getStyle('A5:P5');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('E0E0E0');

        $rowNum     = 6;
        $serial     = 1;
        $totalSC    = 0.0;
        $totalBills = 0;

        foreach ($rows as $r) {
            $row = isset($r[0]) ? $r[0] : $r;

            $scAmount = (float)($row['sc_amount']    ?? 0);
            $scRate   = (float)($row['store_sc_rate'] ?? 0);
            $totalSC += $scAmount;
            $totalBills++;

            $sheet->setCellValue("A$rowNum", $serial++);
            $sheet->setCellValue("B$rowNum", $r['t']['order_number']    ?? '-');
            $sheet->setCellValue("C$rowNum", $r['l']['location_name']   ?? '-');
            $sheet->setCellValue("D$rowNum", number_format($scRate,  1));
            $sheet->setCellValue("E$rowNum", number_format($scAmount, 2));
            $sheet->setCellValue("F$rowNum", $r['pay']['payment_modes']   ?? '-');
            $sheet->setCellValue("G$rowNum", !empty($row['sc_removed_time'])  ? date('d-m-Y h:i:s A', strtotime($row['sc_removed_time']))  : '-');
            $sheet->setCellValue("H$rowNum", !empty($r['t']['bill_print_time'])  ? date('d-m-Y h:i:s A', strtotime($r['t']['bill_print_time']))  : '-');
            $sheet->setCellValue("I$rowNum", !empty($r['pay']['bill_settle_time'])  ? date('d-m-Y h:i:s A', strtotime($r['pay']['bill_settle_time']))  : '-');
            $sheet->setCellValue("J$rowNum", $row['removed_by_user'] ?? '-');
            $sheet->setCellValue("K$rowNum", $row['cashier_username'] ?? '-');
            $sheet->setCellValue("L$rowNum", $row['waiter_username']  ?? '-');
            $sheet->setCellValue("M$rowNum", $row['customer_name']    ?: '-');
            $sheet->setCellValue("N$rowNum", $row['customer_mobile']  ?: '-');
            $sheet->setCellValue("O$rowNum", $row['edit_bill_comment'] ?: '-');
            $sheet->setCellValue("P$rowNum", $r['t']['guest_count']      ?? '-');
            $rowNum++;
        }

        // Summary footer
        $rowNum++;
        $sheet->setCellValue("A$rowNum", 'Total Bills');
        $sheet->setCellValue("B$rowNum", $totalBills);
        $sheet->setCellValue("D$rowNum", 'Total SC Removed');
        $sheet->setCellValue("E$rowNum", number_format($totalSC, 2));
        $footerStyle = $sheet->getStyle("A$rowNum:P$rowNum");
        $footerStyle->getFont()->setBold(true);
        $footerStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $footerStyle->getFill()->getStartColor()->setRGB('F0F0F0');

        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Sc_removal_report(' . $startdate . '--' . $enddate . ').xls"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function bills_without_sc_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {

            $date_range = $this->request->data['sdate'];
            $dates      = explode(" - ", $date_range);
            $start_date = trim($dates[0]);
            $end_date   = trim($dates[1] ?? $dates[0]);
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));

            $cashier_id = $this->request->data['cashier'] ?? '';

            $authUser       = $this->Session->read('Auth.User');
            $userRole       = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            // --- Store filters (identical to main report) ---
            $storeFilterT   = '';
            $storeFilterO   = '';
            $storeFilterPay = '';

            if ($userRole !== 'admin') {
                if (!empty($cashier_id)) {
                    $sid            = (int)$cashier_id;
                    $storeFilterT   = " AND t.store_id = $sid";
                    $storeFilterO   = " AND o.store_id = $sid";
                    $storeFilterPay = " AND ot.store_id = $sid";
                } else {
                    $list = is_array($userLocationId)
                        ? implode(',', array_map('intval', $userLocationId))
                        : implode(',', array_map('intval', array_map('trim', explode(',', $userLocationId))));
                    $storeFilterT   = " AND t.store_id IN ($list)";
                    $storeFilterO   = " AND o.store_id IN ($list)";
                    $storeFilterPay = " AND ot.store_id IN ($list)";
                }
            } elseif (!empty($cashier_id)) {
                $sid            = (int)$cashier_id;
                $storeFilterT   = " AND t.store_id = $sid";
                $storeFilterO   = " AND o.store_id = $sid";
                $storeFilterPay = " AND ot.store_id = $sid";
            }

            // --- Shift times ---
            $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, isset($cashier_id) ? $cashier_id : (isset($location_id) ? $location_id : (isset($store_id) ? $store_id : null)));
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

            // --- Store name for header ---
            if (!empty($cashier_id)) {
                $store = $this->Location->find('first', [
                    'conditions' => ['Location.id' => (int)$cashier_id],
                    'fields'     => ['Location.name']
                ]);
                $store_name = $store['Location']['name'] ?? 'Unknown';
            } else {
                $store_name = 'All Stores';
            }

            // --- Main query (identical to main report) ---
            $rows = $this->Order_transaction->query("
                SELECT
                    t.created                                                          AS bill_print_time,
                    pay.created                                                          AS bill_settle_time,
                    DATE_SUB(t.created, INTERVAL 10 SECOND)                            AS sc_removed_time,
                    t.reference_id                                                      AS bill_reference_id,
                    t.orderno                                                           AS order_number,
                    l.name                                                              AS location_name,
                    COALESCE(l.service_charge_rate, 0)                                  AS store_sc_rate,
                    ROUND(dedup.unique_taxable * COALESCE(l.service_charge_rate, 0) / 100, 2) AS sc_amount,
                    pay.payment_modes,
                    CONCAT(
                        COALESCE(u.username, 'N/A'), ' (', COALESCE(u.name, 'N/A'), ')'
                    )                                                                   AS removed_by_user,
                    COALESCE(u.username, 'N/A')                                         AS cashier_username,
                    COALESCE(t.waiter_name, '-')                                        AS waiter_username,
                    COALESCE(t.name, '')                                                AS customer_name,
                    COALESCE(t.phone, '')                                               AS customer_mobile,
                    COALESCE(t.remarks, '')                                             AS edit_bill_comment,
                    t.pax                                                               AS guest_count

                FROM order_transactions t

                INNER JOIN locations l
                    ON l.id = t.store_id and l.service_charge_rate != 0

                LEFT JOIN users u
                    ON u.id = t.cashier_id

                LEFT JOIN (
                    SELECT
                        op.reference_id,
                        GROUP_CONCAT(op.type_value ORDER BY op.id SEPARATOR ' | ') AS payment_modes,op.created
                    FROM order_payments op
                    INNER JOIN order_transactions ot
                        ON  ot.reference_id = op.reference_id
                        AND ot.status       = 2
                        AND ot.modified     BETWEEN '$startDatetime' AND '$endDatetime'
                        $storeFilterPay
                    WHERE op.status = 2
                    GROUP BY op.reference_id
                ) pay ON pay.reference_id = t.reference_id

                LEFT JOIN (
                    SELECT
                        o.reference_id,
                        ROUND(
                            SUM(
                                (o.price * o.quantity)
                                * (1 - IFNULL(td.max_discount, 0) / 100)
                            ), 2
                        ) AS unique_taxable
                    FROM order_orderhistories o
                    LEFT JOIN (
                        SELECT
                            orderhistory_id,
                            store_id,
                            MAX(discount_percentage) AS max_discount
                        FROM order_tax
                        WHERE status = 2
                        GROUP BY orderhistory_id, store_id
                    ) td ON  td.orderhistory_id = COALESCE(NULLIF(o.local_id, ''), o.id)
                         AND td.store_id        = o.store_id
                    WHERE o.status = 2
                      $storeFilterO
                    GROUP BY o.reference_id
                ) dedup ON dedup.reference_id = t.reference_id

                WHERE t.status       = 2
                  AND t.servicecharge = 0
                  AND t.created      BETWEEN '$startDatetime' AND '$endDatetime'
                  $storeFilterT

                ORDER BY t.created DESC
            ");

            // --- Flatten rows + calculate totals ---
            $datas      = [];
            $totalSC    = 0.0;
            $totalBills = 0;

            foreach ($rows as $r) {
                $scAmount = isset($r[0]['sc_amount']) ? (float)$r[0]['sc_amount'] : 0;

                $totalSC += $scAmount;
                $totalBills++;

                // push original row (no filtering)
                $datas[] = $r;
            }

            $this->set(compact(
                'startdate',
                'enddate',
                'store_name',
                'datas',
                'totalSC',
                'totalBills'
            ));

            $this->render('bills_without_sc_pdf');
        }
    }

    private function _getMaterialUsageData($startdate, $enddate, $startDatetime, $endDatetime, $locationCondition, $damageLocCond) {
        // ── STEP 1: Product sales (billed vs NC) ─────────────────────────────
        $salesRows = $this->Order_orderhistory->query("
            SELECT
                o.product_id,
                MAX(o.product_name) AS product_name,
                SUM(CASE WHEN rc.is_nc = 0 THEN o.quantity ELSE 0 END) AS billed_qty,
                SUM(CASE WHEN rc.is_nc = 1 THEN o.quantity ELSE 0 END) AS nc_qty
            FROM order_orderhistories o
            JOIN (
                SELECT reference_id, store_id,
                    MAX(CASE WHEN type = 7 THEN 1 ELSE 0 END) AS is_nc
                FROM order_payments
                WHERE status = 2
                GROUP BY reference_id, store_id
            ) rc ON rc.reference_id = o.reference_id AND rc.store_id = o.store_id
            WHERE o.status = 2
              AND o.created BETWEEN '$startDatetime' AND '$endDatetime'
              $locationCondition
            GROUP BY o.product_id
            HAVING SUM(o.quantity) > 0
        ");

        $productSales = [];
        foreach ($salesRows as $row) {
            $pid = intval($row['o']['product_id'] ?? $row[0]['product_id']);
            $productSales[$pid] = [
                'name'   => $row['o']['product_name'] ?? $row[0]['product_name'],
                'billed' => floatval($row['o']['billed_qty'] ?? $row[0]['billed_qty']),
                'nc'     => floatval($row['o']['nc_qty']     ?? $row[0]['nc_qty']),
            ];
        }

        // ── STEP 2: Load recipe links ─────────────────────────────────────────
        $productIdList = implode(',', array_map('intval', array_keys($productSales) ?: [0]));

        $productLinkRows = $this->Inventory_sku->query("
            SELECT parent_id, component_type, component_id, component_name,
                   quantity, conversion_quantity, status
            FROM inventory_linking
            WHERE parent_type = 'Product'
              AND component_type IN ('Sku', 'Semifinished')
              AND status = 1
              AND parent_id IN ($productIdList)
        ");

        $productLinks = [];
        $allComponentSkuIds = [];
        foreach ($productLinkRows as $r) {
            $d = $r['inventory_linking'] ?? $r[0];
            $pid = intval($d['parent_id']);
            $productLinks[$pid][] = $d;
            if ($d['component_type'] === 'Sku') {
                $allComponentSkuIds[] = intval($d['component_id']);
            }
        }

        $sfInfoRows = $this->Inventory_sku->query("
            SELECT id, name, consumption_factor FROM inventory_semifinished WHERE status = 1
        ");
        $sfInfo = [];
        foreach ($sfInfoRows as $r) {
            $d = $r['inventory_semifinished'] ?? $r[0];
            $sfInfo[intval($d['id'])] = [
                'name'               => $d['name'],
                'consumption_factor' => floatval($d['consumption_factor'] ?: 1),
            ];
        }

        $sfLinkRows = $this->Inventory_sku->query("
            SELECT parent_id, component_type, component_id, component_name, quantity, conversion_quantity
            FROM inventory_linking
            WHERE parent_type = 'Semifinished'
              AND component_type IN ('Sku', 'Semifinished')
              AND status = 1
        ");
        $sfLinks = [];
        foreach ($sfLinkRows as $r) {
            $d = $r['inventory_linking'] ?? $r[0];
            $sfid = intval($d['parent_id']);
            $sfLinks[$sfid][] = $d;
            if ($d['component_type'] === 'Sku') {
                $allComponentSkuIds[] = intval($d['component_id']);
            }
        }

        // Load consumption_factor for all component SKUs
        $skuConsumptionFactors = [];
        $allComponentSkuIds = array_unique($allComponentSkuIds);
        if (!empty($allComponentSkuIds)) {
            $cfIdStr = implode(',', array_map('intval', $allComponentSkuIds));
            $cfRows  = $this->Inventory_sku->query("
                SELECT id, consumption_factor FROM inventory_skus WHERE id IN ($cfIdStr)
            ");
            foreach ($cfRows as $cfRow) {
                $cf = $cfRow['inventory_skus'] ?? $cfRow[0];
                $skuConsumptionFactors[intval($cf['id'])] = max(1.0, floatval($cf['consumption_factor'] ?: 1));
            }
        }

        // ── STEP 3: Expand recipes (iterative SF unwinding) ───────────────────
        $expandSF = null;
        $expandSF = function($sfId, $sfLinks, $sfInfo, $skuCF, $depth = 0) use (&$expandSF) {
            if ($depth > 6 || empty($sfLinks[$sfId])) return [];
            $skuQty = [];
            foreach ($sfLinks[$sfId] as $link) {
                if ($link['component_type'] === 'Sku') {
                    $skuid   = intval($link['component_id']);
                    $convQty = floatval($link['conversion_quantity']);
                    $cf      = max(1.0, $skuCF[$skuid] ?? 1.0);
                    $skuQty[$skuid] = ($skuQty[$skuid] ?? 0) + ($convQty / $cf);
                } elseif ($link['component_type'] === 'Semifinished') {
                    $nestedId       = intval($link['component_id']);
                    $nestedConvQty  = floatval($link['conversion_quantity']);
                    $nestedYield    = max(1.0, floatval($sfInfo[$nestedId]['consumption_factor'] ?? 1));
                    $nestedFraction = $nestedConvQty / $nestedYield;
                    $nestedSkus     = $expandSF($nestedId, $sfLinks, $sfInfo, $skuCF, $depth + 1);
                    foreach ($nestedSkus as $skuid => $qPerNestedBatch) {
                        $skuQty[$skuid] = ($skuQty[$skuid] ?? 0) + $nestedFraction * $qPerNestedBatch;
                    }
                }
            }
            return $skuQty;
        };

        $skuConsumption = [];
        foreach ($productSales as $pid => $sale) {
            $billedQty = $sale['billed'];
            $ncQty     = $sale['nc'];
            if (($billedQty + $ncQty) == 0) continue;

            $skuPerProduct = [];
            foreach ($productLinks[$pid] ?? [] as $link) {
                if ($link['component_type'] === 'Sku') {
                    $skuid    = intval($link['component_id']);
                    $convQty  = floatval($link['conversion_quantity']);
                    $cf       = max(1.0, $skuConsumptionFactors[$skuid] ?? 1.0);
                    $skuPerProduct[$skuid] = ($skuPerProduct[$skuid] ?? 0) + ($convQty / $cf);
                } elseif ($link['component_type'] === 'Semifinished') {
                    $sfId       = intval($link['component_id']);
                    $sfConvQty  = floatval($link['conversion_quantity']);
                    $sfYield    = max(1.0, floatval($sfInfo[$sfId]['consumption_factor'] ?? 1));
                    $sfFraction = $sfConvQty / $sfYield;
                    $sfSkuAmounts = $expandSF($sfId, $sfLinks, $sfInfo, $skuConsumptionFactors);
                    foreach ($sfSkuAmounts as $skuid => $qtyPerBatch) {
                        $skuPerProduct[$skuid] = ($skuPerProduct[$skuid] ?? 0) + $sfFraction * $qtyPerBatch;
                    }
                }
            }

            foreach ($skuPerProduct as $skuid => $qtyPerProduct) {
                if (!isset($skuConsumption[$skuid])) {
                    $skuConsumption[$skuid] = ['billed' => 0, 'nc' => 0];
                }
                $skuConsumption[$skuid]['billed'] += $qtyPerProduct * $billedQty;
                $skuConsumption[$skuid]['nc']     += $qtyPerProduct * $ncQty;
            }
        }

        // ── STEP 4: Damage by SKU ─────────────────────────────────────────────
        $skuDamage = [];
        $damageRows = $this->Inventory_sku->query("
            SELECT di.item_id, SUM(CAST(di.quantity AS DECIMAL(15,4))) AS damage_qty
            FROM inventory_damage_items di
            JOIN inventory_damage d ON d.damage_id = di.damage_id
            WHERE d.status = 1
              AND d.damage_date BETWEEN '$startdate' AND '$enddate'
              $damageLocCond
            GROUP BY di.item_id
        ");
        foreach ($damageRows as $r) {
            $d = $r['inventory_damage_items'] ?? $r[0];
            $skuDamage[intval($d['item_id'])] = floatval($d['damage_qty']);
        }

        // ── STEP 5: SKU master (name, UOM, avg price) ────────────────────────
        $allSkuIds = array_unique(array_merge(array_keys($skuConsumption), array_keys($skuDamage)));
        $skuMaster = [];
        if (!empty($allSkuIds)) {
            $skuIdStr = implode(',', array_map('intval', $allSkuIds));
            $skuRows  = $this->Inventory_sku->query("
                SELECT id, name,cat_name,subcat_name,unit_name, avg_stockin_price, last_stockin_price, price,(SELECT sum(tax_percentage) from inventory_sku_tax WHERE product_id=inventory_skus.id and status=1) as total_tax,(SELECT GROUP_CONCAT(CONCAT(tax_name, '(', tax_percentage, '%)') ORDER BY tax_id SEPARATOR ', ') from inventory_sku_tax WHERE product_id=inventory_skus.id and status=1) as tax_slabs 
                FROM inventory_skus WHERE id IN ($skuIdStr)
            ");
            foreach ($skuRows as $r) {
                $d = $r['inventory_skus'] ?? $r[0];
                $avgStockin  = floatval($d['avg_stockin_price']  ?? 0);
                $lastStockin = floatval($d['last_stockin_price'] ?? 0);
                $basePrice   = floatval($d['price']              ?? 0);

                $totalTax    = floatval($r[0]['total_tax']          ?? 0);
                $avgPrice = $avgStockin > 0 ? $avgStockin : ($lastStockin > 0 ? $lastStockin : $basePrice);
                $taxSlabs = $r[0]['tax_slabs']          ?? '';

                $hasTax   = !empty($taxSlabs);
                $taxValue = $hasTax ? round($avgPrice * $totalTax / 100, 2) : null;

                $skuMaster[intval($d['id'])] = [
                    'name'      => $d['name'],
                    'total_tax' => $r[0]['total_tax'] ?? 0,
                    'tax_slabs'             => ($r[0]['tax_slabs'] ?? ''),
                    'cat_name' => $d['cat_name'] ?? '',
                    'subcat_name' => $d['subcat_name'] ?? '',
                    'unit_name' => $d['unit_name'],
                    'avg_price_without_tax' => round($avgPrice,2),
                    'tax_value'             => $taxValue,
                    'avg_price'             => round($avgPrice + ($taxValue ?? 0), 2),
                ];
            }
        }

        // ── STEP 6: Build report rows ─────────────────────────────────────────
        $reportRows = [];
        foreach ($allSkuIds as $skuid) {
            if (!isset($skuMaster[$skuid])) continue;
            $billed = round($skuConsumption[$skuid]['billed'] ?? 0, 4);
            $nc     = round($skuConsumption[$skuid]['nc']     ?? 0, 4);
            $damage = round($skuDamage[$skuid]                ?? 0, 4);
            $total  = $billed + $nc + $damage;
            if ($total == 0) continue;
            $avgPx  = $skuMaster[$skuid]['avg_price'];
            $reportRows[] = [
                'sku_id'             => $skuid,
                'sku_name'           => $skuMaster[$skuid]['name'],
                'cat_name'           => $skuMaster[$skuid]['cat_name'],      // ✅ consistent
                'subcat_name'        => $skuMaster[$skuid]['subcat_name'],   // ✅ consistent
                'tax_slabs'          => $skuMaster[$skuid]['tax_slabs'],
                'total_tax'          => $skuMaster[$skuid]['total_tax'],   // ✅ make sure this is passed
                'tax_value'          => $skuMaster[$skuid]['tax_value'],
                'unit_name'          => $skuMaster[$skuid]['unit_name'],
                'avg_price'          => $avgPx,
                'billed_consumption' => $billed,
                'nc_consumption'     => $nc,
                'damage'             => $damage,
                'total_consumption'  => $total,
                'total_cost'         => round($total * $skuMaster[$skuid]['avg_price'], 2),
            ];
        }
        usort($reportRows, function($a, $b) { return strcmp($a['sku_name'], $b['sku_name']); });
        return $reportRows;
    }
    
    public function material_usage_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-7 days'));
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {
            $daterange = $this->request->data['daterange'];
            $dates     = explode(" - ", $daterange);
            $startDate = date('m/d/Y', strtotime(trim($dates[0])));
            $endDate   = date('m/d/Y', strtotime(trim($dates[1])));
            $startdate = date('Y-m-d', strtotime(trim($dates[0])));
            $enddate   = date('Y-m-d', strtotime(trim($dates[1])));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }

        // ===== [UPDATED] Read auth user FIRST before building any conditions =====
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';

        // Normalize location IDs from session
        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        // Deduplicate & sanitize
        $userLocationIds = array_values(array_unique($locationIdsArray));
        $userLocationIds = array_filter($userLocationIds, 'is_numeric');
        // ===== [END UPDATED] =====

        // ===== [UPDATED] Validate submitted location_id against user's allowed locations =====
        $location_id = isset($this->request->data['location_id'])
            ? intval($this->request->data['location_id'])
            : 0;

        if ($userRole !== 'admin') {
            // Non-admin cannot select "All Stores" — force their first assigned location
            if ($location_id === 0 && !empty($userLocationIds)) {
                $location_id = intval(reset($userLocationIds));
            }
            // Non-admin cannot pick a store outside their assigned list
            if ($location_id && !in_array($location_id, $userLocationIds)) {
                $location_id = intval(reset($userLocationIds));
            }
        }
        // ===== [END UPDATED] =====

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $location_id ?: null);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // ===== [UPDATED] Build SQL conditions based on role =====
        if ($userRole === 'admin') {
            $locationCondition = $location_id ? "AND o.store_id = $location_id" : "";
            $damageLocCond     = $location_id ? "AND d.location_id = $location_id" : "";
        } else {
            if ($location_id) {
                $locationCondition = "AND o.store_id = $location_id";
                $damageLocCond     = "AND d.location_id = $location_id";
            } else {
                $idList            = implode(',', $userLocationIds);
                $locationCondition = "AND o.store_id IN ($idList)";
                $damageLocCond     = "AND d.location_id IN ($idList)";
            }
        }
        // ===== [END UPDATED] =====

        $reportRows = $this->_getMaterialUsageData(
            $startdate, $enddate, $startDatetime, $endDatetime,
            $locationCondition, $damageLocCond
        );

        // ===== [UPDATED] Locations dropdown filtered by role =====
        $locationQuery = "SELECT id, name FROM locations WHERE status = 1";
        if ($userRole !== 'admin' && !empty($userLocationIds)) {
            $idList = implode(',', $userLocationIds);
            $locationQuery .= " AND id IN ($idList)";
        }
        $locationQuery .= " ORDER BY name";
        $locations = $this->Inventory_sku->query($locationQuery);
        // ===== [END UPDATED] =====

        $this->set('reportRows',  $reportRows);
        $this->set('startdate',   $startDate . ' - ' . $endDate);
        $this->set('locations',   $locations);
        $this->set('location_id', $location_id);
        $this->set('totalCost',   array_sum(array_column($reportRows, 'total_cost')));
    }

    public function material_usage_report_export() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->autoRender = false;

        // ── Parse date range ────────────────────────────────────────────────────
        $daterange = isset($_GET['daterange']) ? trim($_GET['daterange']) : '';
        $dates     = explode(" - ", $daterange);
        $startDate = trim($dates[0] ?? date('m/d/Y', strtotime('-7 days')));
        $endDate   = trim($dates[1] ?? date('m/d/Y'));
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));

        // ===== [UPDATED] Same role-based location restriction as view action =====
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';

        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(array_unique($locationIdsArray));
        $userLocationIds = array_filter($userLocationIds, 'is_numeric');

        $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;

        // Validate — non-admin cannot bypass via URL manipulation
        if ($userRole !== 'admin') {
            if ($location_id === 0 && !empty($userLocationIds)) {
                $location_id = intval(reset($userLocationIds));
            }
            if ($location_id && !in_array($location_id, $userLocationIds)) {
                $location_id = intval(reset($userLocationIds));
            }
        }

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $location_id ?: null);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        if ($userRole === 'admin') {
            $locationCondition = $location_id ? "AND o.store_id = $location_id" : "";
            $damageLocCond     = $location_id ? "AND d.location_id = $location_id" : "";
        } else {
            if ($location_id) {
                $locationCondition = "AND o.store_id = $location_id";
                $damageLocCond     = "AND d.location_id = $location_id";
            } else {
                $idList            = implode(',', $userLocationIds);
                $locationCondition = "AND o.store_id IN ($idList)";
                $damageLocCond     = "AND d.location_id IN ($idList)";
            }
        }
        // ===== [END UPDATED] =====

        // ── Location label for filename/header ───────────────────────────────────
        if ($location_id) {
            $locRow   = $this->Location->find('first', ['conditions' => ['id' => $location_id]]);
            $locLabel = $locRow ? $locRow['Location']['name'] : 'Store ' . $location_id;
        } else {
            $locLabel = 'All Stores';
        }

        // ── Get data ─────────────────────────────────────────────────────────────
        $reportRows = $this->_getMaterialUsageData(
            $startdate, $enddate, $startDatetime, $endDatetime,
            $locationCondition, $damageLocCond
        );

        // ── Totals ────────────────────────────────────────────────────────────────
        $totBilled = 0; $totNC = 0; $totDamage = 0;
        $totConsumption = 0; $totCost = 0;
        foreach ($reportRows as $row) {
            $totBilled      += $row['billed_consumption'];
            $totNC          += $row['nc_consumption'];
            $totDamage      += $row['damage'];
            $totConsumption += $row['total_consumption'];
            $totCost        += $row['total_cost'];
        }

        // ── Filename ──────────────────────────────────────────────────────────────
        $locSlug    = preg_replace('/[^A-Za-z0-9]+/', '_', $locLabel);
        $startSlug  = date('d_M_Y', strtotime($startdate));
        $endSlug    = date('d_M_Y', strtotime($enddate));
        $exportedAt = date('d_M_Y_Hi');
        $filename   = "Material_Usage_Cost_Report_{$locSlug}_{$startSlug}_to_{$endSlug}_Exported_{$exportedAt}.csv";

        // ── Stream CSV ────────────────────────────────────────────────────────────
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $fp = fopen('php://output', 'w');
        fputs($fp, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

        fputcsv($fp, ['MATERIAL USAGE & COST REPORT']);
        fputcsv($fp, ['Location:', $locLabel]);
        fputcsv($fp, ['Period:', date('d M Y', strtotime($startdate)) . ' to ' . date('d M Y', strtotime($enddate))]);
        fputcsv($fp, ['Exported:', date('d M Y, h:i A')]);
        fputcsv($fp, ['Total SKUs:', count($reportRows)]);
        fputcsv($fp, []);

        fputcsv($fp, [
            'Sr No', 'SKU Name', 'Category', 'Sub Category',
            'Tax Slabs (Total Tax %)', 'Tax Value (Rs)', 'UOM',
            'Avg Purchase Price (Rs)', 'Consumption via Billed Items',
            'Consumption via NC', 'Damage / Wastage',
            'Total Ideal Consumption', 'Total Ideal Cost (Rs)',
        ]);

        $sr = 1;
        foreach ($reportRows as $row) {
            $taxSlabDisplay  = !empty($row['tax_slabs']) ? $row['tax_slabs'] . ' (' . $row['total_tax'] . '%)' : '';
            $taxValueDisplay = ($row['tax_value'] !== null) ? number_format($row['tax_value'], 2, '.', '') : '';

            fputcsv($fp, [
                $sr++,
                $row['sku_name'],
                $row['cat_name'],
                $row['subcat_name'],
                $taxSlabDisplay,
                $taxValueDisplay,
                $row['unit_name'],
                number_format($row['avg_price'],          2, '.', ''),
                number_format($row['billed_consumption'], 4, '.', ''),
                number_format($row['nc_consumption'],     4, '.', ''),
                number_format($row['damage'],             4, '.', ''),
                number_format($row['total_consumption'],  4, '.', ''),
                number_format($row['total_cost'],         2, '.', ''),
            ]);
        }

        fputcsv($fp, []);
        fputcsv($fp, [
            '', 'TOTAL', '', '', '', '', '', '',
            number_format($totBilled,      4, '.', ''),
            number_format($totNC,          4, '.', ''),
            number_format($totDamage,      4, '.', ''),
            number_format($totConsumption, 4, '.', ''),
            number_format($totCost,        2, '.', ''),
        ]);

        fclose($fp);
    }

    private function _getEmployeeSalesData() {
        $startDate = date('m/d/Y');
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {
            $daterange = $this->request->data['daterange'] ?? ($this->request->data['Search']['startdate'] ?? '');
        } else {
            $daterange = $this->request->query['daterange'] ?? ($this->request->query['sdate'] ?? '');
        }

        if (!empty($daterange) && strpos($daterange, ' - ') !== false) {
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $end_date   = trim($dates[1]);
            $endDate    = date('m/d/Y', strtotime($end_date));
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $selectedStoreId = 0;
        if (!empty($this->request->data['Search']['cashier'])) {
            $selectedStoreId = intval($this->request->data['Search']['cashier']);
        } elseif (!empty($this->request->data['cashier'])) {
            $selectedStoreId = intval($this->request->data['cashier']);
        } elseif (!empty($this->request->query['cashier'])) {
            $selectedStoreId = intval($this->request->query['cashier']);
        }

        $location_id = $selectedStoreId > 0 ? $selectedStoreId : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];
        $daterangeValue = date('m/d/Y', strtotime($startdate)) . ' - ' . date('m/d/Y', strtotime($enddate));

        $this->loadModel('Location');
        $this->loadModel('User');
        $this->loadModel('Order_transaction');
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_payment');
        $this->loadModel('Order_tax');

        // Fetch stores accessible to user
        $conditions = ['status' => 1, 'type' => 'Outlet'];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', array_filter($userLocationIds));
        }
        $users = $this->Location->find('all', ['conditions' => $conditions, 'order' => ['name' => 'ASC']]);

        $storeCondition         = "";
        $storeConditionOt2      = "";
        $storeConditionTax      = "";
        $storeConditionOrderTax = "";
        $storeConditionPlain    = "";
        $oohFilter              = "";
        $otTaxFilter            = "";
        $storeName = "All Stores";
        if ($selectedStoreId > 0) {
            $storeCondition         = " AND ot.store_id = $selectedStoreId ";
            $storeConditionOt2      = " AND ot2.store_id = $selectedStoreId ";
            $storeConditionTax      = " AND tax.store_id = $selectedStoreId ";
            $storeConditionOrderTax = " AND order_tax.store_id = $selectedStoreId ";
            $storeConditionPlain    = " AND store_id = $selectedStoreId ";
            $oohFilter              = " AND ooh.store_id = $selectedStoreId ";
            $otTaxFilter            = " AND ot_tax.store_id = $selectedStoreId ";
            $storeLoc = $this->Location->find('first', ['conditions' => ['id' => $selectedStoreId]]);
            $storeName = $storeLoc['Location']['name'] ?? ('Store #' . $selectedStoreId);
        } elseif ($userRole !== 'admin' && !empty($userLocationId)) {
            $locIdsStr = is_array($userLocationId) ? implode(',', $userLocationId) : $userLocationId;
            if (!empty($locIdsStr)) {
                $storeCondition         = " AND ot.store_id IN ($locIdsStr) ";
                $storeConditionOt2      = " AND ot2.store_id IN ($locIdsStr) ";
                $storeConditionTax      = " AND tax.store_id IN ($locIdsStr) ";
                $storeConditionOrderTax = " AND order_tax.store_id IN ($locIdsStr) ";
                $storeConditionPlain    = " AND store_id IN ($locIdsStr) ";
                $oohFilter              = " AND ooh.store_id IN ($locIdsStr) ";
                $otTaxFilter            = " AND ot_tax.store_id IN ($locIdsStr) ";
            }
        }

        $isSearched = false;
        if ($this->request->is('post') || $this->request->is('put') || !empty($this->request->query['daterange']) || !empty($this->request->query['sdate'])) {
            $isSearched = true;
        }

        if (!$isSearched) {
            $cashierSalesList = [];
            $grandTotals = [
                'bills_count' => 0, 'qty_sold' => 0, 'gross_sales' => 0, 'discount' => 0,
                'net_sales' => 0, 'tax' => 0, 'net_payable' => 0, 'nc_sales' => 0
            ];
            $allCategories = [];
            $categoryGrandTotals = [];
            $allDishTypesNoSc = [];
            $grandTotalsDtNoSc = [];

            return compact(
                'users', 'selectedStoreId', 'storeName', 'daterangeValue',
                'cashierSalesList', 'grandTotals', 'allCategories', 'categoryGrandTotals',
                'allDishTypesNoSc', 'grandTotalsDtNoSc', 'isSearched'
            );
        }

        // Fetch transaction metrics grouped by cashier ID from order_orderhistories (excluding NC orders / type 7)
        $cashierRows = $this->Order_transaction->query("
            SELECT 
                COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id) AS cashier_id,
                COUNT(DISTINCT ot.reference_id) AS bills_count,
                SUM(IFNULL(ot.price, 0)) AS gross_sales,
                SUM(IFNULL(ot.discount, 0)) AS discount,
                SUM(IFNULL(ot.total_tax, 0)) AS tax,
                SUM(IFNULL(ot.amount, 0)) AS net_payable
            FROM order_transactions ot
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_orderhistories
                WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                GROUP BY reference_id, store_id
            ) b_cashier ON b_cashier.reference_id = ot.reference_id AND b_cashier.store_id = ot.store_id
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeCondition
              AND ot.reference_id NOT IN (
                  SELECT op_nc.reference_id FROM order_payments op_nc WHERE op_nc.type = 7 AND op_nc.status = 2
              )
            GROUP BY COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id)
            ORDER BY net_payable DESC
        ");

        $cashierRowsMap = [];
        foreach ($cashierRows as $row) {
            $cid = intval($row[0]['cashier_id'] ?? $row['b_cashier']['cashier_id'] ?? $row['ot']['cashier_id'] ?? 0);
            $cashierRowsMap[$cid] = [
                'bills_count' => intval($row[0]['bills_count'] ?? 0),
                'gross_sales' => floatval($row[0]['gross_sales'] ?? 0),
                'discount'    => floatval($row[0]['discount'] ?? 0),
                'tax'         => floatval($row[0]['tax'] ?? 0),
                'net_payable' => floatval($row[0]['net_payable'] ?? 0),
            ];
        }

        // Fetch Category Sales Breakdown grouped by orderhistory cashier_id (excluding NC orders / type 7)
        $catRows = $this->Order_orderhistory->query("
            SELECT 
                COALESCE(NULLIF(ooh.cashier_id, 0), ot.cashier_id) AS cashier_id,
                IFNULL(NULLIF(TRIM(ooh.category_name), ''), 'Other Dish') AS category_name,
                SUM(IFNULL(ooh.quantity, 0)) AS total_qty,
                SUM(IFNULL(ooh.total_price, 0)) AS total_cat_sales
            FROM order_orderhistories ooh
            JOIN order_transactions ot ON ot.reference_id = ooh.reference_id AND ot.status = 2
            WHERE ooh.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeCondition
              AND ot.reference_id NOT IN (
                  SELECT op_nc.reference_id FROM order_payments op_nc WHERE op_nc.type = 7 AND op_nc.status = 2
              )
            GROUP BY COALESCE(NULLIF(ooh.cashier_id, 0), ot.cashier_id), IFNULL(NULLIF(TRIM(ooh.category_name), ''), 'Other Dish')
        ");

        $cashierCategoryData = [];
        $allCategoriesMap = [];
        $cashierQtyMap = [];
        foreach ($catRows as $cr) {
            $cid = intval($cr[0]['cashier_id'] ?? $cr['ooh']['cashier_id'] ?? $cr['ot']['cashier_id'] ?? 0);
            $cat = trim($cr[0]['category_name'] ?? 'Other Dish');
            $cSales = floatval($cr[0]['total_cat_sales'] ?? 0);
            $cQty   = floatval($cr[0]['total_qty'] ?? 0);

            $cashierCategoryData[$cid][$cat] = ($cashierCategoryData[$cid][$cat] ?? 0) + $cSales;
            $cashierQtyMap[$cid] = ($cashierQtyMap[$cid] ?? 0) + $cQty;
            $allCategoriesMap[$cat] = true;
        }
        $allCategories = array_keys($allCategoriesMap);
        sort($allCategories);

        $storeConditionOp = "";
        if ($selectedStoreId > 0) {
            $storeConditionOp = " AND op.store_id = $selectedStoreId ";
        } elseif ($userRole !== 'admin' && !empty($userLocationId)) {
            $locIdsStr = is_array($userLocationId) ? implode(',', $userLocationId) : $userLocationId;
            if (!empty($locIdsStr)) {
                $storeConditionOp = " AND op.store_id IN ($locIdsStr) ";
            }
        }

        $pmRows = $this->Order_payment->query("
            SELECT 
                COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id) AS cashier_id,
                op.type,
                op.type_value,
                SUM(IFNULL(op.amount, 0)) AS pm_amount
            FROM order_payments op
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_transactions
                WHERE status = 2
                GROUP BY reference_id, store_id
            ) ot ON ot.reference_id = op.reference_id AND ot.store_id = op.store_id
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_orderhistories
                WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                GROUP BY reference_id, store_id
            ) b_cashier ON b_cashier.reference_id = op.reference_id AND b_cashier.store_id = op.store_id
            WHERE op.status = 2
              AND op.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeConditionOp
            GROUP BY COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id), op.type, op.type_value
        ");

        $cashierPaymentData = [];
        $cashierPlatformTax = [];
        foreach ($pmRows as $pmr) {
            $cid = intval($pmr[0]['cashier_id'] ?? $pmr['b_cashier']['cashier_id'] ?? $pmr['ot']['cashier_id'] ?? 0);
            $pType = intval($pmr['op']['type'] ?? $pmr[0]['type'] ?? 0);
            $tVal  = trim($pmr['op']['type_value'] ?? $pmr[0]['type_value'] ?? '');
            $amt   = floatval($pmr[0]['pm_amount'] ?? 0);

            if (!isset($cashierPaymentData[$cid])) {
                $cashierPaymentData[$cid] = [
                    'cash' => 0, 'card' => 0, 'upi' => 0, 'online' => 0, 'storecard' => 0, 'nc' => 0, 'custom' => []
                ];
                $cashierPlatformTax[$cid] = 0.0;
            }

            if ($pType == 1) $cashierPaymentData[$cid]['cash'] += $amt;
            elseif ($pType == 2) $cashierPaymentData[$cid]['card'] += $amt;
            elseif ($pType == 9) $cashierPaymentData[$cid]['online'] += $amt;
            elseif ($pType == 6) $cashierPaymentData[$cid]['upi'] += $amt;
            elseif ($pType == 7) $cashierPaymentData[$cid]['nc'] += $amt;
            elseif ($pType == 8) $cashierPaymentData[$cid]['storecard'] += $amt;
            else {
                $label = !empty($tVal) ? $tVal : ('Type ' . $pType);

                if ($pType == 11 && in_array(strtolower($label), ['swiggy', 'zomato'])) {
                    $pTax = round($amt * 5 / 105, 2);
                    $cashierPlatformTax[$cid] += $pTax;
                    $amt = round($amt - $pTax, 2);
                }

                $cashierPaymentData[$cid]['custom'][$label] = ($cashierPaymentData[$cid]['custom'][$label] ?? 0) + $amt;
            }
        }

        // Dish Type Sales WITHOUT Service Charge per Cashier
        $dtNoScRows = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT 
                    ooh.id,
                    ooh.local_id,
                    ooh.reference_id,
                    ooh.store_id,
                    ooh.product_id,
                    ooh.quantity,
                    COALESCE(NULLIF(b_cashier.cashier_id, 0), ot0.cashier_id, ooh.cashier_id) AS cashier_id,
                    cp.dish_type,
                    (ooh.price * ooh.quantity) * (1 - IFNULL(ot_tax.discount_percentage, 0) / 100) AS item_amount
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                LEFT JOIN order_transactions ot0 ON ot0.reference_id = ooh.reference_id AND ot0.store_id = ooh.store_id AND ot0.status = 2
                LEFT JOIN (
                    SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                    FROM order_orderhistories
                    WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                    GROUP BY reference_id, store_id
                ) b_cashier ON b_cashier.reference_id = ooh.reference_id AND b_cashier.store_id = ooh.store_id
                LEFT JOIN (
                    SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                    FROM order_tax
                    WHERE order_tax.status != 0 $storeConditionOrderTax
                    GROUP BY orderhistory_id, store_id
                ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND ooh.status = 2
                  $oohFilter
                  AND ooh.reference_id NOT IN (
                      SELECT op_dw.reference_id FROM order_payments op_dw
                      WHERE op_dw.type = 7 AND op_dw.status = 2 $storeConditionPlain
                  )
            ),
            bill_totals AS (
                SELECT 
                    ot.reference_id,
                    ot.store_id,
                    (ot.price - ot.discount) AS bill_net,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $storeCondition
                  AND ot.reference_id NOT IN (
                      SELECT op_dw.reference_id FROM order_payments op_dw
                      WHERE op_dw.type = 7 AND op_dw.status = 2 $storeConditionPlain
                  )
                GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount
            )
            SELECT 
                ia.cashier_id,
                ia.dish_type,
                SUM(ia.quantity) AS total_qty,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            GROUP BY ia.cashier_id, ia.dish_type
            ORDER BY ia.dish_type DESC
        ");

        $dishTypeMap = [
            '0' => 'Food',
            '1' => 'Beverage',
            '2' => 'Liquor',
            '3' => 'Others',
            0   => 'Food',
            1   => 'Beverage',
            2   => 'Liquor',
            3   => 'Others'
        ];

        $cashierDtNoSc = [];
        $allDishTypesNoScMap = [];
        foreach ($dtNoScRows as $dtr) {
            $cid     = intval($dtr['ia']['cashier_id'] ?? $dtr[0]['cashier_id'] ?? 0);
            $rawType = trim($dtr['ia']['dish_type'] ?? $dtr[0]['dish_type'] ?? '0');
            $dType   = $dishTypeMap[$rawType] ?? (is_numeric($rawType) ? ($dishTypeMap[(int)$rawType] ?? $rawType) : $rawType);
            $amt     = floatval($dtr[0]['total_amount'] ?? 0);

            $cashierDtNoSc[$cid][$dType] = ($cashierDtNoSc[$cid][$dType] ?? 0) + $amt;
            $allDishTypesNoScMap[$dType] = true;
        }
        $allDishTypesNoSc = array_keys($allDishTypesNoScMap);
        sort($allDishTypesNoSc);

        // Fetch subtotal & discount for ALL orders per cashier
        $subtotalRows = $this->Order_transaction->query("
            SELECT 
                COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id) AS cashier_id,
                SUM(IFNULL(ot.price, 0)) AS subtotal,
                SUM(IFNULL(ot.discount, 0)) AS discount
            FROM order_transactions ot
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_orderhistories
                WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                GROUP BY reference_id, store_id
            ) b_cashier ON b_cashier.reference_id = ot.reference_id AND b_cashier.store_id = ot.store_id
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeCondition
            GROUP BY COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id)
        ");
        $cashierSubtotalMap = [];
        $cashierDiscountMap = [];
        foreach ($subtotalRows as $str) {
            $cid = intval($str[0]['cashier_id'] ?? $str['b_cashier']['cashier_id'] ?? $str['ot']['cashier_id'] ?? 0);
            $cashierSubtotalMap[$cid] = floatval($str[0]['subtotal'] ?? 0);
            $cashierDiscountMap[$cid] = floatval($str[0]['discount'] ?? 0);
        }

        // Union of every cashier id
        $allCashierIds = array_filter(array_unique(array_merge(
            array_keys($cashierRowsMap),
            array_keys($cashierSubtotalMap),
            array_keys($cashierPaymentData),
            array_keys($cashierCategoryData),
            array_keys($cashierDtNoSc)
        )));

        $userNamesMap = [];
        if (!empty($allCashierIds)) {
            $uList = $this->User->find('all', [
                'conditions' => ['User.id IN' => array_values($allCashierIds)],
                'fields' => ['User.id', 'User.name', 'User.username']
            ]);
            foreach ($uList as $u) {
                $uName = !empty($u['User']['name']) ? $u['User']['name'] : $u['User']['username'];
                $userNamesMap[$u['User']['id']] = $uName;
            }
        }

        $cashierSalesList = [];
        $grandTotals = [
            'bills_count' => 0, 'qty_sold' => 0, 'gross_sales' => 0, 'discount' => 0,
            'net_sales' => 0, 'tax' => 0, 'net_payable' => 0, 'nc_sales' => 0
        ];
        $categoryGrandTotals = array_fill_keys($allCategories, 0.0);
        $grandTotalsDtNoSc   = array_fill_keys($allDishTypesNoSc, 0.0);

        foreach ($allCashierIds as $cid) {
            $cName = !empty($userNamesMap[$cid]) ? $userNamesMap[$cid] : ($cid > 0 ? ('Cashier #' . $cid) : 'General Cashier');

            $rr         = $cashierRowsMap[$cid] ?? ['bills_count' => 0, 'tax' => 0, 'net_payable' => 0, 'gross_sales' => 0, 'discount' => 0];
            $billsCount = intval($rr['bills_count']);
            $grossSales = floatval($cashierSubtotalMap[$cid] ?? $rr['gross_sales'] ?? 0);
            $discount   = floatval($cashierDiscountMap[$cid] ?? $rr['discount'] ?? 0);
            $netSales   = floatval($rr['gross_sales']) - floatval($rr['discount']);
            $tax        = floatval($rr['tax']);
            $netPayable = floatval($rr['net_payable']) - floatval($cashierPlatformTax[$cid] ?? 0);
            $qtySold    = floatval($cashierQtyMap[$cid] ?? 0);

            $pm = $cashierPaymentData[$cid] ?? [
                'cash' => 0, 'card' => 0, 'upi' => 0, 'online' => 0, 'storecard' => 0, 'nc' => 0, 'custom' => []
            ];
            $ncSales = $pm['nc'];
            unset($pm['nc']);

            $cats = [];
            foreach ($allCategories as $catName) {
                $v = floatval($cashierCategoryData[$cid][$catName] ?? 0);
                $cats[$catName] = $v;
                $categoryGrandTotals[$catName] += $v;
            }

            $dtNoScMap = [];
            foreach ($allDishTypesNoSc as $dtKey) {
                $v = floatval($cashierDtNoSc[$cid][$dtKey] ?? 0);
                $dtNoScMap[$dtKey] = $v;
                $grandTotalsDtNoSc[$dtKey] += $v;
            }

            $cashierSalesList[] = [
                'cashier_id'      => $cid,
                'cashier_name'    => $cName,
                'bills_count'     => $billsCount,
                'qty_sold'        => $qtySold,
                'gross_sales'     => $grossSales,
                'discount'        => $discount,
                'net_sales'       => $netSales,
                'tax'             => $tax,
                'net_payable'     => $netPayable,
                'nc_sales'        => $ncSales,
                'categories'      => $cats,
                'dish_type_no_sc' => $dtNoScMap,
                'payments'        => $pm,
            ];

            $grandTotals['bills_count'] += $billsCount;
            $grandTotals['qty_sold']    += $qtySold;
            $grandTotals['gross_sales'] += $grossSales;
            $grandTotals['discount']    += $discount;
            $grandTotals['net_sales']   += $netSales;
            $grandTotals['tax']         += $tax;
            $grandTotals['net_payable'] += $netPayable;
            $grandTotals['nc_sales']    += $ncSales;
        }

        return compact(
            'users', 'selectedStoreId', 'storeName', 'daterangeValue',
            'cashierSalesList', 'grandTotals', 'allCategories', 'categoryGrandTotals',
            'allDishTypesNoSc', 'grandTotalsDtNoSc', 'isSearched'
        );
    }

    public function service_po_report()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller'=>'dashboard','action'=>'login'));
        }
        $this->layout = 'users';
        $this->_spoModels();

        $authUser = $this->Session->read('Auth.User');
        $role     = $authUser['role'] ?? '';
        $locIds   = $authUser['location_ids'] ?? 0;

        $startDate = date('Y-m-01');
        $endDate   = date('Y-m-d');

        if ($this->request->is(array('post','put'))) {
            $dr = $this->request->data('daterange');
            if ($dr) {
                $parts = explode(' - ', $dr);
                $startDate = date('Y-m-d', strtotime(trim($parts[0])));
                $endDate   = date('Y-m-d', strtotime(trim($parts[1])));
            }
        }

        $storeFilter   = $this->request->data('Search.store')        ?: null;
        $vendorFilter  = $this->request->data('Search.vendor')       ?: null;
        $serviceFilter = $this->request->data('Search.service_type') ?: null;
        $statusFilter  = $this->request->data('Search.status');

        $where  = "WHERE spo.status != 0";
        $where .= " AND DATE(spo.created) BETWEEN '$startDate' AND '$endDate'";
        if ($storeFilter)   $where .= " AND spo.location_id = " . (int)$storeFilter;
        elseif ($role !== 'admin' && $locIds) $where .= " AND spo.location_id IN ($locIds)";
        if ($vendorFilter)  $where .= " AND spo.vendor_id = " . (int)$vendorFilter;
        if ($serviceFilter) $where .= " AND spo.service_type_id = " . (int)$serviceFilter;
        if ($statusFilter !== null && $statusFilter !== '') $where .= " AND spo.status = " . (int)$statusFilter;

        $location_id = !empty(null) ? null : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];



        $rows = $this->Service_purchase_order->query("
            SELECT
                spo.spo_id, spo.vendor_name, spo.location_name,
                spo.service_type_name, spo.status,
                spo.total_amount, spo.approved_amount, spo.processed_amount,
                spo.period_from, spo.period_to, spo.invoice_no, spo.created,
                spo.created_by_name,
                COUNT(spoi.id)             AS line_count,
                SUM(spoi.quantity)         AS qty_total,
                SUM(spoi.total_amount)     AS items_total
            FROM service_purchase_orders spo
            LEFT JOIN service_purchase_order_items spoi
                ON spo.spo_id = spoi.spo_id AND spoi.status = 1
            $where
            GROUP BY spo.spo_id
            ORDER BY spo.id DESC
        ");

        $spos = array();
        $grandTotal = array('submitted'=>0,'approved'=>0,'processed'=>0);
        foreach ($rows as $r) {
            $h  = isset($r['spo']) ? $r['spo'] : $r['service_purchase_orders'];
            $ag = $r[0];
            $st = (int)$h['status'];
            $row = array(
                'spo_id'           => $h['spo_id'],
                'vendor'           => $h['vendor_name'],
                'store'            => $h['location_name'],
                'service_type'     => $h['service_type_name'],
                'status'           => $st,
                'status_label'     => $this->_spoStatusLabel($st),
                'total_amount'     => (float)$h['total_amount'],
                'approved_amount'  => (float)$h['approved_amount'],
                'processed_amount' => (float)$h['processed_amount'],
                'period_from'      => $h['period_from'],
                'period_to'        => $h['period_to'],
                'invoice_no'       => $h['invoice_no'],
                'created'          => $h['created'],
                'created_by'       => $h['created_by_name'],
                'line_count'       => $ag['line_count'],
            );
            $spos[] = $row;
            $grandTotal['submitted']  += $row['total_amount'];
            $grandTotal['approved']   += $row['approved_amount'];
            $grandTotal['processed']  += $row['processed_amount'];
        }

        $stores   = $this->_spoStores();
        $vendors  = $this->_spoVendors();
        $services = $this->_spoServices();
        $this->set(compact('spos','stores','vendors','services','grandTotal','startDate','endDate'));
    }
    
    public function service_po_export() {
        $this->autoRender = false;
        $this->_spoModels();
        $authUser = $this->Session->read('Auth.User');
        $role     = $authUser['role'] ?? '';
        $locIds   = $authUser['location_ids'] ?? 0;
        $startDate = isset($_GET['from']) ? trim($_GET['from']) : date('Y-m-01');
        $endDate   = isset($_GET['to'])   ? trim($_GET['to'])   : date('Y-m-d');

        $where  = "WHERE spo.status != 0";
        $where .= " AND DATE(spo.created) BETWEEN '$startDate' AND '$endDate'";
        if ($role !== 'admin' && $locIds) $where .= " AND spo.location_id IN ($locIds)";

        $statusMap = [0 => 'Cancelled', 1 => 'Submitted', 2 => 'Approved', 3 => 'Processed'];

        // Fetch SPO-level summary only


        $location_id = !empty(null) ? null : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];

        $rows = $this->Service_purchase_order->query("
            SELECT
                spo.spo_id, spo.vendor_name, spo.location_name, spo.service_type_name,
                spo.status, spo.period_from, spo.period_to, spo.invoice_no, spo.created,
                COUNT(spoi.id)              AS item_count,
                SUM(spoi.quantity)          AS qty_total,
                SUM(spoi.unit_price * spoi.quantity)                          AS gross,
                SUM(COALESCE(spoi.discount_value, 0))                         AS discount,
                SUM(spoi.unit_price * spoi.quantity - COALESCE(spoi.discount_value, 0)) AS base,
                SUM(COALESCE(spoi.tax_value, 0))                              AS tax,
                SUM(spoi.total_amount)                                        AS total,
                SUM(spoi.approved_amount)                                     AS approved_amt,
                SUM(spoi.processed_amount)                                    AS processed_amt
            FROM service_purchase_orders spo
            LEFT JOIN service_purchase_order_items spoi
                ON spo.spo_id = spoi.spo_id AND spoi.status = 1
            $where
            GROUP BY spo.spo_id
            ORDER BY spo.id DESC
        ");

        // Collect all SPO IDs
        $spoIds = [];
        foreach ($rows as $r) {
            $spoIds[] = "'" . $r['spo']['spo_id'] . "'";
        }

        // Fetch all item lines for these SPOs in one query
        $itemsMap = [];
        if (!empty($spoIds)) {
            $inList   = implode(',', $spoIds);
            $itemRows = $this->Service_purchase_order->query("
                SELECT
                    spoi.spo_id,
                    spoi.service_name,
                    spoi.unit,
                    spoi.quantity,
                    spoi.unit_price,
                    COALESCE(spoi.discount_value, 0)                                          AS discount,
                    (spoi.unit_price * spoi.quantity - COALESCE(spoi.discount_value, 0))      AS base,
                    spoi.tax_percentage,
                    spoi.tax_value                                                             AS tax,
                    spoi.total_amount                                                          AS total,
                    spoi.approved_amount                                                       AS approved_amt,
                    spoi.processed_amount                                                      AS processed_amt
                FROM service_purchase_order_items spoi
                WHERE spoi.spo_id IN ($inList) AND spoi.status = 1
                ORDER BY spoi.spo_id, spoi.id ASC
            ");

            foreach ($itemRows as $ir) {
                $spoId = $ir['spoi']['spo_id'] ?? $ir[0]['spo_id'] ?? null;
                if (!$spoId) continue;
                $itemsMap[$spoId][] = [
                    'service_name'  => $ir['spoi']['service_name']  ?? $ir[0]['service_name']  ?? '',
                    'unit'          => $ir['spoi']['unit']          ?? $ir[0]['unit']          ?? '',
                    'quantity'      => $ir['spoi']['quantity']      ?? $ir[0]['quantity']      ?? 0,
                    'unit_price'    => $ir['spoi']['unit_price']    ?? $ir[0]['unit_price']    ?? 0,
                    'discount'      => $ir[0]['discount']           ?? 0,
                    'base'          => $ir[0]['base']               ?? 0,
                    'tax_percentage'=> $ir['spoi']['tax_percentage']?? $ir[0]['tax_percentage']?? 0,
                    'tax'           => $ir[0]['tax']                ?? 0,
                    'total'         => $ir['spoi']['total']         ?? $ir[0]['total']         ?? 0,
                    'approved_amt'  => $ir['spoi']['approved_amt']  ?? $ir[0]['approved_amt']  ?? 0,
                    'processed_amt' => $ir['spoi']['processed_amt'] ?? $ir[0]['processed_amt'] ?? 0,
                ];
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="service_po_report_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');

        // Header row
        fputcsv($out, [
            'SPO ID', 'Vendor', 'Store', 'Service Type', 'Status',
            'Period From', 'Period To', 'Invoice No', 'Created',
            'Items', 'Qty', 'Gross', 'Discount', 'Base', 'Tax',
            'Total', 'Approved Amt', 'Processed Amt'
        ]);

        foreach ($rows as $r) {
            $h    = $r['spo'];
            $calc = $r[0];
            $spoId = $h['spo_id'];

            // SPO summary row
            fputcsv($out, [
                $spoId,
                $h['vendor_name'],
                $h['location_name'],
                $h['service_type_name'],
                $statusMap[(int)$h['status']] ?? '',
                $h['period_from'],
                $h['period_to'],
                $h['invoice_no'],
                $h['created'],
                $calc['item_count']   ?? 0,
                round($calc['qty_total']    ?? 0, 3),
                round($calc['gross']        ?? 0, 2),
                round($calc['discount']     ?? 0, 2),
                round($calc['base']         ?? 0, 2),
                round($calc['tax']          ?? 0, 2),
                round($calc['total']        ?? 0, 2),
                round($calc['approved_amt'] ?? 0, 2),
                round($calc['processed_amt']?? 0, 2),
            ]);

            // Item detail rows — leading columns blank like po_report_v2
            foreach ($itemsMap[$spoId] ?? [] as $item) {
                fputcsv($out, [
                    '',  // SPO ID
                    '',  // Vendor
                    '',  // Store
                    '',  // Service Type
                    '',  // Status
                    '',  // Period From
                    '',  // Period To
                    '',  // Invoice No
                    '',  // Created
                    $item['service_name'],
                    round($item['quantity'],      3),
                    round($item['unit_price'] * $item['quantity'], 2),
                    round($item['discount'],      2),
                    round($item['base'],          2),
                    round($item['tax'],           2),
                    round($item['total'],         2),
                    round($item['approved_amt'],  2),
                    round($item['processed_amt'], 2),
                ]);
            }
        }

        fclose($out);
    }

    public function service_po_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller'=>'dashboard','action'=>'login'));
        }
        $this->layout = 'login';
        $this->_spoModels();

        $spoId = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$spoId) {
            return $this->redirect(array('action' => 'service_po_list'));
        }

        $spo = $this->Service_purchase_order->find('first', array(
            'conditions' => array('Service_purchase_order.spo_id' => $spoId),
        ));
        $items = $this->Service_purchase_order_item->find('all', array(
            'conditions' => array(
                'Service_purchase_order_item.spo_id'   => $spoId,
                'Service_purchase_order_item.status'   => 1,
            ),
        ));

        if (empty($spo)) {
            return $this->redirect(array('action' => 'service_po_list'));
        }

        $this->set(compact('spo', 'items'));
        $this->render('service_po_pdf');
    }

    public function service_po_report_items()
    {
        if (!$this->Session->check('Auth.User.id')) {
            $this->autoRender = false; 
            $this->response->type('application/json');
            return $this->response->body(json_encode(array('error' => 'Unauthorized')));
        }
        $this->autoRender = false;
        $this->response->type('application/json');
        $this->_spoModels();

        $spo_id = $this->request->query('spo_id') ?? '';
        if (!$spo_id) {
            return $this->response->body(json_encode(array('error' => 'Invalid SPO')));
        }
            

        $iRows = $this->Service_purchase_order_item->query("
            SELECT
                spoi.service_name,
                spoi.description,
                spoi.unit,
                spoi.quantity,
                spoi.unit_price,
                spoi.discount_value,
                spoi.tax_percentage,
                spoi.tax_value,
                spoi.total_amount,
                spoi.approved_amount,
                spoi.processed_amount
            FROM service_purchase_order_items spoi
            WHERE spoi.spo_id = '$spo_id'
              AND spoi.status = 1
            ORDER BY spoi.id ASC
        ");
        $items = array();
        foreach ($iRows as $row) {
            $r  = $row['spoi'] ?? array();
            $r0 = $row[0]      ?? array();

            $items[] = array(
                'service_name'     => $r['service_name']     ?? $r0['service_name']     ?? '',
                'description'      => $r['description']      ?? $r0['description']      ?? '',
                'unit'             => $r['unit']             ?? $r0['unit']             ?? '',
                'quantity'         => (float)($r['quantity']         ?? $r0['quantity']         ?? 0),
                'unit_price'       => (float)($r['unit_price']       ?? $r0['unit_price']       ?? 0),
                'discount_value'   => (float)($r['discount_value']   ?? $r0['discount_value']   ?? 0),
                'tax_percentage'   => (float)($r['tax_percentage']   ?? $r0['tax_percentage']   ?? 0),
                'tax_value'        => (float)($r['tax_value']        ?? $r0['tax_value']        ?? 0),
                'total_amount'     => (float)($r['total_amount']     ?? $r0['total_amount']     ?? 0),
                'approved_amount'  => (float)($r['approved_amount']  ?? $r0['approved_amount']  ?? 0),
                'processed_amount' => (float)($r['processed_amount'] ?? $r0['processed_amount'] ?? 0),
            );
        }

        return $this->response->body(json_encode(array('items' => $items)));
    }

    public function purchase_classification()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        if (!in_array($authUser['role'] ?? '', ['admin', 'manager'])) {
            $this->Session->setFlash('Access denied.', 'default', ['class' => 'alert alert-danger']);
            return $this->redirect(['action' => 'po_list']);
        }

        // Handle save single SKU
        if ($this->request->is('post') && !empty($this->request->data['sku_id'])) {
            $sku_id      = (int)$this->request->data['sku_id'];
            $etype       = in_array($this->request->data['expense_type'], ['CAPEX','OPEX','UNCLASSIFIED'])
                           ? $this->request->data['expense_type'] : 'UNCLASSIFIED';
            $this->Inventory_sku->updateAll(
                ['Inventory_sku.expense_type' => "'$etype'"],
                ['Inventory_sku.id' => $sku_id]
            );
            $this->Session->setFlash("SKU #$sku_id classified as $etype.", 'default', ['class' => 'alert alert-success']);
        }

        // Handle bulk-by-category
        if ($this->request->is('post') && !empty($this->request->data['bulk_cat_id'])) {
            $cat_id = (int)$this->request->data['bulk_cat_id'];
            $etype  = in_array($this->request->data['bulk_expense_type'], ['CAPEX','OPEX','UNCLASSIFIED'])
                      ? $this->request->data['bulk_expense_type'] : 'UNCLASSIFIED';
            $this->Inventory_sku->updateAll(
                ['Inventory_sku.expense_type' => "'$etype'"],
                ['Inventory_sku.cat_id' => $cat_id]
            );
            $this->Session->setFlash("All SKUs in category #$cat_id set to $etype.", 'default', ['class' => 'alert alert-success']);
        }

        $skus = $this->Inventory_sku->find('all', [
            'conditions' => ['Inventory_sku.status' => 1],
            'fields'     => ['Inventory_sku.id','Inventory_sku.name','Inventory_sku.cat_name','Inventory_sku.expense_type'],
            'order'      => ['Inventory_sku.name ASC'],
        ]);

        $categories = $this->Inventory_category->find('list', [
            'conditions' => ['Inventory_category.status' => 1],
            'fields'     => ['Inventory_category.id','Inventory_category.name'],
        ]);

        $this->set(compact('skus', 'categories'));
    }

    public function kds_reports()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "users";
        
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate   = date('m/d/Y');
        
        $authUser  = $this->Session->read('Auth.User');
        $userRole  = $authUser['role'] ?? '';
        
        if($this->request->is('post', 'put')) {
            $daterange  = $this->request->data['daterange'];
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $end_date   = trim($dates[1]);
            $endDate    = date('m/d/Y', strtotime($end_date));
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }
        
        // FIXED: $cashier_id read outside post check so it's always available
        $cashier_id = $this->request->data['Search']['cashier'] ?? '';

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $cashier_id ?: null);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        // FIXED: $users initialised so it's never undefined if no post yet
        $users = [];

        if($this->request->is('post', 'put')) {
            $selectedStore = $this->request->data['Search']['cashier'] ?? '';
            $kdsPrinterCond = !empty($selectedStore)
                ? "kds = k.kds_station_id AND store_id = k.location_id"
                : "kds = k.kds_station_id";

            if ($userRole !== 'admin') {
                // FIXED: use location_ids if no cashier filter selected
                if (!$cashier_id) {
                    $cashier_id = $authUser['location_ids'] ?? 0;
                }
                $users = $this->Kds_track->query("
                    SELECT k.*, 
                           (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS station_name 
                    FROM kds_tracks k
                    WHERE k.created BETWEEN '$startDatetime' AND '$endDatetime' 
                      AND k.location_id IN ($cashier_id)
                    ORDER BY k.id DESC
                ");
            } else {
                if (!$cashier_id) {
                    $users = $this->Kds_track->query("
                        SELECT k.*, 
                               (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS station_name 
                        FROM kds_tracks k
                        WHERE k.created BETWEEN '$startDatetime' AND '$endDatetime'
                        ORDER BY k.id DESC
                    ");
                } else {
                    $users = $this->Kds_track->query("
                        SELECT k.*, 
                               (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS station_name 
                        FROM kds_tracks k
                        WHERE k.created BETWEEN '$startDatetime' AND '$endDatetime' 
                          AND k.location_id IN ($cashier_id)
                        ORDER BY k.id DESC
                    ");
                }
            }
        }

        // Location dropdown for filter
        $userLocationId = $authUser['location_ids'] ?? 0;
        $locationCondition = ['status' => 1];
        if ($userRole !== 'admin') {
            $userLocationIds = array_filter(array_map('trim', explode(',', $userLocationId)));
            $locationCondition['id'] = $userLocationIds;
        }
        $cashier_users = $this->Location->find('all', ['conditions' => $locationCondition]);

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
        $this->set(compact('cashier_users'));
    }

    public function kds_reportexport() {
        $date_range= $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate= date('Y-m-d', strtotime($start_date));
        $enddate= date('Y-m-d', strtotime($end_date));
        
        $cashier_id = isset($_GET['cashier']) ? $_GET['cashier'] : '';

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $cashier_id ?: null);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        // Get transactions
        $conditions = array();
        $conditions[] = "created BETWEEN '$startDatetime' and  '$endDatetime'";
        if ($cashier_id) {
            $conditions[] = "location_id=" . (int)$cashier_id;
            $cashiernames = $this->Location->find('first', array('conditions' => array('Location.id' => $cashier_id)));
            $cashiername  = !empty($cashiernames['Location']['name']) ? $cashiernames['Location']['name'] : 'Unknown Cashier';
        } else {
            $cashiername = 'All Stores';
        }
        $where = implode(' AND ', $conditions);
        
        $kdsPrinterCond = !empty($cashier_id)
            ? "kds = k.kds_station_id AND store_id = k.location_id"
            : "kds = k.kds_station_id";

        $transactions = $this->Kds_track->query("
            SELECT k.*, (SELECT name FROM pos_printers WHERE $kdsPrinterCond AND status = 1 LIMIT 1) AS station_name 
            FROM kds_tracks k
            WHERE $where
            ORDER BY k.id DESC
        ");
    
        // Prepare data for Excel
        $datas = array();
        foreach ($transactions as $user) {
            $statusLabel = '';
            switch ($user['k']['status']) {
                case 0: $statusLabel = 'Unfire'; break;
                case 1: $statusLabel = 'Fire'; break;
                case 2: $statusLabel = 'Hold'; break;
                case 3: $statusLabel = 'Start'; break;
                case 4: $statusLabel = 'Bump'; break;
                case 5: $statusLabel = 'Flag'; break;
                case 6: $statusLabel = 'Send to Table'; break;
                case 7: $statusLabel = 'Recall'; break;
                default: $statusLabel = 'Unknown'; break;
            }
            $datas[] = array(
                'Reference ID'   => $user['k']['order_reference_id'],
                'Group ID'       => $user['k']['kds_group_id'],
                'KDS Station'    => !empty($user[0]['station_name']) ? $user[0]['station_name'] : '',
                'Product Name'   => $user['k']['product_name'],
                'Role'           => $user['k']['role'],
                'Location Name'  => $user['k']['location_name'],
                'Status'         => $statusLabel,
                'Created'        => $user['k']['created'],
            );
        }
    
        // Load PHPExcel
        App::import('Vendor', 'PHPExcel', array('file' => 'PHPExcel/PHPExcel.php'));
        App::import('Vendor', 'PHPExcel_IOFactory', array('file' => 'PHPExcel/PHPExcel/IOFactory.php'));
        error_reporting(0);
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("KDS Transaction Report")
            ->setSubject("Transaction Report")
            ->setDescription("Exported KDS transaction data.")
            ->setCategory("Export Data");
    
        // Set headers
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', "TRANSACTION REPORT FROM $startdate TO $enddate ($cashiername)");
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
        $objPHPExcel->getActiveSheet()->getStyle("A1:H1")->getFont()->setBold(true);
    
        // Column headings for Excel
        $headers = array('SR. NO.', 'Reference ID', 'Group ID', 'KDS Station', 'Product Name', 'Role', 'Location Name', 'Status', 'Created');
        foreach ($headers as $col => $heading) {
            $colLetter = PHPExcel_Cell::stringFromColumnIndex($col);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colLetter . '2', $heading);
            $objPHPExcel->getActiveSheet()->getStyle($colLetter . '2')->getFont()->setBold(true);
        }
    
        // Data rows
        $rowNum = 3;
        foreach ($datas as $idx => $dat) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $rowNum, ($idx + 1));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . $rowNum, $dat['Reference ID']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . $rowNum, $dat['Group ID']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . $rowNum, $dat['KDS Station']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . $rowNum, $dat['Product Name']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . $rowNum, $dat['Role']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . $rowNum, $dat['Location Name']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $rowNum, $dat['Status']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . $rowNum, $dat['Created']);
            $rowNum++;
        }
    
        $objPHPExcel->getActiveSheet()->setTitle('Export Data');
        $objPHPExcel->setActiveSheetIndex(0);
    
        // Output to browser
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="kds_REPORT.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Past date
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function view_kds_report()
    {
        // Session check (as before)
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));     
        }
        $this->layout = "users";
        
        $id = $_REQUEST['id'];
    
        // Fetch with station name in result
        $flatProducts = $this->Kds_track->query(
            "SELECT *, 
                COALESCE(
                    (SELECT name FROM pos_printers WHERE kds = kds_tracks.kds_station_id AND store_id = kds_tracks.location_id AND status = 1 LIMIT 1),
                    (SELECT name FROM pos_printers WHERE kds = kds_tracks.kds_station_id AND status = 1 LIMIT 1)
                ) AS station_name 
            FROM kds_tracks 
            WHERE order_reference_id = '$id'"
        );
        // Group by station_name and product_name
        $stations = [];
        foreach ($flatProducts as $row) {
            // Get station name
            $stationName = isset($row[0]['station_name']) ? $row[0]['station_name'] : $row['kds_tracks']['station_name'];
            // Get product data structure (may need to adjust based on your query result)
            $data = isset($row['kds_tracks']) ? $row['kds_tracks'] : (isset($row[0]) ? $row[0] : $row);
            // Get product name
            $productName = $data['product_name'];
            // Group by [station][product][]
            $stations[$stationName][$productName][] = $row;
        }
    
        $this->set(compact('stations'));
    }

    private function _spoModels() {
        $this->loadModel('Service_purchase_order');
        $this->loadModel('Service_purchase_order_item');
        $this->loadModel('Inventory_vendor');
    }

    private function _spoStatusLabel($s) {
        $map = array(0=>'Cancelled', 1=>'Submitted', 2=>'Approved', 3=>'Processed');
        return isset($map[$s]) ? $map[$s] : 'Unknown';
    }

    private function _spoServices() {
        $rows = $this->Service_purchase_order->query(
            "SELECT id, name FROM services WHERE status=1 ORDER BY name ASC"
        );
        $out = array();
        foreach ($rows as $r) {
            $k = isset($r['services']['id']) ? $r['services']['id'] : $r[0]['id'];
            $v = isset($r['services']['name']) ? $r['services']['name'] : $r[0]['name'];
            $out[$k] = $v;
        }
        return $out;
    }

    private function _spoVendors() {
        $rows = $this->Inventory_vendor->find('list', array(
            'conditions' => array('Inventory_vendor.status' => 1),
            'fields'     => array('Inventory_vendor.id', 'Inventory_vendor.company'),
            'order'      => array('Inventory_vendor.company ASC'),
        ));
        return $rows;
    }

    private function _spoStores() {
        $this->loadModel('Location');
        $authUser = $this->Session->read('Auth.User');
        $role     = $authUser['role'] ?? '';
        $locIds   = $authUser['location_ids'] ?? 0;
        $conds    = array('Location.status' => 1);
        if ($role !== 'admin' && $locIds) {
            $conds['Location.id'] = array_map('trim', explode(',', $locIds));
        }
        return $this->Location->find('all', array('conditions' => $conds));
    }

    public function bill_lifecycle_report() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $startDate = date('m/d/Y');
        $endDate   = date('m/d/Y');
        $orderno_search = '';

        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));
        $cashier_id = null;

        if ($this->request->is('post', 'put')) {
            if (!empty($this->request->data['daterange'])) {
                $daterange  = $this->request->data['daterange'];
                $dates      = explode(" - ", $daterange);
                $start_date = trim($dates[0]);
                $startDate  = date('m/d/Y', strtotime($start_date));
                $end_date   = trim($dates[1]);
                $endDate    = date('m/d/Y', strtotime($end_date));
                $startdate  = date('Y-m-d', strtotime($start_date));
                $enddate    = date('Y-m-d', strtotime($end_date));
            }
            $cashier_id     = $this->request->data['Search']['cashier'] ?? null;
            $orderno_search = $this->request->data['Search']['orderno'] ?? '';
        }

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $cashier_id);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $bills = $this->_getBillLifecycleData($startDatetime, $endDatetime, $cashier_id, $orderno_search);

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', $userLocationIds);
        }

        $users = $this->Location->find('all', [
            'conditions' => $conditions
        ]);

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set('orderno_search', $orderno_search);
        $this->set('cashier_id', $cashier_id);
        $this->set(compact('users', 'bills'));
    }

    public function bill_lifecycle_report_export() {
        $date_range = $_GET['sdate'] ?? '';
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0] ?? date('m/d/Y'));
        $end_date = trim($dates[1] ?? date('m/d/Y'));

        $startdate = date('Y-m-d', strtotime($start_date));
        $enddate   = date('Y-m-d', strtotime($end_date));
        $cashier_id = $_GET['cashier'] ?? null;
        $orderno_search = $_GET['orderno'] ?? '';

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $cashier_id);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $bills = $this->_getBillLifecycleData($startDatetime, $endDatetime, $cashier_id, $orderno_search);

        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("ERP System")
            ->setTitle("Bill Lifecycle Report")
            ->setDescription("Auto-generated Bill Lifecycle Report");

        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Bill Lifecycle');

        $sheet->setCellValue('A1', 'BILL LIFECYCLE REPORT (' . $startDatetime . ' TO ' . $endDatetime . ')');
        $sheet->mergeCells('A1:Q1');
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true)->setSize(14);

        $headers = [
            'A3' => 'SR. NO.',
            'B3' => 'Bill / Order No',
            'C3' => 'Ref ID',
            'D3' => 'Store Name',
            'E3' => 'Table',
            'F3' => 'Cashier',
            'G3' => 'Server / Waiter',
            'H3' => 'Manager',
            'I3' => 'Customer Name',
            'J3' => 'Customer Phone',
            'K3' => 'Payment Modes',
            'L3' => 'Created Time',
            'M3' => 'Status',
            'N3' => 'Amount (₹)',
            'O3' => 'Events Count',
            'P3' => 'Active Items',
            'Q3' => 'Deleted Items'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $sheet->getStyle('A3:Q3')->getFont()->setBold(true);

        $rowNum = 4;
        $r = 1;
        foreach ($bills as $bill) {
            $sum = $bill['summary'];
            $sheet->setCellValue("A{$rowNum}", $r)
                ->setCellValue("B{$rowNum}", $sum['orderno'])
                ->setCellValue("C{$rowNum}", $sum['reference_id'])
                ->setCellValue("D{$rowNum}", $sum['store_name'])
                ->setCellValue("E{$rowNum}", $sum['table_number'])
                ->setCellValue("F{$rowNum}", $sum['cashier_name'])
                ->setCellValue("G{$rowNum}", $sum['waiter_name'] ?? 'N/A')
                ->setCellValue("H{$rowNum}", $sum['manager_name'] ?? 'N/A')
                ->setCellValue("I{$rowNum}", $sum['customer_name'] ?? '')
                ->setCellValue("J{$rowNum}", $sum['customer_phone'] ?? '')
                ->setCellValue("K{$rowNum}", $sum['payment_modes'] ?? 'Pending Settlement')
                ->setCellValue("L{$rowNum}", date('d-m-Y h:i A', strtotime($sum['created'])))
                ->setCellValue("M{$rowNum}", $sum['status_text'])
                ->setCellValue("N{$rowNum}", round($sum['amount'], 2))
                ->setCellValue("O{$rowNum}", count($bill['timeline']))
                ->setCellValue("P{$rowNum}", count($bill['active_items']))
                ->setCellValue("Q{$rowNum}", count($bill['deleted_items']));

            $rowNum++;

            // Timeline details
            $sheet->setCellValue("B{$rowNum}", "--- Event Timeline ---");
            $sheet->getStyle("B{$rowNum}")->getFont()->setItalic(true);
            $rowNum++;

            foreach ($bill['timeline'] as $evt) {
                $sheet->setCellValue("C{$rowNum}", date('d-m-Y h:i A', strtotime($evt['timestamp'])))
                    ->setCellValue("D{$rowNum}", $evt['label'])
                    ->setCellValue("E{$rowNum}", $evt['description']);
                $rowNum++;
            }

            // Deleted items details (if any)
            if (!empty($bill['deleted_items'])) {
                $sheet->setCellValue("B{$rowNum}", "--- Deleted Items ---");
                $sheet->getStyle("B{$rowNum}")->getFont()->setItalic(true);
                $rowNum++;
                foreach ($bill['deleted_items'] as $dItem) {
                    $sheet->setCellValue("C{$rowNum}", date('d-m-Y h:i A', strtotime($dItem['created'])))
                        ->setCellValue("D{$rowNum}", $dItem['product_name'] . " (Qty: " . $dItem['quantity'] . ", Rate: ₹" . $dItem['price'] . ")")
                        ->setCellValue("E{$rowNum}", "Removed by: " . $dItem['user_name']);
                    $rowNum++;
                }
            }

            // Split bills breakdown (if any)
            if (!empty($bill['split_bills'])) {
                $sheet->setCellValue("B{$rowNum}", "--- Split Bills & Items Breakdown ---");
                $sheet->getStyle("B{$rowNum}")->getFont()->setItalic(true);
                $rowNum++;
                foreach ($bill['split_bills'] as $sb) {
                    $sheet->setCellValue("C{$rowNum}", "Sub-Bill " . $sb['split_index'] . ": #" . $sb['orderno'])
                        ->setCellValue("D{$rowNum}", "Amount: ₹" . number_format($sb['amount'], 2) . " (Price: ₹" . number_format($sb['price'], 2) . ", Tax: ₹" . number_format($sb['total_tax'], 2) . ", Disc: ₹" . number_format($sb['discount'], 2) . ")")
                        ->setCellValue("E{$rowNum}", "Items (" . count($sb['items']) . "):");
                    $sheet->getStyle("C{$rowNum}:E{$rowNum}")->getFont()->setBold(true);
                    $rowNum++;

                    if (!empty($sb['items'])) {
                        foreach ($sb['items'] as $sItem) {
                            $sheet->setCellValue("D{$rowNum}", "• " . $sItem['product_name'] . " (KOT: " . ($sItem['kot_no'] ?? 'N/A') . ")")
                                ->setCellValue("E{$rowNum}", "Qty: " . $sItem['quantity'] . " | Rate: ₹" . number_format($sItem['price'], 2) . " | Total: ₹" . number_format($sItem['total_price'], 2));
                            $rowNum++;
                        }
                    }
                }
            }

            $r++;
            $rowNum++;
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="BILL_LIFECYCLE_REPORT_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function bill_lifecycle_report_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "pdf";
        $startDate = date('m/d/Y');
        $endDate   = date('m/d/Y');
        $orderno_search = '';

        if ($this->request->is('post', 'put')) {
            if (!empty($this->request->data['daterange'])) {
                $daterange  = $this->request->data['daterange'];
                $dates      = explode(" - ", $daterange);
                $start_date = trim($dates[0]);
                $startDate  = date('m/d/Y', strtotime($start_date));
                $end_date   = trim($dates[1]);
                $endDate    = date('m/d/Y', strtotime($end_date));
            }
            $cashier_id     = $this->request->data['Search']['cashier'] ?? null;
            $orderno_search = $this->request->data['Search']['orderno'] ?? '';
        } else {
            $cashier_id     = $_GET['cashier'] ?? null;
            $orderno_search = $_GET['orderno'] ?? '';
            if (!empty($_GET['sdate'])) {
                $dates      = explode(" - ", $_GET['sdate']);
                $startDate  = trim($dates[0]);
                $endDate    = trim($dates[1] ?? $dates[0]);
            }
        }

        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate, $cashier_id);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $bills = $this->_getBillLifecycleData($startDatetime, $endDatetime, $cashier_id, $orderno_search);

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('bills'));
        $this->render('bill_lifecycle_report_pdf');
    }

    private function _getBillLifecycleData($startDatetime, $endDatetime, $cashier_id = null, $orderno = null) {
        $this->loadModel('Order_split_transaction');
        $this->loadModel('Order_split_orderhistory');
        $this->loadModel('Order_split_payment');

        $cashierCondition = "";
        if (!empty($cashier_id)) {
            $cashierCondition = " AND t.store_id = " . (int)$cashier_id;
        }

        $ordernoCondition = "";
        if (!empty($orderno)) {
            $ordernoClean = addslashes(trim($orderno));
            $ordernoCondition = " AND (t.orderno LIKE '%{$ordernoClean}%' OR t.reference_id LIKE '%{$ordernoClean}%')";
        }

        // Query transactions directly from order_transactions
        $transactions = $this->Order_transaction->query("
            SELECT 
                t.id, t.reference_id, t.orderno, t.store_id, t.table_number, t.cashier_id, t.waiter_id, t.waiter_name, t.pax,
                t.price, t.total_tax, t.servicecharge, t.discount, t.discount_type, t.discount_type_value, t.discount_items, t.amount, t.round, 
                t.status, t.created, t.name as customer_name, t.phone as customer_phone,
                l.name AS store_name,
                u.username AS cashier_username,
                u.name AS cashier_fullname
            FROM order_transactions t
            LEFT JOIN locations l ON l.id = t.store_id
            LEFT JOIN users u ON u.id = t.cashier_id
            WHERE t.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
                {$cashierCondition}
                {$ordernoCondition}
            ORDER BY t.id DESC
        ");

        // Also check order_orderhistories for any active unbilled items (status = 1) in date range
        $activeOhLocCond = !empty($cashier_id) ? " AND oh.store_id = " . (int)$cashier_id : "";
        $activeOHRows = $this->Order_orderhistory->query("
            SELECT DISTINCT oh.reference_id, oh.store_id, oh.table_number, l.name as store_name, MIN(oh.created) as min_created, MAX(oh.created) as max_created
            FROM order_orderhistories oh
            LEFT JOIN locations l ON l.id = oh.store_id
            WHERE oh.created BETWEEN '{$startDatetime}' AND '{$endDatetime}'
              AND oh.status = 1
              AND oh.reference_id IS NOT NULL
              AND oh.reference_id != ''
              {$activeOhLocCond}
            GROUP BY oh.reference_id
        ");

        $activeOrders = [];
        $activeRefs = [];
        if (!empty($activeOHRows)) {
            foreach ($activeOHRows as $ohRow) {
                $ohRef = $ohRow['oh']['reference_id'] ?? '';
                if (!empty($ohRef)) {
                    $activeOrders[] = [
                        'reference_id' => $ohRef,
                        'table_number' => $ohRow['oh']['table_number'] ?? 'N/A',
                        'store_id' => $ohRow['oh']['store_id'] ?? 0,
                        'store_name' => $ohRow['l']['store_name'] ?? 'N/A',
                        'waiter_name' => '',
                        'created' => $ohRow[0]['min_created'] ?? date('Y-m-d H:i:s'),
                        'modified' => $ohRow[0]['max_created'] ?? date('Y-m-d H:i:s'),
                        'status' => 1
                    ];
                    $activeRefs[$ohRef] = $ohRef;
                }
            }
        }

        // Collect all distinct reference IDs to batch query
        $allRefs = [];
        foreach ($transactions as $tr) {
            $r = $tr['t']['reference_id'] ?? '';
            if (!empty($r)) $allRefs[$r] = $r;
        }
        foreach ($activeOrders as $ao) {
            $r = $ao['reference_id'] ?? '';
            if (!empty($r)) $allRefs[$r] = $r;
        }

        if (empty($allRefs)) {
            return [];
        }

        $refInSql = "'" . implode("','", array_map('addslashes', $allRefs)) . "'";

        // 1. Batch Table Tracking (Merges & Shifts)
        $trackingMap = [];
        $activeMergedChildRefs = [];
        $rawTracking = $this->Order_table_tracking->query("
            SELECT ott.*, 
                   COALESCE(ot1.table_number, ott.main_table_number) as main_tbl, 
                   COALESCE(ot2.table_number, ott.merged_table_number) as merged_tbl
            FROM order_table_tracking ott
            LEFT JOIN order_transactions ot1 ON ot1.reference_id = ott.main_table_ref_id
            LEFT JOIN order_transactions ot2 ON ot2.reference_id = ott.merged_table_ref_id
            WHERE ott.main_table_ref_id IN ({$refInSql}) OR ott.merged_table_ref_id IN ({$refInSql})
            ORDER BY ott.created ASC
        ");
        foreach ($rawTracking as $tRow) {
            $mainRef = $tRow['ott']['main_table_ref_id'] ?? '';
            $mergedRef = $tRow['ott']['merged_table_ref_id'] ?? '';
            $trackType = strtolower($tRow['ott']['type'] ?? '');
            $isMerge = ($trackType === 'merge' || empty($trackType) || !in_array($trackType, ['swap', 'shift']));
            if ($mainRef) {
                $trackingMap[$mainRef][] = $tRow;
                if ($isMerge && $mergedRef && (int)($tRow['ott']['status'] ?? 0) === 1) {
                    $activeMergedChildRefs[$mergedRef] = $mainRef;
                }
            }
            if (!$isMerge && $mergedRef && $mergedRef != $mainRef) {
                $trackingMap[$mergedRef][] = $tRow;
            }
        }

        // 1.5 Batch All Order Transactions for refIds
        $allTxnsMap = [];
        $rawAllTxns = $this->Order_transaction->query("
            SELECT t.*, u.username as cashier_username, u.name as cashier_fullname
            FROM order_transactions t
            LEFT JOIN users u ON u.id = t.cashier_id
            WHERE t.reference_id IN ({$refInSql})
            ORDER BY t.created ASC
        ");
        foreach ($rawAllTxns as $txRow) {
            $tRef = $txRow['t']['reference_id'] ?? '';
            if ($tRef) {
                $allTxnsMap[$tRef][] = $txRow;
            }
        }

        // 2. Batch Split Transactions
        $splitTxnMap = [];
        $rawSplitTxns = $this->Order_split_transaction->query("
            SELECT ost.*
            FROM order_split_transactions ost
            WHERE ost.reference_id IN ({$refInSql}) AND ost.status != 0
            ORDER BY ost.id ASC
        ");
        foreach ($rawSplitTxns as $stRow) {
            $stRef = $stRow['ost']['reference_id'] ?? '';
            if ($stRef) {
                $splitTxnMap[$stRef][] = $stRow;
            }
        }

        // 3. Batch Split Items (order_split_orderhistories)
        $splitItemsMap = [];
        $splitHistToSplitRefMap = [];
        $rawSplitItems = $this->Order_split_orderhistory->query("
            SELECT osoh.*, p.name as catalog_pname, u.username as staff_username, u.name as staff_fullname
            FROM order_split_orderhistories osoh
            LEFT JOIN catalog_product p ON p.id = osoh.product_id
            LEFT JOIN users u ON u.id = osoh.cashier_id
            WHERE osoh.reference_id IN ({$refInSql}) AND osoh.status != 0
            ORDER BY osoh.id ASC
        ");
        foreach ($rawSplitItems as $siRow) {
            $spRef = $siRow['osoh']['split_reference_id'] ?? '';
            $ohId = $siRow['osoh']['order_history_id'] ?? null;
            if ($spRef) {
                $splitItemsMap[$spRef][] = $siRow;
            }
            if ($ohId && $spRef) {
                $splitHistToSplitRefMap[$ohId] = $spRef;
            }
        }

        // 4. Batch Order Items (order_orderhistories)
        $itemsMap = [];
        $rawItems = $this->Order_orderhistory->query("
            SELECT oh.*, p.name as catalog_pname, u.username as staff_username, u.name as staff_fullname
            FROM order_orderhistories oh
            LEFT JOIN catalog_product p ON p.id = oh.product_id
            LEFT JOIN users u ON u.id = oh.cashier_id
            WHERE oh.reference_id IN ({$refInSql})
            ORDER BY oh.created ASC, oh.id ASC
        ");
        foreach ($rawItems as $itRow) {
            $itRef = $itRow['oh']['reference_id'] ?? '';
            if ($itRef) {
                $itemsMap[$itRef][] = $itRow;
            }
        }

        // 5. Batch Split Payments
        $splitPaymentsMap = [];
        $rawSplitPayments = $this->Order_split_payment->query("
            SELECT osp.*, u.username as payment_username, u.name as payment_fullname
            FROM order_split_payments osp
            LEFT JOIN users u ON u.id = osp.cashier_id
            WHERE osp.reference_id IN ({$refInSql})
            ORDER BY osp.created ASC
        ");
        foreach ($rawSplitPayments as $spRow) {
            $spRef = $spRow['osp']['reference_id'] ?? '';
            if ($spRef) {
                $splitPaymentsMap[$spRef][] = $spRow;
            }
        }

        // 6. Batch Standard Payments
        $paymentsMap = [];
        $rawPayments = $this->Order_payment->query("
            SELECT op.*, u.username as payment_username, u.name as payment_fullname
            FROM order_payments op
            LEFT JOIN users u ON u.id = op.cashier_id
            WHERE op.reference_id IN ({$refInSql})
            ORDER BY op.created ASC
        ");
        foreach ($rawPayments as $pRow) {
            $pRef = $pRow['op']['reference_id'] ?? '';
            if ($pRef) {
                $paymentsMap[$pRef][] = $pRow;
            }
        }

        // 7. Batch User Tracks
        $tracksMap = [];
        $rawTracks = $this->User_track->query("
            SELECT ut.*, u.username as user_username, u.name as user_fullname, u.role as user_role
            FROM user_tracks ut
            LEFT JOIN users u ON u.id = ut.user_id
            WHERE ut.reference_id IN ({$refInSql})
              AND ut.type NOT IN ('login', 'logout')
            ORDER BY ut.created ASC
        ");
        foreach ($rawTracks as $tRow) {
            $tRef = $tRow['ut']['reference_id'] ?? '';
            if ($tRef) {
                $tracksMap[$tRef][] = $tRow;
            }
        }

        // 8. Batch Dish Tax Map and Removed Taxes Map
        $taxDishMap = [];
        $removedTaxesMap = [];
        $rawTaxes = $this->Order_tax->query("
            SELECT ot.*
            FROM order_tax ot
            WHERE ot.reference_id IN ({$refInSql})
        ");
        foreach ($rawTaxes as $txr) {
            $rId = $txr['ot']['reference_id'] ?? '';
            $dType = $txr['ot']['dish_type'] ?? null;
            $otStatus = (int)($txr['ot']['status'] ?? 0);
            $discPct = (float)($txr['ot']['discount_percentage'] ?? 0);
            if ($otStatus != 0 && $discPct > 0 && $rId && $dType !== null) {
                $taxDishMap[$rId][] = (int)$dType;
            }
            if ($otStatus == 0 && $rId) {
                $removedTaxesMap[$rId][] = $txr['ot'];
            }
        }

        // 9. Batch Reelo Discounts (reelo_discounts where status = 1)
        $reeloDiscountsMap = [];
        $this->loadModel('ReeloDiscount');
        try {
            $rawReeloDiscs = $this->ReeloDiscount->query("
                SELECT rd.*, u.username as user_username, u.name as user_fullname
                FROM reelo_discounts rd
                LEFT JOIN users u ON u.id = rd.user_id
                WHERE (rd.reference_id IN ({$refInSql}) OR rd.split_reference_id IN ({$refInSql}))
                  AND rd.status = 1
                ORDER BY rd.created ASC
            ");
            foreach ($rawReeloDiscs as $rdRow) {
                $rd = $rdRow['rd'] ?? ($rdRow['ReeloDiscount'] ?? ($rdRow['reelo_discounts'] ?? []));
                $rRef = $rd['reference_id'] ?? '';
                $sRef = $rd['split_reference_id'] ?? '';
                if (!empty($rRef)) $reeloDiscountsMap[$rRef][] = $rdRow;
                if (!empty($sRef) && $sRef != $rRef) $reeloDiscountsMap[$sRef][] = $rdRow;
            }
        } catch (Exception $e) {
            // Reelo discount query fallback
        }

        $dishTypeMap = [
            0 => 'Food',
            1 => 'Beverage',
            2 => 'Liquor',
            3 => 'Others'
        ];

        $seenRefIds = [];
        $bills = [];

        // Helper closures for fast in-memory assembly
        $assembleBill = function($refId, $orderNo, $txStoreId, $storeName, $tableNum, $cashierName, $txWaiterName, $createdTime, $txStatus, $txPrice, $txTax, $txSC, $txDisc, $txDiscType, $txDiscVal, $txDiscItems, $txAmt, $custName, $custPhone, $isLiveActive = false) use (
            &$trackingMap, &$activeMergedChildRefs, &$splitTxnMap, &$splitItemsMap, &$splitHistToSplitRefMap, &$itemsMap, &$splitPaymentsMap, &$paymentsMap, &$tracksMap, &$taxDishMap, &$removedTaxesMap, &$reeloDiscountsMap, &$allTxnsMap, $dishTypeMap
        ) {
            $mergedRefs = [$refId];

            // Resolve table number from transaction, items, or user_tracks swap events
            $currentTableNum = !empty($tableNum) ? trim((string)$tableNum) : '';
            if (empty($currentTableNum) && !empty($allTxnsMap[$refId])) {
                foreach ($allTxnsMap[$refId] as $relTx) {
                    if (!empty($relTx['t']['table_number'])) {
                        $currentTableNum = trim((string)$relTx['t']['table_number']);
                        break;
                    }
                }
            }
            if (empty($currentTableNum) && !empty($itemsMap[$refId])) {
                foreach ($itemsMap[$refId] as $relIt) {
                    if (!empty($relIt['oh']['table_number'])) {
                        $currentTableNum = trim((string)$relIt['oh']['table_number']);
                        break;
                    }
                }
            }
            if (empty($currentTableNum)) {
                $currentTableNum = 'N/A';
            }

            if (!empty($tracksMap[$refId])) {
                foreach ($tracksMap[$refId] as $uRow) {
                    $ut = $uRow['ut'] ?? [];
                    $utType = strtolower($ut['type'] ?? '');
                    $utRem = $ut['remarks'] ?? '';
                    if (in_array($utType, ['swap', 'table_swap', 'table_shift', 'table_move', 'table_swapped', 'table_shifted']) || strpos($utRem, 'switched/shifted') !== false || strpos($utRem, 'shifted') !== false) {
                        if (preg_match('/to\s+Table\s+([^\s\(\)\,\.]+)/i', $utRem, $m)) {
                            $currentTableNum = trim($m[1]);
                        }
                    }
                }
            }

            // Gather items for this order/bill
            $itemsRaw = $itemsMap[$refId] ?? [];
            if ($isLiveActive && !empty($trackingMap[$refId])) {
                foreach ($trackingMap[$refId] as $ttRow) {
                    $ott = $ttRow['ott'];
                    if ((int)($ott['status'] ?? 0) === 1 && !empty($ott['merged_table_ref_id'])) {
                        $mChildRef = $ott['merged_table_ref_id'];
                        if (!empty($itemsMap[$mChildRef])) {
                            foreach ($itemsMap[$mChildRef] as $mIt) {
                                $itemsRaw[] = $mIt;
                            }
                        }
                    }
                }
            }

            // 1. First order info from orderhistories ($itemsRaw)
            $firstOrderItem = !empty($itemsRaw[0]) ? $itemsRaw[0] : null;
            $firstOrderStaff = '';
            $firstOrderTime = $createdTime;
            if ($firstOrderItem) {
                if (!empty($firstOrderItem['u']['staff_fullname'])) {
                    $firstOrderStaff = trim($firstOrderItem['u']['staff_fullname']);
                } elseif (!empty($firstOrderItem['u']['staff_username'])) {
                    $firstOrderStaff = trim($firstOrderItem['u']['staff_username']);
                }
                if (!empty($firstOrderItem['oh']['created']) && $firstOrderItem['oh']['created'] != '0000-00-00 00:00:00') {
                    $firstOrderTime = $firstOrderItem['oh']['created'];
                }
            }
            if (empty($firstOrderStaff)) {
                $firstOrderStaff = (!empty($cashierName) && $cashierName !== 'N/A') ? $cashierName : 'Cashier';
            }

            // Resolved Cashier Name (for summary/header)
            $resolvedCashierName = (!empty($cashierName) && $cashierName !== 'N/A') ? $cashierName : $firstOrderStaff;

            // Waiter / Server name resolution: transaction waiter_name if available, else first order puncher (order_punch_by)
            $waiterName = '';
            if (!empty($txWaiterName) && trim((string)$txWaiterName) !== '' && trim((string)$txWaiterName) !== '0' && strtolower(trim((string)$txWaiterName)) !== 'n/a') {
                $waiterName = trim((string)$txWaiterName);
            }
            if (empty($waiterName) && !empty($allTxnsMap[$refId])) {
                foreach ($allTxnsMap[$refId] as $relTx) {
                    $rTx = $relTx['t'] ?? [];
                    if (!empty($rTx['waiter_name']) && trim((string)$rTx['waiter_name']) !== '' && trim((string)$rTx['waiter_name']) !== '0' && strtolower(trim((string)$rTx['waiter_name'])) !== 'n/a') {
                        $waiterName = trim((string)$rTx['waiter_name']);
                        break;
                    }
                }
            }
            if (empty($waiterName)) {
                $waiterName = $firstOrderStaff;
            }
            if (empty($waiterName)) {
                $waiterName = 'N/A';
            }

            // Bill Generated Events for any bills created under this order
            $billGeneratedEvents = [];
            $allRelatedTxns = $allTxnsMap[$refId] ?? [];
            foreach ($allRelatedTxns as $txRow) {
                $tx = $txRow['t'];
                $cName = !empty($txRow['u']['cashier_fullname']) ? $txRow['u']['cashier_fullname'] : (!empty($txRow['u']['cashier_username']) ? $txRow['u']['cashier_username'] : $resolvedCashierName);
                $tblText = !empty($tx['table_number']) ? " on Table {$tx['table_number']}" : "";
                $amtText = !empty($tx['amount']) ? " (Amount: ₹" . number_format($tx['amount'], 2) . ")" : "";
                
                $billGeneratedEvents[] = [
                    'timestamp' => $tx['created'],
                    'type' => 'BILL_GENERATED',
                    'label' => 'Bill Generated',
                    'badge' => 'primary',
                    'description' => "Bill #{$tx['orderno']} generated{$tblText}{$amtText} by {$cName}.",
                    'details' => $tx
                ];
            }

            // Customer Details Resolution (from transactions or reelo discounts)
            $resolvedCustName = !empty($custName) ? trim((string)$custName) : '';
            $resolvedCustPhone = !empty($custPhone) ? trim((string)$custPhone) : '';
            $resolvedCustAddress = '';
            $resolvedCustPax = 0;

            if (empty($resolvedCustPhone) && !empty($allTxnsMap[$refId])) {
                foreach ($allTxnsMap[$refId] as $relTx) {
                    $rTx = $relTx['t'] ?? [];
                    $rZero = $relTx[0] ?? [];
                    $pCheck = $rTx['phone'] ?? ($rTx['customer_phone'] ?? ($rZero['customer_phone'] ?? ($rZero['phone'] ?? '')));
                    if (!empty($pCheck)) {
                        $resolvedCustPhone = trim((string)$pCheck);
                        if (empty($resolvedCustName)) {
                            $resolvedCustName = trim((string)($rTx['name'] ?? ($rTx['customer_name'] ?? ($rZero['customer_name'] ?? ($rZero['name'] ?? '')))));
                        }
                        break;
                    }
                }
            }

            if (empty($resolvedCustPhone) && !empty($reeloDiscountsMap[$refId])) {
                foreach ($reeloDiscountsMap[$refId] as $rdRow) {
                    $rd = $rdRow['rd'] ?? [];
                    $pCheck = $rd['customer_mobile'] ?? ($rd['customer_phone'] ?? ($rd['phone'] ?? ''));
                    if (!empty($pCheck)) {
                        $resolvedCustPhone = trim((string)$pCheck);
                        if (empty($resolvedCustName) && !empty($rd['customer_name'])) {
                            $resolvedCustName = trim((string)$rd['customer_name']);
                        }
                        break;
                    }
                }
            }

            // Split transactions for this order
            $splitTxns = $splitTxnMap[$refId] ?? [];
            $splitEvents = [];
            if (!empty($splitTxns)) {
                $splitCount = count($splitTxns);
                $splitDetails = [];
                foreach ($splitTxns as $idx => $stRow) {
                    $st = $stRow['ost'];
                    $splitDetails[] = "Part " . ($idx + 1) . " (Bill #" . $st['orderno'] . " - ₹" . number_format($st['amount'], 2) . ")";
                }
                $splitEvents[] = [
                    'timestamp' => $splitTxns[0]['ost']['created'],
                    'type' => 'BILL_SPLIT',
                    'label' => "Bill Split into {$splitCount} Sub-Bills",
                    'badge' => 'purple',
                    'description' => "Order was split into {$splitCount} separate sub-bills: " . implode(', ', $splitDetails) . " by {$resolvedCashierName}.",
                    'details' => $splitTxns
                ];
            }

            // Reelo Discounts from reelo_discounts table
            $reeloEvents = [];
            $reeloDiscountsData = [];
            $reeloDiscountTotal = 0;
            foreach ($mergedRefs as $mRef) {
                if (!empty($reeloDiscountsMap[$mRef])) {
                    foreach ($reeloDiscountsMap[$mRef] as $rdRow) {
                        $rd = $rdRow['rd'] ?? ($rdRow['ReeloDiscount'] ?? ($rdRow['reelo_discounts'] ?? []));
                        $rdStaff = !empty($rdRow['u']['user_fullname']) ? $rdRow['u']['user_fullname'] : (!empty($rdRow['u']['user_username']) ? $rdRow['u']['user_username'] : $resolvedCashierName);
                        $discTypeNum = (int)($rd['reelo_discount_type'] ?? 0);
                        
                        $discTypeLabel = 'Reelo Discount';
                        if ($discTypeNum === 0) {
                            $discTypeLabel = 'Reelo Coupon Discount';
                        } elseif ($discTypeNum === 1) {
                            $discTypeLabel = 'Reelo Points / Cashback Discount';
                        } elseif ($discTypeNum === 2) {
                            $discTypeLabel = 'Reelo Wallet Discount';
                        }

                        $valText = "";
                        if (!empty($rd['coupon_code'])) {
                            $valText .= " (Coupon: " . $rd['coupon_code'] . ")";
                        }
                        if (!empty($rd['discount_value'])) {
                            $discUnit = ($rd['discount_type'] == 'percentage') ? '%' : '₹';
                            $valText .= " [Value: {$discUnit}{$rd['discount_value']} on " . ucfirst($rd['discount_on'] ?? 'bill') . "]";
                        }
                        if (!empty($rd['amount']) && $rd['amount'] > 0) {
                            $valText .= " [Discount Amount: ₹" . number_format($rd['amount'], 2) . "]";
                            $reeloDiscountTotal += (float)$rd['amount'];
                        }

                        $reeloDiscountsData[] = [
                            'id' => $rd['id'] ?? null,
                            'type' => $discTypeNum,
                            'type_label' => $discTypeLabel,
                            'coupon_code' => $rd['coupon_code'] ?? '',
                            'discount_on' => $rd['discount_on'] ?? 'bill',
                            'discount_type' => $rd['discount_type'] ?? 'fixed',
                            'discount_value' => (float)($rd['discount_value'] ?? 0),
                            'amount' => (float)($rd['amount'] ?? 0),
                            'user_name' => $rdStaff,
                            'created' => $rd['created'] ?? $createdTime
                        ];

                        $reeloEvents[] = [
                            'timestamp' => $rd['created'] ?? $createdTime,
                            'type' => 'REELO_DISCOUNT',
                            'label' => $discTypeLabel,
                            'badge' => 'warning',
                            'description' => "{$discTypeLabel} applied{$valText} by {$rdStaff}.",
                            'details' => $rd
                        ];
                    }
                }
            }

            // User Tracks
            $userTracksRaw = [];
            foreach ($mergedRefs as $mRef) {
                if (!empty($tracksMap[$mRef])) {
                    foreach ($tracksMap[$mRef] as $uRow) {
                        $userTracksRaw[] = $uRow;
                    }
                }
            }

            // Find deletion remarks from user tracks to enrich deleted items
            $delTrackMap = [];
            foreach ($userTracksRaw as $uRow) {
                $ut = $uRow['ut'];
                $tType = strtolower($ut['type'] ?? '');
                if (in_array($tType, ['deletemultipe', 'delete', 'item_delete', 'delete_item', 'item_deleted', 'kot_delete', 'kot_item_delete'])) {
                    $uNameTrack = !empty($uRow['u']['user_fullname']) ? $uRow['u']['user_fullname'] : ($uRow['u']['user_username'] ?? '');
                    $rem = trim($ut['remarks'] ?? '');
                    if (!empty($rem)) {
                        $delTrackMap[] = [
                            'remarks' => $rem,
                            'manager' => $uNameTrack,
                            'timestamp' => $ut['created']
                        ];
                    }
                }
            }

            $activeItems = [];
            $deletedItems = [];
            $itemEvents = [];
            $runningSubtotal = 0;

            foreach ($itemsRaw as $itRow) {
                $it = $itRow['oh'];
                $pName = !empty($it['product_name']) ? $it['product_name'] : (!empty($itRow['p']['catalog_pname']) ? $itRow['p']['catalog_pname'] : ('Product #' . $it['product_id']));
                $uName = !empty($itRow['u']['staff_fullname']) ? $itRow['u']['staff_fullname'] : (!empty($itRow['u']['staff_username']) ? $itRow['u']['staff_username'] : $resolvedCashierName);
                $kotNum = $it['kot_number'] ?? 'N/A';
                $itemRemarks = trim($it['remarks'] ?? '');
                $itemTableText = (!empty($it['table_number']) && $it['table_number'] != $tableNum) ? " (Table {$it['table_number']})" : "";

                $itemSplitRef = !empty($it['split_reference_id']) ? $it['split_reference_id'] : ($splitHistToSplitRefMap[$it['id']] ?? '');
                $subBillInfo = "";
                $subBillOrderNo = "";
                $subBillIdx = "";
                if (!empty($itemSplitRef) && !empty($splitTxns)) {
                    foreach ($splitTxns as $idx => $stRow) {
                        if ($stRow['ost']['split_reference_id'] == $itemSplitRef) {
                            $subBillIdx = ($idx + 1);
                            $subBillOrderNo = $stRow['ost']['orderno'];
                            $subBillInfo = " [Assigned to Sub-Bill " . $subBillIdx . ": #" . $subBillOrderNo . "]";
                            break;
                        }
                    }
                }

                $itemData = [
                    'id' => $it['id'],
                    'product_id' => $it['product_id'],
                    'product_name' => $pName,
                    'quantity' => $it['quantity'],
                    'price' => $it['price'],
                    'total_price' => $it['total_price'],
                    'kot_no' => $kotNum,
                    'status' => $it['status'],
                    'user_name' => $uName,
                    'created' => $it['created'],
                    'split_reference_id' => $itemSplitRef,
                    'split_sub_bill_no' => $subBillOrderNo,
                    'split_sub_bill_idx' => $subBillIdx
                ];

                if ($it['status'] == 0) {
                    $deletedItems[] = $itemData;

                    // If item itself didn't have remarks in oh, fetch from user tracks
                    if (empty($itemRemarks) && !empty($delTrackMap)) {
                        $itemRemarks = $delTrackMap[0]['remarks'];
                    }
                    $remarkText = !empty($itemRemarks) ? " Remarks: {$itemRemarks}" : "";

                    $itemEvents[] = [
                        'timestamp' => $it['created'],
                        'type' => 'ITEM_ADDED',
                        'label' => 'Item Added (KOT)',
                        'badge' => 'info',
                        'description' => "Item '{$pName}' (Qty: {$it['quantity']}, Rate: ₹{$it['price']}, Total: ₹{$it['total_price']}) added to order{$itemTableText}{$subBillInfo} under KOT #" . ($kotNum ? $kotNum : 'N/A') . " by {$uName}.",
                        'details' => $itemData
                    ];
                    $delTime = (!empty($it['modified']) && $it['modified'] != '0000-00-00 00:00:00') ? $it['modified'] : $it['created'];
                    $itemEvents[] = [
                        'timestamp' => $delTime,
                        'type' => 'ITEM_DELETED',
                        'label' => 'Item Deleted / Removed',
                        'badge' => 'danger',
                        'description' => "Item '{$pName}' (Qty: {$it['quantity']}, Rate: ₹{$it['price']}, Total: ₹{$it['total_price']}) was removed by {$uName}.{$remarkText}",
                        'details' => $itemData
                    ];
                } else {
                    $activeItems[] = $itemData;
                    $runningSubtotal += (float)$it['total_price'];
                    $itemEvents[] = [
                        'timestamp' => $it['created'],
                        'type' => 'ITEM_ADDED',
                        'label' => 'Item Added (KOT)',
                        'badge' => 'info',
                        'description' => "Item '{$pName}' (Qty: {$it['quantity']}, Rate: ₹{$it['price']}, Total: ₹{$it['total_price']}) added to order{$itemTableText}{$subBillInfo} under KOT #" . ($kotNum ? $kotNum : 'N/A') . " by {$uName}.",
                        'details' => $itemData
                    ];
                }
            }

            // Table Events
            $tableEvents = [];
            if (!empty($trackingMap[$refId])) {
                foreach ($trackingMap[$refId] as $ttRow) {
                    $ott = $ttRow['ott'];
                    $mainTbl = !empty($ttRow[0]['main_tbl']) ? $ttRow[0]['main_tbl'] : (!empty($ott['main_table_number']) ? $ott['main_table_number'] : 'N/A');
                    $mergedTbl = !empty($ttRow[0]['merged_tbl']) ? $ttRow[0]['merged_tbl'] : (!empty($ott['merged_table_number']) ? $ott['merged_table_number'] : 'N/A');
                    $ottType = strtolower($ott['type'] ?? '');
                    if ($ottType == 'merge' || empty($ottType)) {
                        $isCurrentlyMerged = ((int)($ott['status'] ?? 0) === 1);
                        $mergeDesc = $isCurrentlyMerged 
                            ? "Table {$mergedTbl} is merged into Main Table {$mainTbl}."
                            : "Table {$mergedTbl} was merged into Main Table {$mainTbl}.";
                        $tableEvents[] = ['timestamp' => $ott['created'], 'type' => 'TABLE_MERGE', 'label' => 'Table Merged', 'badge' => 'warning', 'description' => $mergeDesc, 'details' => $ott];
                    } elseif (in_array($ottType, ['swap', 'shift'])) {
                        $tableEvents[] = ['timestamp' => $ott['created'], 'type' => 'TABLE_SHIFT', 'label' => 'Table Shift / Swap', 'badge' => 'purple', 'description' => "Order shifted/transferred from Table {$mainTbl} to Table {$mergedTbl}.", 'details' => $ott];
                    }
                }
            }

            $trackEvents = [];
            $managersList = [];
            $curTableClean = trim((string)$currentTableNum);
            $curTableCleanLower = strtolower(str_replace(['table ', 't - '], '', $curTableClean));

            foreach ($userTracksRaw as $uRow) {
                $ut = $uRow['ut'];
                $usrFullName = !empty($uRow['u']['user_fullname']) ? $uRow['u']['user_fullname'] : ($uRow['u']['user_username'] ?? 'User');
                $usrRole = strtolower($uRow['u']['user_role'] ?? '');
                $tType = strtolower($ut['type'] ?? '');
                $remarks = $ut['remarks'] ?? '';
                
                // Identify Managers: user role is manager or username/fullname is manager
                if ($usrRole === 'manager' || strpos(strtolower($usrFullName), 'manager') !== false) {
                    if (!empty($usrFullName) && !in_array($usrFullName, $managersList)) {
                        $managersList[] = $usrFullName;
                    }
                }

                // Suppress duplicate standalone events for item deletions and internal adjustments
                if (in_array($tType, ['deletemultipe', 'delete', 'item_delete', 'delete_item', 'item_deleted', 'kot_delete', 'kot_item_delete', 'split_adjustment', 'adjustment', 'tax_adjustment', 'sc_adjustment', 'discount', 'discount_apply', 'discount_applied'])) {
                    continue;
                }

                // Filter movement events per table
                if (in_array($tType, ['item_move', 'kot_move'])) {
                    if (preg_match('/to\s+Table\s+([^\s\(\)]+)/i', $remarks, $m)) {
                        $destTbl = strtolower(trim($m[1]));
                        if ($destTbl === $curTableCleanLower) {
                            continue; // Skip move-out on the destination table
                        }
                    }
                } elseif (in_array($tType, ['item_received', 'kot_received'])) {
                    if (preg_match('/from\s+Table\s+([^\s\(\)]+)/i', $remarks, $m)) {
                        $srcTbl = strtolower(trim($m[1]));
                        if ($srcTbl === $curTableCleanLower) {
                            continue; // Skip receive on the source table
                        }
                    }
                }

                $badge = 'info';
                $label = ucwords(str_replace('_', ' ', $tType));
                if ($tType === 'discount') {
                    $badge = 'warning';
                    $label = 'Discount Applied';
                } elseif (in_array($tType, ['bill_modified'])) {
                    $badge = 'primary';
                    $label = 'Bill Modified';
                } elseif (strpos($tType, 'unbill') !== false) {
                    $badge = 'warning';
                    $label = 'Bill Unbilled / Re-opened';
                } elseif (strpos($tType, 'cancel') !== false || strpos($tType, 'void') !== false) {
                    $badge = 'danger';
                    $label = 'Order Cancelled / Voided';
                } elseif ($tType === 'item_move' || strpos($tType, 'move') !== false) {
                    $badge = 'purple';
                    $label = 'Item Moved';
                } elseif ($tType === 'item_received' || strpos($tType, 'received') !== false) {
                    $badge = 'success';
                    $label = 'Item Received';
                } elseif ($tType === 'swap') {
                    $badge = 'purple';
                    $label = 'Table Swapped';
                } elseif ($tType === 'quantity_increase') {
                    $badge = 'info';
                    $label = 'Quantity Increased';
                } elseif ($tType === 'quantity_decrease') {
                    $badge = 'info';
                    $label = 'Quantity Decreased';
                } elseif (in_array($tType, ['payment_change', 'payment_changed', 'payment_mode_change', 'payment_mode_changed'])) {
                    $badge = 'warning';
                    $label = 'Payment Changed';
                } elseif (in_array($tType, ['payment_delete', 'payment_deleted', 'payment_removed', 'payment_remove'])) {
                    $badge = 'danger';
                    $label = 'Payment Deleted';
                }

                $actorName = $usrFullName;
                if (in_array($tType, ['discount', 'bill_modified', 'unbill', 'cancel', 'void', 'nc', 'payment_change', 'payment_changed', 'payment_delete', 'payment_deleted'])) {
                    $actorName = !empty($managersList) ? $managersList[0] : (!empty($usrFullName) ? $usrFullName : 'manager');
                }

                if (in_array($tType, ['item_move', 'item_received', 'kot_move', 'kot_received', 'quantity_increase', 'quantity_decrease']) && !empty($remarks)) {
                    $eventDesc = "{$remarks} by {$usrFullName}.";
                } else {
                    $formattedRemarks = $remarks;
                    if (in_array($tType, ['discount', 'bill_modified', 'unbill']) && !empty($resolvedCashierName)) {
                        $mgrSubstitute = !empty($managersList) ? $managersList[0] : 'manager';
                        $formattedRemarks = preg_replace('/\bby\s+' . preg_quote($resolvedCashierName, '/') . '\b/i', 'by ' . $mgrSubstitute, $formattedRemarks);
                    }
                    $eventDesc = "{$label} by {$actorName}." . (!empty($formattedRemarks) ? " Remarks: {$formattedRemarks}" : "");
                }

                $trackEvents[] = [
                    'timestamp' => $ut['created'],
                    'type' => 'USER_TRACK',
                    'label' => $label,
                    'badge' => $badge,
                    'description' => $eventDesc,
                    'details' => $ut
                ];
            }

            $primaryManager = !empty($managersList) ? $managersList[0] : 'manager';

            // Target Category: from discount_items column (0=Food, 1=Beverage, 2=Liquor, all=All)
            $targetCategory = 'All';
            $dItemRaw = strtolower(trim((string)($txDiscItems ?? 'all')));
            if ($dItemRaw === '0' || $dItemRaw === 'food') {
                $targetCategory = 'Food';
            } elseif ($dItemRaw === '1' || $dItemRaw === 'beverage' || $dItemRaw === 'bev') {
                $targetCategory = 'Beverage';
            } elseif ($dItemRaw === '2' || $dItemRaw === 'liquor' || $dItemRaw === 'bar') {
                $targetCategory = 'Liquor';
            } elseif ($dItemRaw === 'all' || $dItemRaw === '' || empty($dItemRaw)) {
                $targetCategory = 'All';
            } else {
                $targetCategory = ucfirst($dItemRaw);
            }

            // Fallback check on related transactions if needed
            if ($targetCategory === 'All' && !empty($allTxnsMap[$refId])) {
                foreach ($allTxnsMap[$refId] as $relTx) {
                    $rTx = $relTx['t'] ?? [];
                    if (isset($rTx['discount_items']) && $rTx['discount_items'] !== '' && $rTx['discount_items'] !== 'all') {
                        $di = strtolower(trim((string)$rTx['discount_items']));
                        if ($di === '0' || $di === 'food') $targetCategory = 'Food';
                        elseif ($di === '1' || $di === 'beverage') $targetCategory = 'Beverage';
                        elseif ($di === '2' || $di === 'liquor') $targetCategory = 'Liquor';
                        break;
                    }
                }
            }

            // Discount Applied Event (from transaction totals if discount given)
            $discountEvents = [];
            $discTypeLabel = 'Discount';
            $summaryDiscText = "";
            if ((float)$txDisc > 0) {
                $resolvedDiscType = (int)$txDiscType;
                $resolvedDiscVal = $txDiscVal;

                if (!empty($userTracksRaw)) {
                    foreach ($userTracksRaw as $uRow) {
                        $utRem = $uRow['ut']['remarks'] ?? '';
                        if (preg_match('/(\d+(?:\.\d+)?)\s*%/i', $utRem, $pm)) {
                            $resolvedDiscType = 1;
                            if (empty($resolvedDiscVal)) $resolvedDiscVal = $pm[1];
                            break;
                        } elseif (preg_match('/\bflat\b/i', $utRem)) {
                            $resolvedDiscType = 2;
                            break;
                        }
                    }
                }

                if ($resolvedDiscType == 1) {
                    $discTypeLabel = 'Percentage Discount';
                    $pctVal = !empty($resolvedDiscVal) ? (float)$resolvedDiscVal : (($txPrice > 0) ? round(($txDisc / $txPrice) * 100, 1) : 0);
                    $discText = "{$pctVal}% (Percentage) on {$targetCategory} (₹" . number_format($txDisc, 2) . ")";
                    $summaryDiscText = "{$pctVal}% (Percentage) on {$targetCategory}";
                } elseif ($resolvedDiscType == 2) {
                    $discTypeLabel = 'Flat Discount';
                    $discText = "₹" . number_format($txDisc, 2) . " (Flat) on {$targetCategory}";
                    $summaryDiscText = "₹" . number_format($txDisc, 2) . " (Flat) on {$targetCategory}";
                } else {
                    $discTypeLabel = 'Coupon Discount';
                    $discText = "₹" . number_format($txDisc, 2) . " (Coupon) on {$targetCategory}";
                    $summaryDiscText = "₹" . number_format($txDisc, 2) . " (Coupon) on {$targetCategory}";
                }

                $discStaff = $primaryManager;
                $discTime = $createdTime;
                if (!empty($userTracksRaw)) {
                    foreach ($userTracksRaw as $uRow) {
                        $ut = $uRow['ut'];
                        $utType = strtolower($ut['type'] ?? '');
                        if (strpos($utType, 'discount') !== false || in_array($utType, ['bill_modified', 'split_adjustment'])) {
                            $discTime = $ut['created'];
                            break;
                        }
                    }
                }
                $discountEvents[] = [
                    'timestamp' => $discTime,
                    'type' => 'DISCOUNT_APPLIED',
                    'label' => 'Discount Applied',
                    'badge' => 'warning',
                    'description' => "Discount of {$discText} applied to bill by {$discStaff}.",
                    'details' => [
                        'discount' => $txDisc,
                        'discount_type' => $resolvedDiscType,
                        'discount_type_value' => $resolvedDiscVal,
                        'category' => $targetCategory
                    ]
                ];
            }

            // Service Charge Removed / Disabled Events (only when txSC == 0)
            $scRemovedEvents = [];
            $scRemoved = false;
            $scStaff = $primaryManager;
            $scTime = $createdTime;
            $scAmountRemoved = 0;

            if ((float)$txSC == 0) {
                if (!empty($removedTaxesMap[$refId])) {
                    foreach ($removedTaxesMap[$refId] as $rt) {
                        $tName = strtolower(trim($rt['tax_name'] ?? ''));
                        if (strpos($tName, 'service') !== false || strpos($tName, 'sc') !== false) {
                            $scRemoved = true;
                            $scAmountRemoved += (float)($rt['tax_value'] ?? 0);
                            $tMod = $rt['modified'] ?? ($rt['created'] ?? $createdTime);
                            $scRemovedEvents[] = [
                                'timestamp' => $tMod,
                                'type' => 'SERVICE_CHARGE_REMOVED',
                                'label' => 'Service Charge Removed',
                                'badge' => 'purple',
                                'description' => "Service Charge removed / waived from order by {$scStaff}.",
                                'details' => $rt
                            ];
                        }
                    }
                }

                if (!empty($userTracksRaw)) {
                    foreach ($userTracksRaw as $uRow) {
                        $ut = $uRow['ut'];
                        $utType = strtolower($ut['type'] ?? '');
                        $utRem = $ut['remarks'] ?? '';

                        if (
                            in_array($utType, ['sc_removed', 'service_charge_removed', 'sc_waived', 'sc_disabled', 'service_charge_disabled']) ||
                            preg_match('/\b(sc_removed|sc_disabled|service\s*charge\s*removed|service\s*charge\s*disabled|disable\s*sc)\b/i', $utType . ' ' . $utRem) ||
                            preg_match('/\b(sc|service\s*charge)\b/i', $utRem)
                        ) {
                            $scRemoved = true;
                            $scTime = $ut['created'];
                            $labelDesc = (strpos($utType, 'disable') !== false || strpos(strtolower($utRem), 'disable') !== false) ? 'Service Charge Disabled' : 'Service Charge Removed';
                            $remText = !empty($utRem) ? " Remarks: {$utRem}" : "";
                            $scRemovedEvents[] = [
                                'timestamp' => $ut['created'],
                                'type' => 'SERVICE_CHARGE_REMOVED',
                                'label' => $labelDesc,
                                'badge' => 'purple',
                                'description' => "{$labelDesc} from order by {$scStaff}.{$remText}",
                                'details' => $ut
                            ];
                        }
                    }
                }

                if (!$isLiveActive) {
                    $scRemoved = true;
                    if (empty($scRemovedEvents)) {
                        $scRemovedEvents[] = [
                            'timestamp' => $createdTime,
                            'type' => 'SERVICE_CHARGE_REMOVED',
                            'label' => 'Service Charge Removed',
                            'badge' => 'purple',
                            'description' => "Service Charge removed / waived from bill by {$scStaff}.",
                            'details' => ['amount' => 0, 'staff' => $scStaff]
                        ];
                    }
                }
            }

            // Service Charge Applied / Added Event (when txSC > 0)
            $scAppliedEvents = [];
            if ((float)$txSC > 0) {
                $scApplyStaff = $primaryManager;
                $scApplyTime = $createdTime;
                $wasScModifiedOrAdded = false;

                if (!empty($userTracksRaw)) {
                    foreach ($userTracksRaw as $uRow) {
                        $ut = $uRow['ut'] ?? [];
                        $utType = strtolower($ut['type'] ?? '');
                        $utRem = $ut['remarks'] ?? '';
                        if (
                            in_array($utType, ['sc_added', 'service_charge_added', 'sc_apply', 'sc_applied', 'sc_adjustment']) ||
                            preg_match('/\b(sc|service\s*charge)\b/i', $utRem)
                        ) {
                            $wasScModifiedOrAdded = true;
                            $scApplyTime = $ut['created'];
                            break;
                        }
                    }
                }

                if (!empty($allTxnsMap[$refId])) {
                    foreach ($allTxnsMap[$refId] as $relTx) {
                        $rTx = $relTx['t'] ?? [];
                        if (isset($rTx['servicecharge']) && (float)$rTx['servicecharge'] == 0 && (int)($rTx['status'] ?? 0) != 0) {
                            $wasScModifiedOrAdded = true;
                            break;
                        }
                    }
                }

                if ($wasScModifiedOrAdded) {
                    $scAppliedEvents[] = [
                        'timestamp' => $scApplyTime,
                        'type' => 'SERVICE_CHARGE_APPLIED',
                        'label' => 'Service Charge Applied',
                        'badge' => 'purple',
                        'description' => "Service Charge of ₹" . number_format($txSC, 2) . " applied to bill by {$scApplyStaff}.",
                        'details' => ['amount' => $txSC, 'staff' => $scApplyStaff]
                    ];
                }
            }

            // Payments
            $paymentEvents = [];
            $paymentModes = [];
            $paymentsRaw = [];
            foreach ($mergedRefs as $mRef) {
                if (!empty($splitPaymentsMap[$mRef])) {
                    foreach ($splitPaymentsMap[$mRef] as $spRow) {
                        $paymentsRaw[] = $spRow['osp'];
                    }
                } elseif (!empty($paymentsMap[$mRef])) {
                    foreach ($paymentsMap[$mRef] as $pRow) {
                        $paymentsRaw[] = $pRow['op'];
                    }
                }
            }

            foreach ($paymentsRaw as $op) {
                $mode = !empty($op['type_value']) ? $op['type_value'] : (!empty($op['type']) ? $op['type'] : 'Payment');
                $amt = (float)($op['amount'] ?? 0);
                $pStaff = !empty($op['payment_fullname']) ? $op['payment_fullname'] : (!empty($op['payment_username']) ? $op['payment_username'] : $resolvedCashierName);
                $opStatus = (int)($op['status'] ?? 0);
                $opRemarks = trim($op['remarks'] ?? '');

                // Skip dummy zero-amount status 0 records
                if ($amt <= 0 && $opStatus === 0) {
                    continue;
                }

                $paymentEvents[] = [
                    'timestamp' => $op['created'],
                    'type' => 'PAYMENT',
                    'label' => 'Payment Received',
                    'badge' => 'success',
                    'description' => "Payment of ₹" . number_format($amt, 2) . " received via {$mode}.",
                    'details' => $op
                ];

                if ($opStatus === 0) {
                    $delTime = (!empty($op['modified']) && $op['modified'] != '0000-00-00 00:00:00') ? $op['modified'] : $op['created'];
                    $remText = !empty($opRemarks) ? " Remarks: {$opRemarks}" : "";
                    if (empty($remText) && !empty($userTracksRaw)) {
                        foreach ($userTracksRaw as $uRow) {
                            $ut = $uRow['ut'] ?? [];
                            $utType = strtolower($ut['type'] ?? '');
                            $utRem = trim($ut['remarks'] ?? '');
                            if (strpos($utType, 'payment') !== false && !empty($utRem)) {
                                $remText = " Remarks: {$utRem}";
                                break;
                            }
                        }
                    }

                    $delStaff = (!empty($primaryManager) && $primaryManager !== 'manager') ? $primaryManager : $pStaff;

                    $paymentEvents[] = [
                        'timestamp' => $delTime,
                        'type' => 'PAYMENT_DELETED',
                        'label' => 'Payment Deleted / Removed',
                        'badge' => 'danger',
                        'description' => "Payment of ₹" . number_format($amt, 2) . " via {$mode} was deleted / removed by {$delStaff}.{$remText}",
                        'details' => $op
                    ];
                } else {
                    $paymentModes[] = "{$mode} (₹" . number_format($amt, 2) . ")";
                }
            }

            // Fallback for settled bills if individual order_payments records not yet queried
            if ((int)$txStatus == 2 && empty($paymentModes)) {
                $paymentModes[] = "Settled (₹" . number_format($txAmt, 2) . ")";
                if (empty($paymentEvents)) {
                    $paymentEvents[] = [
                        'timestamp' => $createdTime,
                        'type' => 'PAYMENT',
                        'label' => 'Payment Received',
                        'badge' => 'success',
                        'description' => "Payment of ₹" . number_format($txAmt, 2) . " received (Settled).",
                        'details' => []
                    ];
                }
            }

            $waiterSuffix = ($waiterName !== 'N/A') ? " (Server: {$waiterName})" : "";
            $initLabel = $isLiveActive ? 'Table Opened (Active)' : 'Order Created';
            $initDesc = $isLiveActive 
                ? "Table {$currentTableNum} occupied and opened{$waiterSuffix} by {$firstOrderStaff}." 
                : "Order #{$orderNo} created on Table {$currentTableNum} by {$firstOrderStaff}{$waiterSuffix}.";

            $timeline = array_merge(
                [['timestamp' => $firstOrderTime, 'type' => 'ORDER_CREATED', 'label' => $initLabel, 'badge' => 'success', 'description' => $initDesc, 'details' => []]],
                $itemEvents,
                $billGeneratedEvents,
                $discountEvents,
                $scAppliedEvents,
                $scRemovedEvents,
                $tableEvents,
                $splitEvents,
                $reeloEvents,
                $trackEvents,
                $paymentEvents
            );
            usort($timeline, function($a, $b) { return strtotime($b['timestamp']) - strtotime($a['timestamp']); });

            $dedupedTimeline = [];
            $seenEvents = [];
            foreach ($timeline as $evt) {
                $evtBucket = floor(strtotime($evt['timestamp']) / 10);
                $evtKey = $evtBucket . '_' . ($evt['label'] ?? '') . '_' . ($evt['description'] ?? '');
                if (!isset($seenEvents[$evtKey])) {
                    $seenEvents[$evtKey] = true;
                    $dedupedTimeline[] = $evt;
                }
            }
            $timeline = $dedupedTimeline;

            $hasUnbillEvent = false;
            $latestUnbillTime = 0;
            foreach ($userTracksRaw as $uRow) {
                $utType = strtolower($uRow['ut']['type'] ?? '');
                if (strpos($utType, 'unbill') !== false) {
                    $hasUnbillEvent = true;
                    $unbillTs = strtotime($uRow['ut']['created']);
                    if ($unbillTs > $latestUnbillTime) {
                        $latestUnbillTime = $unbillTs;
                    }
                }
            }

            $isCurrentlyUnbilledAndOpen = $isLiveActive && $hasUnbillEvent && ($latestUnbillTime >= strtotime($createdTime));

            $activeDiscount = $isCurrentlyUnbilledAndOpen ? 0.0 : (float)$txDisc;
            $activeDiscountSummaryText = $isCurrentlyUnbilledAndOpen ? "" : $summaryDiscText;
            $activeScRemoved = ($isCurrentlyUnbilledAndOpen || (float)$txSC > 0) ? 0 : ($scRemoved ? 1 : 0);
            $activeScStaff = $activeScRemoved ? $scStaff : '';

            // Status resolution directly from transactions and payments
            $hasSuccessfulPayment = false;
            if (!empty($paymentEvents)) {
                foreach ($paymentEvents as $pe) {
                    $pDetails = $pe['details'] ?? [];
                    if (!isset($pDetails['status']) || (int)$pDetails['status'] !== 0) {
                        $hasSuccessfulPayment = true;
                        break;
                    }
                }
            }

            if ($isLiveActive) {
                $statusText = 'Preparing';
                $txPrice = $runningSubtotal;
                $txAmt = $runningSubtotal;
            } else {
                if ((int)$txStatus === 2 || $hasSuccessfulPayment) {
                    $statusText = 'Settled / Paid';
                } elseif ((int)$txStatus === 0 && count($activeItems) === 0) {
                    $statusText = 'Cancelled / Void';
                } elseif ((int)$txStatus === 0) {
                    $statusText = 'Cancelled / Void';
                } else {
                    if (!empty($paymentModes) && $paymentModes[0] !== 'Pending Settlement') {
                        $statusText = 'Settled / Paid';
                    } elseif ($hasUnbillEvent && $latestUnbillTime >= strtotime($createdTime)) {
                        $statusText = 'Unbilled';
                    } else {
                        $statusText = 'Preparing';
                    }
                }
            }

            $splitBillsData = [];
            if (!empty($splitTxns)) {
                foreach ($splitTxns as $idx => $stRow) {
                    $st = $stRow['ost'];
                    $stSplitRef = $st['split_reference_id'];
                    $stItems = [];

                    if (!empty($splitItemsMap[$stSplitRef])) {
                        foreach ($splitItemsMap[$stSplitRef] as $soRow) {
                            $so = $soRow['osoh'];
                            $pName = !empty($so['product_name']) ? $so['product_name'] : (!empty($soRow['p']['catalog_pname']) ? $soRow['p']['catalog_pname'] : ('Product #' . $so['product_id']));
                            $uName = !empty($soRow['u']['staff_fullname']) ? $soRow['u']['staff_fullname'] : (!empty($soRow['u']['staff_username']) ? $soRow['u']['staff_username'] : $resolvedCashierName);
                            $stItems[] = [
                                'id' => $so['id'],
                                'product_id' => $so['product_id'],
                                'product_name' => $pName,
                                'quantity' => $so['quantity'],
                                'price' => (float)$so['price'],
                                'total_price' => (float)$so['total_price'],
                                'kot_no' => !empty($so['kot_number']) ? $so['kot_number'] : (!empty($so['kot_no']) ? $so['kot_no'] : 'N/A'),
                                'user_name' => $uName,
                                'created' => $so['created']
                            ];
                        }
                    }

                    if (empty($stItems)) {
                        foreach ($activeItems as $aItem) {
                            if (!empty($aItem['split_reference_id']) && $aItem['split_reference_id'] == $stSplitRef) {
                                $stItems[] = $aItem;
                            }
                        }
                    }

                    $splitBillsData[] = [
                        'split_index' => ($idx + 1),
                        'orderno' => $st['orderno'],
                        'split_reference_id' => $stSplitRef,
                        'price' => (float)$st['price'],
                        'total_tax' => (float)$st['total_tax'],
                        'servicecharge' => (float)($st['servicecharge'] ?? 0),
                        'discount' => (float)($st['discount'] ?? 0),
                        'discount_type' => (int)($st['discount_type'] ?? 0),
                        'discount_type_value' => $st['discount_type_value'] ?? '',
                        'amount' => (float)$st['amount'],
                        'status' => (int)$st['status'],
                        'created' => $st['created'],
                        'items' => $stItems
                    ];
                }
            }

            return [
                'summary' => [
                    'id' => $refId,
                    'orderno' => $orderNo,
                    'reference_id' => $refId,
                    'store_id' => $txStoreId,
                    'store_name' => $storeName,
                    'table_number' => $currentTableNum,
                    'cashier_name' => $resolvedCashierName,
                    'waiter_name' => $waiterName,
                    'manager_name' => !empty($managersList) ? implode(', ', $managersList) : 'N/A',
                    'created' => $createdTime,
                    'status' => $txStatus,
                    'status_text' => $statusText,
                    'price' => (float)$txPrice,
                    'total_tax' => (float)$txTax,
                    'servicecharge' => (float)$txSC,
                    'service_charge_removed' => $activeScRemoved,
                    'sc_removed_by' => $activeScStaff,
                    'discount' => (float)$activeDiscount,
                    'discount_type' => $isCurrentlyUnbilledAndOpen ? 0 : $txDiscType,
                    'discount_type_value' => $isCurrentlyUnbilledAndOpen ? '' : $txDiscVal,
                    'discount_type_label' => $isCurrentlyUnbilledAndOpen ? '' : $discTypeLabel,
                    'discount_category' => $isCurrentlyUnbilledAndOpen ? '' : $targetCategory,
                    'discount_summary_text' => $activeDiscountSummaryText,
                    'amount' => (float)$txAmt,
                    'customer_name' => $resolvedCustName,
                    'customer_phone' => $resolvedCustPhone,
                    'customer_address' => $resolvedCustAddress,
                    'customer_pax' => $resolvedCustPax,
                    'payment_modes' => !empty($paymentModes) ? implode(', ', $paymentModes) : 'Pending Settlement',
                    'active_item_count' => count($activeItems),
                    'deleted_item_count' => count($deletedItems),
                    'is_split' => !empty($splitTxns) ? 1 : 0,
                    'split_count' => !empty($splitTxns) ? count($splitTxns) : 0,
                    'reelo_discounts' => $reeloDiscountsData,
                    'reelo_discount_total' => $reeloDiscountTotal,
                    'has_reelo_discount' => !empty($reeloDiscountsData) ? 1 : 0
                ],
                'active_items' => $activeItems,
                'deleted_items' => $deletedItems,
                'split_bills' => $splitBillsData,
                'reelo_discounts' => $reeloDiscountsData,
                'timeline' => $timeline
            ];
        };

        foreach ($transactions as $tr) {
            $tx = $tr['t'];
            $refId = $tx['reference_id'];
            if (empty($refId) || in_array($refId, $seenRefIds)) {
                continue;
            }
            $seenRefIds[] = $refId;
            $storeName = $tr['l']['store_name'] ?? 'N/A';
            $cashierName = !empty($tr['u']['cashier_fullname']) ? $tr['u']['cashier_fullname'] : ($tr['u']['cashier_username'] ?? 'N/A');

            $custNameInput = $tx['name'] ?? ($tx['customer_name'] ?? ($tr[0]['customer_name'] ?? ($tr[0]['name'] ?? '')));
            $custPhoneInput = $tx['phone'] ?? ($tx['customer_phone'] ?? ($tr[0]['customer_phone'] ?? ($tr[0]['phone'] ?? '')));
            $custDiscItems = $tx['discount_items'] ?? ($tr[0]['discount_items'] ?? 'all');
            $txWaiterInput = $tx['waiter_name'] ?? ($tr[0]['waiter_name'] ?? '');

            $bills[] = $assembleBill(
                $refId,
                $tx['orderno'],
                $tx['store_id'],
                $storeName,
                $tx['table_number'],
                $cashierName,
                $txWaiterInput,
                $tx['created'],
                $tx['status'],
                $tx['price'],
                $tx['total_tax'],
                $tx['servicecharge'],
                $tx['discount'],
                $tx['discount_type'] ?? 0,
                $tx['discount_type_value'] ?? '',
                $custDiscItems,
                $tx['amount'],
                $custNameInput,
                $custPhoneInput,
                false
            );
        }

        foreach ($activeOrders as $ao) {
            $refId = $ao['reference_id'];
            if (empty($refId) || in_array($refId, $seenRefIds)) {
                continue;
            }

            // Skip only if this table is currently actively merged into an active main table
            if (isset($activeMergedChildRefs[$refId])) {
                continue;
            }

            $itemsRawCheck = $itemsMap[$refId] ?? [];
            if (empty($itemsRawCheck)) {
                continue;
            }

            $seenRefIds[] = $refId;
            $storeName = $ao['store_name'] ?? 'N/A';
            $tableNum = $ao['table_number'];
            $orderNo = "Active (Table {$tableNum})";
            $createdTime = !empty($itemsRawCheck[0]['oh']['created']) ? $itemsRawCheck[0]['oh']['created'] : ($ao['modified'] ?? ($ao['created'] ?? date('Y-m-d H:i:s')));

            $activeCashier = '';
            foreach ($itemsRawCheck as $itc) {
                if (!empty($itc['u']['staff_fullname'])) {
                    $activeCashier = $itc['u']['staff_fullname'];
                    break;
                } elseif (!empty($itc['u']['staff_username'])) {
                    $activeCashier = $itc['u']['staff_username'];
                    break;
                }
            }
            if (empty($activeCashier)) {
                $activeCashier = 'POS Staff';
            }

            $bills[] = $assembleBill(
                $refId,
                $orderNo,
                $ao['store_id'],
                $storeName,
                $tableNum,
                $activeCashier,
                $ao['waiter_name'] ?? '',
                $createdTime,
                1,
                0,
                0,
                0,
                0,
                0,
                '',
                'all',
                0,
                '',
                '',
                true
            );
        }

        // Sort all bills (completed + live active tables) with newest created first
        usort($bills, function($a, $b) {
            return strtotime($b['summary']['created']) - strtotime($a['summary']['created']);
        });

        return $bills;
    }

    public function item_wise_purchase_vendors()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->response->body(json_encode(['error' => 'Unauthorized']));
        }
        $this->autoRender = false;
        $this->response->type('application/json');

        $sku_id   = (int)($this->request->query('sku_id')   ?? 0);
        $startDT  = trim($this->request->query('start')     ?? '');
        $endDT    = trim($this->request->query('end')       ?? '');
        $store_id = (int)($this->request->query('store_id') ?? 0);

        if (!$sku_id || !$startDT || !$endDT) {
            return $this->response->body(json_encode(['error' => 'Invalid parameters']));
        }
        
        
        $startdate = date('Y-m-d', strtotime($startDT));
        $enddate   = date('Y-m-d', strtotime($endDT));
        // po_date is a DATE column — compare with date-only $startdate/$enddate (no shift range needed)

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipoi.sku_id = $sku_id
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin') $where .= " AND ipo.location_id IN ($userLocationId)";

        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipo.vendor_id,
                ipo.vendor_name AS vendor,
                COUNT(DISTINCT ipo.p_o_id) AS po_count,
                SUM(ipoi.sku_quantity) AS qty,
                ROUND(SUM(ipoi.sku_price * ipoi.sku_quantity) / NULLIF(SUM(ipoi.sku_quantity),0), 4) AS weighted_avg_rate,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            $where
            GROUP BY ipo.vendor_id, ipo.vendor_name
            ORDER BY total DESC
        ");

        $result = [];
        foreach ($rows as $row) {
            $vendor = $row['ipo']['vendor'] ?? $row[0]['vendor'] ?? '';
            if ($vendor === '') {
                $vendor = $row['Inventory_purchase_order']['vendor'] ?? '';
            }
            $result[] = [
                'vendor'       => $vendor,
                'po_count'     => $row[0]['po_count']       ?? 0,
                'qty'          => $row[0]['qty']             ?? 0,
                'weighted_avg' => $row[0]['weighted_avg_rate'] ?? 0,
                'base'         => $row[0]['base']            ?? 0,
                'tax'          => $row[0]['tax']             ?? 0,
                'excise_total' => $row[0]['excise_total']      ?? 0,
                'addl_excise_total' => $row[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $row[0]['vat_total']         ?? 0,
                'tcs_total'    => $row[0]['tcs_total']         ?? 0,
                'total'        => $row[0]['total']           ?? 0,
            ];
        }

        if (empty($result)) {
            $raw = !empty($rows) ? $rows[0] : [];
            return $this->response->body(json_encode([
                'items' => [],
                'debug_raw_first_row' => $raw,
                'debug_row_count'     => count($rows)
            ]));
        }

        return $this->response->body(json_encode(['items' => $result]));
    }
    
    public function employeewise_customer_info() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->layout = "users";
        $data = $this->_getEmployeewiseCustomerInfoData();
        $this->set($data);
    }

    public function employeewise_customer_info_export() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->autoRender = false;
        $data = $this->_getEmployeewiseCustomerInfoData();

        $filename = "EmployeewiseCustomerInfo_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');

        // UTF-8 BOM for Microsoft Excel compatibility
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Exact requested fields as header
        fputcsv($out, ['Store Name','Ordernumber', 'Orderstartby', 'name', 'phone', 'address', 'Bill Time']);

        foreach ($data['records'] as $r) {
            fputcsv($out, [
                $r['store_name'],
                $r['Ordernumber'],
                $r['Orderstartby'],
                $r['name'],
                $r['phone'],
                $r['address'],
                $r['Order date']
            ]);
        }

        fclose($out);
        exit;
    }

    public function employeewise_customer_info_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->layout = false;
        $data = $this->_getEmployeewiseCustomerInfoData();
        $this->set($data);
    }

    private function _getEmployeewiseCustomerInfoData() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $startDate = date('m/d/Y');
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {
            $daterange = $this->request->data['daterange'] ?? ($this->request->data['Search']['startdate'] ?? '');
        } else {
            $daterange = $this->request->query['daterange'] ?? ($this->request->query['sdate'] ?? '');
        }

        if (!empty($daterange) && strpos($daterange, ' - ') !== false) {
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $end_date   = trim($dates[1]);
            $endDate    = date('m/d/Y', strtotime($end_date));
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userModules    = $authUser['modules'] ?? '';

        // Subadmin permissions check (like other reports)
        if ($userRole !== 'admin') {
            $allowedModules = array_map('trim', explode(',', $userModules));
            $hasReportAccess = in_array('report_management', $allowedModules)
                || in_array('report_management:employeewise_customer_info', $allowedModules)
                || in_array('report_management:all', $allowedModules);
            if (!$hasReportAccess) {
                $this->Flash->error(__('You do not have permission to access this report.'));
                return $this->redirect(['controller' => 'dashboard', 'action' => 'home']);
            }
        }

        // Location IDs normalization for subadmin / outlet
        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';
        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];
        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }
        $userLocationIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $locationIdsArray)
                )
            )
        );

        $selectedStoreId = 0;
        if (!empty($this->request->data['Search']['cashier'])) {
            $selectedStoreId = intval($this->request->data['Search']['cashier']);
        } elseif (!empty($this->request->data['cashier'])) {
            $selectedStoreId = intval($this->request->data['cashier']);
        } elseif (!empty($this->request->query['cashier'])) {
            $selectedStoreId = intval($this->request->query['cashier']);
        }

        $selectedCashierId = 0;
        if (!empty($this->request->data['Search']['cashier_id'])) {
            $selectedCashierId = intval($this->request->data['Search']['cashier_id']);
        } elseif (!empty($this->request->data['cashier_id'])) {
            $selectedCashierId = intval($this->request->data['cashier_id']);
        } elseif (!empty($this->request->query['cashier_id'])) {
            $selectedCashierId = intval($this->request->query['cashier_id']);
        }

        $customerFilter = 'all';
        if (!empty($this->request->data['Search']['customer_filter'])) {
            $customerFilter = $this->request->data['Search']['customer_filter'];
        } elseif (!empty($this->request->data['customer_filter'])) {
            $customerFilter = $this->request->data['customer_filter'];
        } elseif (!empty($this->request->query['customer_filter'])) {
            $customerFilter = $this->request->query['customer_filter'];
        }

        $location_id = $selectedStoreId > 0 ? $selectedStoreId : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];
        $daterangeValue = date('m/d/Y', strtotime($startdate)) . ' - ' . date('m/d/Y', strtotime($enddate));

        $this->loadModel('Location');
        $this->loadModel('User');
        $this->loadModel('Order_transaction');
        $this->loadModel('Order_orderhistory');

        // Stores accessible to current user (subadmin / admin)
        $storeConditions = ['status' => 1, 'type' => 'Outlet'];
        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $storeConditions['id'] = -1;
            } else {
                $storeConditions['id'] = $userLocationIds;
            }
        }
        $stores = $this->Location->find('all', ['conditions' => $storeConditions, 'order' => ['name' => 'ASC']]);

        // Build store SQL filter
        $storeName = "All Stores";
        if ($selectedStoreId > 0) {
            $storeCondition = " AND ot.store_id = " . intval($selectedStoreId);
            $storeLoc = $this->Location->find('first', ['conditions' => ['id' => $selectedStoreId]]);
            $storeName = $storeLoc['Location']['name'] ?? ('Store #' . $selectedStoreId);
        } elseif ($userRole !== 'admin') {
            if (!empty($userLocationIds)) {
                $storeCondition = " AND ot.store_id IN (" . implode(',', $userLocationIds) . ")";
            } else {
                $storeCondition = " AND ot.store_id = -1";
            }
        } else {
            $storeCondition = "";
        }

        // Role-wise cashier filtering:
        // Only active users with role = 'cashier'
        // If subadmin (role != 'admin'): restrict to cashiers belonging to subadmin's assigned stores ($userLocationIds)
        $roleLocationFilter = "";
        if ($userRole !== 'admin') {
            if (!empty($userLocationIds)) {
                $locListStr = implode(',', $userLocationIds);
                $roleLocationFilter = " AND (u.location_id IN ($locListStr) OR (ul.location_id IN ($locListStr) AND ul.status = 1))";
            } else {
                $roleLocationFilter = " AND u.location_id = -1";
            }
        }

        // Query all cashiers permitted for this role, along with all their assigned active locations
        $allRoleCashiers = $this->User->query("
            SELECT 
                u.id, 
                u.name, 
                u.username, 
                u.location_id,
                GROUP_CONCAT(DISTINCT CASE WHEN ul.status = 1 THEN ul.location_id END) AS extra_locations
            FROM users u
            LEFT JOIN user_locations ul ON ul.user_id = u.id AND ul.status = 1
            WHERE u.status = 1 
              AND u.role = 'cashier'
              $roleLocationFilter
            GROUP BY u.id
            ORDER BY u.name ASC, u.username ASC
        ");

        $cashiersList = [];
        $cashierOptions = ['' => 'All Cashiers'];

        foreach ($allRoleCashiers as $r) {
            $u   = !empty($r['u']) ? $r['u'] : (!empty($r['User']) ? $r['User'] : (!empty($r['users']) ? $r['users'] : ($r[0] ?? [])));
            $sub = $r[0] ?? [];
            $uid = intval($u['id'] ?? ($sub['id'] ?? 0));
            if (!$uid) continue;

            $uName = trim($u['name'] ?? ($sub['name'] ?? ''));
            $uUser = trim($u['username'] ?? ($sub['username'] ?? ''));
            $disp  = !empty($uName) ? $uName : $uUser;
            $label = $disp . (!empty($uUser) && $uUser !== $disp ? " ({$uUser})" : "");

            $locs = [];
            $primLoc = intval($u['location_id'] ?? ($sub['location_id'] ?? 0));
            if ($primLoc > 0) {
                $locs[] = $primLoc;
            }
            $extraLocs = $sub['extra_locations'] ?? ($u['extra_locations'] ?? '');
            if (!empty($extraLocs)) {
                foreach (explode(',', $extraLocs) as $el) {
                    $el = trim($el);
                    if ($el !== '') $locs[] = intval($el);
                }
            }
            $locs = array_values(array_unique(array_filter($locs)));

            $cashiersList[] = [
                'id' => $uid,
                'name' => $label,
                'locations' => $locs
            ];

            // If a specific store is selected, only add to PHP options if this cashier belongs to it
            if ($selectedStoreId > 0) {
                if (in_array($selectedStoreId, $locs)) {
                    $cashierOptions[$uid] = $label;
                }
            } else {
                $cashierOptions[$uid] = $label;
            }
        }

        // If a store is selected and the selected cashier doesn't belong to it, reset cashier selection
        if ($selectedStoreId > 0 && $selectedCashierId > 0 && !isset($cashierOptions[$selectedCashierId])) {
            $selectedCashierId = 0;
        }

        $selectedCashierName = 'All Cashiers';
        if ($selectedCashierId > 0 && isset($cashierOptions[$selectedCashierId])) {
            $selectedCashierName = $cashierOptions[$selectedCashierId];
        }

        // Global users map to resolve cashier display names
        $allUsers = $this->User->find('all', [
            'fields' => ['id', 'name', 'username'],
            'order' => ['name' => 'ASC']
        ]);
        $allUsersMap = [];
        foreach ($allUsers as $u) {
            $uid   = intval($u['User']['id']);
            $uName = trim($u['User']['name'] ?? '');
            $uUser = trim($u['User']['username'] ?? '');
            $allUsersMap[$uid] = !empty($uName) ? $uName : $uUser;
        }

        // Query only success bills (status = 2) directly from order_transactions
        // Orderstartby: first cashier id from orderhistory without check status, fallback to ot.cashier_id
        $orders = $this->Order_transaction->query("
            SELECT 
                ot.id,
                ot.orderno,
                ot.reference_id,
                ot.store_id,
                ot.name,
                ot.phone,
                ot.address,
                ot.created,
                ot.cashier_id AS tx_cashier_id,
                (
                    SELECT ooh.cashier_id 
                    FROM order_orderhistories ooh 
                    WHERE ooh.reference_id = ot.reference_id 
                      AND ooh.cashier_id IS NOT NULL 
                      AND ooh.cashier_id > 0
                    ORDER BY ooh.id ASC 
                    LIMIT 1
                ) AS first_cashier_id,
                (
                    SELECT name
                    FROM locations  
                    WHERE locations.id = ot.store_id
                ) AS store_name
            FROM order_transactions ot
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeCondition
            ORDER BY ot.created DESC, ot.id DESC
        ");

        $records = [];
        $totalOrdersCount = 0;
        $withCustomerInfoCount = 0;
        $uniqueCustomersMap = [];
        $uniqueCashiersMap = [];

        foreach ($orders as $r) {
            $d    = !empty($r['ot']) ? $r['ot'] : (!empty($r['Order_transaction']) ? $r['Order_transaction'] : ($r[0] ?? []));
            $subD = $r[0] ?? [];

            $orderNo   = trim($d['orderno'] ?? ($d['order_number'] ?? ($subD['orderno'] ?? ($subD['order_number'] ?? ''))));
            $refId     = trim($d['reference_id'] ?? ($subD['reference_id'] ?? ''));
            $name      = trim($d['name'] ?? ($subD['name'] ?? ''));
            $phone     = trim($d['phone'] ?? ($subD['phone'] ?? ''));
            $address   = trim($d['address'] ?? ($subD['address'] ?? ''));
            $orderDate = $d['created'] ?? ($d['order_date'] ?? ($subD['created'] ?? ($subD['order_date'] ?? '')));

            // Orderstartby: first cashier id from orderhistory without check status, fallback to transaction cashier_id
            $store_name = $subD['store_name'];
            
            $firstCashierId = !empty($subD['first_cashier_id']) ? intval($subD['first_cashier_id']) : (!empty($d['first_cashier_id']) ? intval($d['first_cashier_id']) : 0);
            $txCashierId    = intval($d['tx_cashier_id'] ?? ($subD['tx_cashier_id'] ?? 0));
            $effectiveCashierId = $firstCashierId > 0 ? $firstCashierId : $txCashierId;

            $orderStartBy = 'N/A';
            if ($effectiveCashierId > 0 && isset($allUsersMap[$effectiveCashierId])) {
                $orderStartBy = $allUsersMap[$effectiveCashierId];
            } elseif ($effectiveCashierId > 0) {
                $orderStartBy = 'Cashier #' . $effectiveCashierId;
            }

            // Cashier filter
            if ($selectedCashierId > 0 && $effectiveCashierId !== $selectedCashierId) {
                continue;
            }

            // Customer info presence check
            $hasCust = (!empty($name) && $name !== '0' && strtolower($name) !== 'na' && strtolower($name) !== 'n/a')
                    || (!empty($phone) && $phone !== '0' && strtolower($phone) !== 'na' && strtolower($phone) !== 'n/a')
                    || (!empty($address) && $address !== '0' && strtolower($address) !== 'na' && strtolower($address) !== 'n/a');

            if ($customerFilter === 'with_info' && !$hasCust) {
                continue;
            } elseif ($customerFilter === 'without_info' && $hasCust) {
                continue;
            }

            $totalOrdersCount++;
            if ($hasCust) {
                $withCustomerInfoCount++;
                $custKey = !empty($phone) ? $phone : $name;
                if (!empty($custKey)) {
                    $uniqueCustomersMap[$custKey] = true;
                }
            }
            if ($effectiveCashierId > 0) {
                $uniqueCashiersMap[$effectiveCashierId] = true;
            }

            $records[] = [
                'Ordernumber'  => $orderNo,
                'referernceID' => $refId,
                'Orderstartby' => $orderStartBy,
                'name'         => $name,
                'phone'        => $phone,
                'address'      => $address,
                'Order date'   => $orderDate,
                'cashier_id'   => $effectiveCashierId,
                'store_name'   => $store_name
            ];
        }
        $summary = [
            'total_orders'          => $totalOrdersCount,
            'with_customer_info'    => $withCustomerInfoCount,
            'without_customer_info' => ($totalOrdersCount - $withCustomerInfoCount),
            'unique_customers'      => count($uniqueCustomersMap),
            'total_cashiers'        => count($uniqueCashiersMap)
        ];

        return compact(
            'records',
            'summary',
            'stores',
            'cashierOptions',
            'cashiersList',
            'selectedStoreId',
            'selectedCashierId',
            'selectedCashierName',
            'customerFilter',
            'storeName',
            'daterangeValue',
            'startdate',
            'enddate'
        );
    }
}