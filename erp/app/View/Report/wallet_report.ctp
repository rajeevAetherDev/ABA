<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
                <h2 class="page-title">Wallet Report</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row" >
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php echo $this->Form->create('Search', array('url'=>'wallet_report')); 
                            echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                            echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                            echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                            echo"&nbsp;". $this->Form->button('Print', array('class'=>'btn btn-info', 'type'=>'button','id'=>'btn-pdf'));
                            echo $this->Form->end(); 
                        ?>
                    </div>
                    <div style="height:30px; width: 100%" class="clearfix"></div>
                    <?php echo $this->Form->create('Report', array('id'=>'pdfform','url'=>'wallet_reportpdf', 'target'=>'_blank')); ?>
        			<?php echo $this->Form->end(); ?>
        			
                </div>
                <div style="height:30px; width: 100%" class="clearfix"></div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        
                        <h3>Card Data</h3>
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <th>Active Cards</th>
                                    <td><?php print_r(round($activecards,2));?></td>
                                </tr>
                                <tr>
                                    <th>Inactive Cards</th>
                                    <td><?php print_r(round($inactivecards,2));?></td>
                                </tr>
                            </tbody>
                        </table>
                    
                        
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <h3>Wallet Data</h3>
                        <table id="example1" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <th>Total Balance Added</th>
                                    <td><?php print_r(round($totaladded,2));?></td>
                                </tr>
                                <tr>
                                    <th>Total Used Amount</th>
                                    <td><?php print_r(round($totalused,2));?></td>
                                </tr>
                                <tr>
                                    <th>Refunded Amount</th>
                                    <td><?php print_r(round($totalrefund,2));?></td>
                                </tr>
                                <tr>
                                    <th>Security Amount</th>
                                    <td><?php print_r(round($totalsecurity,2));?></td>
                                </tr>
                                <tr>
                                    <th>Net Balance</th>
                                    <td><?php print_r(round(($totaladded-$totalused),2));?></td>
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
        
        window.open('<?php echo $this->base; ?>/report/wallet_reportexport?sdate='+sdate, '_blank');
    
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
