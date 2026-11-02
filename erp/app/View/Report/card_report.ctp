<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
                <h2 class="page-title">Card History</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row" >
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php echo $this->Form->create('Search', array('url'=>'card_report')); 
                            echo $this->Form->input('card_no', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','id'=>'card_no','required'=>'required')); 
                            echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                            echo $this->Form->button('View Details', array('class'=>'btn btn-info', 'type'=>'submit'));
                            echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                            echo"&nbsp;". $this->Form->button('Print', array('class'=>'btn btn-info', 'type'=>'button','id'=>'btn-pdf'));
                            echo $this->Form->end();
                        ?>
                    </div>
                    <div style="height:30px; width: 100%" class="clearfix"></div>
                    <?php echo $this->Form->create('Report', array('id'=>'pdfform','url'=>'card_reportpdf', 'target'=>'_blank')); ?>
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
                                    <th><?php echo 'Purpose'; ?></th>
                                    <th><?php echo 'Type'; ?></th>
                                    <th><?php echo 'Amount'; ?></th>
                                    <th><?php echo 'Date'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $x=1;
                                foreach ($cards as $card): 
                                    if($card['cardtracking']['type']==1){
                                        $type='Credit';
                                    }elseif($card['cardtracking']['type']==2){
                                        $type='Debit';
                                    }elseif($card['cardtracking']['type']==3){
                                        $type='Security';
                                    }elseif($card['cardtracking']['type']==4){
                                        $type='Refund';
                                    }
                                    
                                    if($card['cardtracking']['purpose']=='card_charge'){
                                        $purpose='card charge';
                                    }elseif($card['cardtracking']['purpose']=='transferfrom'){
                                        $purpose='transfer from';
                                    }elseif($card['cardtracking']['purpose']=='transferto'){
                                        $purpose='transfer to';
                                    }else{
                                        $purpose=$card['cardtracking']['purpose'];
                                    }
                                    ?>
                                    <tr>
                                        <td><?php print_r($x);?></td>
                                        <td><?php print_r($card['cardtracking']['txnid']);?></td>
                                        <td> <?php print_r($purpose); ?></td>
                                        <td><?php print_r($type);?></td>
                                        <td><?php print_r(abs($card['cardtracking']['amount']));?></td>
                                        <td><?php print_r($card['cardtracking']['created']);?></td>
                                        
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
        var cardno=$("#card_no").val();
        var sdate=$("#startdate").val();
        if(cardno==''){
            alert('Card Number Required');
        }else{
            window.open('<?php echo $this->base; ?>/report/card_reportexport?id='+cardno+'&sdate='+sdate, '_blank');
        }
    });

$("#btn-pdf").click(function(){
	var pdfform =document.getElementById('pdfform');
	$('#pdfform').hide();
	var form_data = new FormData(pdfform);
      
	var cardno=$("#card_no").val();
	var sdate=$("#startdate").val();
	if(cardno==''){
        alert('Card Number Required');
    }else{
        var cardnoelement= document.createElement("input"); 
        var sdateelement= document.createElement("input"); 
    
    	cardnoelement.value=cardno;
    	cardnoelement.name="cardno";
    	pdfform.appendChild(cardnoelement); 
    	sdateelement.value=sdate;
    	sdateelement.name="sdate";
    	pdfform.appendChild(sdateelement); 
    	
		pdfform.submit(); 
	
	}




});


</script>
