<?php 
function searchArray($array, $key, $value)
{
    
     
    $results = array();

    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }

        foreach ($array as $subarray) {
            $results = array_merge($results, searchArray($subarray, $key, $value));
        }
    }

    return $results;
}
?>
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <section class="box ">
                            <header class="panel_header">
                                <h2 class="title pull-left">Item Sale Statement Report</h2>
                            </header>
                            <div class="content-body">
                                <div class="row clearfix">
                                   
                                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                            <h3>View Report</h3>
                                            <?php echo $this->Form->create('Productsreport'); 
                                            echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Cashier','empty'=>'select cashier')); 
											echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control  datepicker', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select From Date', 'data-start-view'=>2, 'required'=>'required','value'=>date('m/d/Y'))); 
                                           
                                             echo $this->Form->input('enddate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control  datepicker', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select To Date', 'data-start-view'=>2, 'required'=>'required','value'=>date('m/d/Y')));
                                            
                                            echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                        //'onclick'=>'submission();'
                                        echo $this->Form->end(); ?>
                                </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <h3>Print Report</h3>
                                        <?php echo $this->Form->create('Productsreport', array('url'=>'menumixreportchef', 'target'=>'_blank')); 
                                            echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Cashier','empty'=>'select cashier')); 
											echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control  datepicker', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select From Date', 'data-start-view'=>2, 'required'=>'required','value'=>date('m/d/Y'))); 
                                           
                                             echo $this->Form->input('enddate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control  datepicker', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select To Date', 'data-start-view'=>2, 'required'=>'required','value'=>date('m/d/Y')));
                                            
                                            echo $this->Form->button('Print', array('class'=>'btn btn-info', 'type'=>'submit'));
                                        //'onclick'=>'submission();'
                                        echo $this->Form->end(); ?>
                                        
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                        <h3>Export Report</h3>
                                        <?php echo $this->Form->create('Productsreport', array('url'=>'menumixreportexportchef', 'target'=>'_blank')); 
                                            echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Cashier','empty'=>'select cashier')); 
											echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control  datepicker', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select From Date', 'data-start-view'=>2, 'required'=>'required','value'=>date('m/d/Y'))); 
                                           
                                             echo $this->Form->input('enddate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control  datepicker', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select To Date', 'data-start-view'=>2, 'required'=>'required','value'=>date('m/d/Y')));
                                            
                                            echo $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'submit'));
                                        //'onclick'=>'submission();'
                                        echo $this->Form->end(); ?>
                                        
                                    </div>
                                </div>
                                <div style="height:30px; width: 100%" class="clearfix"></div>

                                
                                <?php if(!empty($resp)) : ?>                            
                                <div class="row">
                                    <div class="col-md-8  col-md-offset-2 col-sm-12 col-xs-6">
                                        <h3 class="text-center">Item Sale Statement report <br> From: <?php echo $startdate; ?> To: <?php echo $enddate; ?></h3>

<hr />

                                        
    <p style="text-align:center; font-size:20px; text-transform: uppercase;">
        <span style="font-size: 16px;">Item Sale Statement</span><br/></p>

<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
    <tbody>    
    <?php /* foreach($cateresponse as $category): */
         $totalquantity = 0; $totalamount = 0; $grandtotalamount=0; ?>

        <?php //$responses = searchArray($resp, 'category_id', $category['id']); ?>
        <tr>
            <td>
				<p align="center" style="text-align: left; font-style: bold;"><b>Product Name<?php //echo $category['name']; ?></b></p>
			</td>
			<td>
                <p align="center" style="text-align: center;">Category</p>
			</td>
			<td>
                <p align="center" style="text-align: center;">Rate</p>
			</td>
			<td>
				<p align="center" style="text-align: right;">QTY</p>
			</td>
            <td>
				<p align="center" style="text-align: right;">Amount</p>
			</td>
            <!---
			<td>
                <p align="center" style="text-align: center;">No Charge</p>
			</td>
            <td>
                <p align="center" style="text-align: center;">Line Discount</p>
			</td>
			<td>
                <p align="center" style="text-align: right;">Item Wise</p>
			</td>
            <td>
                <p align="center" style="text-align: right;">Sale Wise</p>
			</td>
			-->
		</tr>
        
        
        <?php foreach ($resp as $response): ?>
	<tr>
		<td>
			<p align="center" style="text-align: left;"><?php echo $response['item_name']; ?></p>
		</td>
		<td>
            <p align="center" style="text-align: center;"><?php echo $response['category_name']; ?></p>
		</td>
        <td>
            <p align="center" style="text-align: center;"><?php echo $response['pprice']; ?></p>
		</td>
        <td>
			<p align="center" style="text-align: right;"><?php echo $response['quantity']; ?></p>
		</td>
		<td>
			<p align="center" style="text-align: right;"><?php echo  $response['pprice']*$response['quantity']; ?></p>
		</td>
		<!--
		<td>
            <p align="center" style="text-align: right;"><?php echo $response['nocharge']; ?></p>
		</td>
        <td>
            <p align="center" style="text-align: right;"><?php echo $response['discount']; ?></p>
		</td>
        
		<td>
            <p align="center" style="text-align: right;"><?php echo $response['price']; ?></p>
		</td>
		<td>
            <p align="center" style="text-align: right;"><?php echo round((($response['price'] * 100) / $grandtotal ) , 2); ?></p>
		</td>
		-->
        
	</tr>
        <?php  $totalquantity = $totalquantity + $response['quantity']; $totalamount = $totalamount + $response['pprice']*$response['quantity']; 
		endforeach;  
		unset($responses);  ?>
        <!----
		<tr>
		<td>
            <p align="center" style="text-align: left;">Total</p>
		</td>
		<td >
            <p align="center" style="text-align: right;">&nbsp;</p>
		</td>
		<td>
            <p align="center" style="text-align: right;"><b><?php echo $totalquantity; ?></b></p>
		</td>
		<td>
            <p align="center" style="text-align: right;"><b><?php echo $totalamount; ?></b></p>
		</td>
        </tr>
		--->
        
    <?php 
	
        // endforeach; ?> 
        
        </tbody>
</table>
    <!---<p align="center" style="margin-top:0.4in; margin-bottom: 0in; line-height: 120%; text-align: center; font-size: small; width: 100%; border-top: 1px solid #888; border-bottom: 1px solid #888; padding-bottom: 0.2in;"><br/>Grand Total* : <?php echo $grandtotal; ?></p>                                      
    <p style="font-size: small">*Note: Grand Total without tax and discount</p>                                    
        --->                                
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                        
                                    </div>
                                    
                                    
                                    
                                    
                                    
                                </div>
          <?php endif; ?>                      
                                
                                
                                
                                
                                
                                
                                
                                

                            </div>
                        </section></div>

