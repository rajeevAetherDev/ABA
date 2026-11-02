<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>KOT Detail Report</title>

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
				KOT Detail Report From <?php echo $startdate; ?> To <?php echo $enddate; ?>  &nbsp;&nbsp; | &nbsp;&nbsp;
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
							<th>Bill Serial Number</th>
							<th>Edit Bill Comment</th>
							<th>KOT Number</th>
							<th>KOT Date</th>
							<th>KOT Time</th>
							<th>KOT Comment</th>
							<th>Item Name</th>
							<th>Rate</th>
							<th>Quantity</th>
							<th>Amount</th>
							<th>Item Comment</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$currentDate = '';
						$dayTotalAmount = 0;
						$grandTotalAmount = 0;
						foreach ($datas as $data): 
							$reportDate = $data['report_date'];
							if($currentDate != '' && $currentDate != $reportDate) {
								?>
								<tr style="font-weight:bold; background:#fafafa;">
									<td>Day Total</td>
									<td colspan="9"></td>
									<td><?php echo number_format($dayTotalAmount, 2); ?></td>
									<td></td>
								</tr>
								<?php
								$dayTotalAmount = 0;
							}
							$currentDate = $reportDate;
							$dayTotalAmount += (float)$data['amount'];
							$grandTotalAmount += (float)$data['amount'];
						?>
	<tr>
		<td><?= $data['report_date']; ?></td>
		<td><?= $data['bill_serial_number']; ?></td>
		<td><?= $data['edit_bill_comment'] ?: '-'; ?></td>
		<td><?= $data['kot_number']; ?></td>
		<td><?= $data['kot_date']; ?></td>
		<td><?= $data['kot_time']; ?></td>
		<td><?= $data['kot_comment']; ?></td>
		<td><?= $data['item_name']; ?></td>
		<td><?= $data['rate']; ?></td>
		<td><?= $data['qty']; ?></td>
		<td><?= $data['amount']; ?></td>
		<td><?= $data['item_comment']; ?></td>
	</tr>
<?php endforeach; ?>

						<?php if($currentDate !== ''): ?>
							<tr style="font-weight:bold; background:#fafafa;">
								<td>Day Total</td>
								<td colspan="9"></td>
								<td><?php echo number_format($dayTotalAmount, 2); ?></td>
								<td></td>
							</tr>
						<?php endif; ?>

						<tr style="font-weight:bold; background:#e0e0e0;">
							<td>Grand Total</td>
							<td colspan="9"></td>
							<td><?php echo number_format($grandTotalAmount, 2); ?></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			<?php else: ?>
				<h4 class="text-center text-danger">No KOT detail data found for the selected filters.</h4>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>






