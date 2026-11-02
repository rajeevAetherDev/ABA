<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DSR Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h3 {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .text-center { text-align: center; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
<div class="container-fluid">

    <h2 class="text-center">
    DSR Report<br>
    <?php echo date('d-m-Y h:i:s', strtotime($startDatetime)) ; ?> 
    &nbsp;To&nbsp; 
    <?php echo date('d-m-Y h:i:s', strtotime($endDatetime)); ?>
</h2>

    <!-- ============================================================
         PAYMENT DATA
    ============================================================ -->
    <h3>Payment Data</h3>
    <table>
        <tbody>
            <tr>
                <th>Store</th>
                <td><?php echo $datas[0]['cashier']; ?></td>
            </tr>
            <tr>
                <th>Total Collection</th>
                <td><?php echo round($payments[0][0]['total_amount'] - $payments[0][0]['no_charge_with_tax'], 2); ?></td>
            </tr>
            <tr>
                <th>Cash</th>
                <td><?php echo round($payments[0][0]['cash'], 2); ?></td>
            </tr>
            <tr>
                <th>Card</th>
                <td><?php echo round($payments[0][0]['card'], 2); ?></td>
            </tr>
            <tr>
                <th>UPI</th>
                <td><?php echo round($payments[0][0]['upi'], 2); ?></td>
            </tr>
            <tr>
                <th>Online</th>
                <td><?php echo round($payments[0][0]['online'], 2); ?></td>
            </tr>

            <?php foreach ($onlinepayments as $onlinepayment): ?>
                <tr>
                    <th><?php echo htmlspecialchars($onlinepayment['order_payments']['type_value']); ?></th>
                    <td><?php echo round($onlinepayment[0]['total_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <th>Storecard</th>
                <td><?php echo round($payments[0][0]['storecard'], 2); ?></td>
            </tr>
            <tr>
                <th>HoldBill</th>
                <td><?php echo round($payments[0][0]['hold'], 2); ?></td>
            </tr>
            <tr>
                <th>No Charge</th>
                <td><?php echo round($payments[0][0]['no_charge_with_tax'] - $transactionsnc[0][0]['total_tax'], 2); ?></td>
            </tr>
            <tr>
                <th>No Charge With Tax</th>
                <td><?php echo round($payments[0][0]['no_charge_with_tax'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ============================================================
         TAXES
    ============================================================ -->
    <h3>Taxes</h3>
    <table>
        <tbody>
            <?php foreach ($taxes as $tax): ?>
                <tr>
                    <th><?php echo htmlspecialchars($tax['order_tax']['tax_name'])
                            . ' (' . $tax['order_tax']['tax_percentage'] . '%)'; ?></th>
                    <td><?php echo round($tax[0]['tax_value'], 2); ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (!empty($other_taxes_sum) && $other_taxes_sum > 0): ?>
                <tr>
                    <th>Other Charges Tax (Total)</th>
                    <td><?php echo $other_taxes_sum; ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ============================================================
         OTHER CHARGES TAX BREAKUP
    ============================================================ -->
    <?php if (!empty($charges_taxes)): ?>
        <h3>Other Charges Tax Breakup</h3>
        <table>
            <tbody>
                <?php foreach ($charges_taxes as $ctax):
                    $ctax_name       = $ctax['tax_charges']['tax_name']      ?? $ctax[0]['tax_name']      ?? '';
                    $ctax_percentage = $ctax['tax_charges']['tax_percentage'] ?? $ctax[0]['tax_percentage'] ?? '';
                    $ctax_value      = $ctax['tax_charges']['tax_value']      ?? $ctax[0]['tax_value']      ?? 0;
                ?>
                    <tr>
                        <th><?php echo htmlspecialchars($ctax_name) . ' (' . htmlspecialchars($ctax_percentage) . '%)'; ?></th>
                        <td><?php echo round($ctax_value, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- ============================================================
         SALES AGGREGATION (KDS)
    ============================================================ -->
    <h3>Sales Aggregation</h3>
    <table>
        <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <th><?php echo htmlspecialchars($sale['final']['kds_name'])
                            . ' (' . $sale['final']['tax_percentage'] . '%)'; ?></th>
                    <td><?php echo round($sale['final']['value'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         NC SALES AGGREGATION (KDS)
    ============================================================ -->
    <h3>NC Sales Aggregation</h3>
    <table>
        <tbody>
            <?php foreach ($ncsales as $ncsale): ?>
                <tr>
                    <th><?php echo htmlspecialchars($ncsale['final']['kds_name'])
                            . ' (' . $ncsale['final']['tax_percentage'] . '%)'; ?></th>
                    <td><?php echo round($ncsale['final']['value'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         DISH TYPE SALES WITH SERVICE CHARGE
    ============================================================ -->
    <?php $dishTypeMap = [0 => 'Food', 1 => 'Beverage', 2 => 'Liquor', 3 => 'Others']; ?>

    <h3>Dish Type Sales With Service Charge</h3>
    <table>
        <tbody>
            <?php foreach ($dish_sales as $row):
                $type = $row['cp']['dish_type'];
            ?>
                <tr>
                    <th><?php echo $dishTypeMap[$type] ?? $type; ?></th>
                    <td><?php echo round($row[0]['total_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         DISH TYPE SALES WITHOUT SERVICE CHARGE
    ============================================================ -->
    <h3>Dish Type Sales Without Service Charge</h3>
    <table>
        <tbody>
            <?php foreach ($dish_saleswithoutservice as $row):
                $type = $row['cp']['dish_type'];
            ?>
                <tr>
                    <th><?php echo $dishTypeMap[$type] ?? $type; ?></th>
                    <td><?php echo round($row[0]['total_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================================
         SERVICE CHARGE BREAKUP
    ============================================================ -->
    <h3>Service Charge Breakup</h3>
    <?php
        $sc_sagg5   = 0;
        $sc_sagg189 = 0;
        foreach ($sales as $s) {
            if ($s['final']['tax_percentage'] == 5)
                $sc_sagg5   += $s['final']['value'];
            elseif ($s['final']['tax_percentage'] == 18.9)
                $sc_sagg189 += $s['final']['value'];
        }

        $sc_bfv = 0;
        $sc_blq = 0;
        foreach ($sc_base_sales as $b) {
            $dt = (int)$b['cp']['dish_type'];
            if ($dt == 0 || $dt == 1) $sc_bfv += $b[0]['base_amount'];
            elseif ($dt == 2)         $sc_blq += $b[0]['base_amount'];
        }

        $sc_gst   = round($sc_sagg5   - $sc_bfv, 2);
        $sc_vat   = round($sc_sagg189 - $sc_blq, 2);
        $sc_total = round($sc_gst + $sc_vat, 2);
        $sgst_sc  = round($sc_gst * 0.025, 2);
        $cgst_sc  = round($sc_gst * 0.025, 2);
        $vat_sc   = round($sc_vat * 0.189, 2);
    ?>
    <table>
        <tbody>
            <tr><th>Total Service Charge</th><td><?php echo $sc_total; ?></td></tr>
            <tr><th>SC on GST Items (5%)</th><td><?php echo $sc_gst; ?></td></tr>
            <tr><th>SC on VAT Items (18.9%)</th><td><?php echo $sc_vat; ?></td></tr>
            <tr><th>SGST on SC (2.5%)</th><td><?php echo $sgst_sc; ?></td></tr>
            <tr><th>CGST on SC (2.5%)</th><td><?php echo $cgst_sc; ?></td></tr>
            <tr><th>VAT on SC (18.9%)</th><td><?php echo $vat_sc; ?></td></tr>
        </tbody>
    </table>

    <!-- ============================================================
         TOTAL DATA
    ============================================================ -->
    <h3>Total Data</h3>
    <table>
        <tbody>
            <tr>
                <th>Subtotal</th>
                <td><?php echo round($transactionsubtotal[0][0]['price'], 2); ?></td>
            </tr>
            <tr>
                <th>Discount</th>
                <td><?php echo round($transactionsubtotal[0][0]['discount'], 2); ?></td>
            </tr>
            <tr>
                <th>Taxable Amount</th>
                <td><?php echo round($taxable[0][0]['total_taxable'] - $transactionsubtotal[0][0]['discount'], 2); ?></td>
            </tr>
            <tr>
                <th>Non Taxable Amount</th>
                <td><?php echo round($nontaxable[0][0]['total_nontaxable'], 2); ?></td>
            </tr>
            <tr>
                <th>NC Taxable Amount</th>
                <td><?php echo round($nctaxable[0][0]['total_taxable'], 2); ?></td>
            </tr>
            <tr>
                <th>NC Non Taxable Amount</th>
                <td><?php echo round($ncnontaxable[0][0]['total_nontaxable'], 2); ?></td>
            </tr>
            <tr>
                <th>NC Net Sale</th>
                <td><?php echo round($ncnet_amount_query[0][0]['net_amount'], 2); ?></td>
            </tr>
            <tr>
                <th>NC Total Tax</th>
                <td><?php echo round($transactionsnc[0][0]['total_tax'], 2); ?></td>
            </tr>
            <tr>
                <th>No Charge</th>
                <td><?php echo round($payments[0][0]['no_charge_with_tax'] - $transactionsnc[0][0]['total_tax'], 2); ?></td>
            </tr>
            <tr>
                <th>No Charge With Tax</th>
                <td><?php echo round($payments[0][0]['no_charge_with_tax'], 2); ?></td>
            </tr>
            <tr>
                <th>Service Charge</th>
                <td><?php echo round($transactions[0][0]['servicecharge'], 2); ?></td>
            </tr>
            <tr>
                <th>Delivery Charge</th>
                <td><?php echo round($transactions[0][0]['delivery_charge'], 2); ?></td>
            </tr>
            <tr>
                <th>Packing Charge</th>
                <td><?php echo round($transactions[0][0]['packing_charge'], 2); ?></td>
            </tr>
            <tr>
                <th>Net Sale</th>
                <td><?php echo round($net_amount_query[0][0]['net_amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Total Tax</th>
                <td><?php echo round($transactions[0][0]['total_tax'], 2); ?></td>
            </tr>
            <tr>
                <th>Final Amount</th>
                <td><?php echo round($transactions[0][0]['amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Round</th>
                <td><?php echo round($transactions[0][0]['round'], 2); ?></td>
            </tr>
            <tr>
                <th>Bill Count</th>
                <td><?php echo $transactions[0][0]['total_count']; ?></td>
            </tr>
            <tr>
                <th>Total PAX</th>
                <td><?php echo round($transactions[0][0]['pax'], 2); ?></td>
            </tr>
        </tbody>
    </table>

</div>
</body>
</html>