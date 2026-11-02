<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Create SKU Direct Purchase</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="direct_purchase">
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
                ?>
            
                <div class="row">
                    <!-- ✅ Vendor dropdown (col-md-8) -->
                    <div class="col-md-8">
                        <div class="form-group">
                            <?= $this->Form->input('vendor', [
                                'label' => 'Select Vendor',
                                'class' => 'form-control',
                                'id' => 'select_vendor',
                                'empty' => '- Select Vendor -',
                                'options' => $vendoroptions,
                                'required' => true,
                                'div' => false,
                            ]); ?>
                        </div>
                    </div>
                </div>
            
                <div class="row mt-3">
                    <!-- ✅ SKU Table (col-md-12 full width) -->
                    <div class="col-md-12">
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Unit Name</th>
                                    <th>Price</th>
                                    <th>Available Quantity</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $x = 1;
                                foreach ($users as $user):
                                    $sku = $user['Inventory_sku'];
                                    $stockledger = $user['StockLedger'];
                                    ?>
                                    <tr>
                                        <td><?= $x; ?></td>
                                        <td><?= h($sku['name']); ?></td>
                                        <td><?= h($sku['unit_name']); ?></td>
                                        <td>
                                            <?= $this->Form->input("sku_price[{$sku['id']}]", [
                                                'class' => 'form-control',
                                                'label' => false,
                                                'type' => 'number',
                                                'style' => 'width:auto',
                                                'value' => $sku['price'],
                                                'min' => 0,
                                                'id' => 'sku_price_' . $sku['id']
                                            ]); ?>
                                        </td>
                                        <td><?=$stockledger['actual_balance'] ?? 'NA'?></td>
                                        <td>
                                            <?= $this->Form->input("quantity[{$sku['id']}]", [
                                                'class' => 'form-control',
                                                'label' => false,
                                                'type' => 'number',
                                                'style' => 'width:auto',
                                                'value' => 0,
                                                'min' => 0,
                                                'id' => 'quantity_' . $sku['id']
                                            ]); ?>
                                        </td>
                                        <input type="hidden" name="sku_id[<?= $sku['id']; ?>]" value="<?= $sku['id']; ?>">
                                        <input type="hidden" name="sku_name[<?= $sku['id']; ?>]" id="sku_name_<?= $sku['id']; ?>" value="<?= h($sku['name']); ?>">
                                        <input type="hidden" name="sku_brand[<?= $sku['id']; ?>]" id="sku_brand_<?= $sku['id']; ?>" value="<?= h($sku['brand']); ?>">
                                        <input type="hidden" name="unit_name[<?= $sku['id']; ?>]" id="unit_name_<?= $sku['id']; ?>" value="<?= h($sku['unit_name']); ?>">
                                        <input type="hidden" name="unit_id[<?= $sku['id']; ?>]" id="unit_id_<?= $sku['id']; ?>" value="<?= h($sku['unit']); ?>">
                                    </tr>
                                <?php $x++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
                <br>
            
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="getCheckedCheckboxes()" class="btn btn-success" style="float:right;">Save</button>
                        <a href="direct_purchase">
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
// Global object to store all SKU quantities across pagination
let selectedItems = {};

// When qty changes, store it
$(document).on('input', "input[type='number'][id^='quantity_']", function () {
    const skuId = this.id.replace('quantity_', '');
    const quantity = $(this).val().trim();
    const price = $('#sku_price_' + skuId).val().trim();

    if (quantity !== "" && quantity !== "0") {
        selectedItems[skuId] = {
            id: skuId,
            sku_name: $('#sku_name_' + skuId).val(),
            sku_brand: $('#sku_brand_' + skuId).val(),
            unit_name: $('#unit_name_' + skuId).val(),
            unit_id: $('#unit_id_' + skuId).val(),
            sku_quantity: quantity,
            price: price
        };
    } else {
        // If quantity reset to 0 → remove from saving list
        delete selectedItems[skuId];
    }
});

function getCheckedCheckboxes() {
    const vendorId = $('#select_vendor').val();

    // Vendor validation
    if (!vendorId || vendorId === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Vendor not selected',
            text: 'Please select a vendor before saving.',
            confirmButtonColor: '#d33',
        });
        return;
    }

    // Convert stored object to array for saving
    const checkedData = Object.values(selectedItems);

    if (checkedData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No SKUs selected',
            text: 'Please enter quantity for at least one SKU.',
            confirmButtonColor: '#d33',
        });
        return;
    }

    $.ajax({
        url: '<?= $this->Html->url(['controller' => 'outlet', 'action' => 'add_direct_purchase']); ?>',
        type: 'POST',
        data: {
            vendor_id: vendorId,
            checkedData: JSON.stringify(checkedData)
        },
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Saved successfully!',
                showConfirmButton: false,
                timer: 1200
            }).then(() => {
                window.location.href = 'direct_purchase';
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
</script>

