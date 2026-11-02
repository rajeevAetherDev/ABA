<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Create Internal Indent</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="internal_indent_list">
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
                ?>

                <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Base Price</th>
                            <th>Excise ₹/u</th>
                            <th>Addl. Excise ₹/u</th>
                            <th>VAT %</th>
                            <th>TCS %</th>
                            <th>Landed Price</th>
                            <th>Tax %</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $sku = $user['Inventory_sku'];
                            $landedPrice = floatval($sku['last_stockin_price'] ?? $sku['price'] ?? 0);
                            if ($landedPrice <= 0) $landedPrice = floatval($sku['price'] ?? 0);
                        ?>
                            <tr>
                                <td><?= $x; ?></td>
                                <td><?= h($sku['name']); ?></td>
                                <td><?= h($sku['brand']); ?></td>
                                <td><?= h($sku['unit_name']); ?></td>
                                <td>₹<?= number_format(floatval($sku['price']), 2) ?></td>
                                <td id="excise_display_<?= $sku['id'] ?>">—</td>
                                <td id="addl_excise_display_<?= $sku['id'] ?>">—</td>
                                <td id="vat_display_<?= $sku['id'] ?>">—</td>
                                <td id="tcs_display_<?= $sku['id'] ?>">—</td>
                                <td><b>₹<span id="landed_display_<?= $sku['id'] ?>"><?= number_format($landedPrice, 2) ?></span></b></td>
                                <td><?= h($user[0]['total_tax']); ?> %</td>
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
                                <input type="hidden" name="sku_base_price[<?= $sku['id']; ?>]" id="sku_base_price_<?= $sku['id']; ?>" value="<?= h($sku['price']); ?>">
                                <input type="hidden" name="sku_price[<?= $sku['id']; ?>]" id="sku_price_<?= $sku['id']; ?>" value="<?= h($landedPrice); ?>">
                                <input type="hidden" id="sku_excise_<?= $sku['id'] ?>" value="0">
                                <input type="hidden" id="sku_addl_excise_<?= $sku['id'] ?>" value="0">
                                <input type="hidden" id="sku_vat_pct_<?= $sku['id'] ?>" value="0">
                                <input type="hidden" id="sku_tcs_pct_<?= $sku['id'] ?>" value="0">
                                <input type="hidden" name="unit_name[<?= $sku['id']; ?>]" id="unit_name_<?= $sku['id']; ?>" value="<?= h($sku['unit_name']); ?>">
                                <input type="hidden" name="unit_id[<?= $sku['id']; ?>]" id="unit_id_<?= $sku['id']; ?>" value="<?= h($sku['unit']); ?>">
                                <input type="hidden"
                                   id="sku_tax_<?= $sku['id']; ?>"
                                   value="<?= h($user[0]['total_tax']); ?>">
                                <input type="hidden" id="excise_applicable_<?= $sku['id'] ?>" value="<?= !empty($sku['excise_applicable']) ? 1 : 0 ?>">
                            </tr>
                        <?php $x++; endforeach; ?>
                    </tbody>
                </table><br>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="getCheckedCheckboxes()" class="btn btn-success" style="float:right;">Save</button>
                        <a href="internal_indent_list">
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

