<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>Purchase Order Report</title>

	<!-- CORE CSS FRAMEWORK - START -->
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
                Purchase Order Report From <?php echo $startdate; ?> To <?php echo $enddate; ?> &nbsp;&nbsp; | &nbsp;&nbsp;
            </h3>
            <h5 class="text-center">
                Vendor: <?php echo !empty($vendor_name) ? $vendor_name : 'All Vendors'; ?>
            </h5>

            <?php if (!empty($datas)) : ?>
                <table class="display table table-hover table-condensed table-bordered" cellspacing="0" width="100%">
                    <thead>
                        <tr style="background-color:#f5f5f5;">
                            <th>#</th>
                            <th>Store</th>
                            <th>Name</th>
                            <th>Vendor</th>
                            <th>Unit</th>
                            <th>Initial Quantity</th>
                            <th>Initial Total Amount</th>
                            <th>Approved Quantity</th>
                            <th>Approved Total Amount</th>
                            <th>Processed Quantity</th>
                            <th>Processed Total Amount</th>
                            <th>Stockin Quantity</th>
                            <th>Stockin Total Amount</th>
                            <th>Excise</th>
                            <th>Addl. Excise</th>
                            <th>VAT</th>
                            <th>TCS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        foreach ($datas as $data): ?>
                            <tr>
                                <td><?php echo $count; ?></td>
                                <td><?php echo h($data['store_name']); ?></td>
                                <td><?php echo h($data['name']); ?></td>
                                <td><?php echo h($data['vendor']); ?></td>
                                <td><?php echo h($data['unit']); ?></td>
                                <td><?php echo h($data['sku_total_qty']); ?></td>
                                <td><?php echo number_format($data['sku_total_amnt'], 2); ?></td>
                                <td><?php echo h($data['approved_total_qty']); ?></td>
                                <td><?php echo number_format($data['approved_total_amnt'], 2); ?></td>
                                <td><?php echo h($data['processed_total_qty']); ?></td>
                                <td><?php echo number_format($data['processed_total_amnt'], 2); ?></td>
                                <td><?php echo h($data['stockin_total_qty']); ?></td>
                                <td><?php echo number_format($data['stockin_total_amnt'], 2); ?></td>
                                <td><?php echo number_format($data['excise_total'], 2); ?></td>
                                <td><?php echo number_format($data['addl_excise_total'], 2); ?></td>
                                <td><?php echo number_format($data['vat_total'], 2); ?></td>
                                <td><?php echo number_format($data['tcs_total'], 2); ?></td>
                            </tr>
                        <?php 
                        $count++;
                        endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <h4 class="text-center text-danger">No purchase order data found for the selected filters.</h4>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
