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
            <h3 class="text-center">Wallet report From  <?php echo $startdate; ?> To <?php echo $enddate; ?></h3>
            <?php 
            if(!empty($datas)) :  ?>
                <table class="display table table-hover table-condensed" cellspacing="0" width="100%">
            	    <thead>
            	        <tr>
                        	<th>#</th>
                        	<th>Transaction Id</th>
                        	<th>Cashier Name</th>
                        	<th>Card Number</th>
                        	<th>Phone</th>
                        	<th>Customer</th>
                        	<th>Type</th>
                        	<th>Payment Mode</th>
                        	<th>Amount</th>
                        	<th>Created</th>
            	        </tr>
            	    </thead>
            	    <tbody>
                        <?php 
                        $count=1;
                        foreach ($datas as $data): 
                            if($data['payment_mode']=='card_charge'){
                                $purpose='card charge';
                            }else{
                                $purpose=$data['payment_mode'];
                            }
                            if($data['type']=='1'){
                                $type='Credit';
                            }elseif($data['type']=='2'){
                                $type='Debit';
                            }elseif($data['type']=='3'){
                                $type='Security';
                            }else{
                                $type='Refund';
                            }
                        ?>
                            <tr>
                            	<td> <?php echo $count; ?></td>
                            	<td> <?php echo $data['txnid']; ?></td>
                            	<td> <?php echo $data['cashierName']; ?></td>
                            	<td> <?php echo $data['cardnumber']; ?></td>
                            	<td> <?php echo $data['mobile']; ?></td>
                            	<td> <?php echo $data['name']; ?></td>
                            	<td> <?php echo $type; ?></td>
                            	<td> <?php echo $purpose; ?></td>
                            	<td><?php echo $data['amount']; ?></td>
                            	<td><?php echo $data['created']; ?></td>
                            </tr>
                        <?php 
                        $count=$count+1;
                        endforeach;?>
                    </tbody>
                </table>
            <?php endif; ?>   
        </div>
    </div>
</body>
</html>
