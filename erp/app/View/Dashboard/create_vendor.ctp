<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Vendor</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="vendor_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('User',['type' => 'file']); ?>
                                   
                                    <div class="form-group row">
                                        
                                        <div class="col-sm-6">
                                            <?php 
                                            
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name', 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('email', array('div'=>array('class'=>'form-group'),'type'=>'email', 'label'=>'Email','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('office_address', array('div'=>array('class'=>'form-group'), 'label'=>'Office Address','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('company', array('div'=>array('class'=>'form-group'), 'label'=>'Company', 'class'=>'form-control','required'=>'required', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('gst', array('div'=>array('class'=>'form-group'), 'label'=>'GST', 'class'=>'form-control','required'=>'required', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('bank_ac', array('div'=>array('class'=>'form-group'), 'label'=>'Bank Account No.','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('bank_name', array('div'=>array('class'=>'form-group'), 'label'=>'Bank Name','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('advance_percentage', array('div'=>array('class'=>'form-group'),'type'=>'number','required'=>'required', 'label'=>'Advance Payment(%)', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                
                                                echo $this->Form->input('approval', [
                                                    'div' => ['class' => 'form-group'],
                                                    'type' => 'select',
                                                    'label' => 'Required Purchase Order Approval',
                                                    'options' => [1 => 'Yes', 0 => 'No'],
                                                    'default' => 1, // Select "Yes" by default
                                                    'required' => 'required',
                                                    'class' => 'form-control',
                                                    'between' => '<span class="desc"></span><div class="controls">',
                                                    'after' => '</div>'
                                                ]);
                                                
                                                
                                                echo $this->Form->input('gst_image', ['type' => 'file','label' => 'GST Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                echo $this->Form->input('agreement_image', ['type' => 'file','label' => 'Agreement Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                                            
                                            ?>
                                        </div>
                                        <div class="col-sm-6">
                                            <?php 
                                            
                                                echo $this->Form->input('mobile', 
                                                array('div'=>array('class'=>'form-group'), 
                                                'min' => '1000000000', 
                                                'max' => '9999999999', 
                                                'oninput' => 'this.value = this.value.slice(0, 10);',
                                                'type'=>'number',
                                                'label'=>'Mobile', 
                                                'class'=>'form-control',
                                                'required'=>'required'));
                                                echo $this->Form->input('landline', array('div'=>array('class'=>'form-group'),'type'=>'number', 'label'=>'Landline', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                // echo $this->Form->input('state', array('div'=>array('class'=>'form-group'), 'label'=>'State','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                
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
                                                
                                                echo $this->Form->input('pan', array('div'=>array('class'=>'form-group'), 'label'=>'PAN','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('tan', array('div'=>array('class'=>'form-group'), 'label'=>'TAN','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('ifsc', array('div'=>array('class'=>'form-group'), 'label'=>'IFSC','required'=>'required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('payment_days', array('div'=>array('class'=>'form-group'),'type'=>'number','required'=>'required', 'label'=>'Payment Days', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                            
                                                echo "<h5>Service Areas</h5><div class='row' style='margin-left:1%;'>";
                                                    foreach ($areas as $area) {
                                                        $ID = $area['Location']['id'];
                                                        $Name = $area['Location']['name'];
                                                    ?>
                                                        <div class="col-md-3  mb-2">
                                                            <?php
                                                                echo $this->Form->input('area_'.$ID, array('label'=>$Name,'div' => false,'class' => 'form-check-input','labelOptions' => ['class' => 'form-check-label'],'value' => $ID,'type'=>'checkbox'));
                                                        ?>
                                                        </div>
                                                    <?php
                                                    }
                                                    ?>
                                                </div>
                                                <br>    
                                                <?php 
                                                echo $this->Form->input('pan_image', ['type' => 'file','label' => 'PAN Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                echo $this->Form->input('tan_image', ['type' => 'file','label' => 'TAN Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                echo $this->Form->input('fssai_image', ['type' => 'file','label' => 'FSSAI Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                
                                                ?>
                                        </div>
                                        
                                    </div>
                                    <a href="vendor_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>

<script>
    $(document).ready(function(){
   
    $('#addtax').click(function(){
        var newInput = $('<div class="form-group"><div class="row">' +
                            '<div class="col-md-4"><label>Tax Name</label>' +
                            '<input type="text" class="form-control tax" required="required" name="tax_name[]"></div>' +
                            '<div class="col-md-4"><label>Tax Percentage</label>' +
                            '<input type="number" class="form-control tax" required="required" name="tax_value[]" step = "0.1"></div>' +
                            '<div class="col-md-2"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
                        '</div>');
        $('#taxInputs').append(newInput);
    });
    
    $(document).on('click', '.removetax', function(){
        $(this).closest('.form-group').remove();
    });
    
    

$('#submitBtn').click(function() {
    
    
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        let atLeastOneChecked = false;
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                atLeastOneChecked = true;
            }
        });
        if (!atLeastOneChecked) {
            event.preventDefault(); // Prevent form submission if no checkbox is checked
            Swal.fire({
              icon: 'warning',
              title: 'Missing Selection',
              text: 'Please select at least one Service Area.',
              confirmButtonText: 'OK'
            });

        }
    });



});

</script>                   