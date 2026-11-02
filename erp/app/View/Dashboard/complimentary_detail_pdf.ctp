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

			<?php if (!empty($datas)) : ?>
				<table class="display table table-hover table-condensed table-bordered" cellspacing="0" width="100%">
					<thead>
						<tr style="background-color:#f5f5f5;">
							<th>Date</th>
							<th>Deployment Name</th>
							<th>Bill No</th>
							<th>Bill Open Time</th>
							<th>Bill Print Time</th>
							<th>Bill Settle Time</th>
							<th>KOT No</th>
							<th>KOT Print Time</th>
							<th>Section</th>
							<th>Category</th>
							<th>Item Name</th>
							<th>Rate</th>
							<th>Qty</th>
							<th>Amount</th>
							<th>Segment</th>
							<th>Table No</th>
							<th>No of PAX</th>
							<th>Consumer</th>
							<th>ConsumerPhone</th>
							<th>User</th>
							<th>Discount User</th>
							<th>Compliment Offer Name</th>
							<th>Compliment Comment</th>
							<th>Complimentary Head</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$total_amount = 0;
						foreach ($datas as $d): 
							$total_amount += (float)$d['amount'];
						?>
							<tr>
								<td><?php echo h($d['report_date']); ?></td>
								<td><?php echo h($d['deployment_name']); ?></td>
								<td><?php echo h($d['bill_no']); ?></td>
								<td><?php echo h($d['bill_open_time']); ?></td>
								<td><?php echo h($d['bill_print_time']); ?></td>
								<td><?php echo h($d['bill_settle_time']); ?></td>
								<td><?php echo h($d['kot_numbers']); ?></td>
								<td><?php echo h($d['kot_print_time']); ?></td>
								<td><?php echo h($d['section']); ?></td>
								<td><?php echo h($d['category']); ?></td>
								<td><?php echo h($d['item_name']); ?></td>
								<td><?php echo number_format((float)$d['rate'], 2); ?></td>
								<td><?php echo h($d['qty']); ?></td>
								<td><?php echo number_format((float)$d['amount'], 2); ?></td>
								<td><?php echo h($d['table_segment']); ?></td>
								<td><?php echo h($d['tableno']); ?></td>
								<td><?php echo h($d['total_pax']); ?></td>
								<td><?php echo h($d['name']); ?></td>
								<td><?php echo h($d['phone']); ?></td>
								<td><?php echo h($d['user_name']); ?></td>
								<td></td>
								<td><?php echo h($d['offer_name']); ?></td>
								<td><?php echo h($d['complimentary_comment']); ?></td>
								<td><?php echo h($d['complimentary_head']); ?></td>
							</tr>
						<?php endforeach; ?>
						<tr style="font-weight:bold; background:#fafafa;">
							<td colspan="13" class="text-right">Total Amount</td>
							<td colspan="5"><?php echo number_format($total_amount, 2); ?></td>
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
