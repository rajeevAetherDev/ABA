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
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                     <th>#</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Unit</th>
                                    <th>Cost Price (CP)</th>
                                    <th>Selling Price (SP)</th>
                                    <th>Billed Qty</th>
                                    <th>Billed Amount</th>
                                    <th>Billed Cost</th>
                                    <th>NC Qty</th>
                                    <th>NC Amount</th>
                                    <th>NC Cost</th>
                                    <th>Contribution %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $x = 1;
                                $total_cp = 0;
                                $total_sp = 0;
                                $total_contribution = 0;
                                foreach ($productcosts as $product):
                                
                                  // Find any one matching order to get SP
                                  $matchingOrder = null;
                                  foreach ($orderhistories as $oh) {
                                      if ($oh['product_id'] == $product['product_id']) {
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
                                ?>
                                <tr>
                                  <td><?= $x++; ?></td>
                                  <td><?= h($product['product_name']) ?></td>
                                  <td><?= h($product['category_name']) ?></td>
                                  <td><?= h($product['unit_name']) ?></td>
                                  <td><?= number_format($product['unit_cost'], 2) ?></td>
                                  <td><?= number_format($sp, 2) ?></td>
                                  <td><?= number_format($product['totalquantity'],2) ?></td>
                                  <td><?= number_format($billed_amount, 2) ?></td>
                                  <td><?= number_format($billed_cost, 2) ?></td>
                                  <td><?= number_format($product['nctotalquantity'],2) ?></td>
                                  <td><?= number_format($ncsp,2) ?></td>
                                  <td><?= number_format($ncbilled_cost,2) ?></td>
                                  <td><?= h($ncsp != 0) 
                                      ? number_format((($billed_amount / $ncsp) * 100), 2) . '%' 
                                      : '0.00%'; ?>
                                      </td>
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
        
        window.open('<?php echo $this->base; ?>/dashboard/item_sale_reportexport?sdate='+sdate+'&cashier='+cashier, '_blank');
    
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

