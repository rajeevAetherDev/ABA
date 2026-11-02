<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Recipe</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="product_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                            echo $this->Form->input('id', ['div'=>['class'=>'form-group'], 'class'=>'form-control','type'=>'hidden','value'=>$products[0]['catalog_product']['id']]);
                            echo $this->Form->input('unit', ['div'=>['class'=>'form-group'], 'class'=>'form-control','type'=>'hidden','value'=>$products[0]['catalog_product']['unit']]);
                            echo $this->Form->input('unit_name', ['div'=>['class'=>'form-group'], 'class'=>'form-control','type'=>'hidden','value'=>$products[0]['catalog_product']['unit_name']]);
                            echo $this->Form->input('name', ['div'=>['class'=>'form-group'], 'label'=>'Product Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$products[0]['catalog_product']['name']]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" >
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-12">
                        <h4 class="page-title">Map Semi-Finished</h4>
                        <?php
                            foreach($recipes as $recipe){
                                $semi_id = $recipe['inventory_linking']['component_id'];
                                $semi_name = $recipe['inventory_linking']['component_name'];
                                $semi_quantity = $recipe['inventory_linking']['conversion_quantity'];
                                $semi_unit_name = $recipe['inventory_linking']['component_unit_name'];
                                $semi_price = $recipe['inventory_linking']['component_price'];
                        ?>
                        <?php
                                $semi_live_price = isset($recipe[0]['live_price']) ? $recipe[0]['live_price'] : 0;
                                $semi_line_cost  = isset($recipe[0]['line_cost'])  ? $recipe[0]['line_cost']  : 0;
                        ?>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <?php
                                    echo $this->Form->input('semi_id_'.$semi_id, ['div'=>['class'=>'form-group'], 'type'=>'hidden', 'class'=>'form-control','value'=>$semi_id]);
                                    echo $this->Form->input('semi_name_'.$semi_id, ['div'=>['class'=>'form-group'], 'label'=>'Semifinished Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$semi_name]);
                                ?>
                            </div>
                            <div class="col-md-2">
                                <?php
                                    echo $this->Form->input('semi_unit_'.$semi_id, ['div'=>['class'=>'form-group'], 'label'=>'Unit', 'class'=>'form-control','readonly'=>true,'value'=>$semi_unit_name]);
                                ?>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group"><label>Unit Cost</label>
                                    <input type="text" class="form-control" readonly value="<?php echo number_format($semi_live_price, 4); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <?php    
                                    echo $this->Form->input('semi_quantity_'.$semi_id, ['div'=>['class'=>'form-group'], 'label'=>'Qty ('.$semi_unit_name.')', 'class'=>'form-control','type'=>'number','value'=>$semi_quantity,'step'=>'0.0000001','required'=>true]);
                                ?>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group"><label>Line Cost (&#8377;)</label>
                                    <input type="text" class="form-control" readonly value="<?php echo number_format($semi_line_cost, 2); ?>" style="font-weight:bold;">
                                </div>
                            </div>
                            <div class="col-md-1"><br>
                                <button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button>
                            </div>
                        </div>
                        <?php
                            }
                        ?>
                    </div>

                    <div class="col-md-12">
                        <div id="semiInputs">
                            <!-- Dynamically added Semi-Finished inputs will appear here -->
                        </div>
                    </div>

                    <div class="col-md-12">
                        <button id="addsemi" class='btn btn-info' type="button" style="float:right;">Add Semi-Finished</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-12">
                        <h4 class="page-title">Map Sku</h4>
                        <?php
                            foreach($skurecipes as $skurecipe){
                                $sku_id = $skurecipe['inventory_linking']['component_id'];
                                $sku_name = $skurecipe['inventory_linking']['component_name'];
                                $sku_quantity = $skurecipe['inventory_linking']['conversion_quantity'];
                                $sku_unit_name = $skurecipe['inventory_linking']['component_unit_name'];
                                $sku_price = $skurecipe['inventory_linking']['component_price'];
                        ?>
                        <?php
                                $sku_live_price = isset($skurecipe[0]['live_price']) ? $skurecipe[0]['live_price'] : 0;
                                $sku_line_cost  = isset($skurecipe[0]['line_cost'])  ? $skurecipe[0]['line_cost']  : 0;
                        ?>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <?php
                                    echo $this->Form->input('sku_id_'.$sku_id, ['div'=>['class'=>'form-group'], 'type'=>'hidden', 'class'=>'form-control','value'=>$sku_id]);
                                    echo $this->Form->input('sku_name_'.$sku_id, ['div'=>['class'=>'form-group'], 'label'=>'Sku Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$sku_name]);
                                ?>
                            </div>
                            <div class="col-md-2">
                                <?php
                                    echo $this->Form->input('sku_unit_'.$sku_id, ['div'=>['class'=>'form-group'], 'label'=>'Unit', 'class'=>'form-control','readonly'=>true,'value'=>$sku_unit_name]);
                                ?>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group"><label>Unit Cost</label>
                                    <input type="text" class="form-control" readonly value="<?php echo number_format($sku_live_price, 4); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <?php
                                    echo $this->Form->input('sku_quantity_'.$sku_id, ['div'=>['class'=>'form-group'], 'label'=>'Qty ('.$sku_unit_name.')', 'class'=>'form-control','type'=>'number','value'=>$sku_quantity,'step'=>'0.0000001','required'=>true]);
                                ?>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group"><label>Line Cost (&#8377;)</label>
                                    <input type="text" class="form-control" readonly value="<?php echo number_format($sku_line_cost, 2); ?>" style="font-weight:bold;">
                                </div>
                            </div>
                            <div class="col-md-1"><br>
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

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                    $total_cost = isset($recipe_total_cost[0][0]['total_cost']) ? $recipe_total_cost[0][0]['total_cost'] : 0;
                    $selling_price = $products[0]['catalog_product']['price'];
                    $margin_val = $selling_price > 0 ? $selling_price - $total_cost : 0;
                    $margin_pct = ($selling_price > 0 && $total_cost > 0) ? round($total_cost / $selling_price * 100, 1) : 0;
                    $cost_flag = ($total_cost > $selling_price && $selling_price > 0) ? 'color:red;font-weight:bold' : '';
                ?>
                <table class="table table-condensed" style="max-width:400px">
                    <tr><th>Selling Price</th><td>&#8377;<?php echo number_format($selling_price, 2); ?></td></tr>
                    <tr><th>Total Recipe Cost</th><td style="<?php echo $cost_flag; ?>">&#8377;<?php echo number_format($total_cost, 2); ?></td></tr>
                    <tr><th>Margin (&#8377;)</th><td style="<?php echo $cost_flag; ?>">&#8377;<?php echo number_format($margin_val, 2); ?></td></tr>
                    <tr><th>Cost %</th><td style="<?php echo $cost_flag; ?>"><?php echo $margin_pct; ?>%</td></tr>
                </table>
                <?php if($cost_flag): ?>
                    <div class="alert alert-warning">&#9888; Recipe cost exceeds selling price. Check ingredient prices or recipe quantities.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="row" >
    <div class="col-md-12" >
        <?php echo '&nbsp'.$this->Form->button('Save', ['class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn','style'=>'float:right']); ?>
        <a href="product_list"><button  class='btn btn-danger' type="button" style="float:right;">Cancel</button></a>&nbsp
    </div>
</div><br>

   
<script>
$(document).ready(function(){
    var semifinishedData = {};
    var skuData = {};

    $('#addsemi').click(function(){
        var newInput = $('<div class="form-group"><div class="row">' +
            '<div class="col-md-5"><label>Semifinished Name</label>' +
            '<select class="form-control semi-select" required name="select_semi_name[]">' +
            '<option value="">--Select--</option></select></div>' +
            '<div class="col-md-2"><label>Unit</label>' +
            '<input type="text" class="form-control semi-unit" readonly></div>' +
            '<div class="col-md-2"><label>Price</label>' +
            '<input type="text" class="form-control semi-price" readonly></div>' +
            '<div class="col-md-2"><label>Quantity</label>' +
            '<input type="number" class="form-control semi-quantity" required name="semi_quantity[]" step="0.0000001"></div>' +
            '<div class="col-md-1"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
            '</div></div>');
        $('#semiInputs').append(newInput);

        if($.isEmptyObject(semifinishedData)){
            $.ajax({
                url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_semifinished_options']); ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response){
                    if(response.response_code === 1){
                        semifinishedData = {};
                        $.each(response.data, function(i, item){
                            semifinishedData[item.id] = item;
                        });
                        var select = newInput.find('select');
                        $.each(semifinishedData, function(id, item){
                            select.append('<option value="'+id+'">'+item.name+'</option>');
                        });
                    } else {
                        alert('Failed to fetch Semifinished options.');
                    }
                },
                error: function(xhr, status, error){
                    alert('Error fetching Semifinished options: '+error);
                }
            });
        } else {
            var select = newInput.find('select');
            $.each(semifinishedData, function(id, item){
                select.append('<option value="'+id+'">'+item.name+'</option>');
            });
        }
    });

    $(document).on('change', '.semi-select', function(){
        var selectedId = $(this).val();
        var container = $(this).closest('.form-group');
        if(semifinishedData[selectedId]){
            container.find('.semi-unit').val(semifinishedData[selectedId].consumption_unit_name);
            container.find('.semi-price').val(semifinishedData[selectedId].consumption_unit_price);
        } else {
            container.find('.semi-unit').val('');
            container.find('.semi-price').val('');
        }
    });

    $('#addsku').click(function(){
        var newInput = $('<div class="form-group"><div class="row">' +
            '<div class="col-md-5"><label>Sku Name</label>' +
            '<select class="form-control sku-select" required name="select_sku_name[]">' +
            '<option value="">--Select--</option></select></div>' +
            '<div class="col-md-2"><label>Unit</label>' +
            '<input type="text" class="form-control sku-unit" readonly></div>' +
            '<div class="col-md-2"><label>Price</label>' +
            '<input type="text" class="form-control sku-price" readonly></div>' +
            '<div class="col-md-2"><label>Quantity</label>' +
            '<input type="number" class="form-control sku-quantity" required name="sku_quantity[]" step="0.0000001"></div>' +
            '<div class="col-md-1"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
            '</div></div>');
        $('#skuInputs').append(newInput);

        if($.isEmptyObject(skuData)){
            $.ajax({
                url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_sku_options']); ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response){
                    if(response.response_code === 1){
                        skuData = {};
                        $.each(response.data, function(i, item){
                            skuData[item.id] = item;
                        });
                        var select = newInput.find('select');
                        $.each(skuData, function(id, item){
                            select.append('<option value="'+id+'">'+item.name+'</option>');
                        });
                    } else {
                        alert('Failed to fetch SKU options.');
                    }
                },
                error: function(xhr, status, error){
                    alert('Error fetching SKU options: '+error);
                }
            });
        } else {
            var select = newInput.find('select');
            $.each(skuData, function(id, item){
                select.append('<option value="'+id+'">'+item.name+'</option>');
            });
        }
    });

    $(document).on('change', '.sku-select', function(){
        var selectedId = $(this).val();
        var container = $(this).closest('.form-group');
        if(skuData[selectedId]){
            container.find('.sku-unit').val(skuData[selectedId].consumption_unit_name);
            container.find('.sku-price').val(skuData[selectedId].consumption_unit_price);
        } else {
            container.find('.sku-unit').val('');
            container.find('.sku-price').val('');
        }
    });

    $(document).on('click', '.removetax', function(){
        $(this).closest('.form-group').remove();
    });
});
</script>