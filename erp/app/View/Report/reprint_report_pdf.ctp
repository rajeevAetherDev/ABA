<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>Reprint Report</title>

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
				Reprint Report From <?php echo $startdate; ?> To <?php echo $enddate; ?> &nbsp;&nbsp; | &nbsp;&nbsp;
			</h3>
			<h5 class="text-center">
				Store: <?php echo !empty($store_name) ? h($store_name) : 'All Stores'; ?> &nbsp;&nbsp; | &nbsp;&nbsp;
			</h5>
			<h5 class="text-center">
				Generated On: <?php echo date('Y-m-d H:i:s A'); ?>
			</h5>

			<?php if (!empty($datas)) : ?>
				<table class="display table table-hover table-condensed table-bordered" cellspacing="0" width="100%">
					<thead>
						<tr style="background-color:#f5f5f5;">
							<th>Date</th>
							<th>billNo</th>
							<th>Originally Printed By</th>
							<th>Re Print Time</th>
							<th>Re Print Bill Amount</th>
							<th>Actual Print Time</th>
							<th>Original Bill Amount</th>
							<th>User</th>
							<th>Comment</th>
							<th>Customer</th>
							<th>Customer Number</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$total_reprint_amount = 0;
						$total_original_amount = 0;
						foreach ($datas as $d): 
							$total_reprint_amount += (float)$d['reprint_bill_amount'];
							$total_original_amount += (float)$d['original_bill_amount'];
						?>
							<tr>
								<td><?php echo h($d['report_date']); ?></td>
								<td><?php echo h($d['bill_no']); ?></td>
								<td><?php echo h($d['originally_printed_by']); ?></td>
								<td><?php echo date('d-m-Y H:i:s A', strtotime($d['reprint_time'])); ?></td>
								<td><?php echo number_format((float)$d['reprint_bill_amount'], 2); ?></td>
								<td><?php echo date('d-m-Y H:i:s A', strtotime($d['original_print_time'])); ?></td>
								<td><?php echo number_format((float)$d['original_bill_amount'], 2); ?></td>
								<td><?php echo h($d['user']); ?></td>
								<td><?php echo h($d['comment']); ?></td>
								<td><?php echo h($d['customer_name']); ?></td>
								<td><?php echo h($d['customer_number']); ?></td>
							</tr>
						<?php endforeach; ?>
						<tr style="font-weight:bold; background:#fafafa;">
							<td colspan="4" class="text-right">Total Original Amount:</td>
							<td colspan="1"><?php echo number_format($total_original_amount, 2); ?></td>
							<td colspan="1" class="text-right">Total Reprint Amount:</td>
							<td colspan="1"><?php echo number_format($total_reprint_amount, 2); ?></td>
							<td colspan="5"></td>
						</tr>
					</tbody>
				</table>
			<?php else: ?>
				<h4 class="text-center text-danger">No reprint data found for the selected filters.</h4>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>

