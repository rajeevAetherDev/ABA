<style>
    .radio-inline label {
        display: inline-block; /* Ensure the labels are displayed inline */
        margin-right: 10px; /* Adjust spacing between radio buttons and labels */
    }
</style>
<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Update Sub Category</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="subcategory_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                echo $this->Form->input('id', array('type'=>'hidden','value'=>$subcategories[0]['catalog_subcategory']['id']));
                                echo $this->Form->input('name', array( 'label'=>'Sub Category Name', 'class'=>'form-control','required'=>'required','value'=>$subcategories[0]['catalog_subcategory']['name']));
    
                                $options = [];
                                $selectedcatId = $subcategories[0]['catalog_subcategory']['cat_id']; 
                                
                                $selectedcategory = null;
                                foreach ($categories as $category) {
                                    if ($category['Catalog_category']['id'] == $selectedcatId) {
                                        $selectedcategory = $category['Catalog_category'];
                                    } else {
                                        $options[$category['Catalog_category']['id']] = $category['Catalog_category']['name'];
                                    }
                                }
    
                                if ($selectedcategory) {
                                    $options = array($selectedcategory['id'] => $selectedcategory['name']) + $options;
                                }
                                
                                echo '<br>'.$this->Form->input('category', array(
                                    'div' => array('class' => 'form-group'),
                                    'label' => 'Category',
                                    'class' => 'form-control',
                                    'between' => '<span class="desc"></span><div class="controls">',
                                    'after' => '</div>',
                                    'options' => $options,
                                    'default' => $selectedcatId
                                ));
    
                                                    
                                
                                echo $this->Form->input('image', ['type' => 'file','label' => 'Sub Category Image','class' => 'form-control','div' => ['class' => 'form-group']]);
                                if($subcategories[0]['catalog_subcategory']['image']){?>
                                    <a href="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $subcategories[0]['catalog_subcategory']['image'];?>" target="_blank" ><img src="<?php echo Router::url('/', true) . DS . 'uploads' . DS . 'category_images' . DS . $subcategories[0]['catalog_subcategory']['image'];?>" style="height:150px;">	</a>
                                <?php }
                                            
                            ?>
                        </div>
                    </div>
                    <a href="subcategory_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
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