<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">View Vendor</h2>
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
                                    <div class="form-group row">
                                        <div class="col-sm-6">
                                            <?php 
                                            
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('email', array('div'=>array('class'=>'form-group'),'type'=>'email', 'label'=>'Email', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['email'],'readonly'=>'readonly'));
                                                echo $this->Form->input('office_address', array('div'=>array('class'=>'form-group'), 'label'=>'Office Address', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['office_address'],'readonly'=>'readonly'));
                                                echo $this->Form->input('company', array('div'=>array('class'=>'form-group'), 'label'=>'Company', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['company'],'readonly'=>'readonly'));
                                                echo $this->Form->input('gst', array('div'=>array('class'=>'form-group'), 'label'=>'GST', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['gst'],'readonly'=>'readonly'));
                                                echo $this->Form->input('bank_ac', array('div'=>array('class'=>'form-group'), 'label'=>'Bank Account No.', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['bank_ac'],'readonly'=>'readonly'));
                                                echo $this->Form->input('bank_name', array('div'=>array('class'=>'form-group'), 'label'=>'Bank Name', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['bank_name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('advance_percentage', array('div'=>array('class'=>'form-group'),'type'=>'number', 'class'=>'form-control', 'label'=>'Advance Payment(%)','value' =>$products[0]['inventory_vendors']['advance_percentage'],'readonly'=>'readonly'));
                                            ?>
                                        </div>
                                        <div class="col-sm-6">
                                            <?php 
                                            
                                                echo $this->Form->input('mobile',array('div'=>array('class'=>'form-group'), 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['mobile'],'readonly'=>'readonly'));
                                                echo $this->Form->input('landline', array('div'=>array('class'=>'form-group'),'type'=>'number', 'label'=>'Landline', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['landline'],'readonly'=>'readonly'));
                                                echo $this->Form->input('state', array('div'=>array('class'=>'form-group'), 'label'=>'State', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['state'],'readonly'=>'readonly'));
                                                echo $this->Form->input('pan', array('div'=>array('class'=>'form-group'), 'label'=>'PAN', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['pan'],'readonly'=>'readonly'));
                                                echo $this->Form->input('tan', array('div'=>array('class'=>'form-group'), 'label'=>'TAN', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['tan'],'readonly'=>'readonly'));
                                                echo $this->Form->input('ifsc', array('div'=>array('class'=>'form-group'), 'label'=>'IFSC', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['ifsc'],'readonly'=>'readonly'));
                                                echo $this->Form->input('payment_days', array('div'=>array('class'=>'form-group'),'type'=>'number', 'label'=>'Payment Days', 'class'=>'form-control','value' =>$products[0]['inventory_vendors']['payment_days'],'readonly'=>'readonly'));

                                                $areaoptions=[];
                                                foreach($areas as $area){
                                                    $areaoptions[$area['inventory_vendor_service_areas']['id']] = $area['inventory_vendor_service_areas']['area_name'];
                                                }
                                                echo $this->Form->input('area', array('label'=>'Selected Areas', 'class'=>'form-control','options'=>$areaoptions, 'multiple'=>true,'readonly'=>'readonly'));
                                        ?>
                                        </div>
                                        <?php
                                                if($products[0]['inventory_vendors']['pan_image']){
                                                ?>
                                                <div class="col-sm-6">
                                                    <label>PAN Image</label><br>
                                                    <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['pan_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['pan_image'];?>" style="height:150px;">	</a>
                                                </div>
                                                <?php 
                                                } 
                                                
                                                  if($products[0]['inventory_vendors']['gst_image']){
                                            ?>
                                                <div class="col-sm-6">
                                                    
                                                    <label>GST Image</label><br>
                                                    <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['gst_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['gst_image'];?>" style="height:150px;">	</a>
                                                </div>
                                            <?php 
                                                } 
                                                
                                                  if($products[0]['inventory_vendors']['tan_image']){
                                            ?>
                                            <div class="col-sm-6">
                                        
                                                    <label>TAN Image</label><br>
                                                <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['tan_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['tan_image'];?>" style="height:150px;">	</a>
                                            </div>
                                            <?php 
                                                } 
                                                  if($products[0]['inventory_vendors']['agreement_image']){
                                            ?>
                                                <div class="col-sm-6">
                                                    
                                                    <label>Agreement Image</label><br>
                                                    <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['agreement_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['agreement_image'];?>" style="height:150px;">	</a>
                                                </div>
                                            <?php 
                                                } 
                                            if($products[0]['inventory_vendors']['fssai_image']){
                                            ?>
                                                <div class="col-sm-6">
                                                    
                                                    <label>FSSAI Image</label><br>
                                                    <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['fssai_image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'vendor_images' . DS . $products[0]['inventory_vendors']['fssai_image'];?>" style="height:150px;">	</a>
                                                </div>
                                            <?php 
                                                } 
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
