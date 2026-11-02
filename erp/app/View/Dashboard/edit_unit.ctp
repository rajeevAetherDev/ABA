<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Update Unit</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="unit_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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

                                                echo $this->Form->input('id', array('type'=>'hidden','value'=>$taxes[0]['Catalog_unit']['id']));
                                                echo $this->Form->input('name', array( 'class'=>'form-control','required'=>'required','value'=>$taxes[0]['Catalog_unit']['name']));
                                            ?>
                                    </div>
                                    </div>
                                    <a href="unit_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
                 