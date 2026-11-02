                    <div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Recipe</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="modifiers"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <?php echo $this->Form->create('User',['type' => 'file']); ?>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <?php 
                                                echo $this->Form->input('id', array('div'=>array('class'=>'form-group'),  'class'=>'form-control','type'=>'hidden','value'=>$products[0]['catalog_modifier']['id']));
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Modifier Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$products[0]['catalog_modifier']['name']));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" >
                        <div class="col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            <h4 class="page-title">Map Semi-Finished</h4>
                                            <?php
                                                foreach($recipes as $recipe){
                                                    $semi_id=$recipe['inventory_linking']['component_id'];
                                                    $semi_name=$recipe['inventory_linking']['component_name'];
                                                    $semi_quantity=$recipe['inventory_linking']['quantity'];
                                                    ?>
                                                    <div class="form-group  row">
                                                        <div class="col-md-7"> 
                                                            <?php
                                                                echo $this->Form->input('semi_id_'.$semi_id, array('div'=>array('class'=>'form-group'),'type'=>'hidden', 'class'=>'form-control','value'=>$semi_id));
                                                                echo $this->Form->input('semi_name_'.$semi_id, array('div'=>array('class'=>'form-group'), 'label'=>'Semifinished Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$semi_name));
                                                            ?>
                                                        </div>
                                                        <div class="col-md-3"> 
                                                            <?php    
                                                                echo $this->Form->input('semi_quantity_'.$semi_id, array('div'=>array('class'=>'form-group'), 'label'=>'Quantity', 'class'=>'form-control','type'=>'number','value'=>$semi_quantity,'step'=>'0.000000000001','required'=>true));
                                                            ?>
                                                        </div>
                                                        <div class="col-md-2"> <br>
                                                            <button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            ?>
                                        </div>
                                        <div class="col-md-12">
                                            <div id="semiInputs">
                                                <!-- Dynamically added SKU inputs will appear here -->
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button id="addsemi" class='btn btn-info' type="button" style="float:right;">Add Semi-Finished</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            <h4 class="page-title">Map Sku</h4>
                                            <?php
                                                foreach($skurecipes as $skurecipe){
                                                    $sku_id=$skurecipe['inventory_linking']['component_id'];
                                                    $sku_name=$skurecipe['inventory_linking']['component_name'];
                                                    $sku_quantity=$skurecipe['inventory_linking']['quantity'];
                                                    ?>
                                                    <div class="form-group  row">
                                                        <div class="col-md-7"> 
                                                            <?php
                                                                echo $this->Form->input('sku_id_'.$sku_id, array('div'=>array('class'=>'form-group'),'type'=>'hidden', 'class'=>'form-control','value'=>$sku_id));
                                                                echo $this->Form->input('sku_name_'.$sku_id, array('div'=>array('class'=>'form-group'), 'label'=>'Sku Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$sku_name));
                                                            ?>
                                                        </div>
                                                        <div class="col-md-3"> 
                                                            <?php    
                                                                echo $this->Form->input('sku_quantity_'.$sku_id, array('div'=>array('class'=>'form-group'), 'label'=>'Quantity', 'class'=>'form-control','type'=>'number','value'=>$sku_quantity,'step'=>'0.000000000001','required'=>true));
                                                            ?>
                                                        </div>
                                                        <div class="col-md-2"> <br>
                                                            <button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            ?>
                                        </div>
                                        <div class="col-md-12">
                                            <div id="skuInputs">
                                                <!-- Dynamically added SKU inputs will appear here -->
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button id="addsku" class='btn btn-info' type="button" style="float:right;">Add Sku</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" >
                        <div class="col-md-12" >
                            <?php echo '&nbsp'.$this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn','style'=>'float:right')); ?>
                            <a href="modifiers"><button  class='btn btn-danger' type="button" style="float:right;">Cancel</button></a>&nbsp
                            
                        </div>
                    </div><br>
                
<script>
$(document).ready(function(){
    
    $('#addsemi').click(function(){
        
        // Create new input fields
        var newInput = $('<div class="form-group"><div class="row">' +
                            '<div class="col-md-7"><label>Semifinished Name</label>' +
                            '<select class="form-control tax" required="required" name="select_semi_name[]"></select></div>' +
                            '<div class="col-md-3"><label>Quantity</label>' +
                            '<input type="number" class="form-control tax" required="required" name="semi_quantity[]" step="0.000000000001"></div>' +
                            '<div class="col-md-2"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
                        '</div>');
        $('#semiInputs').append(newInput);

        // Fetch tax options from the server
        $.ajax({
            url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_semifinished_options']); ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.response_code === 1) {
                    var Semifinished = newInput.find('select');
                    $.each(response.data, function(index, semifinished) {
                        Semifinished.append('<option value="' + semifinished.id + '">' + semifinished.name+'('+semifinished.unit_name+')' + '</option>');
                    });
                } else {
                    alert('Failed to fetch Semifinished options.');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while fetching Semifinished options: ' + error);
            }
        });
    });

    $('#addsku').click(function(){
        
        // Create new input fields
        var newInput1 = $('<div class="form-group"><div class="row">' +
                            '<div class="col-md-7"><label>Sku Name</label>' +
                            '<select class="form-control tax" required="required" name="select_sku_name[]"></select></div>' +
                            '<div class="col-md-3"><label>Quantity</label>' +
                            '<input type="number" class="form-control tax" required="required" name="sku_quantity[]" step="0.000000000001"></div>' +
                            '<div class="col-md-2"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
                        '</div>');
        $('#skuInputs').append(newInput1);

        // Fetch tax options from the server
        $.ajax({
            url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_sku_options']); ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.response_code === 1) {
                    var SkuSelect = newInput1.find('select');
                    $.each(response.data, function(index, sku) {
                        SkuSelect.append('<option value="' + sku.id + '">' + sku.name+'('+sku.unit_name+')' + '</option>');
                    });
                } else {
                    alert('Failed to fetch sku options.');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while fetching Sku options: ' + error);
            }
        });
    });

    // Remove tax input fields
    $(document).on('click', '.removetax', function(){
        $(this).closest('.form-group').remove();
    });
});

</script>                   