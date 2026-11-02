<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Process Indent</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="indent"><button class='btn btn-danger' type="button">Back</button></a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        <?php
                        $id=$id;
                        
                        echo $this->Form->input('location', [
                            'label' => 'Location',
                            'class' => 'form-control',
                            'value' => $selected_location_id,
                            'id' => 'location',
                            'type' => 'hidden'
                        ]);
                        ?>

                        <br>
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Brand</th>
                                    <th>Unit Name</th>
                                    <th>Price</th>
                                    <th>Tax %</th>
                                    <th>Requested Quantity</th>
                                    <th>Quantity Supplied</th>
                                    <th>Item Type</th> <!-- ✅ Added new column -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php $x = 1;
                                foreach ($users as $user):
                                    
                                    $sku = $user['inventory_indent_items'];
                                    $quantity = isset($sku['sku_quantity']) ? $sku['sku_quantity'] : 0;
                                    $approved_quantity = isset($sku['approved_quantity']) ? $sku['approved_quantity'] : 0;
                                    $price = isset($sku['approved_price']) ? $sku['approved_price'] : 0;
                                    $item_type = isset($sku['item_type']) ? $sku['item_type'] : 'sku';
                                ?>
                                    <tr>
                                        <td><?= $x ?></td>
                                        <td><?= h($sku['sku_name']) ?></td>
                                        <td><?= h($sku['sku_brand']) ?></td>
                                        <td><?= h($sku['sku_unit_name']) ?></td>
                                        <td>
                                            <?= h($price) ?>
                                                <input type="hidden" class="price-input" data-skuid="<?= $sku['sku_id'] ?>"  data-itemtype="<?= $sku['item_type'] ?>" value="<?= $price ?>">

                                        </td>
                                        <td>
    <?= h($sku['sku_total_tax_percentage']) ?>%
    <input
        type="hidden"
        class="tax-input"
        data-skuid="<?= $sku['sku_id'] ?>"
        value="<?= $sku['sku_total_tax_percentage'] ?>">
</td>

                                        <td><?= h($quantity) ?></td>
                                        <td>
                                            <input type="number" class="form-control quantity-input" data-skuid="<?= $sku['sku_id'] ?>"  data-itemtype="<?= $sku['item_type'] ?>" value="<?= $approved_quantity ?>" style="width:auto">
                                        </td>
                                        <td><?= ucfirst($item_type) ?></td>
                                    </tr>
                                <?php $x++; endforeach; ?>
                            </tbody>
                        </table><br>

                        <div class="row">
                            <div class="col-md-12">
                                <button onclick="getCheckedCheckboxes()" class="btn btn-success" id="dltbtn" style="float:right;">Process</button>
                                <a href="indent"><button class='btn btn-danger' type="button" style="float:right; margin-right:10px;">Cancel</button></a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

$(document).ready(function () {
    if (!$.fn.DataTable.isDataTable('#example')) {
        $('#example').DataTable();
    }
});


/* ===============================
   Collect rows + open popup
================================*/
function getCheckedCheckboxes() {

    var locationId = $('#location').val();
    var table = $('#example').DataTable();
    
    var items = [];

    table.$('.quantity-input').each(function () {

        let sku_quantity = parseFloat($(this).val());
        if (!sku_quantity || sku_quantity <= 0) return;

        let row = $(this).closest('tr');
        let skuId = $(this).data("skuid");

        let price = parseFloat(
            table.$(".price-input[data-skuid='" + skuId + "']").val()
        ) || 0;
        
        let tax = parseFloat(
            table.$(".tax-input[data-skuid='" + skuId + "']").val()
        ) || 0;

        items.push({
            id: skuId,
            name: row.find('td:eq(1)').text(),
            brand: row.find('td:eq(2)').text(),
            unit: row.find('td:eq(3)').text(),
            item_type: $(this).data("itemtype"),   
            price: price,
            sku_quantity: sku_quantity,
            tax: tax
        });

    });


    if (items.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Items',
            text: 'Please enter at least one quantity'
        });
        return;
    }


    showProcessConfirmation(items, function () {

        $.ajax({
            url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'update_process_indent']); ?>',
            type: 'POST',
            data: {
                indent_id: '<?= $id ?>',
                location_id: locationId,
                checkedData: JSON.stringify(items)
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Indent Processed Successfully'
                }).then(() => window.location.href = 'indent');
            }
        });

    });
}



