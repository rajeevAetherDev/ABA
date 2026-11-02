<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Approve Indent</h2>
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

                        echo $this->Form->input('remarks', [
                            'label' => 'Remarks',
                            'type' => 'text',
                            'class' => 'form-control',
                            'id' => 'remarks',
                        ]);

                        echo '<br>' . $this->Form->input('vehicle', [
                            'label' => 'Vehicle Number',
                            'type' => 'text',
                            'class' => 'form-control',
                            'id' => 'vehicle',
                        ]);

                        echo '<br>'.$this->Form->input('expiry', [
                            'label' => 'Date',
                            'type' => 'text',
                            'class' => 'form-control',
                            'id' => 'expiry',
                            'value' => date('Y-m-d'),
                            'after' => '<script>document.getElementById("expiry").type = "date";</script>'
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
                                    <th>Item Type</th> <!-- âœ… Added new column -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php $x = 1;
                                    foreach ($users as $user):
                                        $sku = $user['inventory_indent_items'];
                                        $quantity = isset($sku['sku_quantity']) ? $sku['sku_quantity'] : 0;
                                        $item_type = isset($sku['item_type']) ? $sku['item_type'] : 'sku';
                                    ?>
                                    <tr>
                                        <td><?= $x ?></td>
                                        <td><?= h($sku['sku_name']) ?></td>
                                        <td><?= h($sku['sku_brand']) ?></td>
                                        <td><?= h($sku['sku_unit_name']) ?></td>
                                        <td>
                                            <input 
                                                type="number" 
                                                class="form-control price-input" 
                                                data-skuid="<?= $sku['sku_id'] ?>"   
                                                data-itemtype="<?= $item_type ?>"  
                                                value="<?= $sku['sku_price'] ?>" 
                                                style="width:auto" 
                                                step="0.01">
                                        </td>
                                        
                                        <td><?= h($sku['sku_total_tax_percentage']) ?>
                                        <input 
                                            type="hidden"
                                            class="tax-input"
                                            data-skuid="<?= $sku['sku_id'] ?>"
                                            value="<?= h($sku['sku_total_tax_percentage']) ?>">
                                        </td>
                                        <td><?= h($quantity) ?></td>
                                        <td>
                                            <input 
                                                type="number" 
                                                class="form-control quantity-input" 
                                                data-skuid="<?= $sku['sku_id'] ?>" 
                                                data-itemtype="<?= $item_type ?>" 
                                                value="<?= $quantity ?>" 
                                                style="width:auto">
                                                
                                        </td>
                                        <td><?= ucfirst($item_type) ?></td>
                                    </tr>
                                    <?php $x++; endforeach; ?>

                            </tbody>
                        </table>
                        <br>

                        <div class="row">
                            <div class="col-md-12">
                                <button onclick="getCheckedCheckboxes()" type="button" class="btn btn-success" id="dltbtn" style="float:right;">Approve</button>
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
$(document).ready(function() {
    $('#example').DataTable();
});
function getCheckedCheckboxes() {

    var locationId = $('#location').val();
    var remarks = $('#remarks').val();
    var vehicle = $('#vehicle').val();
    var expiry = $('#expiry').val();

    if (!expiry) {
        Swal.fire({
            icon: 'warning',
            title: 'Please select date'
        });
        return;
    }

    var table = $('#example').DataTable();
    var items = [];

    // collect rows
    table.$('.quantity-input').each(function () {

        var qty = parseFloat($(this).val());
        if (!qty || qty <= 0) return;

        var skuId = $(this).data("skuid");
        var tax = parseFloat(
            table.$(".tax-input[data-skuid='" + skuId + "']").val()
        ) || 0;

        var row = $(this).closest('tr');

        items.push({
            id: skuId,
            item_type: $(this).data("itemtype"),
            sku_name: row.find('td:eq(1)').text(),
            sku_brand: row.find('td:eq(2)').text(),
            unit_name: row.find('td:eq(3)').text(),
            price: parseFloat(table.$(".price-input[data-skuid='" + skuId + "']").val()) || 0,
            sku_quantity: qty,
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

    // ðŸ”¥ SHOW CONFIRMATION
    showApproveIndentConfirmation(items, {
        location: locationId,
        remarks: remarks,
        vehicle: vehicle,
        expiry: expiry
    }, function () {

        $.ajax({
            url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'update_approve_indent']); ?>',
            type: 'POST',
            data: {
                indent_id: '<?= $id ?>',
                location_id: locationId,
                remarks: remarks,
                vehicle: vehicle,
                expiry: expiry,
                checkedData: JSON.stringify(items)
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Indent approved successfully',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => window.location.href = 'indent');
            }
        });

    });
}
function showApproveIndentConfirmation(items, formData, onConfirm) {

    let html = `<div class="text-start mb-3" style="font-size:15px">`;
    
    html += `<b>Date :</b> ${formData.expiry}<br>`;
    
    if (formData.vehicle)
        html += `<b>Vehicle :</b> ${formData.vehicle}<br>`;
    
    if (formData.remarks)
        html += `<b>Remarks :</b> ${formData.remarks}<br>`;
    
    html += `</div>`;

    html += `
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
            </tr>
        </thead>
        <tbody>
    `;

    let subtotal = 0;
    let totalTax = 0;

    items.forEach(item => {

        const price = parseFloat(item.price);
        const qty   = parseFloat(item.sku_quantity);
        const tax   = parseFloat(item.tax);

        const base  = price * qty;
        const taxVal = base * tax / 100;
        const total = base + taxVal;

        subtotal += base;
        totalTax += taxVal;

        html += `
        <tr>
            <td>${item.sku_name}</td>
            <td>${item.sku_brand}</td>
            <td>${item.unit_name}</td>
            <td>${price.toFixed(2)}</td>
            <td>${tax}%</td>
            <td><b>${qty}</b></td>
            <td>${base.toFixed(2)}</td>
            <td>${taxVal.toFixed(2)}</td>
            <td><b>${total.toFixed(2)}</b></td>
        </tr>
        `;
    });

    const grandTotal = subtotal + totalTax;

    html += `
        </tbody>
    </table>

    <div class="text-end mt-3">
        <b>Subtotal :</b> ${subtotal.toFixed(2)}<br>
        <b>Total Tax :</b> ${totalTax.toFixed(2)}<br>
        <h5 class="mt-2"><b>Grand Total : ${grandTotal.toFixed(2)}</b></h5>
    </div>
    `;

    Swal.fire({
        title: 'Confirm Indent Approval',
        html: html,
        width: '1100px',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
}

</script>
