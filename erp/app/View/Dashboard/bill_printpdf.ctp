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
      <!-- CSS to hide default print headers/footers -->
    <style type="text/css" media="print">
        @page {
            margin: 0; /* Removes default margins */
            size: auto; /* Auto page size */
        }
        body {
            margin: 0; /* Removes default margins */
            -webkit-print-color-adjust: exact; /* Ensures colors print accurately */
        }
        header, footer, title {
            display: none; /* Hides headers, footers, and title */
        }
    </style>
<script>
    window.print();
</script>
</head>
<body lang="en-US" dir="ltr">
    <div class="container-fluid" style="width:450px;float:left;">
        <div class="row">
            <div class="col-md-3">
                <div class="topbar-left" style="padding: 10px;">
                    <a href="" class="logo">
                        <center><span>
                            <?php echo $this->Html->link($this->Html->image('../assets/images/logo.png',array('height' => '70px')), '#', array('escape'=>false, 'class'=>'toggle', 'data-toggle'=>'dropdown')); ?>
                        </span></center>
    
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <center><h5 class="text-center" ><?php echo $name; ?> </h5></center>
            </div>
        </div>
        <?php if($print_line1!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center"  style="font-size:small;"><?php echo $print_line1; ?> </h5></center>
                </div>
            </div>
        <?php } 
        if($print_line2!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line2; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line3!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line3; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line4!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line4; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line5!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line5; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line6!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line6; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line7!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line7; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line8!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line8; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line9!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line9; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        <?php if($print_line10!=''){?>
            <div class="row">
                <div class="col-md-3">
                    <center><h5 class="text-center" style="font-size:small;"><?php echo $print_line10; ?> </h5></center>
                </div>
            </div>
        <?php } ?>
        
        <div class="row">
            <div class="col-md-3">
                <center><h4 class="text-center">[ Duplicate Tax Invoice ]</h4></center>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <table cellspacing="0" width="90%" >
        	        <tr>
                    	<th style="font-weight:400!important;">Printed On </th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $today; ?></th>
                    </tr>
                    <tr>
                    	<th style="font-weight:400!important;">Bill No</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $orderno; ?></th>
                    </tr>
                    <tr>
                    	<th style="font-weight:400!important;">Bill Date</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $created; ?></th>
                    </tr>
                    <tr>
                    	<th style="font-weight:400!important;">User</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $cashiername; ?></th>
                    </tr>
                    <tr>
                    	<th style="font-weight:400!important;">Pay Mode</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $mode; ?></th>
                    </tr>
                    <?php if($table_number>0){ ?>
                        <tr>
                        	<th style="font-weight:400!important;">Table Number</th>
                        	<th style="font-weight:400!important;float:right;"><?php echo $table_number; ?></th>
                        </tr>
                        <tr>
                        	<th style="font-weight:400!important;">PAX</th>
                        	<th style="font-weight:400!important;float:right;"><?php echo $pax; ?></th>
                        </tr>
                    <?php } ?>
            	</table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                ------------------------------------------------------
            </div>
        </div>
            
        <div class="row">
            <div class="col-md-3">
                <center><h4 class="text-center"> Order# 1</h4></center>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                ------------------------------------------------------
            </div>
        </div>
        <?php 
        if(!empty($orderss)) :  ?>
        <div class="row">
            <div class="col-md-3">
                <table cellspacing="0" width="90%">
        	    <thead>
        	        <tr>
                    	<th>Qty</th>
                    	<th>Product</th>
                    	<th style="float:right;">Amount</th>
        	        </tr>
        	    </thead>
        	    <tbody>
                    <?php 
                    foreach ($orderss as $data): 
                        $product_name=$data['order_orderhistories']['product_name'];
                        $product_name = wordwrap($product_name, 30, "\n", true);

                    ?>
                        <tr>
                        	<td> <?php echo $data[0]['quantity']; ?></td>
                        	<td> <?php echo nl2br($product_name); ?></td>
                        	<td style="float:right;"> <?php echo $currency_symbol.' '.$data[0]['total_price']; ?></td>
                        </tr>
                    <?php 
                    endforeach;?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-4">
                ------------------------------------------------------
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <table cellspacing="0" width="90%" >
        	        <tr>
                    	<th style="font-weight:400!important;">Subtotal </th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $currency_symbol.' '.$price; ?></th>
                    </tr>
                    <?php if($discount_percentage>0){?>
                    <tr>
                    	<th style="font-weight:400!important;">Discount (<?php echo $discount_percentage;?>%)</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $currency_symbol.' '.$discount; ?></th>
                    </tr>
                    <?php } ?>
                    <?php if($servicecharge>0){?>
                    <tr>
                    	<th style="font-weight:400!important;">Service Charge</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $currency_symbol.' '.$servicecharge; ?></th>
                    </tr>
                    <?php } ?>
                    <?php if($delivery_charge>0){?>
                    <tr>
                    	<th style="font-weight:400!important;">Delivery Charge</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $currency_symbol.' '.$delivery_charge; ?></th>
                    </tr>
                    <?php } ?>
                    <?php if($packing_charge>0){?>
                    <tr>
                    	<th style="font-weight:400!important;">Packaging Charge</th>
                    	<th style="font-weight:400!important;float:right;"><?php echo $currency_symbol.' '.$packing_charge; ?></th>
                    </tr>
                    <?php } 
                    if($taxes){
                        foreach ($taxes as $tax){
                        ?>
                            <tr>
                            	<th style="font-weight:400!important;"><?php print_r($tax['order_tax']['tax_name'].'('.$tax['order_tax']['tax_percentage'].'%)');?></th>
                            	<th style="font-weight:400!important;float:right;"><?php echo $currency_symbol.' '.number_format($tax[0]['tax_value'],2); ?></th>
                            </tr>
                        <?php } 
                    }?>
                    <tr>
                    	<th style="font-weight:400!important;"><b>Gross Amount</b></th>
                    	<th style="font-weight:400!important;float:right;"><b><?php echo $currency_symbol.' '.number_format($amount,2); ?></b></th>
                    </tr>
            	</table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                ------------------------------------------------------
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <center><h4 class="text-center"><b><?php echo $footer_msg;?></b></h4></center>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                ------------------------------------------------------
            </div>
        </div>
    </div>
</body>
</html>
