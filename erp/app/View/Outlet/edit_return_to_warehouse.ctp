<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit Return</h2>
        </div>
    </div>

    <div class="col-sm-1">
        <div class="page-title-box">
            <a href="return_to_warehouse">
                <button class='btn btn-danger' type="button">Back</button>
            </a>
        </div>
    </div>
</div>


<input type="hidden" id="return_id" value="<?= $transfer['Inventory_return']['return_id'] ?>">


<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?php
                $options = [];

                foreach ($locations as $location) {
                    $options[$location['Location']['id']] = $location['Location']['name'];
                }

                echo $this->Form->input('to', [
                    'label' => 'Select Warehouse',
                    'class' => 'form-control',
                    'options' => $options,
                    'id' => 'select_to',
                    'value' => $transfer['Inventory_return']['warehouse_id'],
                    'required' => true
                ]);
                ?>


                <br>

                <div class="form-group">
                    <label>Remarks</label>

                    <textarea
                        id="remarks"
                        class="form-control"
                        rows="3"
                    ><?= $transfer['Inventory_return']['remarks'] ?></textarea>
                </div>


                <br>

                <h5>Items</h5>


                <table class="table table-bordered" id="example">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax %</th>
                            <th>Qty</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($users as $user): ?>

                            <?php

                            $qty = 0;

                            foreach ($transferItems as $item) {

                                if (
                                    $item['Inventory_return_items']['sku_id']
                                    ==
                                    $user['Inventory_sku']['id']
                                ) {

                                    $qty =
                                    $item['Inventory_return_items']['sku_quantity'];

                                }

                            }

                            ?>

                            <tr>

                                <td><?= $user['Inventory_sku']['id'] ?></td>

                                <td><?= $user['Inventory_sku']['name'] ?></td>

                                <td><?= $user['Inventory_sku']['brand'] ?></td>

                                <td><?= $user['Inventory_sku']['unit_name'] ?></td>

                                <td><?= $user['Inventory_sku']['price'] ?></td>

                                <td><?= h($user[0]['total_tax_percentage']) ?>%</td>

                                <td>

                                    <input type="number"
                                           class="form-control quantity"
                                           value="<?= $qty ?>"
                                           min="0">

                                    <input type="hidden"
                                           class="sku_id"
                                           value="<?= $user['Inventory_sku']['id'] ?>">

                                    <input type="hidden"
                                           class="sku_name"
                                           value="<?= $user['Inventory_sku']['name'] ?>">

                                    <input type="hidden"
                                           class="sku_brand"
                                           value="<?= $user['Inventory_sku']['brand'] ?>">

                                    <input type="hidden"
                                           class="sku_price"
                                           value="<?= $user['Inventory_sku']['price'] ?>">

                                    <input type="hidden"
                                           class="unit_name"
                                           value="<?= $user['Inventory_sku']['unit_name'] ?>">

                                    <input type="hidden"
                                           class="unit_id"
                                           value="<?= $user['Inventory_sku']['unit'] ?>">

                                    <input type="hidden"
                                           class="tax_percentage"
                                           value="<?= h($user[0]['total_tax_percentage']) ?>">

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>


                <br>

                <button type="button"
                        onclick="updateReturn()"
                        class="btn btn-success"
                        style="float:right;">

                    Update

                </button>


                <a href="return_to_warehouse">

                    <button class="btn btn-danger"
                            type="button"
                            style="float:right;margin-right:10px;">

                        Cancel

                    </button>

                </a>

            </div>
        </div>
    </div>
</div>

<script>

let qtyStore = {};

/*
=====================================
PRELOAD EXISTING SAVED QTY FROM PHP
=====================================
*/
<?php foreach ($transferItems as $item): ?>
qtyStore["<?= $item['Inventory_return_items']['sku_id'] ?>"] = {
    id: "<?= $item['Inventory_return_items']['sku_id'] ?>",
    sku_name: "<?= addslashes($item['Inventory_return_items']['sku_name']) ?>",
    sku_brand: "<?= addslashes($item['Inventory_return_items']['sku_brand']) ?>",
    price: "<?= $item['Inventory_return_items']['sku_price'] ?>",
    unit_name: "<?= addslashes($item['Inventory_return_items']['sku_unit_name']) ?>",
    unit_id: "<?= $item['Inventory_return_items']['sku_unit'] ?>",
    tax_percentage: "<?= $item['Inventory_return_items']['tax_percent'] ?>",
    sku_quantity: "<?= $item['Inventory_return_items']['sku_quantity'] ?>"
};
<?php endforeach; ?>