/* ===============================
   SWEETALERT POPUP (WITH TAX)
================================*/
function showProcessConfirmation(items, onConfirm) {

    let subtotal = 0;
    let totalTax = 0;

    let html = `
    <table class="table table-bordered table-sm text-start">
        <thead>
            <tr>
                <th>Name</th>
                <th>Brand</th>
                <th>Unit</th>
                <th>Price</th>
                <th>Tax %</th>
                <th>Qty</th>
                <th>Amount</th>
                <th>Tax</th>
                <th>Total</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
    `;


    items.forEach(item => {

        let base   = item.price * item.sku_quantity;
        let taxVal = base * item.tax / 100;
        let total  = base + taxVal;

        subtotal += base;
        totalTax += taxVal;

        html += `
        <tr>
            <td>${item.name}</td>
            <td>${item.brand}</td>
            <td>${item.unit}</td>
            <td>₹${item.price.toFixed(2)}</td>
            <td>${item.tax}%</td>
            <td><b>${item.sku_quantity}</b></td>
            <td>₹${base.toFixed(2)}</td>
            <td>₹${taxVal.toFixed(2)}</td>
            <td><b>₹${total.toFixed(2)}</b></td>
            <td>${item.item_type}</td>  
        </tr>
        `;
    });


    let grandTotal = subtotal + totalTax;

    html += `
        </tbody>
    </table>

    <div class="text-end mt-3">
        <b>Subtotal :</b> ₹${subtotal.toFixed(2)}<br>
        <b>Total Tax :</b> ₹${totalTax.toFixed(2)}<br>
        <h5><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
    </div>
    `;


    Swal.fire({
        title: 'Confirm Process Indent',
        html: html,
        width: '1100px',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Process',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) onConfirm();
    });
}

/*
function showProcessConfirmation(items, onConfirm) {

    let html = `
    <table class="table table-bordered table-sm text-start">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
    `;

    let grandTotal = 0;

    items.forEach(item => {

        const price = parseFloat(item.price) || 0;
        const qty   = parseFloat(item.sku_quantity) || 0;

        const total = price * qty;
        grandTotal += total;

        html += `
        <tr>
            <td>${item.id}</td>
            <td>₹${price.toFixed(2)}</td>
            <td>${qty}</td>
            <td><b>₹${total.toFixed(2)}</b></td>
        </tr>
        `;
    });

    html += `
        </tbody>
    </table>

    <div class="text-end mt-3">
        <h5><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
    </div>
    `;

    Swal.fire({
        title: 'Confirm Process Indent',
        html: html,
        width: '900px',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Process',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
}

function getCheckedCheckboxes() {
    var checkedData = [];
    var locationId = $('#location').val();
    
    var table = $('#example').DataTable();

    table.$('.quantity-input').each(function () {
        var quantity = $(this).val().trim();
        var skuId = $(this).data("skuid");
        var price = table.$(".price-input[data-skuid='" + skuId + "']").val().trim();
        var itemType = $(this).data("itemtype");

        if (quantity !== "" && quantity !== "0") {
            checkedData.push({
                id: skuId,
                price: price,
                item_type: itemType,
                sku_quantity: quantity
            });
        }
    });


    if (checkedData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Items',
            text: 'Please enter at least one item with quantity.'
        });
        return;
    }

    showProcessConfirmation(checkedData, function () {

    $.ajax({
        url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'update_process_indent']); ?>',
        type: 'POST',
        data: {
            indent_id: '<?= $id ?>',
            location_id: locationId,
            checkedData: JSON.stringify(checkedData)
        },
        success: function () {
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: 'Indent has been Processed successfully.'
            }).then(() => window.location.href = 'indent');
        }
    });

});

}

$(document).ready(function () {
    if (!$.fn.DataTable.isDataTable('#example')) {
        $('#example').DataTable();
    }
});
*/
</script>
