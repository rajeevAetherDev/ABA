<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html  moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title></title>
    <!-- CORE CSS FRAMEWORK - START -->
      <?php echo $this->Html->css(array('../assets/plugins/pace/pace-theme-flash', '../assets/plugins/bootstrap/css/bootstrap.min', '../assets/plugins/bootstrap/css/bootstrap-theme.min','../assets/fonts/font-awesome/css/font-awesome', '../assets/css/animate.min', '../assets/plugins/perfect-scrollbar/perfect-scrollbar')); ?>
	<?php echo $this->Html->css(array('../assets/css/style', '../assets/css/responsive')); ?>
        <?php  echo $this->Html->script(array('../assets/js/jquery-1.11.2.min')); ?>
		<?php  echo $this->Html->script(array('../assets/plugins/daterangepicker/js/moment','../assets/plugins/daterangepicker/js/daterangepicker')); ?>
  
<script>
    window.print();
</script>
</head>
<body lang="en-US" dir="ltr">
    <div class="container-fluid">
        <div class="row">
            <h3 class="text-center">Wallet report From  <?php echo $startdate; ?> To <?php echo $enddate; ?></h3><br>
        </div>
        <div class="row">
            <h3><br>Card Data</h3>
            
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
    	        <tr>
                	<th>Active Cards</th>
                	<td><?php print_r(round($activecards,2));?></td>
                </tr>
                <tr>
                	<th>Inactive Cards</th>
    	            <td><?php print_r(round($inactivecards,2));?></td>
                </tr>
            </table>
            
            <h3 class="text-center">Wallet Data</h3>
            
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
    	        <tr>
                	<th>Total Balance Added</th>
                	<td><?php print_r(round($totaladded,2));?></td>
                </tr>
                <tr>
                    <th>Total Used Amount</th>
    	            <td><?php print_r(round($totalused,2));?></td>
                </tr>
                <tr>
                	<th>Refunded Amount</th>
                	<td><?php print_r(round($totalrefund,2));?></td>
                </tr>
                <tr>
                	<th>Security Amount</th>
    	            <td><?php print_r(round($totalsecurity,2));?></td>
                </tr>
                <tr>
                	<th>Net Balance</th>
    	            <td><?php print_r(round(($totaladded-$totalused),2));?></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