function showInternalIndentConfirmation(items, onConfirm) {

    let html = `
        <table class="table table-bordered table-sm text-start">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Unit</th>
                    <th>Base Price</th>
                    <th>Excise ₹/u</th>
                    <th>VAT%</th>
                    <th>TCS%</th>
                    <th>Landed Price</th>
                    <th>Tax %</th>
                    <th>Qty</th>
                    <th>Landed Amt</th>
                    <th>Tax Amt</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subtotal = 0;
    let totalTax = 0;
    let totalExcise = 0;

    items.forEach(item => {

        const landedPrice  = parseFloat(item.price) || 0;   // landed = base + excise + VAT
        const excisePu     = parseFloat(item.excise_per_unit)    || 0;
        const addlExcisePu = parseFloat(item.addl_excise_per_unit) || 0;
        const vatPct       = parseFloat(item.vat_percentage)     || 0;
        const tcsPct       = parseFloat(item.tcs_percentage)     || 0;
        const qty          = parseFloat(item.sku_quantity) || 0;
        const taxPct       = parseFloat(item.tax) || 0;

        const landedAmt  = landedPrice * qty;
        const taxValue   = (landedAmt * taxPct) / 100;
        const total      = landedAmt + taxValue;
        const exciseAmt  = (excisePu + addlExcisePu) * qty;

        subtotal    += landedAmt;
        totalTax    += taxValue;
        totalExcise += exciseAmt;

        html += `
            <tr>
                <td>${item.sku_name}</td>
                <td>${item.sku_brand}</td>
                <td>${item.unit_name}</td>
                <td>₹${(landedPrice - excisePu - addlExcisePu).toFixed(2)}</td>
                <td>₹${(excisePu + addlExcisePu).toFixed(2)}</td>
                <td>${vatPct > 0 ? vatPct + '%' : '—'}</td>
                <td>${tcsPct > 0 ? tcsPct + '%' : '—'}</td>
                <td><b>₹${landedPrice.toFixed(2)}</b></td>
                <td>${taxPct}%</td>
                <td><b>${qty}</b></td>
                <td>₹${landedAmt.toFixed(2)}</td>
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
            <b>Landed Subtotal :</b> ₹${subtotal.toFixed(2)}<br>
            <b>Excise (incl. in landed) :</b> ₹${totalExcise.toFixed(2)}<br>
            <b>Total Tax (GST) :</b> ₹${totalTax.toFixed(2)}<br>
            <h5 class="mt-2"><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
        </div>
    `;

    Swal.fire({
        title: 'Confirm Internal Indent',
        html: html,
        icon: 'warning',
        width: '1100px',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
}

// Store all selected SKUs across pages
let selectedSkus = {};
// Track changes in quantity inputs
$(document).on('change', "input[type='number'][id^='quantity_']", function () {
    const quantity = $(this).val().trim();
    const skuId = this.id.replace('quantity_', '');
    if (quantity !== "" && quantity !== "0") {
        selectedSkus[skuId] = {
            id: skuId,
            sku_name: $('#sku_name_' + skuId).val(),
            sku_brand: $('#sku_brand_' + skuId).val(),
            unit_name: $('#unit_name_' + skuId).val(),
            unit_id: $('#unit_id_' + skuId).val(),
            sku_quantity: quantity,
            price: $('#sku_price_' + skuId).val(),      // landed price (base + excise + VAT)
            excise_per_unit: $('#sku_excise_' + skuId).val(),
            addl_excise_per_unit: $('#sku_addl_excise_' + skuId).val(),
            vat_percentage: $('#sku_vat_pct_' + skuId).val(),
            tcs_percentage: $('#sku_tcs_pct_' + skuId).val(),
            tax: $('#sku_tax_' + skuId).val()
        };
    } else {
        delete selectedSkus[skuId];
    }
});

// When location changes, fetch per-location excise rates and update the table
$('#location').on('change', function () {
    const locationId = $(this).val();
    if (!locationId) return;


    $.get('/dashboard/get_location_excise_rates', { location_id: locationId }, function(data) {
        if (data && data.rates) {
            Object.keys(data.rates).forEach(function(skuId) {
                const epu    = parseFloat(data.rates[skuId].excise_per_unit)      || 0;
                const aepu   = parseFloat(data.rates[skuId].addl_excise_per_unit) || 0;
                const vatPct = parseFloat(data.rates[skuId].vat_percentage)       || 0;
                const tcsPct = parseFloat(data.rates[skuId].tcs_percentage)       || 0;
                const basePr = parseFloat($('#sku_base_price_' + skuId).val())    || 0;
                // Landed = base + excise + addl_excise + VAT on (base+excise)
                const taxBasePu = basePr + epu + aepu;
                const vatPu     = taxBasePu * (vatPct / 100);
                const landedPr  = taxBasePu + vatPu;

                $('#sku_excise_'       + skuId).val(epu);
                $('#sku_addl_excise_'  + skuId).val(aepu);
                $('#sku_vat_pct_'      + skuId).val(vatPct);
                $('#sku_tcs_pct_'      + skuId).val(tcsPct);
                $('#sku_price_'        + skuId).val(landedPr.toFixed(2));

                $('#excise_display_'     + skuId).text(epu > 0 ? '₹' + epu.toFixed(2) : '—');
                $('#addl_excise_display_'+ skuId).text(aepu > 0 ? '₹' + aepu.toFixed(2) : '—');
                $('#vat_display_'        + skuId).text(vatPct > 0 ? vatPct + '%' : '—');
                $('#tcs_display_'        + skuId).text(tcsPct > 0 ? tcsPct + '%' : '—');
                $('#landed_display_'     + skuId).text(landedPr.toFixed(2));

                // Refresh selectedSkus if this SKU was already selected
                if (selectedSkus[skuId]) {
                    selectedSkus[skuId].price              = landedPr.toFixed(2);
                    selectedSkus[skuId].excise_per_unit    = epu;
                    selectedSkus[skuId].addl_excise_per_unit = aepu;
                    selectedSkus[skuId].vat_percentage     = vatPct;
                    selectedSkus[skuId].tcs_percentage     = tcsPct;
                }
            });
        }
    }, 'json');
});

// Auto-trigger on page load if a location is already pre-selected
$(document).ready(function () {
    const preselected = $('#location').val();
    if (preselected) {
        $('#location').trigger('change');
    }
});

/*
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
    const checkedData = Object.values(selectedSkus);
    // const checkedData = [];
    // $("input[type='number'][id^='quantity_']").each(function () {
    //     const quantity = $(this).val().trim();
    //     const skuId = this.id.replace('quantity_', '');
    //     if (quantity !== "" && quantity !== "0") {
    //         checkedData.push({
    //             id: skuId,
    //             sku_name: $('#sku_name_' + skuId).val(),
    //             sku_brand: $('#sku_brand_' + skuId).val(),
    //             unit_name: $('#unit_name_' + skuId).val(),
    //             unit_id: $('#unit_id_' + skuId).val(),
    //             sku_quantity: quantity,
    //             price: $('#sku_price_' + skuId).val()
    //         });
    //     }
    // });
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
        url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'add_internal_indent']); ?>',
        type: 'POST',
        data: {
            location_id: locationId,
            checkedData: JSON.stringify(checkedData)
        },
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Saved successfully!',
                showConfirmButton: false,
                timer: 1200
            }).then(() => {
                window.location.href = 'internal_indent_list';
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

    if (!locationId) {
        Swal.fire({
            icon: 'warning',
            title: 'Location not selected',
            text: 'Please select a location before saving.'
        });
        return;
    }

    const checkedData = Object.values(selectedSkus);

    if (checkedData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No SKUs selected',
            text: 'Please enter quantity for at least one SKU.'
        });
        return;
    }

    // 🔥 SHOW ITEM SUMMARY POPUP
    showInternalIndentConfirmation(checkedData, function () {

        // ✅ AJAX only after confirm
        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'add_internal_indent']); ?>',
            type: 'POST',
            data: {
                location_id: locationId,
                checkedData: JSON.stringify(checkedData)
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved successfully!',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'internal_indent_list';
                });
            }
        });

    });
}

</script>

