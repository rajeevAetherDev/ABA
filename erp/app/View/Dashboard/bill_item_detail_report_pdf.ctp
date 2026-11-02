<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Bill Item Detail Report</title>

    <?php echo $this->Html->css([
        '../assets/plugins/pace/pace-theme-flash',
        '../assets/plugins/bootstrap/css/bootstrap.min',
        '../assets/plugins/bootstrap/css/bootstrap-theme.min',
        '../assets/fonts/font-awesome/css/font-awesome',
        '../assets/css/animate.min',
        '../assets/plugins/perfect-scrollbar/perfect-scrollbar',
        '../assets/css/style',
        '../assets/css/responsive'
    ]); ?>

    <?php echo $this->Html->script([
        '../assets/js/jquery-1.11.2.min',
        '../assets/plugins/daterangepicker/js/moment',
        '../assets/plugins/daterangepicker/js/daterangepicker'
    ]); ?>

    <script>
        window.print();
    </script>
</head>
<body lang="en-US" dir="ltr">
    <div class="container-fluid">
        <div class="row">
            <h3 class="text-center">
                Bill Item Detail Report From <?php echo $startdate; ?> To <?php echo $enddate; ?> &nbsp;&nbsp; | &nbsp;&nbsp;
            </h3>
            <h5 class="text-center">
                Store: <?php echo !empty($store_name) ? h($store_name) : 'All Stores'; ?>  &nbsp;&nbsp; | &nbsp;&nbsp;
            </h5>
            <h5 class="text-center">
                Generated On: <?php echo date('Y-m-d H:i:s A'); ?>
            </h5>

            <?php if (!empty($rows)) : ?>
                <?php
                    $totalAmount   = 0;
                    $totalDiscount = 0;
                    $totalGst      = 0;
                    $totalNet      = 0;

                    // Initialize dynamic tax totals if available
                    $taxTotals = [];
                    if (!empty($taxColumns)) {
                        foreach ($taxColumns as $tc) {
                            $taxTotals[$tc] = 0.0;
                        }
                    }
                ?>
                <table class="display table table-hover table-condensed table-bordered" cellspacing="0" width="100%">
                    <thead>
                        <tr style="background-color:#f5f5f5;">
                            <th>State</th>
                            <th>Deployment</th>
                            <th>Bill Number</th>
                            <th>Order Id</th>
                            <th>Day Serial</th>
                            <th>Date</th>
                            <th>Tab</th>
                            <th>Table No</th>
                            <th>Open Time</th>
                            <th>Close Time</th>
                            <th>Category</th>
                            <th>Item Name</th>
                            <th>Item Number</th>
                            <th>Item Section</th>
                            <th>Item Classification</th>
                            <th>Rate</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Discount Amount</th>
                            <th>Discount %</th>
                            <th>Taxable Amount</th>
                            <th>Net Amount</th>
                            <th>Source</th>
                            <?php if (!empty($taxColumns)) : ?>
                                <?php foreach ($taxColumns as $tc) : ?>
                                    <th><?php echo h($tc); ?></th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <?php 
                        foreach ($rows as $r):
                            $row = $r[0];
                            $loc = $r['loc'];
                            $t = $r['t'];
                            $h = $r['h'];
                            ?>
                            <?php
                                $openTime = !empty($row['open_time']) ? date('H:i:s A', strtotime($row['open_time'])) : '-';
                                $closeTime = !empty($row['close_time']) ? date('H:i:s A', strtotime($row['close_time'])) : '-';
                                $totalAmount += (float)$row['amount'];
                                $totalDiscount += (float)$row['discount_amount'];
                                $totalGst += (float)$row['gst_total']; // kept for backward compatibility if needed elsewhere
                                $totalNet += (float)$row['net_amount'];

                                // Build per-row dynamic tax values
                                $rowTaxValues = [];
                                if (!empty($taxColumns)) {
                                    foreach ($taxColumns as $tc) {
                                        $rowTaxValues[$tc] = 0.0;
                                    }
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

                                    // Accumulate tax totals
                                    foreach ($taxColumns as $tc) {
                                        $taxTotals[$tc] += $rowTaxValues[$tc];
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo h($row['state']); ?></td>
                                <td><?php echo h($loc['deployment']); ?></td>
                                <td><?php echo h($t['bill_no']); ?></td>
                                <td><?php echo h($t['order_id']); ?></td>
                                <td><?php echo h($t['day_serial']); ?></td>
                                <td><?php echo h($row['bill_date']); ?></td>
                                <td><?php echo h($row['tab_name']); ?></td>
                                <td><?php echo h($h['table_no']); ?></td>
                                <td><?php echo h($openTime); ?></td>
                                <td><?php echo h($closeTime); ?></td>
                                <td><?php echo h($h['category']); ?></td>
                                <td><?php echo h($h['item_name']); ?></td>
                                <td><?php echo h($h['item_number']); ?></td>
                                <td><?php echo h($row['item_section']); ?></td>
                                <td><?php echo h($row['item_classification']); ?></td>
                                <td><?php echo number_format((float)$row['rate'], 2); ?></td>
                                <td><?php echo number_format((float)$h['qty'], 2); ?></td>
                                <td><?php echo number_format((float)$row['amount'], 2); ?></td>
                                <td><?php echo number_format((float)$row['discount_amount'], 2); ?></td>
                                <td><?php echo number_format((float)$row['discount_percent'], 2); ?></td>
                                <td><?php echo number_format((float)$row['taxable_amount'], 2); ?></td>
                                <td><?php echo number_format((float)$row['net_amount'], 2); ?></td>
                                <td><?php echo h($row['source']); ?></td>
                                <?php if (!empty($taxColumns)) : ?>
                                    <?php foreach ($taxColumns as $tc) : ?>
                                        <td><?php echo number_format(isset($rowTaxValues[$tc]) ? (float)$rowTaxValues[$tc] : 0, 2); ?></td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>

                        <tr style="font-weight:bold; background:#e0e0e0;">
                            <td colspan="17">Grand Totals</td>
                            <td><?php echo number_format($totalAmount, 2); ?></td>
                            <td><?php echo number_format($totalDiscount, 2); ?></td>
                            <td><?php echo number_format($totalNet, 2); ?></td>
                            <td></td>
                            <?php if (!empty($taxColumns)) : ?>
                                <?php foreach ($taxColumns as $tc) : ?>
                                    <td><?php echo number_format($taxTotals[$tc], 2); ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <h4 class="text-center text-danger">No bill item detail data found for the selected filters.</h4>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

