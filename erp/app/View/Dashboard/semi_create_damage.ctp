<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Create Semifinished Damage</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="semi_stock_damage">
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

                $options = ['' => 'Select location'];
                foreach ($locations as $location) {
                    $options[$location['Location']['id']] = h($location['Location']['name']);
                }

                echo $this->Form->input('location', [
                    'div' => ['class' => 'form-group'],
                    'label' => 'Select Location',
                    'class' => 'form-control',
                    'options' => $options,
                    'id' => 'location',
                    'required' => true
                ]);
                
                echo $this->Form->input('damage_date', [
                    'label' => 'Damage Date',       
                    'type' => 'text', // Force CakePHP to use input type, which will be changed via JS
                    'class' => 'form-control',
                    'id' => 'damage_date',
                    'value' => date('Y-m-d'), // Predefined today's date
                    'after' => '<script>document.getElementById("damage_date").type = "date";</script>'
                ]).'<br>';

                ?>

                <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Unit Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $sku = $user['Inventory_semifinished']; ?>
                            <tr>
                                <td><?= $x; ?></td>
                                <td><?= h($sku['name']); ?></td>
                                <td><?= h($sku['brand']); ?></td>
                                <td><?= h($sku['unit_name']); ?></td>
                                <td><?= h($sku['price']); ?></td>
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
                                <input type="hidden" name="sku_price[<?= $sku['id']; ?>]" id="sku_price_<?= $sku['id']; ?>" value="<?= h($sku['price']); ?>">
                                <input type="hidden" name="unit_name[<?= $sku['id']; ?>]" id="unit_name_<?= $sku['id']; ?>" value="<?= h($sku['unit_name']); ?>">
                                <input type="hidden" name="unit_id[<?= $sku['id']; ?>]" id="unit_id_<?= $sku['id']; ?>" value="<?= h($sku['unit']); ?>">
                                <input type="hidden" name="cat_name[<?= $sku['id']; ?>]" id="cat_name_<?= $sku['id']; ?>" value="<?= h($sku['cat_name']); ?>">
                                <input type="hidden" name="cat_id[<?= $sku['id']; ?>]" id="cat_id_<?= $sku['id']; ?>" value="<?= h($sku['cat_id']); ?>">
                            </tr>
                        <?php $x++; endforeach; ?>
                    </tbody>
                </table><br>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="getCheckedCheckboxes()" class="btn btn-success" style="float:right;">Save</button>
                        <a href="semi_stock_damage">
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
function getCheckedCheckboxes() {
    const locationId = $('#location').val();

    if (!locationId) {
        Swal.fire({
            icon: 'warning',
            title: 'Location not selected',
            text: 'Please select a location before saving.',
            confirmButtonColor: '#d33',
        });
        return;
    }
    
    const damage_date = $('#damage_date').val();

    if (!damage_date) {
        Swal.fire({
            icon: 'warning',
            title: 'Damage Date not selected',
            text: 'Please select a Date before saving.',
            confirmButtonColor: '#d33',
        });
        return;
    }

    const checkedData = [];

    $("input[type='number'][id^='quantity_']").each(function () {
        const quantity = $(this).val().trim();
        const skuId = this.id.replace('quantity_', '');

        if (quantity !== "" && quantity !== "0") {
            checkedData.push({
                id: skuId,
                sku_name: $('#sku_name_' + skuId).val(),
                sku_brand: $('#sku_brand_' + skuId).val(),
                unit_name: $('#unit_name_' + skuId).val(),
                unit_id: $('#unit_id_' + skuId).val(),
                cat_id: $('#cat_id_' + skuId).val(),
                cat_name: $('#cat_name_' + skuId).val(),
                sku_quantity: quantity,
                price: $('#sku_price_' + skuId).val()
            });
        }
    });

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
        url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'semi_add_damage']); ?>',
        type: 'POST',
        data: {
            location_id: locationId,
            damage_date: damage_date,
            checkedData: JSON.stringify(checkedData)
        },
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Saved successfully!',
                showConfirmButton: false,
                timer: 1200
            }).then(() => {
                window.location.href = 'semi_stock_damage';
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
