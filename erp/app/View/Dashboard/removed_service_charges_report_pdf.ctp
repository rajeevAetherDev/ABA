<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>Removed Service Charges Report</title>

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
				Removed Service Charges Report From <?php echo $startdate; ?> To <?php echo $enddate; ?>  &nbsp;&nbsp; | &nbsp;&nbsp;
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
							<th>Date</th>
							<th>Bill Number</th>
							<th>Tax Name</th>
							<th>Tax Amount</th>
							<th>Removed At</th>
							<th>Bill Print Time</th>
							<th>Settled Time</th>
							<th>Removed By(User Id)</th>
							<th>Username</th>
							<th>Waiter Username</th>
							<th>Customer Name</th>
							<th>Customer Mobile</th>
							<th>Edit Bill Comment</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$currentDate = '';
						$dayTotalAmount = 0;
						$dayBillCount = 0;
						$grandTotalAmount = 0;
						$grandBillCount = 0;
						$processedBills = [];
						// echo "<pre/>";
                        // print_r($datas);exit;
						foreach ($datas as $index => $d): 
							$reportDate = $d['report_date'];
							$billKey = $reportDate . '_' . $d['bill_no'];
							
							if($currentDate != '' && $currentDate != $reportDate) {
							
								?>
								<tr style="font-weight:bold; background:#fafafa;">
									<td>Day Total</td>
									<td>Bill Count: <?php echo $dayBillCount; ?></td>
									<td></td>
									<td><?php echo number_format($dayTotalAmount, 2); ?></td>
									<td colspan="9"></td>
								</tr>
								<?php
								
								$dayTotalAmount = 0;
								$dayBillCount = 0;
								$processedBills = [];
							}
							
							$currentDate = $reportDate;
							$dayTotalAmount += (float)$d['tax_amount'];
							$grandTotalAmount += (float)$d['tax_amount'];
							
							
							if (!in_array($billKey, $processedBills)) {
								$dayBillCount++;
								$grandBillCount++;
								$processedBills[] = $billKey;
							}
						?>
							<tr>
								<td><?php echo h($d['report_date']); ?></td>
								<td><?php echo h($d['bill_no']); ?></td>
								<td><?php echo h($d['tax_name']); ?></td>
								<td><?php echo number_format((float)$d['tax_amount'], 2); ?></td>
								<td><?php echo date('H:i:s A', strtotime($d['removed_at'])); ?></td>
								<td><?php echo date('H:i:s A', strtotime($d['bill_print_time'])); ?></td>
								<td><?php echo !empty($d['settled_time']) ? date('H:i:s A', strtotime($d['settled_time'])) : '-'; ?></td>
								<td><?php echo h($d['removed_by_user']); ?></td>
								<td><?php echo h($d['username']); ?></td>
								<td><?php echo h($d['waiter_username'] ?: '-'); ?></td>
								<td><?php echo h($d['customer_name'] ?: '-'); ?></td>
								<td><?php echo h($d['customer_mobile'] ?: '-'); ?></td>
								<td><?php echo h($d['edit_bill_comment'] ?: '-'); ?></td>
							</tr>
						<?php endforeach; ?>
						
						<?php if($dayBillCount > 0): ?>
							<!-- Final day total -->
							<tr style="font-weight:bold; background:#fafafa;">
								<td>Day Total</td>
								<td>Bill Count: <?php echo $dayBillCount; ?></td>
								<td></td>
								<td><?php echo number_format($dayTotalAmount, 2); ?></td>
								<td colspan="9"></td>
							</tr>
						<?php endif; ?>
						
						<!-- Grand Total -->
						<?php if($grandBillCount > 0): ?>
							<tr style="font-weight:bold; background:#e0e0e0;">
								<td>Grand Total</td>
								<td>Bill Count: <?php echo $grandBillCount; ?></td>
								<td></td>
								<td><?php echo number_format($grandTotalAmount, 2); ?></td>
								<td colspan="9"></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			<?php else: ?>
				<h4 class="text-center text-danger">No removed service charges data found for the selected filters.</h4>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>

