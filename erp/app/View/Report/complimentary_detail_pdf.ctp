<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>Complimentary Detail Report</title>

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
				Complimentary Detail Report From <?php echo $startdate; ?> To <?php echo $enddate; ?>
			</h3>
			<h5 class="text-center">
				Store: <?php echo !empty($store_name) ? h($store_name) : 'All Stores'; ?> 
			</h5>

			<?php 
			// Normalize $datas into bills if raw items are passed
			$bills = [];
			if (!empty($datas)) {
				foreach ($datas as $r) {
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

					if (isset($r['items']) && is_array($r['items'])) {
						$ref = $r['reference_id'] ?: ($r['bill_no'] ?: uniqid());
						$bills[$ref] = $r;
						continue;
					}

					$ref = $get(['reference_id']);
					if (empty($ref)) {
						$ref = $get(['bill_no', 'orderno']) ?: uniqid();
					}

					if (!isset($bills[$ref])) {
						$bills[$ref] = [
							'report_date'      => $get(['report_date']),
							'store_name'       => $get(['store_name', 'deployment_name']),
							'bill_no'          => $get(['bill_no', 'orderno']),
							'reference_id'     => $ref,
							'bill_open_time'   => $get(['bill_open_time']),
							'bill_print_time'  => $get(['bill_print_time']),
							'bill_settle_time' => $get(['bill_settle_time']),
							'tableno'          => $get(['tableno', 'table_number']),
							'total_pax'        => $get(['total_pax', 'pax']),
							'customer_name'    => $get(['customer_name', 'name']),
							'customer_phone'   => $get(['customer_phone', 'phone']),
							'remarks'          => $get(['complimentary_head']) ?: ($get(['manager_remarks']) ?: ($get(['remarks']) ?: '-')),
							'items'            => [],
							'item_count'       => 0,
							'total_qty'        => 0,
							'total_amount'     => 0,
						];
					}

					$rateVal = (float)str_replace(',', '', (string)$get(['rate', 'price'], 0));
					$qtyVal  = (float)$get(['qty', 'quantity'], 0);
					$amtRaw  = $get(['amount']);
					$amtVal  = ($amtRaw !== '') ? (float)str_replace(',', '', (string)$amtRaw) : ($rateVal * $qtyVal);
					$itemRem = $get(['complimentary_comment']) ?: ($get(['remarks']) ?: '-');

					$bills[$ref]['items'][] = [
						'item_name' => $get(['item_name', 'product_name']),
						'qty'       => $qtyVal,
						'amount'    => $amtVal,
						'remarks'   => $itemRem
					];
					$bills[$ref]['total_qty'] += $qtyVal;
					$bills[$ref]['total_amount'] += $amtVal;
					$bills[$ref]['item_count']++;
				}
			}
			$bills = array_values($bills);
			?>

			<?php if (!empty($bills)) : ?>
				<table class="display table table-hover table-condensed table-bordered" cellspacing="0" width="100%">
					<thead>
						<tr style="background-color:#f5f5f5;">
							<th style="width:30px; text-align:center;">#</th>
							<th>Bill No</th>
							<th>Date</th>
							<th>Store Name</th>
							<th>Bill Open Time</th>
							<th>Bill Print Time</th>
							<th>Bill Settle Time</th>
							<th>Table</th>
							<th>Remarks</th>
							<th style="text-align:center;">Items</th>
							<th style="text-align:center;">Total Qty</th>
							<th style="text-align:right;">Total Amount</th>
							<th style="text-align:center;">PAX</th>
							<th>Customer Name</th>
							<th>Customer Phone</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$grandTotalQty = 0;
						$grandTotalAmt = 0;
						$i = 1;
						foreach ($bills as $b): 
							$qty = (float)($b['total_qty'] ?? 0);
							$amt = (float)($b['total_amount'] ?? 0);
							$grandTotalQty += $qty;
							$grandTotalAmt += $amt;
							$itemCount = isset($b['items']) && is_array($b['items']) ? count($b['items']) : ($b['item_count'] ?? 0);
						?>
							<tr style="background-color:#f0f4f8; font-weight:bold;">
								<td style="text-align:center;"><?php echo $i++; ?></td>
								<td><strong><?php echo h($b['bill_no']); ?></strong></td>
								<td><?php echo h($b['report_date']); ?></td>
								<td><?php echo h($b['store_name'] ?? ($b['deployment_name'] ?? '')); ?></td>
								<td><?php echo h($b['bill_open_time']); ?></td>
								<td><?php echo h($b['bill_print_time']); ?></td>
								<td><?php echo h($b['bill_settle_time']); ?></td>
								<td><?php echo h($b['tableno']); ?></td>
								<td><?php echo h($b['remarks'] ?? ($b['complimentary_head'] ?? '-')); ?></td>
								<td style="text-align:center;"><?php echo $itemCount; ?></td>
								<td style="text-align:center;"><?php echo $qty; ?></td>
								<td style="text-align:right; font-weight:600;"><?php echo number_format($amt, 2); ?></td>
								<td style="text-align:center;"><?php echo h($b['total_pax']); ?></td>
								<td><?php echo h($b['customer_name'] ?? ($b['name'] ?? '')); ?></td>
								<td><?php echo h($b['customer_phone'] ?? ($b['phone'] ?? '')); ?></td>
							</tr>
							<?php if (!empty($b['items']) && is_array($b['items'])): ?>
								<?php foreach ($b['items'] as $item): ?>
									<tr style="color:#555; background-color:#ffffff; font-size:11px;">
										<td colspan="8" style="border-top:none; border-bottom:none;"></td>
										<td><?php echo h($item['remarks'] ?? '-'); ?></td>
										<td style="padding-left:12px;">↳ <?php echo h($item['item_name'] ?? ''); ?></td>
										<td style="text-align:center;"><?php echo $item['qty'] ?? 0; ?></td>
										<td style="text-align:right;"><?php echo number_format((float)($item['amount'] ?? 0), 2); ?></td>
										<td colspan="3" style="border-top:none; border-bottom:none;"></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						<?php endforeach; ?>
						<tr style="font-weight:bold; background:#e9ecef; font-size:13px;">
							<td colspan="10" style="text-align:right;">Grand Total:</td>
							<td style="text-align:center;"><?php echo $grandTotalQty; ?></td>
							<td style="text-align:right;"><?php echo number_format($grandTotalAmt, 2); ?></td>
							<td colspan="3"></td>
						</tr>
					</tbody>
				</table>
			<?php else: ?>
				<h4 class="text-center text-danger">No complimentary data found for the selected filters.</h4>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
