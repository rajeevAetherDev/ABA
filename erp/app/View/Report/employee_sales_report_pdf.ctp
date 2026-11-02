<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Sales & Dish Type Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 15px; }
        h2 { margin: 0 0 5px 0; color: #2c3e50; }
        p { margin: 0 0 15px 0; color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; font-size: 11px; }
        th { background-color: #2c3e50; color: #ffffff !important; font-weight: bold; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .section-title { font-size: 13px; font-weight: bold; margin-top: 18px; margin-bottom: 8px; color: #1a5276; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:15px;">
        <button onclick="window.print();" style="padding:8px 16px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">
            Print / Save as PDF
        </button>
    </div>

    <h2>Employee / Cashier Sales & Dish Type Report</h2>
    <p>
        <strong>Store:</strong> <?php echo h($storeName); ?> &nbsp;|&nbsp; 
        <strong>Date Range:</strong> <?php echo h($daterangeValue); ?> &nbsp;|&nbsp; 
        <strong>Generated On:</strong> <?php echo date('d-M-Y H:i'); ?>
    </p>

        <!-- 1. DSR Financial Summary -->
    <div class="section-title">1. Cashier Sales & Financial Summary</div>
    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Employee / Cashier</th>
                <th class="text-center">Bills</th>
                <th class="text-center">Qty Sold</th>
                <th class="text-right">Subtotal (₹)</th>
                <th class="text-right">Discount (₹)</th>
                <th class="text-right">Net Sales (₹)</th>
                <th class="text-right">Tax (₹)</th>
                <th class="text-right">Total Collection (₹)</th>
                <th class="text-right">NC Sales (₹)</th>
                <th>Payment Breakdown</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($cashierSalesList)): ?>
                <?php $i = 1; foreach ($cashierSalesList as $cs): ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td class="font-bold"><?php echo h($cs['cashier_name']); ?></td>
                        <td class="text-center"><?php echo number_format($cs['bills_count']); ?></td>
                        <td class="text-center"><?php echo number_format($cs['qty_sold'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($cs['gross_sales'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($cs['discount'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($cs['net_sales'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($cs['tax'], 2); ?></td>
                        <td class="text-right font-bold"><?php echo number_format($cs['net_payable'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($cs['nc_sales'], 2); ?></td>
                        <td style="font-size:10px;">
                            <?php
                            $pmStr = [];
                            if ($cs['payments']['cash'] > 0)      $pmStr[] = '<b>Cash:</b> ₹' . number_format($cs['payments']['cash'], 2);
                            if ($cs['payments']['card'] > 0)      $pmStr[] = '<b>Card:</b> ₹' . number_format($cs['payments']['card'], 2);
                            if ($cs['payments']['upi'] > 0)       $pmStr[] = '<b>UPI:</b> ₹' . number_format($cs['payments']['upi'], 2);
                            if ($cs['payments']['online'] > 0)    $pmStr[] = '<b>Online:</b> ₹' . number_format($cs['payments']['online'], 2);
                            if ($cs['payments']['storecard'] > 0) $pmStr[] = '<b>Storecard:</b> ₹' . number_format($cs['payments']['storecard'], 2);
                            if ($cs['payments']['nc'] > 0)        $pmStr[] = '<b>NC:</b> ₹' . number_format($cs['payments']['nc'], 2);

                            if (!empty($cs['payments']['custom'])) {
                                foreach ($cs['payments']['custom'] as $cName => $cAmt) {
                                    if ($cAmt > 0) {
                                        $pmStr[] = '<b>' . h($cName) . ':</b> ₹' . number_format($cAmt, 2);
                                    }
                                }
                            }
                            echo !empty($pmStr) ? implode(' | ', $pmStr) : '<span style="color:#999;">No payments recorded</span>';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center">No sales data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="font-bold" style="background:#e9ecef;">
                <td colspan="2" class="text-right">TOTAL:</td>
                <td class="text-center"><?php echo number_format($grandTotals['bills_count'] ?? 0); ?></td>
                <td class="text-center"><?php echo number_format($grandTotals['qty_sold'] ?? 0, 2); ?></td>
                <td class="text-right">₹<?php echo number_format($grandTotals['gross_sales'] ?? 0, 2); ?></td>
                <td class="text-right">₹<?php echo number_format($grandTotals['discount'] ?? 0, 2); ?></td>
                <td class="text-right">₹<?php echo number_format($grandTotals['net_sales'] ?? 0, 2); ?></td>
                <td class="text-right">₹<?php echo number_format($grandTotals['tax'] ?? 0, 2); ?></td>
                <td class="text-right">₹<?php echo number_format($grandTotals['net_payable'] ?? 0, 2); ?></td>
                <td class="text-right">₹<?php echo number_format($grandTotals['nc_sales'] ?? 0, 2); ?></td>
                <td>-</td>
            </tr>
        </tfoot>
    </table>


    <!-- 2. Dish Type Sales WITHOUT Service Charge -->
    <div class="section-title">2. Dish Type Sales Without Service Charge</div>
    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Employee / Cashier</th>
                <?php if (!empty($allDishTypesNoSc)): ?>
                    <?php foreach ($allDishTypesNoSc as $dtKey): ?>
                        <th class="text-right"><?php echo h($dtKey); ?> (₹)</th>
                    <?php endforeach; ?>
                <?php else: ?>
                    <th>Dish Type</th>
                <?php endif; ?>
                <th class="text-right">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($cashierSalesList)): ?>
                <?php $n = 1; foreach ($cashierSalesList as $cs): ?>
                    <tr>
                        <td class="text-center"><?php echo $n++; ?></td>
                        <td class="font-bold"><?php echo h($cs['cashier_name']); ?></td>
                        <?php 
                        $nSum = 0;
                        if (!empty($allDishTypesNoSc)):
                            foreach ($allDishTypesNoSc as $dtKey): 
                                $v = floatval($cs['dish_type_no_sc'][$dtKey] ?? 0);
                                $nSum += $v;
                                ?>
                                <td class="text-right"><?php echo number_format($v, 2); ?></td>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <td>-</td>
                        <?php endif; ?>
                        <td class="text-right font-bold"><?php echo number_format($nSum, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo !empty($allDishTypesNoSc) ? (count($allDishTypesNoSc) + 3) : 3; ?>" class="text-center">No data.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="font-bold" style="background:#e9ecef;">
                <td colspan="2" class="text-right">TOTAL WITHOUT SERVICE CHARGE:</td>
                <?php 
                $dtNoScTotSum = 0;
                if (!empty($allDishTypesNoSc)):
                    foreach ($allDishTypesNoSc as $dtKey): 
                        $t = floatval($grandTotalsDtNoSc[$dtKey] ?? 0);
                        $dtNoScTotSum += $t;
                        ?>
                        <td class="text-right">₹<?php echo number_format($t, 2); ?></td>
                    <?php endforeach; ?>
                <?php else: ?>
                    <td>-</td>
                <?php endif; ?>
                <td class="text-right">₹<?php echo number_format($dtNoScTotSum, 2); ?></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
