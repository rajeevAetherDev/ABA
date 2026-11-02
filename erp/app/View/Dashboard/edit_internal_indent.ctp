<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit Internal Indent</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="internal_indent_list"><button class='btn btn-danger' type="button">Back</button></a></h2>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <label>Select Location</label>
                        <select class="form-control" id="location_id" name="location_id" required>
                            <?php foreach ($locations as $locId => $locName): ?>
                                <option value="<?= $locId ?>" <?= ($locId == $selected_location_id) ? 'selected' : '' ?>>
                                    <?= h($locName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $x = 1;
                                foreach ($users as $user):
                                    
                                    $sku = $user['inventory_skus'];
                                    $quantity = isset($user[0]['quantity']) ? $user[0]['quantity'] : 0;
                                ?>
                                    <tr>
                                        <td><?= $x ?></td>
                                        <td><?= h($sku['name']) ?></td>
                                        <td><?= h($sku['brand']) ?></td>
                                        <td><?= h($sku['unit_name']) ?></td>
                                        <td><?= h($sku['price']) ?></td>
                                        <td><?= h($user[0]['total_tax'] ?? 0) ?> %</td>
                                        <td><?= $this->Form->input('quantity', [
                                            'class' => 'form-control',
                                            'label' => false,
                                            'required' => true,
                                            'type' => 'number',
                                            'style' => 'width:auto',
                                            'value' => $quantity,
                                            'id' => 'quantity_' . $sku['id']
                                        ]) ?></td>
                                        
                                        <input type="hidden" name="indent_id" value="<?= $id ?>">
                                        <input type="hidden" name="sku_id" value="<?= $sku['id'] ?>">
                                        <input type="hidden" name="sku_name" value="<?= h($sku['name']) ?>">
                                        <input type="hidden" name="sku_brand" value="<?= h($sku['brand']) ?>">
                                        <input type="hidden" name="sku_price" value="<?= h($sku['price']) ?>">
                                        <input type="hidden" name="unit_name" value="<?= h($sku['unit_name']) ?>">
                                        <input type="hidden" name="unit_id" value="<?= h($sku['unit']) ?>">
                                        <input type="hidden" name="is_completed" value="<?= h($sku['is_completed'] ?? 0) ?>">
                                        <input type="hidden" name="completed_quantity" value="<?= h($sku['completed_quantity'] ?? 0) ?>">
                                        <input type="hidden" name="sku_tax" value="<?= h($user[0]['total_tax'] ?? 0) ?>">

                                    </tr>
                                <?php $x++; endforeach; ?>
                            </tbody>
                        </table><br>

                        <div class="row">
                            <div class="col-md-12">
                                <button onclick="getCheckedCheckboxes()" class="btn btn-success" id="dltbtn" style="float:right;">Save</button>
                                <a href="internal_indent_list"><button class='btn btn-danger' type="button" style="float:right;">Cancel</button></a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

function showEditIndentConfirmation(items, onConfirm) {

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
                    <th>Tax Amt</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subtotal = 0;
    let totalTax = 0;

    items.forEach(item => {

        const price = parseFloat(item.price) || 0;
        const qty   = parseFloat(item.sku_quantity) || 0;
        const taxPct = parseFloat(item.tax) || 0;

        const baseAmount = price * qty;
        const taxValue  = (baseAmount * taxPct) / 100;
        const total     = baseAmount + taxValue;

        subtotal += baseAmount;
        totalTax += taxValue;

        html += `
            <tr>
                <td>${item.sku_name}</td>
                <td>${item.sku_brand}</td>
                <td>${item.unit_name}</td>
                <td>₹${price.toFixed(2)}</td>
                <td>${taxPct}%</td>
                <td>${qty}</td>
                <td>₹${baseAmount.toFixed(2)}</td>
                <td>₹${taxValue.toFixed(2)}</td>
                <td><b>₹${total.toFixed(2)}</b></td>
            </tr>
        `;
    });

    const grandTotal = subtotal + totalTax;

    html += `
        </tbody>
        </table>
        <div class="text-end mt-3">
            <b>Subtotal :</b> ₹${subtotal.toFixed(2)}<br>
            <b>Total Tax :</b> ₹${totalTax.toFixed(2)}<br>
            <h5 class="mt-2"><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
        </div>
    `;

    Swal.fire({
        title: 'Confirm Internal Indent Update',
        html: html,
        icon: 'question',
        width: '1100px',
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
}

function getCheckedCheckboxes() {
    var skurows = $("#example").dataTable().fnGetNodes();
    var checkedData = [];
    var locationId = $('#location_id').val();
    var indentid = '';

    if (!locationId) {
        Swal.fire({
            icon: 'warning',
            title: 'Location Required',
            text: 'Please select a location before saving.'
        });
        return;
    }

    for (var i = 0; i < skurows.length; i++) {
        var inputdata = $(skurows[i]).find('input');
        var currentquantity = inputdata[0].value.trim();
        indentid = inputdata[1].value.trim();
        var currentSkuId = inputdata[2].value.trim();
        var currentSkuname = inputdata[3].value.trim();
        var currentSkubrand = inputdata[4].value.trim();
        var currentSkuPrice = inputdata[5].value.trim();
        var currentSkuunitname = inputdata[6].value.trim();
        var currentSkuunitid = inputdata[7].value.trim();
        var currentSkuis_completed = inputdata[8].value.trim();
        var currentSkucompleted_quantity = inputdata[9].value.trim();
        var currentSkuTax = inputdata[10].value.trim(); // ✅ NEW
        
        if (currentSkucompleted_quantity>currentquantity) {
            Swal.fire({
                icon: 'warning',
                title: 'Some quantities PO already created - ' + currentSkuname,
            });
            return;
        }
        
        if (currentquantity !== "" && currentquantity !== "0") {
            checkedData.push({
                sku_name: currentSkuname,
                sku_brand: currentSkubrand,
                unit_name: currentSkuunitname,
                unit_id: currentSkuunitid,
                sku_quantity: currentquantity,
                id: currentSkuId,
                tax: currentSkuTax, // ✅ NEW
                price: currentSkuPrice,
                is_completed: currentSkuis_completed,
                completed_quantity: currentSkucompleted_quantity,
            });
        }
    }

    if (checkedData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Items',
            text: 'Please enter at least one item with quantity.'
        });
        return;
    }

    showEditIndentConfirmation(checkedData, function () {

    $.ajax({
        url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'update_internal_indent']); ?>',
        type: 'POST',
        data: {
            indent_id: indentid,
            location_id: locationId,
            checkedData: JSON.stringify(checkedData)
        },
        success: function(response) {
            var res = (typeof response === 'string') ? JSON.parse(response) : response;
            if (res && res.response_code === 0) {
                Swal.fire({ icon: 'error', title: 'Blocked', text: res.data || 'Update failed.' });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: 'Internal Indent has been updated successfully.'
                }).then(() => {
                    window.location.href = 'internal_indent_list';
                });
            }
        }
    });

});

}
</script>

