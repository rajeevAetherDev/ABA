<style>
  .radio-inline label {
    display: inline-block; /* Ensure the labels are displayed inline */
    margin-right: 10px; /* Adjust spacing between radio buttons and labels */
}

</style>
<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Location Details</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                          <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="location_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('name', array( 'label'=>'Location Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['name']));
                                                echo '<br>'.$this->Form->input('username', array( 'label'=>'User Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['username']));
                                                echo '<br>'.$this->Form->input('type', array( 'label'=>'Type', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['type']));
                                                $defStartFmt = date('h:i A', strtotime(date('Y-m-d') . " " . trim(shiftstart)));
                                                $defEndFmt = date('h:i A', strtotime(date('Y-m-d') . " " . trim(shiftend)));
                                                $valStart = !empty($locations[0]['locations']['shift_start_time']) ? date('h:i A', strtotime(date('Y-m-d') . " " . $locations[0]['locations']['shift_start_time'])) : $defStartFmt;
                                                $valEnd = !empty($locations[0]['locations']['shift_end_time']) ? date('h:i A', strtotime(date('Y-m-d') . " " . $locations[0]['locations']['shift_end_time'])) : $defEndFmt;
                                                echo '<br>'.$this->Form->input('shift_start_time', array('type'=>'text', 'label'=>'Shift Start Time', 'class'=>'form-control','readonly'=>'readonly','value'=>$valStart));
                                                echo '<br>'.$this->Form->input('shift_end_time', array('type'=>'text', 'label'=>'Shift End Time', 'class'=>'form-control','readonly'=>'readonly','value'=>$valEnd));
                                                echo '<br>'.$this->Form->input('reelo_merchant_id', array('type'=>'text', 'label'=>'Reelo Merchant ID', 'class'=>'form-control','readonly'=>'readonly','value'=>isset($locations[0]['locations']['reelo_merchant_id']) ? $locations[0]['locations']['reelo_merchant_id'] : ''));
                                                echo '<br>'.$this->Form->input('reelo_customer_key', array('type'=>'text', 'label'=>'Reelo Customer Key', 'class'=>'form-control','readonly'=>'readonly','value'=>isset($locations[0]['locations']['reelo_customer_key']) ? $locations[0]['locations']['reelo_customer_key'] : ''));
                                                echo '<br>'.$this->Form->input('service_charge_rate', array('type'=>'text', 'label'=>'Service Charge Rate (%)', 'class'=>'form-control','readonly'=>'readonly','value'=>isset($locations[0]['locations']['service_charge_rate']) ? $locations[0]['locations']['service_charge_rate'] : ''));
                                                echo '<br>'.$this->Form->input('state', array( 'label'=>'State', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['state']));
                                                echo '<br>'.$this->Form->input('print1', array( 'label'=>'Print Line 1', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line1']));
                                                echo '<br>'.$this->Form->input('print2', array( 'label'=>'Print Line 2', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line2']));
                                                echo '<br>'.$this->Form->input('print3', array( 'label'=>'Print Line 3', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line3']));
                                                echo '<br>'.$this->Form->input('print4', array( 'label'=>'Print Line 4', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line4']));
                                                echo '<br>'.$this->Form->input('print5', array( 'label'=>'Print Line 5', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line5']));
                                                echo '<br>'.$this->Form->input('print6', array( 'label'=>'Print Line 6', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line6']));
                                                echo '<br>'.$this->Form->input('print7', array( 'label'=>'Print Line 7', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line7']));
                                                echo '<br>'.$this->Form->input('print8', array( 'label'=>'Print Line 8', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line8']));
                                                echo '<br>'.$this->Form->input('print9', array( 'label'=>'Print Line 9', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line9']));
                                                echo '<br>'.$this->Form->input('print10', array( 'label'=>'Print Line 10', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['print_line10']));
                                                echo '<br>'.$this->Form->input('footermsg', array( 'label'=>'Footer Msg', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['footer_msg']));
                                                echo '<br>'.$this->Form->input('footermsg', array( 'label'=>'Footer Msg 2', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['footer_msg2']));
                                                echo '<br>'.$this->Form->input('footermsg', array( 'label'=>'Footer Msg 3', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['footer_msg3']));
                                                echo '<br>'.$this->Form->input('currency_symbol', array( 'label'=>'Currency Symbol', 'class'=>'form-control','readonly'=>'readonly','value'=>$locations[0]['locations']['currency_symbol']));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
<style>
    .hidden{
        display:none;
    }
    
</style>
<script>
    $(document).ready(function(){
    $('#addsubtask').click(function(){
        var newInput = $('<div class="form-group"><div class="row"><div class="col-md-6">' +
                            '<label>Sub Category Name</label>' +
                            '<input type="text" class="form-control sub-task" required="required" name="subcat[]"></div>' +
                            '<div class="col-md-2"><br><button class="btn btn-danger removesubtask" type="button" style="float:right;">Remove</button></div>' +
                        '</div>');
        $('#subtaskInputs').append(newInput);
    });
    
    $(document).on('click', '.removesubtask', function(){
        $(this).closest('.form-group').remove();
    });
    
});

</script>                   