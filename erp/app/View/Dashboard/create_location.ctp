
<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Location</h2>
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
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('username', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('password', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','required'=>'required'));
                                                $types = [ 'Outlet' => 'Outlet','DC' => 'DC'];
                                                echo $this->Form->input('type', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => array('class' => 'form-label'),
                                                    'options' => $types,
                                                    'class' => 'form-control',
                                                    'required' => 'required'
                                                ));
                                                echo $this->Form->input('state', [
                                                    'type'     => 'select',
                                                    'options'  => $indiaStates,
                                                    'empty'    => 'Select State',
                                                    'label'    => 'State',
                                                    'required' => true,
                                                    'class'    => 'form-control',
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

                                                echo '<br>'.$this->Form->input('shift_start_time', array(
                                                    'type' => 'select',
                                                    'options' => $shiftTimeOptions,
                                                    'label' => 'Shift Start Time',
                                                    'class' => 'form-control',
                                                    'id' => 'shift_start_time_select',
                                                    'selected' => '06:00:00'
                                                ));

                                                echo '<br>'.$this->Form->input('shift_end_time', array(
                                                    'type' => 'select',
                                                    'options' => $shiftTimeOptions,
                                                    'label' => 'Shift End Time',
                                                    'class' => 'form-control',
                                                    'id' => 'shift_end_time_select',
                                                    'selected' => '06:00:00'
                                                ));
                                                
                                                echo '<br>'.$this->Form->input('reelo_merchant_id', array('type'=>'text', 'label'=>'Reelo Merchant ID', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('reelo_customer_key', array('type'=>'text', 'label'=>'Reelo Customer Key', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('service_charge_rate', array('type'=>'number', 'label'=>'Service Charge Rate (%)', 'class'=>'form-control', 'value'=>0));
                                                
                                                echo '<br>'.$this->Form->input('print_line1', array( 'label'=>'Brand Name', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line2', array( 'label'=>'Address Line 1', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line3', array( 'label'=>'Address Line 2', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line4', array( 'label'=>'Other Attribute 1', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line5', array( 'label'=>'Other Attribute 2', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line6', array( 'label'=>'Other Attribute 3', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line7', array( 'label'=>'Other Attribute 4', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line8', array( 'label'=>'Other Attribute 5', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line9', array( 'label'=>'Other Attribute 6', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('print_line10', array( 'label'=>'Other Attribute 7', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('footer_msg', array( 'label'=>'Footer Message', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('footer_msg2', array( 'label'=>'Footer Message 2', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('footer_msg3', array( 'label'=>'Footer Message 3', 'class'=>'form-control'));
                                                echo '<br>'.$this->Form->input('currency_symbol', array( 'label'=>'Currency Symbol', 'class'=>'form-control'));
                                                
?>
                                    </div>
                                    </div>
                                    <a href="location_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
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