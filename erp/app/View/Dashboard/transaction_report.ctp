<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
                <h2 class="page-title">Transaction Report</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row" >
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php echo $this->Form->create('Search', array('url'=>'transaction_report')); 
                            // echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select User','required'=>'required')); 
                            $options = [];

                             if ($this->Session->read('Auth.User.role') == 'admin') {
                                $options[''] = 'All Stores';
                            }
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
                    <?php echo $this->Form->create('Report', array('id'=>'pdfform','url'=>'transaction_reportpdf', 'target'=>'_blank')); ?>
        			<?php echo $this->Form->end(); ?>
        			
                </div>
                <div style="height:30px; width: 100%" class="clearfix"></div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th><?php echo 'ID'; ?></th>
                                    <th><?php echo 'Transaction Id'; ?></th>
                                    <th><?php echo 'Bill No'; ?></th>
                                    <th><?php echo 'Store Name'; ?></th>
                                    <th><?php echo 'Table Number'; ?></th>
                                    <th><?php echo 'PAX'; ?></th>
                                    <th><?php echo 'Phone'; ?></th>
                                    <th><?php echo 'Name'; ?></th>
                                    <th><?php echo 'Payment Mode'; ?></th>
                                    <th><?php echo 'Amount'; ?></th>
                                    <th><?php echo 'Status'; ?></th>
                                    <th><?php echo 'Created'; ?></th>
                                    <th><?php echo 'Action'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $x=1;
                                foreach ($transactions as $transaction): 
                                    if($transaction['order_transactions']['status']==0){
                                        $status='<span class="badge badge-danger">Cancelled</span>';
                                    }elseif($transaction['order_transactions']['status']==1){
                                        $status='<span class="badge badge-warning">Pending / Unsettled</span>';
                                    }elseif($transaction['order_transactions']['status']==2){
                                        $status='<span class="badge badge-success">Settled</span>';
                                    }else{
                                        $status='<span class="badge badge-secondary">Unknown</span>';
                                    }
                                    
                                    ?>
                                    <tr>
                                        <td><?php print_r($x);?></td>
                                        <td><?php print_r($transaction['order_transactions']['reference_id']);?></td>
                                        <td><?php print_r($transaction['order_transactions']['orderno']);?></td>
                                        <td><?php print_r($transaction[0]['store_name']);?></td>
                                        <td><?php print_r($transaction['order_transactions']['table_number']);?></td>
                                        <td><?php print_r($transaction['order_transactions']['pax']);?></td>
                                        <td><?php print_r($transaction['order_transactions']['phone']);?></td>
                                        <td><?php print_r($transaction['order_transactions']['name']);?></td>
                                        <td><?php print_r($transaction[0]['mode']);?></td>
                                        <td><?php print_r(round($transaction['order_transactions']['amount'],2));?></td>
                                        <td><?php echo $status;?></td>
                                        <td><?php print_r($transaction['order_transactions']['created']);?></td>
                                        <td>
                                            <?php
                                            if($transaction['order_transactions']['status']==2){
                                                echo '<a  target="_blank" href="bill_printpdf?id=' . $transaction['order_transactions']['id'] . '">
                                                    <button class="btn btn-info" type="button">Print&nbspBill</button>
                                                </a>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php $x=$x+1; 
                                    endforeach; ?>            
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
        
        window.open('<?php echo $this->base; ?>/report/transaction_reportexport?sdate='+sdate+'&cashier='+cashier, '_blank');
    
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

