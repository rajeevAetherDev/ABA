<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>Bill Wise Sales Report</title>

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
				Bill Wise Sales Report From <?php echo $startdate; ?> To <?php echo $enddate; ?>  &nbsp;&nbsp; | &nbsp;&nbsp;
			</h3>
			<h5 class="text-center">
				Store: <?php echo !empty($store_name) ? h($store_name) : 'All Stores'; ?>  &nbsp;&nbsp; | &nbsp;&nbsp;
			</h5>
			<h5 class="text-center">
				Generated On: <?php echo date('Y-m-d H:i:s A'); ?>
			</h5>

			<?php if (!empty($datas)) : ?>
				<table class="display table table-hover table-condensed table-bordered" cellspacing="0" width="100%">
					<thead>
						<tr style="background-color:#f5f5f5;">
							<th>Bill Number</th>
							<th>Order ID</th>
							<th>Bill Date</th>
							<th>Bill Time</th>
							<th>Print Time</th>
							<th>Close Time</th>
							<th>Table Number</th>
							<th>Covers</th>
							<th>Total</th>
							<th>Discount</th>
							<th>Net Total</th>
							<th>Gross Bill Amount</th>
							<th>Service Charge</th>
                            <?php if (!empty($taxColumns)) : ?>
                                <?php foreach ($taxColumns as $tc) : ?>
                                    <th><?= h($tc); ?></th>
                                <?php endforeach; ?>
                            <?php endif; ?>
						</tr>
					</thead>
					<tbody>
                        <?php 
                        // Initialize grand totals
                        $grandTotals = [
                            'covers' => 0,
                            'total' => 0,
                            'discount' => 0,
                            'net_total' => 0,
                            'gross_bill_amount' => 0,
                            'total_service_charge' => 0,
                        ];
                        $taxTotals = [];
                        if (!empty($taxColumns)) {
                            foreach ($taxColumns as $tc) {
                                $taxTotals[$tc] = 0.0;
                            }
                        }
                        ?>

                        <?php foreach ($datas as $d): ?>

                            <?php
                            // Add to grand totals
                            $grandTotals['covers'] += (int)$d['covers'];
                            $grandTotals['total'] += (float)$d['total'];
                            $grandTotals['discount'] += (float)$d['discount'];
                            $grandTotals['net_total'] += (float)$d['net_total'];
                            $grandTotals['gross_bill_amount'] += (float)$d['gross_bill_amount'];
                            $grandTotals['total_service_charge'] += (float)$d['total_service_charge'];

                            // Build per-row tax values
                            $rowTaxValues = [];
                            if (!empty($taxColumns)) {
                                foreach ($taxColumns as $tc) {
                                    $rowTaxValues[$tc] = 0.0;
                                }
                                $tax_map = isset($d['tax_map']) ? $d['tax_map'] : '';
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
                                    $taxTotals[$tc] += $rowTaxValues[$tc];
                                }
                            }
                            ?>

                            <tr>
                                <td><?= h($d['bill_number']); ?></td>
                                <td><?= h($d['order_id']); ?></td>
                                <td><?= h($d['bill_date']); ?></td>
                                <td><?= h($d['bill_time']); ?></td>
                                <td><?= date('H:i:s', strtotime($d['print_time'])); ?></td>
                                <td><?= date('H:i:s', strtotime($d['close_time'])); ?></td>
                                <td><?= h($d['table_number']); ?></td>
                                <td><?= h($d['covers']); ?></td>
                                <td><?= number_format($d['total'], 2); ?></td>
                                <td><?= number_format($d['discount'], 2); ?></td>
                                <td><?= number_format($d['net_total'], 2); ?></td>
                                <td><?= number_format($d['gross_bill_amount'], 2); ?></td>
                                <td><?= number_format($d['total_service_charge'], 2); ?></td>
                                <?php if (!empty($taxColumns)) : ?>
                                    <?php foreach ($taxColumns as $tc) : ?>
                                        <td><?= number_format($rowTaxValues[$tc], 2); ?></td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>

                        <?php endforeach; ?>

                        <!-- GRAND TOTAL ONLY (NO DAY TOTALS) -->
                        <tr style="font-weight:bold; background:#e0e0e0;">
                            <td colspan="7">Grand Total</td>
                            <td><?= $grandTotals['covers']; ?></td>
                            <td><?= number_format($grandTotals['total'], 2); ?></td>
                            <td><?= number_format($grandTotals['discount'], 2); ?></td>
                            <td><?= number_format($grandTotals['net_total'], 2); ?></td>
                            <td><?= number_format($grandTotals['gross_bill_amount'], 2); ?></td>
                            <td><?= number_format($grandTotals['total_service_charge'], 2); ?></td>
                            <?php if (!empty($taxColumns)) : ?>
                                <?php foreach ($taxColumns as $tc) : ?>
                                    <td><?= number_format($taxTotals[$tc], 2); ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    </tbody>

				</table>
			<?php else: ?>
				<h4 class="text-center text-danger">No bill data found for the selected filters.</h4>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>