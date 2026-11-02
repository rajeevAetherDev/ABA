<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Update Location</h2>
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
                                    <?php echo $this->Form->create('User'); ?>
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('id', array('value' => $locations[0]['locations']['id'],'type'=>'hidden'));
                                                echo $this->Form->input('name', array( 'label'=>'Location Name', 'class'=>'form-control','value'=>$locations[0]['locations']['name']));
                                                echo '<br>'.$this->Form->input('username', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'),'value'=>$locations[0]['locations']['username'], 'class'=>'form-control','readonly'=>true));
                                                echo $this->Form->input('password', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'),'placeholder'=>'******', 'class'=>'form-control'));
                                                
                                                if($locations[0]['locations']['type']=='Outlet'){
                                                    $types = ['Outlet' => 'Outlet', 'DC' => 'DC'];
                                                }else{
                                                    $types = ['DC' => 'DC','Outlet' => 'Outlet'];   
                                                }
                                                echo $this->Form->input('type', [
                                                    'div' => ['class' => 'form-group'],
                                                    'label' => ['class' => 'form-label'],
                                                    'options' => $types,
                                                    'class' => 'form-control',
                                                    'required' => 'required'
                                                ]);
                                                echo $this->Form->input('state', [
                                                    'type'     => 'select',
                                                    'options'  => $indiaStates,
                                                    'empty'    => 'Select State',
                                                    'required' => true,
                                                    'class'    => 'form-control',
                                                    'value'    => $locations[0]['locations']['state'],
                                                    'label'    => 'State',
                                                    'div'      => ['class' => 'form-group'],
                                                    'between'  => '<span class="desc"></span><div class="controls">',
                                                    'after'    => '</div>'
                                                ]);
                                                $shiftPresets = [
                                                    '' => '-- Quick Select Shift Preset --',
                                                    'std' => 'Standard: 6:00 AM to 6:00 AM (Next Day)',
                                                    'same_8_8' => 'Same Day: 8:00 AM to 8:00 PM (Same Day)',
                                                    'next_8_8' => 'Overnight: 8:00 AM to 8:00 AM (Next Day)',
                                                    'night' => 'Night Shift: 7:00 PM to 7:00 AM (Next Day)',
                                                    'fullday' => 'Full Day: 12:00 AM to 11:59 PM (Same Day)'
                                                ];

                                                echo '<br>'.$this->Form->input('shift_preset', array(
                                                    'type' => 'select',
                                                    'options' => $shiftPresets,
                                                    'label' => 'Quick Shift Preset',
                                                    'class' => 'form-control',
                                                    'id' => 'shift_preset_select'
                                                ));

                                                $selectedStart = isset($locations[0]['locations']['shift_start_time']) && !empty($locations[0]['locations']['shift_start_time']) ? trim($locations[0]['locations']['shift_start_time']) : (defined('shiftstart') ? trim(shiftstart) : '06:00:00');
                                                $selectedEnd = isset($locations[0]['locations']['shift_end_time']) && !empty($locations[0]['locations']['shift_end_time']) ? trim($locations[0]['locations']['shift_end_time']) : (defined('shiftend') ? trim(shiftend) : '06:00:00');
                                                $selectedType = isset($locations[0]['locations']['shift_type']) ? $locations[0]['locations']['shift_type'] : 'next_day';

                                                echo '<br>'.$this->Form->input('shift_start_time', array(
                                                    'type' => 'select',
                                                    'options' => $shiftTimeOptions,
                                                    'label' => 'Shift Start Time',
                                                    'class' => 'form-control',
                                                    'id' => 'shift_start_time_select',
                                                    'selected' => $selectedStart
                                                ));

                                                echo '<br>'.$this->Form->input('shift_end_time', array(
                                                    'type' => 'select',
                                                    'options' => $shiftTimeOptions,
                                                    'label' => 'Shift End Time',
                                                    'class' => 'form-control',
                                                    'id' => 'shift_end_time_select',
                                                    'selected' => $selectedEnd
                                                ));
                                                echo '<br>'.$this->Form->input('reelo_merchant_id', array('type'=>'text', 'label'=>'Reelo Merchant ID', 'class'=>'form-control','value'=>isset($locations[0]['locations']['reelo_merchant_id']) ? $locations[0]['locations']['reelo_merchant_id'] : ''));
                                                echo '<br>'.$this->Form->input('reelo_customer_key', array('type'=>'text', 'label'=>'Reelo Customer Key', 'class'=>'form-control','value'=>isset($locations[0]['locations']['reelo_customer_key']) ? $locations[0]['locations']['reelo_customer_key'] : ''));
                                                echo '<br>'.$this->Form->input('service_charge_rate', array('type'=>'number', 'label'=>'Service Charge Rate (%)', 'class'=>'form-control','value'=>isset($locations[0]['locations']['service_charge_rate']) ? $locations[0]['locations']['service_charge_rate'] : 0));
                                            
                                                echo $this->Form->input('print_line1', array( 'label'=>'Brand Name', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line1']));
                                                echo '<br>'.$this->Form->input('print_line2', array( 'label'=>'Address Line 1', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line2']));
                                                echo '<br>'.$this->Form->input('print_line3', array( 'label'=>'Address Line 2', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line3']));
                                                echo '<br>'.$this->Form->input('print_line4', array( 'label'=>'Other Attribute 1', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line4']));
                                                echo '<br>'.$this->Form->input('print_line5', array( 'label'=>'Other Attribute 2', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line5']));
                                                echo '<br>'.$this->Form->input('print_line6', array( 'label'=>'Other Attribute 3', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line6']));
                                                echo '<br>'.$this->Form->input('print_line7', array( 'label'=>'Other Attribute 4', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line7']));
                                                echo '<br>'.$this->Form->input('print_line8', array( 'label'=>'Other Attribute 5', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line8']));
                                                echo '<br>'.$this->Form->input('print_line9', array( 'label'=>'Other Attribute 6', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line9']));
                                                echo '<br>'.$this->Form->input('print_line10', array( 'label'=>'Other Attribute 7', 'class'=>'form-control','value'=>$locations[0]['locations']['print_line10']));
                                                echo '<br>'.$this->Form->input('footer_msg', array( 'label'=>'Footer Message', 'class'=>'form-control','value'=>$locations[0]['locations']['footer_msg']));
                                                echo '<br>'.$this->Form->input('footer_msg2', array( 'label'=>'Footer Message 2', 'class'=>'form-control','value'=>isset($locations[0]['locations']['footer_msg2']) ? $locations[0]['locations']['footer_msg2'] : ''));
                                                echo '<br>'.$this->Form->input('footer_msg3', array( 'label'=>'Footer Message 3', 'class'=>'form-control','value'=>isset($locations[0]['locations']['footer_msg3']) ? $locations[0]['locations']['footer_msg3'] : ''));
                                                echo '<br>'.$this->Form->input('currency_symbol', array( 'label'=>'Currency Symbol', 'class'=>'form-control','value'=>$locations[0]['locations']['currency_symbol']));
                                            ?>
                                        </div>
                                    </div>
                                    <a href="location_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Update', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
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
    
    $('#shift_preset_select').change(function(){
        var val = $(this).val();
        if(val === 'same_8_8'){
            $('#shift_start_time_select').val('08:00:00');
            $('#shift_end_time_select').val('20:00:00');
        } else if(val === 'next_8_8'){
            $('#shift_start_time_select').val('08:00:00');
            $('#shift_end_time_select').val('08:00:00');
        } else if(val === 'std'){
            $('#shift_start_time_select').val('06:00:00');
            $('#shift_end_time_select').val('06:00:00');
        } else if(val === 'night'){
            $('#shift_start_time_select').val('19:00:00');
            $('#shift_end_time_select').val('07:00:00');
        } else if(val === 'fullday'){
            $('#shift_start_time_select').val('00:00:00');
            $('#shift_end_time_select').val('23:59:59');
        }
    });
});

</script>                   