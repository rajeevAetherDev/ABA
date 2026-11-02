<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Product Group Modifiers</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="product_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                            
                                                echo $this->Form->input('id', array('div'=>array('class'=>'form-group'),  'class'=>'form-control','type'=>'hidden','value'=>$products[0]['catalog_product']['id']));
                                                echo $this->Form->input('unit', array('div'=>array('class'=>'form-group'),  'class'=>'form-control','type'=>'hidden','value'=>$products[0]['catalog_product']['unit']));
                                                echo $this->Form->input('unit_name', array('div'=>array('class'=>'form-group'),  'class'=>'form-control','type'=>'hidden','value'=>$products[0]['catalog_product']['unit_name']));
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Product Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$products[0]['catalog_product']['name']));
                                            ?>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-8">
                                            <?php foreach ($modifiers as $modifier): 
                                                $modifier_group_id = $modifier['inventory_linking']['component_id'];
                                                $modifier_group_name = $modifier['inventory_linking']['component_name'];
                                            ?>
                                                <div class="form-group  row">
                                                    <div class="col-md-7"> 
                                                        <?php
                                                            echo $this->Form->input('semi_id_'.$modifier_group_id, array('div'=>array('class'=>'form-group'),'type'=>'hidden', 'class'=>'form-control','value'=>$modifier_group_id));
                                                            echo $this->Form->input('semi_name_'.$modifier_group_id, array('div'=>array('class'=>'form-group'), 'label'=>'Modifier Group Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$modifier_group_name));
                                                        ?>
                                                    </div>
                                                    <div class="col-md-2"> <br>
                                                        <button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                    
                                            <!-- Add Button -->
                                            <button id="addsku" class="btn btn-info" type="button" style="float:right;">Add Modifiers</button>
                                        </div>
                    
                                        <!-- New Modifier Group Inputs -->
                                        <div class="col-md-8">
                                            <div id="skuInputs">
                                                <!-- Dynamic modifier group options will be appended here -->
                                            </div>
                                        </div>
                                    </div>

                                    <a href="product_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
<script>
$(document).ready(function () {
    $('#addsku').click(function () {
        var newInput = $('<div class="form-group"><div class="row">' +
            '<div class="col-md-7"><label>Modifier Group Name</label>' +
            '<select class="form-control" required name="select_sku_name[]">' +
            '<option value="">Select Modifier</option>' +
            '</select></div>' +
            '<div class="col-md-2"><br>' +
            '<button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button>' +
            '</div></div></div>');

        $('#skuInputs').append(newInput);

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_modifiers_group_options']); ?>',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.response_code === 1) {
                    var select = newInput.find('select');
                    $.each(response.data, function (index, item) {
                        select.append('<option value="' + item.id + '">' + item.name + '</option>');
                    });
                } else {
                    alert('Failed to fetch modifier group options.');
                }
            },
            error: function (xhr, status, error) {
                alert('Error fetching modifier groups: ' + error);
            }
        });
    });

    // Remove Row
    $(document).on('click', '.removetax', function () {
        $(this).closest('.form-group').remove();
    });
});
</script>

             