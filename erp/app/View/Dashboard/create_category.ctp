<style>
  .radio-inline label {
    display: inline-block; /* Ensure the labels are displayed inline */
    margin-right: 10px; /* Adjust spacing between radio buttons and labels */
}

</style>
<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Category</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="category_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','required'=>'required'));
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
                                    <a href="category_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
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
    $('#addsubtask').click(function(){
        var newInput = $('<div class="form-group"><div class="row"><div class="col-md-6">' +
                            '<label>Sub Category Name</label>' +
                            '<input type="text" class="form-control sub-task" required="required" name="subcat[]"></div>' +
                            '<div class="col-md-2"><br><button class="btn btn-danger removesubtask" type="button" style="float:right;">Remove</button></div>' +
                        '</div>');
        $('#subtaskInputs').append(newInput);
    });
    
    $(document).on('click', '.removesubtask', function(){
        $(this).closest('.form-group').remove();
    });
    
});

</script>                   