$(document).ready(function () {

    /*
    =============================
    DATATABLE INIT
    =============================
    */
    var table = $('#example').DataTable({
        destroy: true,
        stateSave: false,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: 6 }
        ]
    });


    /*
    =============================
    STORE QTY ON INPUT CHANGE
    =============================
    */
    $('#example tbody').on('input', '.quantity', function () {

        let row = $(this).closest('tr');

        let skuId = row.find('.sku_id').val();
        let qty = parseFloat($(this).val()) || 0;

        if (qty > 0) {

            qtyStore[skuId] = {
                id: skuId,
                sku_name: row.find('.sku_name').val(),
                sku_brand: row.find('.sku_brand').val(),
                price: row.find('.sku_price').val(),
                unit_name: row.find('.unit_name').val(),
                unit_id: row.find('.unit_id').val(),
                tax_percentage: row.find('.tax_percentage').val(),
                sku_quantity: qty
            };

        } else {

            delete qtyStore[skuId];

        }

    });


    /*
    =============================
    RESTORE QTY AFTER PAGINATION
    =============================
    */
    table.on('draw', function () {

        $('#example tbody tr').each(function () {

            let row = $(this);

            let skuId = row.find('.sku_id').val();

            if (qtyStore[skuId]) {

                row.find('.quantity').val(qtyStore[skuId].sku_quantity);

            }

        });

    });

});



/*
=====================================
UPDATE RETURN FUNCTION
=====================================
*/
function updateReturn() {

    let to = $('#select_to').val();
    let remarks = $('#remarks').val();
    let toText = $('#select_to option:selected').text();

    if (to == '') {
        Swal.fire('Warning', 'Warehouse required', 'warning');
        return;
    }

    let checkedData = [];
    let itemRowsHtml = '';
    let grandTotal = 0;

    $.each(qtyStore, function (key, item) {

        let baseTotal = parseFloat(item.price) * parseFloat(item.sku_quantity);

        let taxAmount = (baseTotal * parseFloat(item.tax_percentage)) / 100;

        let rowTotal = baseTotal + taxAmount;

        grandTotal += rowTotal;

        checkedData.push(item);

        itemRowsHtml += `
            <tr>
                <td>${item.sku_name}</td>
                <td>${item.sku_brand}</td>
                <td>${item.unit_name}</td>
                <td>${item.price}</td>
                <td>${item.tax_percentage}%</td>
                <td>${item.sku_quantity}</td>
                <td>${rowTotal.toFixed(2)}</td>
            </tr>
        `;
    });

    if (checkedData.length == 0) {
        Swal.fire('Warning', 'Enter at least one quantity', 'warning');
        return;
    }


    /*
    =============================
    CONFIRM POPUP
    =============================
    */
    Swal.fire({
        title: 'Confirm Update Return',
        width: '900px',

        html: `
            <div style="text-align:left">

                <p><strong>Warehouse:</strong> ${toText}</p>
                <p><strong>Remarks:</strong> ${remarks}</p>

                <table border="1" width="100%" cellpadding="8" style="border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        ${itemRowsHtml}
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="6" style="text-align:right;">
                                Grand Total
                            </th>

                            <th>
                                ${grandTotal.toFixed(2)}
                            </th>
                        </tr>
                    </tfoot>
                </table>

            </div>
        `,

        showCancelButton: true,
        confirmButtonText: 'Confirm Update',
        cancelButtonText: 'Cancel',
        reverseButtons: true

    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({

                url: "<?= $this->Html->url(['action'=>'update_return_to_warehouse']) ?>",

                type: "POST",

                data: {
                    return_id: $('#return_id').val(),
                    transferto: to,
                    remarks: remarks,
                    checkedData: JSON.stringify(checkedData)
                },

                beforeSend: function () {

                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                },

                success: function (res) {

                    let response = JSON.parse(res);

                    if (response.response_code == 1) {

                        Swal.fire(
                            'Success',
                            response.data,
                            'success'
                        ).then(() => {

                            window.location = "return_to_warehouse";

                        });

                    } else {

                        Swal.fire(
                            'Error',
                            response.data,
                            'error'
                        );

                    }

                },

                error: function () {

                    Swal.fire(
                        'Error',
                        'Something went wrong',
                        'error'
                    );

                }

            });

        }

    });

}

</script>
