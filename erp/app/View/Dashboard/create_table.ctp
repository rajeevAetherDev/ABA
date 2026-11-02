<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Table</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="table_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                                echo $this->Form->input('number', array('div'=>array('class'=>'form-group'), 'label'=>'Table Number', 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('cover_charge', array('div'=>array('class'=>'form-group'), 'label'=>'Cover Charge', 'class'=>'form-control','required'=>'required', 'step' => '0.1','type'=>'number'));
                                                echo $this->Form->input('weekend_charge', array('div'=>array('class'=>'form-group'), 'label'=>'Weekend Cover Charge', 'class'=>'form-control','required'=>'required', 'step' => '0.1','type'=>'number'));
                                                echo $this->Form->input('pax', array('div'=>array('class'=>'form-group'), 'label'=>'PAX', 'class'=>'form-control','required'=>'required', 'step' => '0.1','type'=>'number'));
                                                $options=[''=>'Select location'];
                                                foreach($locations as $location){
                                                $options[$location['Location']['id']] = $location['Location']['name'];
                                                }
                                                echo $this->Form->input('location', array('div'=>array('class'=>'form-group'), 'label'=>'Select Location', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'location','required'=>true));

                                                $category_options = ['' => 'Select Table Category'];
                                                if (!empty($table_categories)) {
                                                    foreach ($table_categories as $tcat) {
                                                        $category_options[$tcat['Table_category']['id']] = $tcat['Table_category']['name'];
                                                    }
                                                }
                                                echo $this->Form->input('table_category_id', array('div'=>array('class'=>'form-group'), 'label'=>'Select Table Category *', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$category_options,'id'=>'table_category_id','required'=>true));
                                                echo $this->Form->input('waiter', array('div'=>array('class'=>'form-group'), 'label'=>'Is Waiter Required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>array('0'=>'No','1'=>'Yes')));
                                                echo $this->Form->input('customer', array('div'=>array('class'=>'form-group'), 'label'=>'Is Customer Detail Required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>array('0'=>'No','1'=>'Yes')));
                                                
?>
                                    </div>
                                    </div>
                                    <a href="table_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
                 