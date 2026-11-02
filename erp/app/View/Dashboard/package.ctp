<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Package</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="product_list">
                    <button class='btn btn-danger' type="button">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>

<?php echo $this->Form->create('User', ['type' => 'file']); ?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group mb-3">
                    <?php 
                        echo $this->Form->input('id', ['type'=>'hidden','value'=>$products[0]['catalog_product']['id']]);
                        echo $this->Form->input('unit', ['type'=>'hidden','value'=>$products[0]['catalog_product']['unit']]);
                        echo $this->Form->input('unit_name', ['type'=>'hidden','value'=>$products[0]['catalog_product']['unit_name']]);
                        echo $this->Form->input('name', ['label'=>'Product Name', 'class'=>'form-control','readonly'=>'readonly','value'=>$products[0]['catalog_product']['name']]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mapped Products -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">Map Products</h4>
                <?php foreach($packages as $package): 
                    $pkg = $package['catalog_package'];
                    if ($pkg['status'] != 1) continue;
                ?>
                <div class="row align-items-end mb-3">
                    <div class="col-md-5">
                        <label>Product Name</label>
                        <input type="hidden" name="select_semi_name[]" value="<?= $pkg['link_product_id'] ?>">
                        <input type="text" class="form-control" value="<?= $pkg['link_product_name']?>" readonly>
                    </div>
                    <div class="col-md-2">
                        <label>Unit</label>
                        <input type="text" name="pro_unit[]" class="form-control" value="<?= $pkg['link_product_unit_name'] ?>" readonly>
                    </div>
                    <div class="col-md-2">
                        <label>Price</label>
                        <input type="number" name="pro_price[]" class="form-control" value="<?= $pkg['link_product_price'] ?>" step="0.1" readonly>
                    </div>
                    <div class="col-md-2">
                        <label>Quantity</label>
                        <input type="number" name="pro_quantity[]" class="form-control" value="<?= $pkg['link_quantity'] ?>" step="0.1" required>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-danger removetax" type="button" style="margin-top:30px;">Remove</button>
                    </div>
                </div>
                <?php endforeach; ?>

                <div id="semiInputs"></div>
                <div class="col-md-12 mt-3">
                    <button id="addsemi" class='btn btn-info' type="button" style="float:right;">Add Product</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submit/Cancel -->
<div class="row">
    <div class="col-md-12">
        <?= $this->Form->button('Save', ['class'=>'btn btn-success', 'type'=>'submit', 'id'=>'submitBtn','style'=>'float:right;']) ?>
        <a href="product_list"><button class='btn btn-danger' type="button" style="float:right;">Cancel</button></a>&nbsp
    </div>
</div>
<?= $this->Form->end(); ?>
<br>

<!-- JavaScript -->
<script>
$(document).ready(function(){
    $('#addsemi').click(function(){
        var newInput = $('<div class="row align-items-end mb-3">' +
            '<div class="col-md-5">' +
                '<label>Product Name</label>' +
                '<select class="form-control select-semi" required name="select_semi_name[]">' +
                    '<option value="">Loading...</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label>Unit</label>' +
                '<input type="text" class="form-control unit-field" required name="pro_unit[]" readonly>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label>Price</label>' +
                '<input type="number" class="form-control price-field" required name="pro_price[]" step="0.1" readonly>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label>Quantity</label>' +
                '<input type="number" class="form-control" required name="pro_quantity[]" step="0.1">' +
            '</div>' +
            '<div class="col-md-1 text-right">' +
                '<button class="btn btn-danger removetax" type="button" style="margin-top:30px;">Remove</button>' +
            '</div>' +
        '</div>');

        $('#semiInputs').append(newInput);

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_pro_options']) ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.response_code === 1) {
                    var select = newInput.find('select');
                    select.empty().append('<option value="">Select Product</option>');
                    $.each(response.data, function(index, item) {
                        select.append('<option value="' + item.id + '" data-price="' + item.price + '" data-unit="' + item.unit_name + '">' + item.name + '</option>');
                    });
                } else {
                    Swal.fire('Error', 'Failed to fetch product options.', 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Something went wrong: ' + error, 'error');
            }
        });
    });

    $(document).on('change', '.select-semi', function(){
        var selected = $(this).find('option:selected');
        var unit = selected.data('unit') || '';
        var price = selected.data('price') || 0;
        $(this).closest('.row').find('.unit-field').val(unit);
        $(this).closest('.row').find('.price-field').val(price);
    });

    $(document).on('click', '.removetax', function(){
        $(this).closest('.row').remove();
    });
});
</script>
