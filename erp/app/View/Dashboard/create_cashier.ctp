<style>
  .radio-inline label {
    display: inline-block; /* Ensure the labels are displayed inline */
    margin-right: 10px; /* Adjust spacing between radio buttons and labels */
}

</style>
<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Cashier/Manager</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="cashier_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('User', array('type'=>'file')); ?>
                                   
                                    <div class="form-group row">
                                        
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('mobile', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Mobile',
                                                    'class' => 'form-control',
                                                    'required' => 'required',
                                                    'type' => 'number',
                                                    'min' => '1000000000', // Minimum value for a 10-digit number
                                                    'max' => '9999999999', // Maximum value for a 10-digit number
                                                    'oninput' => 'this.value = this.value.slice(0, 10);' // Ensure input is no more than 10 digits
                                                ));
                                                echo $this->Form->input('username', array('div'=>array('class'=>'form-group'), 'label'=>'User Name', 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('password', array('div'=>array('class'=>'form-group'), 'label'=>'Password', 'class'=>'form-control','required'=>'required','type'=>'password'));
                                                
                                                $options=[''=>'Select Billing location'];
                                                foreach($locations as $location){
                                                    $options[$location['Location']['id']] = $location['Location']['name'];
                                                }
                                                
                                                $locationoptions = ['0' => 'No', '1' => 'Yes'];
                                                echo $this->Form->input('loc_type', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Do you want to select multiple location',
                                                    'class' => 'form-control',
                                                    'between' => '<span class="desc"></span><div class="controls">',
                                                    'after' => '</div>',
                                                    'options' => $locationoptions,
                                                    'id' => 'loc_type',
                                                    'required' => true
                                                ));
                                                ?>
                                                
                                                <div id="locWrapper" >
                                                    <?php
                                                        echo $this->Form->input('location', array('div'=>array('class'=>'form-group'), 'label'=>'Select Location','multiple' => 'multiple', 'class'=>'form-control select2', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'location','required'=>true));
                                                    ?>
                                                </div>
                                                
                                                <?php
                                                echo $this->Form->input('bill_location', array('div'=>array('class'=>'form-group'), 'label'=>'Select Billing Location', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'bill_location','required'=>true));
                                                
                                                $roleoptions = ['cashier' => 'Cashier', 'manager' => 'Manager'];
                                                echo $this->Form->input('role', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Select Role',
                                                    'class' => 'form-control',
                                                    'between' => '<span class="desc"></span><div class="controls">',
                                                    'after' => '</div>',
                                                    'options' => $roleoptions,
                                                    'id' => 'role',
                                                    'required' => true
                                                ));
                                                ?>
                                                <div id="pinWrapper" >
                                                    <?php
                                                    echo $this->Form->input('pin', array(
                                                        'div' => array('class' => 'form-group'),
                                                        'label' => 'Manager Pin',
                                                        'class' => 'form-control',
                                                        'type' => 'number',
                                                        'id' => 'pin',
                                                        'min' => '1000',
                                                        'max' => '9999',
                                                        'oninput' => 'this.value = this.value.slice(0, 4);'
                                                    ));
                                                    ?>
                                                </div>

                                                <?php
                                                
                                                $settlementoptions = ['0' => 'No', '1' => 'Yes'];
                                                echo $this->Form->input('settlement', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Can settlement',
                                                    'class' => 'form-control',
                                                    'between' => '<span class="desc"></span><div class="controls">',
                                                    'after' => '</div>',
                                                    'options' => $settlementoptions,
                                                    'id' => 'settlement',
                                                    'required' => true
                                                ));


                                                echo "<h5>Select Modules</h5>";
                                                foreach ($modules as $moduleKey => $moduleLabel) {
                                                    echo $this->Form->input($moduleKey, array('label' => $moduleLabel, 'type' => 'checkbox'));
                                                }

                                                ?>

                                        </div>
                                    </div>
                                    <a href="cashier_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
                    
                    <script>
                    
                    $(document).ready(function() {
                        function togglePinField() {
                            if ($('#role').val() === 'manager') {
                                $('#pinWrapper').show();
                                $('#pin').prop('required', true);
                            } else {
                                $('#pinWrapper').hide();
                                $('#pin').prop('required', false);  // <-- important
                            }
                        }
                    
                        // Run on page load
                        togglePinField();
                    
                        // Run when role changes
                        $('#role').change(function() {
                            togglePinField();
                        });
                        
                        function toggleLocField() {
                            if ($('#loc_type').val() === '1') {
                                $('#locWrapper').show();
                                $('#locWrapper select, #locWrapper input').prop('required', true);
                            } else {
                                $('#locWrapper').hide();
                                $('#locWrapper select, #locWrapper input').prop('required', false);
                            }
                        }
                        toggleLocField();

                        // Run when loc_type changes
                        $('#loc_type').change(function() {
                            toggleLocField();
                        });
                    });


                    $(document).ready(function() {
                        $('#isRepeat').change(function() {
                            var selectedValue = $(this).val();
                            if (selectedValue == '1') { // Show radio buttons if "Yes" is selected
                                $('#repeatOptionsWrapper').show();
                            } else { // Hide radio buttons if "No" is selected
                                $('#repeatOptionsWrapper').hide();
                            }
                        });
                    });
                    
                    jQuery(document).ready(function($) {
                        if ($.fn.select2) {
                            $('.select2').select2({
                                placeholder: "Select locations",
                                allowClear: true,
                                width: '100%'
                            });
                        } else {
                            console.error("Select2 library not loaded!");
                        }
                    });
                </script>