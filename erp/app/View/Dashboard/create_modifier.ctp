<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Modifier</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="modifiers"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('User',['type' => 'file']); ?>
                                   
                                    <div class="form-group row">
                                        
                                        <div class="col-sm-8">
                                            <?php 
                                            
                                            echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Title', 'class'=>'form-control','required'=>'required'));
                                            echo $this->Form->input('description', array('div'=>array('class'=>'form-group'), 'label'=>'Description', 'class'=>'form-control','rows'=>3));
                                            echo $this->Form->input('food_type', array('div'=>array('class'=>'form-group'), 'label'=>'Food type', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>array('0'=>'Veg','1'=>'Non-Veg')));
                                            echo $this->Form->input('sort_order', array('div'=>array('class'=>'form-group'), 'label'=>'Sort Order','value'=>'0', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','type'=>'number'));
                                            echo $this->Form->input('price', array('div'=>array('class'=>'form-group'), 'label'=>'Default Sales Price','value'=>'0', 'class'=>'form-control','required'=>'required','type'=>'number','step' => '0.01'));
                                            echo $this->Form->input('weight', array('div'=>array('class'=>'form-group'), 'label'=>'Weight', 'class'=>'form-control','type'=>'number'));
                                        ?>
                                        </div>
                                    </div>
                                    <a href="modifiers"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
                 