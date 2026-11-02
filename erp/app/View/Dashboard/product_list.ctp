<!--<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Product List</h2>
        </div>
    </div>
    <div class="col-sm-2 text-end"> 
        <div class="page-title-box">
            <a href="create_product" class="btn btn-success">Add Product</a>
            <a href="recalculate_food_costs" class="btn btn-warning ms-1" onclick="return confirm('Recalculate food costs for all products and semi-finished items?')">&#8635; Recalculate Food Costs</a>
            <button type="button" id="exportRecipeBtn" class="btn btn-primary ms-1">&#8659; Export Recipe Costing</button>
            <a href="<?= Router::url(['controller'=>'Dashboard','action'=>'product_list_export']) ?>" class="btn btn-success ms-1">&#8659; Export Products</a>
        </div>
    </div>
</div>
-->
<div class="row align-items-center mb-2">
    <div class="col-sm-4">
        <div class="page-title-box">
            <h2 class="page-title mb-0">Product List</h2>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="d-flex justify-content-end align-items-center flex-nowrap gap-2">
            <a href="create_product" class="btn btn-success btn-sm text-nowrap">Add Product</a>
            <a href="recipe_integrity" class="btn btn-danger btn-sm  ms-1"><i class="fa fa-stethoscope"></i> Recipe Integrity</a>
            <a href="recalculate_food_costs" class="btn btn-warning btn-sm text-nowrap"
               onclick="return confirm('Recalculate food costs for all products and semi-finished items?')">
               &#8635; Recalculate Food Costs
            </a>
            <button type="button" id="exportRecipeBtn" class="btn btn-primary btn-sm text-nowrap">&#8659; Export Recipe Costing</button>
            <a href="<?= Router::url(['controller'=>'Dashboard','action'=>'product_list_export']) ?>" class="btn btn-success btn-sm text-nowrap">&#8659; Export Products</a>
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?= $this->Form->create('Search', ['url' => 'product_list']) ?>
                <div class="form-group">
                    <?= $this->Form->input('startdate', [
                        'label' => 'Select Date',
                        'class' => 'form-control calendarmultiple',
                        'div' => false,
                        'id' => 'startdate',
                        'name' => 'daterange',
                        'required' => true,
                        'value' => $startdate
                    ]) ?>
                    <br>
                    <?= $this->Form->button('View', ['class' => 'btn btn-info']) ?>
                </div>
                <?= $this->Form->end() ?>

                <div class="table-responsive mt-4">
                    <table id="example" class="display table table-hover table-condensed" width="100%">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" title="Select All"></th>
                                <th>#</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>SubCategory</th>
                                <th>Food Type</th>
                                <th>Recipe Cost</th>
                                <th>Price</th>
                                <th>Margin</th>
                                <th>Cost %</th>
                                <th>Discountable</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): 
                                $product = $user['catalog_product'];
                                $product['recipe_cost'] = isset($user[0]['recipe_cost']) ? $user[0]['recipe_cost'] : 0;
                                $id = $product['id'];
                                $status = $product['status'] ? 'Active' : 'Inactive';
                                $statusBtnClass = $product['status'] ? 'btn-danger' : 'btn-success';
                                $statusBtnText = $product['status'] ? 'Mark Inactive' : 'Mark Active';
                                
                                if($product['food_type']=='3'){
                                    $foodType='Alcoholic';
                                }elseif($product['food_type']=='2'){
                                    $foodType='Non-Alcoholic';
                                }elseif($product['food_type']=='1'){
                                    $foodType='Non-Veg';
                                }else{
                                    $foodType='Veg';
                                }
                                
                                // $foodtype = ['0' => 'Veg', '1' => 'Non-Veg', '2' => 'Non-Alcoholic', '3' => 'Alcoholic']  ?? 'N/A';
                                // $foodType = $product['food_type'] == 1 ? 'Non-Veg' : 'Veg';
                                // $package = $product['package'] ? 'Yes' : 'No';
                            ?>
                            <tr>
                                <td><input type="checkbox" class="product-check" value="<?= $id ?>"></td>
                                <td><?= $id ?></td>
                                <td><?= h($product['name']) ?></td>
                                <td><?= h($product['cat_name']) ?></td>
                                <td><?= h($product['subcat_name'] ?: 'N/A') ?></td>
                                <td><?= h($foodType) ?></td>
                                <td><?= number_format($product['recipe_cost'], 2) ?></td>
                                <td><?= h($product['price']) ?></td>
                                <td><?= ($product['price'] > 0 && $product['recipe_cost'] > 0) ? number_format($product['price'] - $product['recipe_cost'], 2) : '-' ?></td>
                                <td><?php
                                    if ($product['price'] > 0 && $product['recipe_cost'] > 0) {
                                        $cost_pct = round($product['recipe_cost'] / $product['price'] * 100, 1);
                                        $badge = $cost_pct > 100 ? 'badge-danger' : ($cost_pct > 60 ? 'badge-warning' : 'badge-success');
                                        echo '<span class="badge '.$badge.'">'.$cost_pct.'%</span>';
                                    } else { echo '-'; }
                                ?></td>
                                <td><?= (isset($product['is_discountable']) && $product['is_discountable'] == 1) ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-danger">No</span>' ?></td>
                                <td><?= h($status) ?></td>
                                <td><?= h($product['created']) ?></td>
                                <td class="text-nowrap">
                                    <a href="product_recipe?id=<?= $id ?>" class="btn btn-info btn-sm">Recipe</a>
                                    <a href="product_modifier?id=<?= $id ?>" class="btn btn-info btn-sm">Group Modifier</a>
                                    <?php if ($product['package']): ?>
                                        <a href="package?id=<?= $id ?>" class="btn btn-info btn-sm">Package</a>
                                    <?php endif; ?>
                                    <a href="edit_product?id=<?= $id ?>" class="btn btn-info btn-sm">Edit</a>
                                    <a href="view_product?id=<?= $id ?>" class="btn btn-info btn-sm">View</a>
                                    <button class="btn <?= $statusBtnClass ?> btn-sm mark-status" data-id="<?= $id ?>">
                                        <?= $statusBtnText ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Status toggle via AJAX -->

<!-- Recipe export form (submitted via JS) -->
<form id="recipeExportForm" method="POST" action="<?= Router::url(['controller'=>'Dashboard','action'=>'export_recipe_costing']) ?>" target="_blank">
    <input type="hidden" name="product_ids" id="exportProductIds" value="">
    <input type="hidden" name="export_all" id="exportAll" value="0">
</form>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#example')) {
        var t = $('#example').DataTable();
        t.order([[0, 'desc']]).draw();
    } else {
        $('#example').DataTable({ order: [[0, 'desc']] });
    }
    // Select All checkbox
    $('#selectAll').on('change', function() {
        $('.product-check').prop('checked', this.checked);
    });

    // Export button click
    $('#exportRecipeBtn').on('click', function() {
        var checked = $('.product-check:checked').map(function() { return this.value; }).get();
        if (checked.length === 0) {
            // Export all
            $('#exportAll').val('1');
            $('#exportProductIds').val('');
        } else {
            $('#exportAll').val('0');
            $('#exportProductIds').val(checked.join(','));
        }
        $('#recipeExportForm').submit();
    });

    $(document).on('click', '.mark-status', function() {
        const id = $(this).data('id');
        $.ajax({
            url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'product_change_status']) ?>',
            type: 'POST',
            data: { _id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    location.reload();
                } else {
                    alert('Failed to update status');
                }
            },
            error: function(xhr) {
                alert('Error updating status');
                console.error(xhr.responseText);
            }
        });
    });
});
</script>
