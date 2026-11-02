<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Waiter</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="waiter_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name', 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('phone', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Phone',
                                                    'class' => 'form-control',
                                                    'required' => 'required',
                                                    'type' => 'number',
                                                    'min' => '1000000000', // Minimum value for a 10-digit number
                                                    'max' => '9999999999', // Maximum value for a 10-digit number
                                                    'oninput' => 'this.value = this.value.slice(0, 10);' // Ensure input is no more than 10 digits
                                                ));
                                                $options=[''=>'Select location'];
                                                foreach($locations as $location){
                                                $options[$location['Location']['id']] = $location['Location']['name'];
                                                }
                                                echo $this->Form->input('location', array('div'=>array('class'=>'form-group'), 'label'=>'Select Location', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'location','required'=>true));
                                                echo $this->Form->input('pin', array('div'=>array('class'=>'form-group'), 'label'=>'Pin Number', 'class'=>'form-control','required'=>'required', 'type'=>'number'));
                                                echo $this->Form->input('pin_req', array('div'=>array('class'=>'form-group'), 'label'=>'Is Pin Required', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>array('0'=>'No','1'=>'Yes')));
                                            ?>
                                    </div>
                                    </div>
                                    <a href="waiter_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
        