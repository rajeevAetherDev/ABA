<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
                <h2 class="page-title">Wallet History</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row" >
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php echo $this->Form->create('Search', array('url'=>'wallet_history')); 
                            // echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select User','required'=>'required')); 
                            echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                            echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                            echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                            echo"&nbsp;". $this->Form->button('Print', array('class'=>'btn btn-info', 'type'=>'button','id'=>'btn-pdf'));
                            echo $this->Form->end(); 
                        ?>
                    </div>
                    <div style="height:30px; width: 100%" class="clearfix"></div>
                    <?php echo $this->Form->create('Report', array('id'=>'pdfform','url'=>'wallet_historypdf', 'target'=>'_blank')); ?>
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
                                    <th><?php echo 'Cashier Name'; ?></th>
                                    <th><?php echo 'Card Number'; ?></th>
                                    <th><?php echo 'Phone'; ?></th>
                                    <th><?php echo 'Customer'; ?></th>
                                    <th><?php echo 'Type'; ?></th>
                                    <th><?php echo 'Mode Of Payment'; ?></th>
                                    <th><?php echo 'Amount'; ?></th>
                                    <th><?php echo 'Created'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $x=1;
                                foreach ($cards as $card): 
                                    if(isset($card['cardtracking']['txnid'])){
            	                        if($card['cardtracking']['type']==1){
                                            $type='credit';
                                        }elseif($card['cardtracking']['type']==2){
                                            $type='debit';
                                        }elseif($card['cardtracking']['type']==3){
                                            $type='security';
                                        }elseif($card['cardtracking']['type']==4){
                                            $type='refund';
                                        }
                                        
                                        if($card['cardtracking']['mop']=='card_charge'){
                                            $purpose='Card Charge';
                                        }else{
                                            $purpose=$card['cardtracking']['mop'];
                                        }
                                    ?>
                                    <tr>
                                        <td><?php print_r($x);?></td>
                                        <td><?php print_r($card['cardtracking']['txnid']);?></td>
                                        <td><?php print_r($card['cardtracking']['cashierName']);?></td>
                                        <td><?php print_r($card['cards']['cardnumber']);?></td>
                                        <td><?php print_r($card['cards']['mobile']);?></td>
                                        <td><?php print_r($card['cards']['name']);?></td>
                                        <td><?php print_r($type);?></td>
                                        <td><?php print_r($purpose);?></td>
                                        <td><?php print_r($card['cardtracking']['amount']);?></td>
                                        <td><?php print_r($card['cards']['created']);?></td>
                                        
                                    </tr>
                                <?php $x=$x+1; 
                                    }
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
        
        window.open('<?php echo $this->base; ?>/dashboard/wallet_historyexport?sdate='+sdate, '_blank');
    
    });
    
$("#btn-pdf").click(function(){
	var pdfform =document.getElementById('pdfform');
	$('#pdfform').hide();
	var form_data = new FormData(pdfform);
      
	var sdate=$("#startdate").val();
	
    var sdateelement= document.createElement("input"); 
    sdateelement.value=sdate;
	sdateelement.name="sdate";
	pdfform.appendChild(sdateelement); 
	pdfform.submit(); 

	});


</script>

