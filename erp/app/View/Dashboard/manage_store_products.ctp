<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Assign Products</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"> 
                <a href="store_products?location_id=<?php echo $locationId; ?>">
                    <button class="btn btn-danger" type="button">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <!-- Store Details -->
                <div class="form-group row">
                    <div class="col-sm-12">
                        <?php 
                            echo $this->Form->input('store_id', [
                                'type'=>'hidden', 
                                'value'=> $locationId
                            ]);
                            echo $this->Form->input('store_name', [
                                'div'=>['class'=>'form-group'], 
                                'label'=>'Store Name', 
                                'class'=>'form-control',
                                'readonly'=>'readonly',
                                'value'=> $locationname
                            ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Save & Cancel -->
 
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">                
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <table id="example1" class="display table table-hover table-condensed"  cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="checkAll"></th>
                                        <th><?php echo 'Product ID'; ?></th>
                                        <th><?php echo 'Product Name'; ?></th>
                                        <th><?php echo 'Category Name'; ?></th>
                                        <th><?php echo 'Unit Name'; ?></th>
                                        <th><?php echo 'Price'; ?></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php 
                                    foreach($products as $product): 
                                        $product_id = $product['Catalog_product']['id'];
                                        $product_name = $product['Catalog_product']['name'];
                                        $cat_name = $product['Catalog_product']['cat_name'];
                                        $subcat_name = $product['Catalog_product']['subcat_name'];
                                        $price = $product['Catalog_product']['price'];
                                        
                                    ?>
                                    <tr id="row-<?php echo $product_id; ?>">
                                        <td><input type="checkbox" class="row-checkbox" data-id="<?php echo $product_id; ?>"></td>
                                        <td><?php echo $product_id; ?></td>
                                        <td><?php echo htmlspecialchars($product_name); ?></td>
                                        <td><?php echo htmlspecialchars($cat_name); ?></td>
                                        <td><?php echo htmlspecialchars($subcat_name); ?></td>
                                        <td> <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">₹</span>
                                            </div>
                                            <input type="number" class="price-input form-control" data-id="<?php echo $product_id; ?>" value="<?php echo htmlspecialchars($price); ?>" step="0.01" min="0">
                                        </div></td>
                                    </tr>
                                    <?php endforeach; ?>            
                                </tbody>
                            </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <?php echo $this->Form->button('Save', ['class'=>'btn btn-success', 'type'=>'button','style'=>'float:right', 'id'=>'submitBtn']); ?>

                        <a href="store_products">
                            <button class='btn btn-danger' type="button" style="float:right; margin-right:10px;">Cancel</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<br>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

$(document).ready(function () {

    // Keep selected products and prices outside DataTables DOM
    var selectedIds = {};
    var selectedPrices = {};

    var table = $('#example1').DataTable({
        destroy: true,
        pageLength: 10,
        order: [],
        initComplete: function () {

            // ==============================
            // CHECK ALL
            // ==============================
            $('#example1 thead').on('change', '#checkAll', function () {

                var isChecked = this.checked;

                var rows = table.rows({ search: 'applied' }).nodes();

                $(rows).each(function () {

                    var $row = $(this);
                    var $checkbox = $row.find('input.row-checkbox');
                    var id = $checkbox.data('id');

                    $checkbox.prop('checked', isChecked);

                    if (isChecked) {

                        selectedIds[id] = true;

                        // Read price from the row node itself,
                        // not a document-wide ID lookup (fails for
                        // rows on other pages, since DataTables
                        // detaches them from the live DOM)
                        var price = $row.find('.price-input').val();

                        if (price !== undefined) {
                            selectedPrices[id] = price;
                        }

                    } else {

                        delete selectedIds[id];
                        delete selectedPrices[id];
                    }
                });
            });
        }
    });


    // ==============================
    // INDIVIDUAL CHECKBOX
    // ==============================
    $('#example1 tbody').on(
        'change',
        'input.row-checkbox',
        function () {

            var id = $(this).data('id');

            if (this.checked) {

                selectedIds[id] = true;

                // Save current price
                var price = $('#row-' + id + ' .price-input').val();

                if (price !== undefined) {
                    selectedPrices[id] = price;
                }

            } else {

                delete selectedIds[id];
                delete selectedPrices[id];

                var el = $('#checkAll').get(0);
                if (el && el.checked) {
                    el.indeterminate = true;
                }
            }
        }
    );


    // ==============================
    // PRICE CHANGE
    // ==============================
    $('#example1 tbody').on(
        'change',
        '.price-input',
        function () {

            var id = $(this).data('id');
            var price = $(this).val();

            // Save price independently of DataTables
            selectedPrices[id] = price;

            // Automatically select product
            var $checkbox = $(this)
                .closest('tr')
                .find('input.row-checkbox');

            if (!$checkbox.prop('checked')) {

                $checkbox
                    .prop('checked', true)
                    .trigger('change');
            }
        }
    );


    // ==============================
    // FOCUS PRICE = SELECT ROW
    // ==============================
    $('#example1 tbody').on(
        'focus',
        '.price-input',
        function () {

            var $row = $(this).closest('tr');
            var $checkbox = $row.find('input.row-checkbox');

            if (!$checkbox.prop('checked')) {

                $checkbox
                    .prop('checked', true)
                    .trigger('change');
            }
        }
    );


    // ==============================
    // RESTORE STATE AFTER PAGINATION
    // ==============================
    table.on('draw', function () {

        $('#example1 tbody input.row-checkbox').each(function () {

            var id = $(this).data('id');

            $(this).prop(
                'checked',
                selectedIds[id] === true
            );
        });


        $('#example1 tbody .price-input').each(function () {

            var id = $(this).data('id');

            if (selectedPrices[id] !== undefined) {

                $(this).val(selectedPrices[id]);
            }
        });
    });


    // ==============================
    // SAVE
    // ==============================
    $('#submitBtn').on('click', function () {

        var ids = Object.keys(selectedIds);

        if (ids.length === 0) {

            alert("No rows selected.");
            return;
        }


        var $btn = $(this);

        $btn
            .prop('disabled', true)
            .text('Saving...');


        // IMPORTANT:
        // Do NOT get prices from #row-ID here.
        // DataTables may have removed those rows from the DOM.

        var prices = {};

        ids.forEach(function (id) {

            prices[id] = selectedPrices[id] !== undefined
                ? selectedPrices[id]
                : '0';
        });


        console.log("Product IDs:", ids);
        console.log("Prices:", prices);

        $.ajax({

            url: '<?php echo Router::url([
                'controller' => 'Dashboard',
                'action' => 'assign_products_to_store'
            ]) ?>',

            method: 'POST',

            contentType: 'application/json',

            dataType: 'json',

            data: JSON.stringify({

                product_ids: ids,

                prices: prices,

                store_id: <?php echo $locationId; ?>,

                store_name: "<?php echo addslashes($locationname); ?>"
            }),

            success: function (response) {
                if (response && response.status) {
                    window.location.href =
                        '<?php echo $this->base; ?>/dashboard/store_products?location_id=' +
                        encodeURIComponent(<?php echo $locationId; ?>);
                } else {
                    alert("An error occurred while saving products.");
                    $btn
                        .prop('disabled', false)
                        .text('Save');
                }
            },

            error: function () {

                alert("An error occurred while saving products.");

                $btn
                    .prop('disabled', false)
                    .text('Save');
            }
        });

    });

});
</script>