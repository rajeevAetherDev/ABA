<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
                <h2 class="page-title">Item Sale Report</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row" >
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php echo $this->Form->create('Search', array('url'=>'item_sale_report')); 
                            // echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select User','required'=>'required')); 
                            $options = [];
                            
                            if ($this->Session->read('Auth.User.role') == 'admin') {
                                $options[''] = 'All Stores';
                            }
                            foreach($users as $user){
                                $options[$user['Location']['id']] = $user['Location']['name'];
                            }
                            echo $this->Form->input('cashier', array('div'=>array('class'=>'form-group'), 'label'=>'Select Store', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'category','id'=>'cashier'));
                            echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                            echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                            echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                            // echo"&nbsp;". $this->Form->button('Print', array('class'=>'btn btn-info', 'type'=>'button','id'=>'btn-pdf'));
                            echo $this->Form->end(); 
                        ?>
                    </div>
                    <div style="height:30px; width: 100%" class="clearfix"></div>
                    <?php echo $this->Form->create('Report', array('id'=>'pdfform','url'=>'item_sale_reportpdf', 'target'=>'_blank')); ?>
        			<?php echo $this->Form->end(); ?>
        			
                </div>
                <div style="height:30px; width: 100%" class="clearfix"></div>
                <div class="row">
                    <div class="table-responsive">
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                     <th>#</th>
                                    <th>Item ID</th>
                                    <th>Item Name</th>
                                    <th>DishType</th>
                                    <th>Category</th>
                                    <th>Sub Category</th>
                                    <th>Unit</th>
                                    <th>Number Sold</th>
                                    <th>Cost Price (CP)</th>
                                    <th>Selling Price</th>
                                    <th>Sold Price</th>
                                    <th>Non-Comp Qty</th>
                                    <th>Net Sales</th>
                                    <th>Billed Cost</th>
                                    <th>Comp Qty</th>
                                    <th>NC Amount</th>
                                    <th>Amount</th>
                                    <th>Comp Amount</th>
                                    <th>Total Discount</th>
                                    <th>NC Cost</th>
                                    <th>Profit %</th>
									<th>Food Cost</th>
									<th>Margin</th>
									<th>Food Cost %</th>
									<th>Net Food Cost %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $x = 1;
                                $total_cp = 0;
                                $total_sp = 0;
                                $total_contribution = 0;
                                // echo "<pre/>";
                                // print_r($productcosts);exit;
                                foreach ($productcosts as $product):
                                
                                  // Find any one matching order to get SP
                                  $matchingOrder = null;
                                //   echo "<pre/>";
                                //   print_r($orderhistories);exit; 
                                  foreach ($orderhistories as $oh) {
                                      if ($oh['o']['product_id'] == $product['product_id']) {
                                          $matchingOrder = $oh;
                                          break;
                                      }
                                  }
                                  $sp = $product['final_price'] ?? 0;
                                  $ncsp = $product['ncfinal_price'] ?? 0;
                                  $billed_amount = $product['totalquantity'] * $sp;
                                  $billed_cost = $product['total_cost'];
                                  $ncbilled_cost = $product['nctotal_cost'];
                                  $contribution = ($billed_amount > 0) ? round((($billed_amount - $billed_cost) / $billed_amount) * 100, 2) : 0;
                        
                                  $total_cp += $billed_cost;
                                  $total_sp += $billed_amount;

                                  $total_sold = $product['nctotalquantity'] + $product['totalquantity'];
                                //   $total_amount = $total_sold * $sp;
                                  $comp_amount = $ncsp * $product['nctotalquantity'];
                                  $total_amount = $comp_amount + $billed_amount; 
								  
																	  
									$food_cost_total = round((float)($product['food_cost'] ?? 0) * $total_sold, 2);
									$margin          = round(((float)$product['selling_price'] * $total_sold) - $food_cost_total, 2);
									
									$food_cost_pct = ($total_amount > 0) ? round(($food_cost_total / $total_amount) * 100, 2) : 0;
									$net_food_cost_pct = ($billed_amount > 0) ? round(($billed_cost / $billed_amount) * 100, 2) : 0;
                                  
                                ?>
                                <tr>
                                  <td><?= $x++; ?></td>
                                  <td><?= h($product['product_id']) ?></td>
                                  <td><?= h($product['product_name']) ?></td>
                                  <td><?= h($product['dish_type']) ?></td>
                                  <td><?= h($product['category_name']) ?></td>
                                  <td><?= h($product['subcat_name']) ?></td>
                                  <td><?= h($product['unit_name']) ?></td>
                                  <td><?= number_format($total_sold,2) ?></td>
                                  <td><?= number_format($product['unit_cost'], 2) ?></td>								  
                                  <td><?= number_format($product['selling_price'], 2) ?></td>
                                  <td><?= number_format($sp, 2) ?></td>
                                  <td><?= number_format($product['totalquantity'],2) ?></td>
                                  <td><?= number_format($billed_amount, 2) ?></td>
                                  <td><?= number_format($billed_cost, 2) ?></td>
                                  <td><?= number_format($product['nctotalquantity'],2) ?></td>
                                  <td><?= number_format($ncsp,2) ?></td>
                                  <td><?= number_format($total_amount,2) ?></td>
                                  <td><?= number_format($comp_amount,2) ?></td>
                                  <td><?= number_format($product['totaldiscount'],2) ?></td>
                                  <td><?= number_format($ncbilled_cost,2) ?></td>
                                  <td><?= number_format($contribution,2) ?></td>
								  <td><?= number_format($food_cost_total) ?></td>
								  <td><?= number_format($margin) ?></td>		
    							  <td><?= number_format($food_cost_pct, 2) ?></td>
								  <td><?= number_format($net_food_cost_pct, 2) ?></td>								  
                                </tr>
                              <?php endforeach; ?>           
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
        
        window.open('<?php echo $this->base; ?>/report/item_sale_reportexport?sdate='+sdate+'&cashier='+cashier, '_blank');
    
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
	var cashierelement= document.createElement("input"); 
    cashierelement.value=cashier;
	cashierelement.name="cashier";
	pdfform.appendChild(sdateelement); 
	pdfform.appendChild(cashierelement); 
	pdfform.submit(); 

	});


</script>

