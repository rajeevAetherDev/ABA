<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Sub Category</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="inv_subcategory_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                                $options=[''=>'Select Category'];
                                                foreach($categories as $category){
                                                    $options[$category['Inventory_category']['id']] = $category['Inventory_category']['name'];
                                                }
                                                echo $this->Form->input('category', array('div'=>array('class'=>'form-group'), 'label'=>'Select Category', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'category','required'=>true));
                                                
                                                echo $this->Form->input('subcat[]', array('div'=>array('class'=>'form-group'), 'label'=>'Sub Category Name', 'class'=>'form-control','required'=>'required','name'=>'subcat[]'));
                                                echo $this->Form->input('image', ['type' => 'file','label' => 'Upload Image','class' => 'form-control','div' => ['class' => 'form-group']]);
?>
<div class="row">
                                                    <div class="col-md-12">
                                                        <button id="addsubtask" class='btn btn-info' type="button" style="float:right;">Add Sub Category</button>
                                                    </div>
                                                    <!--<div class="col-md-2">-->
                                                    <!--    <button id="removesubtask" class='btn btn-danger hidden' type="button" style="float:right;">Remove</button>-->
                                                    <!--</div>-->
                                                </div>
                                                <br><br>
                                                <div id="subtaskInputs">
                                                    
                                                </div>
                                        </div>
                                    </div>
                                    <a href="inv_subcategory_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>

<script>
    $(document).ready(function(){
    $('#addsubtask').click(function(){
        var newInput = $('<div class="form-group">' +
                            '<label>Sub Category Name</label>' +
                            '<input type="text" class="form-control sub-task" required="required" name="subcat[]">' +
                            '<button class="btn btn-danger removesubtask" type="button" style="float:right;">Remove</button>' +
                        '</div>');
        $('#subtaskInputs').append(newInput);
    });
    
    $(document).on('click', '.removesubtask', function(){
        $(this).closest('.form-group').remove();
    });
    
});

</script>                   