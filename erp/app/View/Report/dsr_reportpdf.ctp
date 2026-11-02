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
            <h3 class="text-center">DSR report From  <?php echo $startdate; ?> To <?php echo $enddate; ?></h3><br>
        </div>
        <div class="row">
            <h3><br>Payment Data</h3>
            
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
    	        <tr>
                	<th>Store</th>
                	<td><?php print_r($datas['0']['cashier']);?></td>
                </tr>
                <tr>
                	<th>Total Collection</th>
    	            <td><?php print_r(round($datas['0']['total_collection'] - $platform_gst_total,2));?></td>
                </tr>
                <tr>
                	<th>Cash</th>
    	            <td><?php print_r(round($datas['0']['cash'],2));?></td>
                </tr>
                <tr>
                	<th>Card</th>
    	            <td><?php print_r(round($datas['0']['card'],2));?></td>
                </tr>
                <tr>
                	<th>UPI</th>
    	            <td><?php print_r(round($datas['0']['upi'],2));?></td>
                </tr>
                <tr>
                	<th>Online</th>
    	            <td><?php print_r(round($datas['0']['online'],2));?></td>
                </tr>
                 <?php
                      foreach ($onlinepayments as $onlinepayment) {

                        $opVal = round($onlinepayment[0]['total_amount'], 2);
                        $opKey = $onlinepayment['order_payments']['type_value'];
                    
                        if (in_array($opKey, $platformGstChannels) && isset($platform_gst_map[$opKey])) {
                            $opVal = round($opVal - $platform_gst_map[$opKey], 2);
                        }
                    
                        // Display name
                        $displayName = [
                            'swiggy' => 'Swiggy Home Delivery',
                            'zomato' => 'Zomato Home Delivery',
                            'EazyDiner Mislleneous' => 'EazyDiner Miscellaneous'
                        ];
                    
                        $displayType = $displayName[$opKey] ?? $opKey;
                    ?>
                        <tr>
                            <th><?php print_r($displayType); ?></th>
                            <td><?php print_r($opVal); ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                <tr>
                	<th>Storecard</th>
    	            <td><?php print_r(round($datas['0']['storecard'],2));?></td>
                </tr>
                <tr>
                	<th>HoldBill</th>
    	            <td><?php print_r(round($datas['0']['hold'],2));?></td>
                </tr>
                <!--<tr>-->
                <!--	<th>Reelo Wallet</th>-->
    	            <!--<td><?php print_r(round($datas['0']['reelo_wallet'],2));?></td>-->
                <!--</tr>-->
                <tr>
                	<th>No Charge</th>
    	            <td><?php print_r(round($datas['0']['no_charge'],2));?></td>
                </tr>
                <tr>
                	<th>No Charge With Tax</th>
    	            <td><?php print_r(round($datas['0']['no_charge_with_tax'],2));?></td>
                </tr>
            </table>
            
            <h3 class="text-center">Taxes</h3>
            
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
    	        <?php foreach($taxesarr as $tax){ ?>
        	        <tr>
                    	<th><?php echo $tax['tax_name'];?></th>
                    	<td><?php print_r(round($tax['tax_value'],2));?></td>
                    </tr>
                <?php } ?>
            </table>
			
			
			<?php if (!empty($charges_taxes)): ?>
			
			<h3 class="text-center">Other Charges Tax Breakup</h3>
			<table class="display table table-hover table-condensed" cellspacing="0" width="50%">
					<?php foreach ($charges_taxes as $ctax): 
						$ctax_name       = $ctax['tax_charges']['tax_name']       ?? $ctax[0]['tax_name']       ?? '';
						$ctax_percentage = $ctax['tax_charges']['tax_percentage']  ?? $ctax[0]['tax_percentage']  ?? '';
						$ctax_value      = $ctax['tax_charges']['tax_value']       ?? $ctax[0]['tax_value']       ?? 0;
					?>
						<tr>
							<th><?php echo htmlspecialchars($ctax_name) . ' (' . htmlspecialchars($ctax_percentage) . '%)'; ?></th>
							<td><?php echo round($ctax_value, 2); ?></td>
						</tr>
					<?php endforeach; ?>
			</table>
            <?php endif; ?>
			
            <h3 class="text-center">Sales Aggregation</h3>
            
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
    	        <?php foreach($salesarr as $sale){  
    	           // print_r($sale);exit;
    	        ?>
        	        <tr>
                    	<th><?php print_r($sale['kds'].'('.$sale['tax_percentage'].'%)');?></th>
                        <td><?php print_r(round($sale['kds_value'],2));?></td>
                    </tr>
                <?php } ?>
            </table>

            <h3 class="text-center">Online Payment Net Sale Aggregation</h3>

            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
                <?php
                $pdfOnlineDisplayNames = [
                    'swiggy' => 'Swiggy Home Delivery',
                    'zomato' => 'Zomato Home Delivery',
                    'eazydiner mislleneous' => 'EazyDiner Miscellaneous'
                ];
                foreach ($online_net_sales_arr as $ons) {
                    $tv  = $ons['type_value'];
                    $ns  = round($ons['net_sale'], 2);
                    $lbl = $pdfOnlineDisplayNames[strtolower($tv)] ?? $tv;
                ?>
                    <tr>
                        <th><?php echo htmlspecialchars($lbl); ?></th>
                        <td><?php echo $ns; ?></td>
                    </tr>
                <?php } ?>
            </table>
            
            <h3 class="text-center">NC Sales Aggregation</h3>
            
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
    	        <?php foreach($ncsalesarr as $sale){  
    	           // print_r($sale);exit;
    	        ?>
        	        <tr>
                    	<th><?php print_r($sale['kds'].'('.$sale['tax_percentage'].'%)');?></th>
                        <td><?php print_r(round($sale['kds_value'],2));?></td>
                    </tr>
                <?php } ?>
            </table>
            
            <h3><br>Dish Type Sales With Service Charge</h3>
            <table class="display table table-hover table-condensed" width="100%">
                <tbody>
                    <?php foreach ($dishsalesarr as $dish) { 
                        if($dish['dish_type']==0){
                            $dish_type='Food';
                        }elseif($dish['dish_type']==1){
                            $dish_type='Beverage';
                        }elseif($dish['dish_type']==2){
                            $dish_type='Liquor';
                        }else{
                            $dish_type='Others';
                        }
                    ?>
                        <tr>
                        	<th><?php print_r($dish_type);?></th>
                            <td><?php print_r(round($dish['total_amount'],2));?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <h3><br>Dish Type Sales Without Service Charge</h3>
            <table class="display table table-hover table-condensed" width="100%">
                <tbody>
                    <?php 
                    $dishTypeMap = [
                        0 => 'Food',
                        1 => 'Beverage',
                        2 => 'Liquor',
                        3 => 'Others'
                    ];
                    
                    foreach ($dishsaleswithoutservicearr as $row) { ?>
                        <tr>
                            <th>
                                <?php 
                                    $type = $row['dish_type'];
                                    echo $dishTypeMap[$type] ?? $type;
                                ?>
                            </th>
                            <td>
                                <?php echo round($row['total_amount'], 2); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <h3 class="text-center"><br>Service Charge Breakup</h3>
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
                <tr>
                    <th>Total Service Charge</th>
                    <td><?php echo $scbreakuparr['sc_total']; ?></td>
                </tr>
                <tr>
                    <th>SC on GST Items (5%)</th>
                    <td><?php echo $scbreakuparr['sc_gst']; ?></td>
                </tr>
                <tr>
                    <th>SC on VAT Items (<?php echo $vat_rate; ?>%)</th>
                    <td><?php echo $scbreakuparr['sc_vat']; ?></td>
                </tr>
                <tr>
                    <th>SGST on SC (2.5%)</th>
                    <td><?php echo $scbreakuparr['sgst_on_sc']; ?></td>
                </tr>
                <tr>
                    <th>CGST on SC (2.5%)</th>
                    <td><?php echo $scbreakuparr['cgst_on_sc']; ?></td>
                </tr>
                <tr>
                    <th>VAT on SC (<?php echo $vat_rate; ?>%)</th>
                    <td><?php echo $scbreakuparr['vat_on_sc']; ?></td>
                </tr>
            </table>

            <h3><br>Total Data</h3>
            
            <table class="display table table-hover table-condensed" cellspacing="0" width="50%">
    	        <tr>
                	<th>Subtotal</th>
                	<td><?php print_r(round($datas['0']['subtotal'],2));?></td>
                </tr>
                <tr>
                	<th>Discount</th>
    	            <td><?php print_r(round($datas['0']['discount'],2));?></td>
                </tr>
                <tr>
                	<th>Taxable Amount</th>
    	            <td><?php print_r(round($datas['0']['taxable_amount']-$datas['0']['discount'],2));?></td>
                </tr>
                <tr>
                	<th>Non-Taxable Amount</th>
    	            <td><?php print_r(round($datas['0']['nontaxable_amount'],2));?></td>
                </tr>
                <tr>
                	<th>NC Taxable Amount</th>
    	            <td><?php print_r(round($datas['0']['nctaxable_amount'],2));?></td>
                </tr>
                <tr>
                	<th>NC Non-Taxable Amount</th>
    	            <td><?php print_r(round($datas['0']['ncnontaxable_amount'],2));?></td>
                </tr>
                <tr>
                	<th>NC Total Sale</th>
    	            <td><?php print_r(round($datas['0']['ncnet_sale'],2));?></td>
                </tr>
                <tr>
                	<th>NC Total Tax</th>
    	            <td><?php print_r(round($datas['0']['nctotal_tax'],2));?></td>
                </tr>
                <tr>
                	<th>No Charge</th>
    	            <td><?php print_r(round($datas['0']['no_charge'],2));?></td>
                </tr>
                
                <tr>
                	<th>No Charge With Tax</th>
    	            <td><?php print_r(round($datas['0']['no_charge_with_tax'],2));?></td>
                </tr>
                
                <tr>
                	<th>Service Charge</th>
    	            <td><?php print_r(round($datas['0']['service_charge'],2));?></td>
                </tr>
                <tr>
                	<th>Delivery Charge</th>
    	            <td><?php print_r(round($datas['0']['delivery_charge'],2));?></td>
                </tr>
                <tr>
                	<th>Packing Charge</th>
    	            <td><?php print_r(round($datas['0']['packing_charge'],2));?></td>
                </tr>
                <tr>
                	<th>Net Sale</th>
    	            <td><?php print_r(round($datas['0']['net_sale'],2));?></td>
                </tr>
                <tr>
                	<th>Total Tax</th>
    	            <td><?php print_r(round($datas['0']['total_tax'],2));?></td>
                </tr>
                                
                <?php if (!empty($platform_gst_total) && $platform_gst_total > 0): ?>
                <tr style="color:#c0392b;">
                    <th>Tax Deposited by Platform (Zomato/Swiggy)</th>
                    <td>-<?php echo $platform_gst_total; ?></td>
                </tr>
                <tr>
                    <th>Net Tax Liability</th>
                    <td><?php echo round($datas['0']['total_tax'] - $platform_gst_total, 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <tr>
                	<th>Final Amount</th>
    	            <td><?php print_r(round($datas['0']['net_amount'] - $platform_gst_total,2));?></td>
                </tr>
                <tr>
                    <th>Round</th>
                    <td><?php print_r(round($datas[0]['round'],2));?></td>
                </tr>
                <tr>
                	<th>Bill Count</th>
    	            <td><?php print_r(round($datas['0']['bill_count'],2));?></td>
                </tr>
                <tr>
                	<th>Total PAX</th>
    	            <td><?php print_r(round($datas['0']['pax'],2));?></td>
                </tr>
                
            </table>
        </div>
    </div>
</body>
</html>