<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit Indent</h2>
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
                <?php
                echo $this->Form->create(false, ['id' => 'editIndentForm']);
                
                $indent_id = $id;

                echo $this->Form->input('location', [
                    'label' => false,
                    'type' => 'hidden',
                    'value' => $selected_location_id,
                    'id' => 'location'
                ]);

                // --- Warehouse Dropdown ---
                $options = [];
                foreach ($optlocations as $loc) {
                    $options[$loc['Location']['id']] = h($loc['Location']['name']);
                }

                echo $this->Form->input('dclocation', [
                    'div' => ['class' => 'form-group'],
                    'label' => 'Select Warehouse',
                    'class' => 'form-control',
                    'options' => $options,
                    'value' => $selected_warehouse_id,
                    'id' => 'dclocation',
                    'required' => true
                ]);

                // --- Expiry Date ---
                echo $this->Form->input('expiry', [
                    'label' => 'Expiry Date',
                    'type' => 'text',
                    'class' => 'form-control',
                    'id' => 'expiry',
                    'value' => $selected_expiry,
                    'after' => '<script>document.getElementById("expiry").type = "date";</script>'
                ]);
                ?>

                <br>

                <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                    
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax %</th>
                            <th>Available Quantity at Outlet</th>
                            <th>Available Quantity at DC</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $x = 1;
                        foreach ($items as $item):
                            $data = [];
                            $type = '';
                            $quantity = 0;
                            $actual_balance = 'NA';

                            if (isset($item['inventory_semifinished']) || isset($item['sf']) || (isset($item[0]['sf']))) {
                                // Semifinished
                                $type = 'Semifinished';
                                $data = $item['inventory_semifinished'] ?? $item['sf'] ?? $item[0]['sf'] ?? $item[0];
                                $quantity = $item['quantity'] ?? $item[0]['quantity'] ?? $data['quantity'] ?? 0;
                                $actual_balance = $item['actual_balance'] ?? $item[0]['actual_balance'] ?? $data['actual_balance'] ?? 'NA';
                            } else {
                                // SKU
                                $type = 'SKU';
                                $data = $item['inventory_skus'] ?? $item['inventory_sku'] ?? $item[0] ?? $item;
                                $quantity = $item['quantity'] ?? $item[0]['quantity'] ?? $data['quantity'] ?? 0;
                                $actual_balance = $item['actual_balance'] ?? $item[0]['actual_balance'] ?? $data['actual_balance'] ?? 'NA';
                            }

                            $idKey = $data['id'] ?? null;
                            if (empty($idKey)) continue;
                        ?>
                        <tr>
                            <td><?= $x; ?></td>
                            <td><?= h($type); ?></td>
                            <td><?= h($data['name'] ?? '-'); ?></td>
                            <td><?= h($data['cat_name'] ?? '-'); ?></td>
                            <td><?= h($data['unit_name'] ?? '-'); ?></td>
                            <td><?= h($data['price'] ?? '0'); ?></td>
                            <td><?= h($item[0]['total_tax_percentage'] ?? '0'); ?> %</td>
                            <td><?= h($item[0]['outlet_balance']) ? h($item[0]['outlet_balance']) : 'NA'; ?></td>
                            <td><?= h($item[0]['dc_balance']) ? h($item[0]['dc_balance']) : 'NA'; ?></td>
                            <td>
                                <?= $this->Form->input("quantity[{$type}_{$idKey}]", [
                                    'class' => 'form-control',
                                    'label' => false,
                                    'type' => 'number',
                                    'style' => 'width:100px;',
                                    'value' => $quantity,
                                    'min' => 0,
                                    'id' => 'quantity_' . $type . '_' . $idKey
                                ]); ?>
                            </td>

                            <!-- Hidden fields -->
                            <input type="hidden" name="sku_id[<?= $type . '_' . $idKey; ?>]" value="<?= h($idKey); ?>">
                            <input type="hidden" name="sku_name[<?= $type . '_' . $idKey; ?>]" value="<?= h($data['name'] ?? ''); ?>">
                            <input type="hidden" name="sku_price[<?= $type . '_' . $idKey; ?>]" value="<?= h($data['price'] ?? ''); ?>">
                            <input type="hidden" name="unit_name[<?= $type . '_' . $idKey; ?>]" value="<?= h($data['unit_name'] ?? ''); ?>">
                            <input type="hidden" name="unit_id[<?= $type . '_' . $idKey; ?>]" value="<?= h($data['unit'] ?? ''); ?>">
                            <input type="hidden" name="item_type[<?= $type . '_' . $idKey; ?>]" value="<?= $type; ?>">
                            <input type="hidden" name="sku_tax[<?= $type . '_' . $idKey; ?>]" value="<?= h($item[0]['total_tax_percentage'] ?? 0); ?>">

                        </tr>
                        <?php $x++; endforeach; ?>
                    </tbody>
                </table>

                <br>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="getCheckedCheckboxes()" class="btn btn-success" style="float:right;">Save</button>
                        <a href="indent"><button class='btn btn-danger' type="button" style="float:right;margin-right:10px;">Cancel</button></a>
                    </div>
                </div>

                <?= $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>

