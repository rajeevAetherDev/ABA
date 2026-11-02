<style>
  .radio-inline label {
    display: inline-block; /* Ensure the labels are displayed inline */
    margin-right: 10px; /* Adjust spacing between radio buttons and labels */
}

</style>
<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Category Details</h2>
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
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('name', array( 'label'=>'Category Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$categories[0]['catalog_category']['name']));
                                                echo "<br>Category Image<br>";
                                                if($categories[0]['catalog_category']['image']){
                                            ?>
                                                <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $categories[0]['catalog_category']['image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $categories[0]['catalog_category']['image'];?>" style="height:150px;">	</a>
                                            <?php }else{
                                                echo "<br>N/A";
                                            }
                                            echo "<br><br>";
                                            foreach($subcategories as $subcategory){
                                            ?>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Sub Category Name</label>
                                                    <input type="text" class="form-control" value="<?php echo $subcategory['catalog_subcategory']['name'];?>" disabled="" >
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Sub Category Image</label>
                                                    <?php if($subcategory['catalog_subcategory']['image']){ ?>
                                                        <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $subcategory['catalog_subcategory']['image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $subcategory['catalog_subcategory']['image'];?>" style="height:100px;">	</a>
                                                    <?php }else{ 
                                                        echo "<br>N/A";
                                                    }?>
                                                </div>
                                            </div>
                                            <br>        
                                            <?php 
                                            }
                                            ?>
                                            
                                        </div>
                                    </div>
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