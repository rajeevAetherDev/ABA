<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Update Category</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                          <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="inv_category_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                                echo $this->Form->input('id', array( 'value'=>$categories[0]['inventory_category']['id'],'type'=>'hidden'));
                                                echo $this->Form->input('name', array( 'label'=>'Category Name', 'class'=>'form-control','value'=>$categories[0]['inventory_category']['name'],'required'=>true));
                                                echo "<br>Category Image<br>";
                                                if($categories[0]['inventory_category']['image']){
                                            ?>
                                                <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $categories[0]['catalog_category']['image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $categories[0]['inventory_category']['image'];?>" style="height:150px;">	</a>
                                            <?php }
                                            echo $this->Form->input('image', ['type' => 'file','label' => 'Upload New Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                                
                                            echo "<br><br>";
                                            foreach($subcategories as $subcategory){
                                                $id=$subcategory['inventory_subcategory']['id'];
                                            ?>
                                            
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label>Sub Category Name</label>
                                                        <input type="text" class="form-control" value="<?php echo $subcategory['inventory_subcategory']['name'];?>" disabled="" name="subcat[]">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label>Sub Category Image</label>
                                                        <?php if($subcategory['inventory_subcategory']['image']){ ?>
                                                            <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $subcategory['inventory_subcategory']['image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $subcategory['inventory_subcategory']['image'];?>" style="height:100px;">	</a>
                                                        <?php }else{ 
                                                            echo "<br>N/A";
                                                        }?>
                                                    </div>
                                                    <div class="col-md-2"><br><button class="btn btn-danger mark-remove" data-id='<?php echo $id;?>' type="button" style="float:right;">Remove</button></div>
                                                </div>
                                                
                                            </div>
                                            <br>        
                                            <?php 
                                            }
                                            ?>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <button id="addsubtask" class='btn btn-info' type="button" style="float:right;">Add Sub Category</button>
                                                </div>
                                            </div>
                                            <br><br>
                                            <div id="subtaskInputs">
                                                
                                            </div>
                                        </div>
                                    </div>
                                    <a href="inv_category_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                    
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>

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
    
    $(document).on('click', '.mark-remove', function() {
        var id = $(this).data('id');
        var button = $(this);
// alert(id);
        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'inv_remove_subcategory']); ?>',
            type: 'POST',
            data: { _id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // alert('Status Updated');
//                    location.reload();
                    button.text('Removed');
                } else {
                    alert('Failed to update status');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while removing');
                console.log(xhr.responseText);
            }
        });
    });
    
});

</script>                   