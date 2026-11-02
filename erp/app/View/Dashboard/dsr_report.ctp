<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
                <h2 class="page-title">DSR Report</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row" >
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php echo $this->Form->create('Search', array('url'=>'dsr_report')); 
                            // echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select User','required'=>'required')); 
                            // $options = [];
                            
                                $options[''] = 'All Stores';
                            
                            foreach($users as $user){
                                $options[$user['Location']['id']] = $user['Location']['name'];
                            }
                            
                            echo $this->Form->input('cashier', array('div'=>array('class'=>'form-group'), 'label'=>'Select Store', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'cashier'));
                            echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                            echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                            echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                            echo"&nbsp;". $this->Form->button('Print', array('class'=>'btn btn-info', 'type'=>'button','id'=>'btn-pdf'));
                            echo $this->Form->end(); 
                        ?>
                    </div>
                    <div style="height:30px; width: 100%" class="clearfix"></div>
                    <?php echo $this->Form->create('Report', array('id'=>'pdfform','url'=>'dsr_reportpdf', 'target'=>'_blank')); ?>
        			<?php echo $this->Form->end(); ?>
        			
                </div>
                <div style="height:30px; width: 100%" class="clearfix"></div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        
                        <h3>Payment Data</h3>
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <th>Store</th>
                                    <td><?php print_r($cashiername);?></td>
                                </tr>
                                <tr>
                                    <th>Total Collection</th>
                                    <td><?php print_r(round($payments[0][0]['total_amount'] - $payments[0][0]['no_charge_with_tax'] - $platform_gst_total, 2));?></td>
                                </tr>
                                <tr>
                                    <th>Cash</th>
                                    <td><?php print_r(round($payments[0][0]['cash'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Card</th>
                                    <td><?php print_r(round($payments[0][0]['card'],2));?></td>
                                </tr>
                                <tr>
                                    <th>UPI</th>
                                    <td><?php print_r(round($payments[0][0]['upi'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Online</th>
                                    <td><?php print_r(round($payments[0][0]['online'],2));?></td>
                                </tr>
                                <?php
                                    foreach($onlinepayments as $onlinepayment){
                                        
                                        $opVal = round($onlinepayment[0]['total_amount'], 2);
                                        $opKey = $onlinepayment['order_payments']['type_value'];
                                        if (in_array($opKey, $platformGstChannels) && isset($platform_gst_map[$opKey])) {
                                            $opVal = round($opVal - $platform_gst_map[$opKey], 2);
                                        }
                                ?>
                                    <tr>
                                        <th><?php print_r($onlinepayment['order_payments']['type_value']);?></th>
                                        <td><?php print_r($opVal);?></td>
                                    </tr>
                                <?php
                                    }
                                ?>
                                <tr>
                                    <th>Storecard</th>
                                    <td><?php print_r(round($payments[0][0]['storecard'],2));?></td>
                                </tr>
                                <tr>
                                    <th>HoldBill</th>
                                    <td><?php print_r(round($payments[0][0]['hold'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Reelo Wallet</th>
                                    <td><?php print_r(round($payments[0][0]['reelo_wallet'],2));?></td>
                                </tr>
                                <tr>
                                    <th>No Charge</th>
                                    <td><?php print_r(round($payments[0][0]['no_charge_with_tax']-$transactionsnc[0][0]['total_tax'],2));?></td>
                                </tr>
                                <tr>
                                    <th>No Charge With Tax</th>
                                    <td><?php print_r(round($payments[0][0]['no_charge_with_tax'],2));?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3><br>Taxes</h3>
                        <table id="example2" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <?php
                                    foreach($taxes as $tax){
                                ?>
                                    <tr>
                                        <th><?php print_r($tax['order_tax']['tax_name'].'('.$tax['order_tax']['tax_percentage'].'%)');?></th>
                                        <td><?php print_r(round($tax[0]['tax_value'],2));?></td>
                                    </tr>
                                <?php
                                    }
                                ?>
								
								<?php if(!empty($other_taxes_sum) && $other_taxes_sum > 0){ ?>
									<tr>
										<th>Other Charges Tax(Total)</th>
										<td><?php echo $other_taxes_sum; ?></td>
									</tr>
								<?php } ?>
                            </tbody>
                        </table>
						
						<?php if (!empty($charges_taxes)): ?>
							<h3><br>Other Charges Tax Breakup</h3>
							<table class="display table table-hover table-condensed" cellspacing="0" width="100%">
								<tbody>
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
								</tbody>
							</table>
						<?php endif; ?>
                        
                        <h3><br>Sales Aggregation</h3>
                        <table id="example3" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <?php 
                                    foreach ($sales as $sale) { ?>
                                        <tr>
                                            <th>
                                                <?php echo $sale['final']['kds_name'].' ('.$sale['final']['tax_percentage'].'%)'; ?>
                                            </th>
                                            <td>
                                                <?php echo round($sale['final']['value'], 2); ?>
                                            </td>
                                        </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        
                        <h3><br>NC Sales Aggregation</h3>
                        <table class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <?php 
                                    foreach ($ncsales as $ncsale) { ?>
                                        <tr>
                                            <th>
                                                <?php echo $ncsale['final']['kds_name'].' ('.$ncsale['final']['tax_percentage'].'%)'; ?>
                                            </th>
                                            <td>
                                                <?php echo round($ncsale['final']['value'], 2); ?>
                                            </td>
                                        </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        
                        <h3><br>Dish Type Sales With Service Charge</h3>
                        <table class="display table table-hover table-condensed" width="100%">
                            <tbody>
                                <?php foreach ($dish_sales as $row) { 
                                    $dishTypeMap = [
                                        0 => 'Food',
                                        1 => 'Beverage',
                                        2 => 'Liquor',
                                        3 => 'Others'
                                    ];
                                ?>
                                    <tr>
                                        <th><?php 
                                                $type = $row['cp']['dish_type'];
                                                        echo $dishTypeMap[$type] ?? $type;
                                             ?></th>
                                        <td>
                                            <?php echo round($row[0]['total_amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        
                        <h3><br>Dish Type Sales Without Service Charge</h3>
                        <table class="display table table-hover table-condensed" width="100%">
                            <tbody>
                                <?php foreach ($dish_saleswithoutservice as $row) { 
                                    $dishTypeMap = [
                                        0 => 'Food',
                                        1 => 'Beverage',
                                        2 => 'Liquor',
                                        3 => 'Others'
                                    ];
                                ?>
                                    <tr>
                                        <th><?php 
                                                $type = $row['cp']['dish_type'];
                                                        echo $dishTypeMap[$type] ?? $type;
                                             ?></th>
                                        <td>
                                            <?php echo round($row[0]['total_amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <h3><br>Service Charge Breakup</h3>
                         <table class="display table table-hover table-condensed" width="100%">
                            <tbody>
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
                            </tbody>
                        </table>

                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <h3>Total Data</h3>
                        <table id="example4" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <th>Subtotal</th>
                                    <td><?php print_r(round($transactionsubtotal[0][0]['price'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Discount</th>
                                    <td><?php print_r(round($transactionsubtotal[0][0]['discount'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Taxable Amount</th>
                                    <td><?php print_r(round($taxable[0][0]['total_taxable']-$transactionsubtotal[0][0]['discount'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Non Taxable Amount</th>
                                    <td><?php print_r(round($nontaxable[0][0]['total_nontaxable'],2));?></td>
                                </tr>
                                <tr>
                                    <th>NC Taxable Amount</th>
                                    <td><?php print_r(round($nctaxable[0][0]['total_taxable'],2));?></td>
                                </tr>
                                <tr>
                                    <th>NC Non Taxable Amount</th>
                                    <td><?php print_r(round($ncnontaxable[0][0]['total_nontaxable'],2));?></td>
                                </tr>
                                <tr>
                                    <th>NC Net Sale</th>
                                    <td><?php print_r(round($ncnet_amount_query[0][0]['net_amount'],2));?></td>
                                </tr>
                                <tr>
                                    <th>NC Total Tax</th>
                                    <td><?php print_r(round($transactionsnc[0][0]['total_tax'],2));?></td>
                                </tr>
                                <tr>
                                    <th>No Charge</th>
                                    <td><?php print_r(round($payments[0][0]['no_charge_with_tax']-$transactionsnc[0][0]['total_tax'],2));?></td>
                                </tr>
                                <tr>
                                    <th>No Charge With Tax</th>
                                    <td><?php print_r(round($payments[0][0]['no_charge_with_tax'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Service Charge</th>
                                    <td><?php print_r(round($transactions[0][0]['servicecharge'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Delivery Charge</th>
                                    <td><?php print_r(round($transactions[0][0]['delivery_charge'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Packing Charge</th>
                                    <td><?php print_r(round($transactions[0][0]['packing_charge'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Net Sale</th>
                                    <td><?php print_r(round($net_amount_query[0][0]['net_amount'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Total Tax</th>
                                    <td><?php print_r(round($transactions[0][0]['total_tax'],2));?></td>
                                </tr>
                                
                                <?php if (!empty($platform_gst_total) && $platform_gst_total > 0): ?>
                                <tr style="color:#c0392b;">
                                    <th>Tax Deposited by Platform (Zomato/Swiggy)</th>
                                    <td>-<?php echo $platform_gst_total; ?></td>
                                </tr>
                                <tr>
                                    <th>Net Tax Liability</th>
                                    <td><?php echo round($transactions[0][0]['total_tax'] - $platform_gst_total, 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <tr>
                                    <th>Final Amount</th>
                                    <td><?php print_r(round($transactions[0][0]['amount'] - $platform_gst_total, 2));?></td>
                                </tr>
                                <tr>
                                    <th>Round</th>
                                    <td><?php print_r(round($transactions[0][0]['round'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Bill Count	</th>
                                    <td><?php print_r(round($transactions[0][0]['total_count'],2));?></td>
                                </tr>
                                <tr>
                                    <th>Total PAX</th>
                                    <td><?php print_r(round($transactions[0][0]['pax'],2));?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                    </div>
                </div>        
            </div>
        </div>
    </div>
</div>

<script>
    $("#exportdata").click(function(){
        var sdate=$("#startdate").val();
        var cashier=$("#cashier").val();
        
        window.open('<?php echo $this->base; ?>/report/dsr_reportexport?sdate='+sdate+'&cashier='+cashier, '_blank');
    
    });

   
$("#btn-pdf").click(function(){
	var pdfform =document.getElementById('pdfform');
	$('#pdfform').hide();
	var form_data = new FormData(pdfform);
      
	var sdate=$("#startdate").val();
	var cashier=$("#cashier").val();
	
    var sdateelement= document.createElement("input"); 
    sdateelement.value=sdate;
	sdateelement.name="sdate";
	pdfform.appendChild(sdateelement); 
	var cashierelement= document.createElement("input"); 
    cashierelement.value=cashier;
	cashierelement.name="cashier";
	pdfform.appendChild(cashierelement); 
	pdfform.submit(); 

	});


</script>