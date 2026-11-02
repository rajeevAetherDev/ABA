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
            <h3 class="text-center">Item Sale Report From  <?php echo $startdate; ?> To <?php echo $enddate; ?></h3>
            <?php 
            if(!empty($datas)) :  ?>
                <table class="display table table-hover table-condensed" cellspacing="0" width="100%">
            	    <thead>
            	        <tr>
                        	<th>#</th>
                        	<th>Product Name</th>
                        	<th>Category</th>
                        	<th>Quantity</th>
                        	<th>Price</th>
            	        </tr>
            	    </thead>
            	    <tbody>
                        <?php 
                        $count=1;
                        foreach ($datas as $data): 
                        ?>
                            <tr>
                            	<td> <?php echo $count; ?></td>
                            	<td> <?php echo $data['product_name']; ?></td>
                            	<td> <?php echo $data['category_name']; ?></td>
                            	<td> <?php echo $data['totalquantity']; ?></td>
                            	<td> <?php echo $data['price']; ?></td>
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