<script>
/*
function getCheckedCheckboxes() {
    var skurows = $("#example").dataTable().fnGetNodes();
    var checkedData = [];
    var locationId = $('#location').val();
    var dclocation = $('#dclocation').val();
    var expiry = $('#expiry').val();

    if (!dclocation) {
        Swal.fire({ icon: 'warning', text: 'Please select Warehouse.', confirmButtonColor: '#d33' });
        return;
    }
    if (!expiry) {
        Swal.fire({ icon: 'warning', text: 'Please select expiry date.', confirmButtonColor: '#d33' });
        return;
    }

    var indentid = '<?= $indent_id ?? ''; ?>';

    // ✅ Loop through all rows and pick those with quantity > 0
    $(skurows).each(function() {
        const $row = $(this);
        const quantityInput = $row.find('input[id^="quantity_"]');
        const quantity = quantityInput.val() ? quantityInput.val().trim() : "0";

        if (quantity === "" || quantity === "0") return; // skip empty/zero quantities

        const sku_id = $row.find('input[name^="sku_id"]').val();
        const sku_name = $row.find('input[name^="sku_name"]').val();
        const sku_price = $row.find('input[name^="sku_price"]').val();
        const unit_name = $row.find('input[name^="unit_name"]').val();
        const unit_id = $row.find('input[name^="unit_id"]').val();
        const item_type = $row.find('input[name^="item_type"]').val();
        const sku_brand = $row.find('input[name^="sku_brand"]').val() || '';

        checkedData.push({
            id: sku_id,
            sku_name: sku_name,
            sku_brand: sku_brand,
            price: sku_price,
            unit_name: unit_name,
            unit_id: unit_id,
            sku_quantity: quantity,
            item_type: item_type
        });
    });

    if (checkedData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Items',
            text: 'Please enter quantity for at least one item.'
        });
        return;
    }

    Swal.fire({
        title: 'Confirm Save',
        text: 'Are you sure you want to save this indent?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Save it!',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?php echo $this->Html->url(['controller' => 'outlet', 'action' => 'update_indent']); ?>',
                type: 'POST',
                data: {
                    indent_id: indentid,
                    location_id: locationId,
                    expiry: expiry,
                    dclocation: dclocation,
                    checkedData: JSON.stringify(checkedData)
                },
                success: function(response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.response_code == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated',
                                text: 'Indent has been updated successfully.'
                            }).then(() => {
                                window.location.href = 'indent';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.data || 'Internal error occurred.'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Unexpected response format.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred: ' + error
                    });
                }
            });
        }
    });
}
*/
function getCheckedCheckboxes() {

    var skurows = $("#example").dataTable().fnGetNodes();
    var checkedData = [];

    var locationId = $('#location').val();
    var dclocation = $('#dclocation').val();
    var expiry = $('#expiry').val();

    if (!dclocation) {
        Swal.fire({ icon: 'warning', text: 'Please select Warehouse.' });
        return;
    }

    if (!expiry) {
        Swal.fire({ icon: 'warning', text: 'Please select expiry date.' });
        return;
    }

    var indentid = '<?= $indent_id ?? ''; ?>';

    let subtotal = 0;
    let totalTax = 0;

    let tableRows = '';

    // ===== LOOP ITEMS =====
    $(skurows).each(function () {

        const $row = $(this);

        const qty = parseFloat($row.find('input[id^="quantity_"]').val()) || 0;
        if (qty <= 0) return;

        const name = $row.find('input[name^="sku_name"]').val();
        const price = parseFloat($row.find('input[name^="sku_price"]').val()) || 0;
        const taxPercent = parseFloat($row.find('input[name^="sku_tax"]').val()) || 0;
        
        
        const unit_name = $row.find('input[name^="unit_name"]').val();
        const unit_id = $row.find('input[name^="unit_id"]').val();
        const item_type = $row.find('input[name^="item_type"]').val();
        const sku_brand = $row.find('input[name^="sku_brand"]').val() || '';
        
        const base = price * qty;
        const taxAmount = (base * taxPercent) / 100;
        const rowTotal = base + taxAmount;

        subtotal += base;
        totalTax += taxAmount;

        // build table row for popup
        tableRows += `
            <tr>
                <td>${name}</td>
                <td>${qty}</td>
                <td>₹ ${price.toFixed(2)}</td>
                <td>₹ ${base.toFixed(2)}</td>
                <td>${taxPercent}%</td>
                <td>₹ ${taxAmount.toFixed(2)}</td>
                <td><b>₹ ${rowTotal.toFixed(2)}</b></td>
            </tr>
        `;

        checkedData.push({
            id: $row.find('input[name^="sku_id"]').val(),
            sku_name: name,
            sku_brand: sku_brand,
            price: price,
            unit_name: unit_name,
            unit_id: unit_id,
            item_type: item_type,
            sku_quantity: qty,
            tax_percent: taxPercent,
            tax_amount: taxAmount
        });
    });

    if (checkedData.length === 0) {
        Swal.fire({
            icon: 'warning',
            text: 'Please enter quantity for at least one item.'
        });
        return;
    }

    let grandTotal = subtotal + totalTax;

    // ===== BEAUTIFUL TABLE POPUP =====
    Swal.fire({
        title: 'Confirm Indent Summary',
        width: 900,
        html: `
            <div style="max-height:400px;overflow:auto">
            <table class="table table-bordered table-sm" style="font-size:15px">
                <thead>
                    <tr style="background:#f1f1f1">
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Base</th>
                        <th>Tax %</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
            </div>

            <hr>

            <div style="font-size:16px">
                <b>Subtotal:</b> ₹ ${subtotal.toFixed(2)} <br>
                <b>Total Tax:</b> ₹ ${totalTax.toFixed(2)} <br>
                <h4>Grand Total: ₹ ${grandTotal.toFixed(2)}</h4>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Save Indent',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        reverseButtons: true
    }).then((result) => {

        if (!result.isConfirmed) return;

        $.ajax({
            url: '<?php echo $this->Html->url(['controller' => 'outlet', 'action' => 'update_indent']); ?>',
            type: 'POST',
            data: {
                indent_id: indentid,
                location_id: locationId,
                expiry: expiry,
                dclocation: dclocation,
                subtotal: subtotal,
                tax: totalTax,
                grand_total: grandTotal,
                checkedData: JSON.stringify(checkedData)
            },
            success: function (response) {

                const res = JSON.parse(response);

                if (res.response_code == 1) {
                    Swal.fire('Updated', 'Indent updated successfully', 'success')
                        .then(() => window.location.href = 'indent');
                } else {
                    Swal.fire('Error', res.data || 'Something went wrong', 'error');
                }
            }
        });

    });
}

</script>
