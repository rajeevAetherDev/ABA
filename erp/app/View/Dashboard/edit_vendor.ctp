<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Update Vendor</h2>
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
                                            
                                                echo $this->Form->input('id', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','value' =>$products[0]['inventory_vendors']['id']));
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name', 'class'=>'form-control','required'=>'required','value' =>$products[0]['inventory_vendors']['name']));
                                                echo $this->Form->input('email', array('div'=>array('class'=>'form-group'),'type'=>'email', 'label'=>'Email','required'=>'required', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['email'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('office_address', array('div'=>array('class'=>'form-group'), 'label'=>'Office Address','required'=>'required', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['office_address'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('company', array('div'=>array('class'=>'form-group'), 'label'=>'Company', 'class'=>'form-control','required'=>'required','value' =>$products[0]['inventory_vendors']['company'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('gst', array('div'=>array('class'=>'form-group'), 'label'=>'GST', 'class'=>'form-control','required'=>'required','value' =>$products[0]['inventory_vendors']['gst'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('bank_ac', array('div'=>array('class'=>'form-group'), 'label'=>'Bank Account No.','required'=>'required', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['bank_ac'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('bank_name', array('div'=>array('class'=>'form-group'), 'label'=>'Bank Name','required'=>'required', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['bank_name'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('advance_percentage', array('div'=>array('class'=>'form-group'),'type'=>'number','required'=>'required', 'label'=>'Advance Payment(%)','value' =>$products[0]['inventory_vendors']['advance_percentage'], 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                
                                                echo $this->Form->input('approval', [
                                                    'div' => ['class' => 'form-group'],
                                                    'type' => 'select',
                                                    'label' => 'Required Purchase Order Approval',
                                                    'options' => [1 => 'Yes', 0 => 'No'],
                                                    'value' =>$products[0]['inventory_vendors']['po_approval_required'],
                                                    'required' => 'required',
                                                    'class' => 'form-control',
                                                    'between' => '<span class="desc"></span><div class="controls">',
                                                    'after' => '</div>'
                                                ]);
                                                
                                            ?>
                                            <br>
                                            <div class="row">
                                                <div class="col-md-6"> 
                                                    <?php
                                                        echo $this->Form->input('gst_image', ['type' => 'file','label' => 'GST Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                    ?>
                                                </div>
                                                <?php
                                                if($products[0]['inventory_vendors']['gst_image']){
                                                ?>
                                                    <div class="col-sm-6">
                                                        <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['gst_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['gst_image'];?>" style="height:100px;">	</a>
                                                    </div>
                                                <?php 
                                                }
                                                ?>
                                            </div>
                                            <br>
                                            <div class="row">
                                                <div class="col-md-6"> 
                                                    <?php
                                                        echo $this->Form->input('agreement_image', ['type' => 'file','label' => 'Agreement Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                    ?>
                                                </div>
                                                <?php
                                                if($products[0]['inventory_vendors']['agreement_image']){
                                                ?>
                                                    <div class="col-sm-6">
                                                        <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['agreement_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['agreement_image'];?>" style="height:100px;">	</a>
                                                    </div>
                                                <?php 
                                                }
                                                ?>
                                            </div>
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
                                                'required'=>'required'
                                                ,'value' =>$products[0]['inventory_vendors']['mobile']));
                                                echo $this->Form->input('landline', array('div'=>array('class'=>'form-group'),'type'=>'number', 'label'=>'Landline', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['landline'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('state', [
                                                    'type'     => 'select',
                                                    'options'  => $indiaStates,
                                                    'empty'    => 'Select State',
                                                    'required' => true,
                                                    'class'    => 'form-control',
                                                    'value'    => $products[0]['inventory_vendors']['state'],
                                                    'label'    => 'State',
                                                    'div'      => ['class' => 'form-group'],
                                                    'between'  => '<span class="desc"></span><div class="controls">',
                                                    'after'    => '</div>'
                                                ]);
                                                echo $this->Form->input('pan', array('div'=>array('class'=>'form-group'), 'label'=>'PAN','required'=>'required', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['pan'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('tan', array('div'=>array('class'=>'form-group'), 'label'=>'TAN','required'=>'required', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['tan'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('ifsc', array('div'=>array('class'=>'form-group'), 'label'=>'IFSC','required'=>'required', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['ifsc'], 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('payment_days', array('div'=>array('class'=>'form-group'),'type'=>'number','required'=>'required', 'label'=>'Payment Days','value' =>$products[0]['inventory_vendors']['payment_days'], 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));

                                                $selectedareaIds = []; // Array to hold selected user IDs
                                                
                                                // Collecting selected user IDs from task assignments
                                                foreach ($vendor_areas as $vendor_area) {
                                                    if (!empty($vendor_area['Inventory_vendor_service_area']['area_id'])) {
                                                        $selectedareaIds[] = $vendor_area['Inventory_vendor_service_area']['area_id'];
                                                    }
                                                }
                                                
                                                // Creating options for employees not in selectedUserIds
                                                foreach ($areas as $area) {
                                                    $ID = $area['Location']['id'];
                                                    $Name = $area['Location']['name'];
                                                    $options[$ID] = $Name;
                                                }
                                                
                                                // Rendering the checkboxes with the correct ones checked
                                                echo "<h5>Service Areas</h5>
                                                <div class='row'  style='margin-left:1%;'>";
                                                    foreach ($areas as $area) {
                                                        $ID = $area['Location']['id'];
                                                        $Name = $area['Location']['name'];
                                                        $isChecked = in_array($ID, $selectedareaIds, true); // strict check
                                                        
                                                        ?>
                                                        <div class="col-md-3">
                                                            <?php
                                                                echo $this->Form->input('area_'.$ID, array('label'=>$Name,'value' => $ID,'checked' => $isChecked,'type'=>'checkbox','div' => false,
                'class' => 'form-check-input',
                'labelOptions' => ['class' => 'form-check-label']));
                                                            ?>
                                                        </div>
                                                        <?php
                                                    }    
                                                ?>
                                                </div>
                                                <br>
                                                <div class="row">
                                                    <div class="col-md-6"> 
                                                        <?php
                                                            echo $this->Form->input('pan_image', ['type' => 'file','label' => 'PAN Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                        ?>
                                                    </div>
                                                    <?php
                                                    if($products[0]['inventory_vendors']['pan_image']){
                                                    ?>
                                                        <div class="col-sm-6">
                                                            <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['pan_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['pan_image'];?>" style="height:100px;">	</a>
                                                        </div>
                                                    <?php 
                                                    }
                                                    ?>
                                                </div>
                                                
                                                <br>
                                                <div class="row">
                                                    <div class="col-md-6"> 
                                                        <?php
                                                            echo $this->Form->input('tan_image', ['type' => 'file','label' => 'TAN Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                        ?>
                                                    </div>
                                                    <?php
                                                    if($products[0]['inventory_vendors']['tan_image']){
                                                    ?>
                                                        <div class="col-sm-6">
                                                            <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['tan_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['tan_image'];?>" style="height:100px;">	</a>
                                                        </div>
                                                    <?php 
                                                    }
                                                    ?>
                                                </div>
                                                <br>
                                                <div class="row">
                                                    <div class="col-md-6"> 
                                                        <?php
                                                            echo $this->Form->input('fssai_image', ['type' => 'file','label' => 'FSSAI Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                        ?>
                                                    </div>
                                                    <?php
                                                    if($products[0]['inventory_vendors']['fssai_image']){
                                                    ?>
                                                        <div class="col-sm-6">
                                                            <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['fssai_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['fssai_image'];?>" style="height:100px;">	</a>
                                                        </div>
                                                    <?php 
                                                    }
                                                    ?>
                                                </div>
                                                        
                                        
                                        </div>
                                        
                                    </div>
                                    <a href="vendor_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Update', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>

<script>
    $(document).ready(function(){
   
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
            alert('Please select at least one Service Area.');
        }
    });



});

</script>                   