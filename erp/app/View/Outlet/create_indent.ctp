<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Create Indent</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="indent">
                    <button class='btn btn-danger' type="button" style="float:right">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo $this->Form->create(false, ['id' => 'internalIndentForm']);

                echo $this->Form->input('location', [
                    'label' => 'Location',
                    'class' => 'form-control',
                    'value' => $locations,
                    'id' => 'location',
                    'type' => 'hidden'
                ]);

                $options = ['' => 'Select Warehouse'];
                foreach ($optlocations as $location) {
                    $options[$location['Location']['id']] = h($location['Location']['name']);
                }

                echo $this->Form->input('dclocation', [
                    'div' => ['class' => 'form-group'],
                    'label' => 'Select Warehouse',
                    'class' => 'form-control',
                    'options' => $options,
                    'id' => 'dclocation',
                    'required' => true,
                    'onchange' => 'loadItemsByLocation(this.value)'
                ]);

                echo $this->Form->input('expiry', [
                    'label' => 'Expiry Date',
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
                            <th>Type</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Unit Name</th>
                            <th>Price</th>
                            <th>Tax %</th>
                            <th>Available Quantity at Outlet</th>
                            <th>Available Quantity at DC</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($items as $item):
                            // if (isset($item['Inventory_semifinished'])) {
                            //     $sku = $item['Inventory_semifinished'];
                            //     $type = 'Semifinished';
                            // } else {
                                $sku = $item['Inventory_sku'];
                                $stockLedger = $item['StockLedger'];
                                $type = 'SKU';
                            // }
                        ?>
                            <tr>
                                <td><?= $x; ?></td>
                                <td><?= $type; ?></td>
                                <td><?= h($sku['name']); ?></td>
                                <td><?= h($sku['cat_name'] ?? '-'); ?></td>
                                <td><?= h($sku['unit_name'] ?? '-'); ?></td>
                                <td><?= h($sku['price'] ?? '0'); ?></td>
                            
                                <!-- ✅ tax -->
                                <td><?= h($item[0]['total_tax_percentage'] ?? '0'); ?> %</td>
                            
                                <!-- ✅ use correct ledgers -->
                                <td><?= h($item['OutletLedger']['actual_balance'] ?? 'NA'); ?></td>
                                <td><?= h($item['DcLedger']['actual_balance'] ?? 'NA'); ?></td>
                            
                                <td>
                                    <?= $this->Form->input("quantity[{$type}_{$sku['id']}]", [
                                        'class' => 'form-control',
                                        'label' => false,
                                        'type' => 'number',
                                        'style' => 'width:90px',
                                        'value' => 0,
                                        'min' => 0,
                                        'id' => 'quantity_' . $type . '_' . $sku['id']
                                    ]); ?>
                            
                                    <!-- ✅ move hidden inside TD -->
                                    <input type="hidden" id="sku_name_<?= $type.'_'.$sku['id']; ?>" value="<?= h($sku['name']); ?>">
                                    <input type="hidden" id="sku_price_<?= $type.'_'.$sku['id']; ?>" value="<?= h($sku['price']); ?>">
                                    <input type="hidden" id="sku_tax_<?= $type.'_'.$sku['id']; ?>" value="<?= h($item[0]['total_tax_percentage'] ?? 0); ?>">
                                    <input type="hidden" id="unit_name_<?= $type.'_'.$sku['id']; ?>" value="<?= h($sku['unit_name']); ?>">
                                    <input type="hidden" id="unit_id_<?= $type.'_'.$sku['id']; ?>" value="<?= h($sku['unit']); ?>">
                                    <input type="hidden" id="item_type_<?= $type.'_'.$sku['id']; ?>" value="<?= $type; ?>">
                                </td>
                            </tr>

                        <?php $x++; endforeach; ?>
                    </tbody>
                </table><br>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="getCheckedCheckboxes()" class="btn btn-success" style="float:right;">Save</button>
                        <a href="indent">
                            <button class='btn btn-danger' type="button" style="float:right; margin-right: 10px;">Cancel</button>
                        </a>
                    </div>
                </div>
                <?= $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>
<script>
let selectedItems = {};
let table; // store DataTable instance

// ✅ Initialize DataTable once page loads
$(document).ready(function() {
    table = $('#example').DataTable({
        destroy: true,
        paging: true,
        searching: true,
        info: true
    });
});

// // ✅ Handle quantity change (works even after AJAX reload)
// $(document).on('change', "input[type='number'][id^='quantity_']", function () {
//     const quantity = $(this).val().trim();
//     const itemKey = this.id.replace('quantity_', '');

//     if (quantity !== "" && quantity !== "0") {
//         selectedItems[itemKey] = {
//             id: itemKey,
//             sku_name: $('#sku_name_' + itemKey).val(),
//             unit_name: $('#unit_name_' + itemKey).val(),
//             unit_id: $('#unit_id_' + itemKey).val(),
//             sku_quantity: quantity,
//             price: $('#sku_price_' + itemKey).val(),
//             item_type: $('#item_type_' + itemKey).val()
//         };
//     } else {
//         delete selectedItems[itemKey];
//     }
// });

$(document).on('change', "input[type='number'][id^='quantity_']", function () {

    const quantity = $(this).val().trim();
    const itemKey = this.id.replace('quantity_', '');

    if (quantity !== "" && quantity !== "0") {

        selectedItems[itemKey] = {
            id: itemKey,
            sku_name: $('#sku_name_' + itemKey).val(),
            unit_name: $('#unit_name_' + itemKey).val(),
            unit_id: $('#unit_id_' + itemKey).val(),
            sku_quantity: quantity,
            price: $('#sku_price_' + itemKey).val(),
            tax: $('#sku_tax_' + itemKey).val(),   // ✅ ADD THIS
            item_type: $('#item_type_' + itemKey).val()
        };

    } else {
        delete selectedItems[itemKey];
    }
});

/*
// ✅ Handle Save (Submit) button
function getCheckedCheckboxes() {
    const locationId = $('#location').val();
    const expiry = $('#expiry').val();
    const dclocation = $('#dclocation').val();

    if (!dclocation) {
        Swal.fire({
            icon: 'warning',
            text: 'Please select Warehouse.',
            confirmButtonColor: '#d33',
        });
        return;
    }
    if (!expiry) {
        Swal.fire({
            icon: 'warning',
            text: 'Please select expiry date.',
            confirmButtonColor: '#d33',
        });
        return;
    }

    const checkedData = Object.values(selectedItems);

    if (checkedData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No items selected',
            text: 'Please enter quantity for at least one item.',
            confirmButtonColor: '#d33',
        });
        return;
    }

    $.ajax({
        url: '<?= $this->Html->url(['controller' => 'outlet', 'action' => 'add_indent']); ?>',
        type: 'POST',
        data: {
            location_id: locationId,
            dclocation: dclocation,
            expiry: expiry,
            checkedData: JSON.stringify(checkedData)
        },
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Saved successfully!',
                showConfirmButton: false,
                timer: 1200
            }).then(() => {
                window.location.href = 'indent';
            });
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'An error occurred',
                text: error,
                confirmButtonColor: '#d33',
            });
        }
    });
}
*/

function getCheckedCheckboxes() {

    const locationId = $('#location').val();
    const expiry = $('#expiry').val();
    const dclocation = $('#dclocation').val();

    if (!dclocation) {
        Swal.fire('Warning', 'Please select Warehouse', 'warning');
        return;
    }

    const checkedData = Object.values(selectedItems);

    if (checkedData.length === 0) {
        Swal.fire('Warning', 'Please enter quantity for at least one item', 'warning');
        return;
    }

    /* =========================
       BUILD TABLE FOR POPUP
    ========================== */

    let html = `
        <div style="max-height:300px;overflow:auto">
        <table class="table table-bordered table-sm text-center">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Base</th>
                    <th>Tax %</th>
                    <th>Tax Amount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let baseSum = 0;
    let taxSum = 0;
    let grandSum = 0;

    checkedData.forEach(item => {
        
        const price = parseFloat(item.price) || 0;
        const qty   = parseFloat(item.sku_quantity) || 0;
        const taxPercent = parseFloat(item.tax || 0);
        
        const base  = price * qty;
        const tax   = (base * taxPercent) / 100;
        const total = base + tax;
        
        baseSum  += base;
        taxSum   += tax;
        grandSum += total;
        
        html += `
        <tr>
            <td>${item.sku_name}</td>
            <td>₹${price.toFixed(2)}</td>
            <td><b>${qty}</b></td>
            <td>₹${base.toFixed(2)}</td>
            <td>${taxPercent}%</td>
            <td>₹${tax.toFixed(2)}</td>
            <td><b>₹${total.toFixed(2)}</b></td>
        </tr>
        `;
    });

    html += `
        </tbody>
        </table>
        </div>
        
        <hr>
        
        <div style="
            text-align:center;
            font-size:15px;
            font-weight:600;
            line-height:28px;
        ">
            Base Total: ₹${baseSum.toFixed(2)} <br>
            Tax Total: ₹${taxSum.toFixed(2)} <br>
            <span style="font-size:18px;color:#28a745">
                Grand Total: ₹${grandSum.toFixed(2)}
            </span>
        </div>
        `;



    /* =========================
       SWEET ALERT POPUP
    ========================== */

    Swal.fire({
        title: 'Confirm Indent',
        html: html,
        width: '700px',
        showCancelButton: true,
        confirmButtonText: 'Confirm & Save',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33'
    }).then((result) => {

        if (!result.isConfirmed) return;

        /* =========================
           SUBMIT AFTER CONFIRM
        ========================== */

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'outlet', 'action' => 'add_indent']); ?>',
            type: 'POST',
            data: {
                location_id: locationId,
                dclocation: dclocation,
                expiry: expiry,
                checkedData: JSON.stringify(checkedData)
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved successfully!',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'indent';
                });
            }
        });

    });
}


// ✅ Dynamic AJAX loading based on selected warehouse
function loadItemsByLocation(locationId) {
    if (!locationId) {
        if ($.fn.DataTable.isDataTable('#example')) {
            table.clear().draw();
        } else {
            $('#example tbody').html('<tr><td colspan="10" class="text-center">Please select a warehouse.</td></tr>');
        }
        return;
    }

    $.ajax({
        url: '<?= $this->Html->url(['controller' => 'outlet', 'action' => 'get_actualbal_items_by_location']); ?>',
        type: 'POST',
        data: { location_id: locationId },
        beforeSend: function() {
            if ($.fn.DataTable.isDataTable('#example')) {
                table.clear().draw();
            } else {
                $('#example tbody').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');
            }
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // clear table before adding new rows
                    if ($.fn.DataTable.isDataTable('#example')) {
                        table.clear();
                    } else {
                        table = $('#example').DataTable({
                            destroy: true,
                            paging: true,
                            searching: true,
                            info: true
                        });
                    }

                    let x = 1;
                    data.items.forEach(item => {
                        const itemKey = `${item.type}_${item.id}`;
                        const quantityValue = selectedItems[itemKey]?.sku_quantity || 0;

                        const rowHtml = `
                            <input type="number"
                                   id="quantity_${itemKey}"
                                   class="form-control"
                                   style="width:auto"
                                   value="${quantityValue}" min="0">
                            <input type="hidden" id="sku_name_${itemKey}" value="${item.name}">
                            <input type="hidden" id="sku_price_${itemKey}" value="${item.price}">
                            <input type="hidden" id="sku_tax_${itemKey}" value="${item.tax || 0}">
                            <input type="hidden" id="unit_name_${itemKey}" value="${item.unit_name}">
                            <input type="hidden" id="unit_id_${itemKey}" value="${item.unit}">
                            <input type="hidden" id="item_type_${itemKey}" value="${item.type}">
                        `;

                        table.row.add([
                            x++,                              // #
                            item.type,                        // Type
                            item.name,                        // Name
                            item.cat_name || '-',             // Category
                            item.unit_name || '-',            // Unit
                            item.price || '0',                // Price
                            item.tax || item.tax || 0,// ✅ Tax %
                            item.outlet_balance || 'NA',      // Outlet
                            item.dc_balance || 'NA',          // DC
                            rowHtml                           // Quantity
                        ]);

                    });

                    table.draw();
                } else {
                    table.clear().draw();
                    table.row.add(['', '', '', '', '', '', '', '', '<td colspan="10" class="text-center">No items found.</td>']).draw();
                }
            } catch (err) {
                table.clear().draw();
                table.row.add(['', '', '', '', '', '', '', '', '<td colspan="10" class="text-center">Error loading items.</td>']).draw();
            }
        },
        error: function() {
            table.clear().draw();
            table.row.add(['', '', '', '', '', '', '', '', '<td colspan="10" class="text-center text-danger">Error fetching data.</td>']).draw();
        }
    });
}
</script>

