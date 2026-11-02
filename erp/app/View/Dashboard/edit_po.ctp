<div class="row mb-3">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Update Purchase Order</h2>
        </div>
    </div>
    <div class="col-sm-1 text-right">
        <a href="po_list" class="btn btn-danger">Back</a>
    </div>
</div>
<?= $this->Form->create('User', ['type' => 'file']); ?>
                
<div class="row mb-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <?= $this->Form->input('po_ids', [
                            'label' => 'PO Id',
                            'class' => 'form-control',
                            'readonly' => true,
                            'value' => $purchase_orders[0]['inventory_purchase_order']['p_o_id']
                        ]); ?>
                    </div>
                    <div class="form-group col-md-3">
                        <?= $this->Form->input('Indent Id', [
                            'class' => 'form-control',
                            'readonly' => true,
                            'value' => $purchase_orders[0]['inventory_purchase_order']['internal_indent_id']
                        ]); ?>
                    </div>
                    <div class="form-group col-md-3">
                        <?= $this->Form->input('Location Name', [
                            'class' => 'form-control',
                            'readonly' => true,
                            'value' => $purchase_orders[0]['inventory_purchase_order']['location_name']
                        ]); ?>
                    </div>
                    <div class="form-group col-md-3">
                        <?= $this->Form->input('Vendor Name', [
                            'class' => 'form-control',
                            'readonly' => true,
                            'value' => $purchase_orders[0]['inventory_purchase_order']['vendor_name']
                        ]); ?>
                    </div>
                    <div class="form-group col-md-3">
                        <?= $this->Form->input('charge', [
                                'div' => ['class' => 'form-group'],
                                'id' => 'charge',
                                'label' => 'Charges',
                                'class' => 'form-control',
                                'required' => true,
                                'type' => 'number',
                                'value' => $purchase_orders[0]['inventory_purchase_order']['charge'],
                                'step' => 'any',
                                'min' => '0'
                            ]); ?>
                    </div>
                    <div class="form-group col-md-3">
                        <label>PO Date</label>
                        <input type="date" id="po_date" name="po_date" class="form-control" value="<?= h($purchase_orders[0]['inventory_purchase_order']['po_date']) ?>"  required>
                    </div>
                        
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?= $this->Form->hidden('indent', ['value' => $vendor_items[0]['i']['indent_id'] ?? '']); ?>
                <?= $this->Form->hidden('p_o_id', ['value' => $purchase_orders[0]['inventory_purchase_order']['p_o_id']]); ?>

                <h5>Items Served by Vendor</h5>
                <div class="table-responsive">
                    <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Brand</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Excise ₹/u</th>
                                <th>Addl.Ex ₹/u</th>
                                <th>VAT%</th>
                                <th>TCS%</th>
                                <th>Tax%</th>
                                <th>Quantity</th>
                                <th>Discount Type</th>
                                <th>Discount</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $x = 1; foreach ($vendor_items as $vendor_item):
                                $sku_price = $vendor_item['p']['psku_price'] ?? $vendor_item[0]['psku_price'] ?? $vendor_item['v']['vprice'] ?? $vendor_item[0]['vprice'] ?? 0;
                                $sku_quantity = $vendor_item['p']['psku_quantity'] ?? $vendor_item[0]['psku_quantity'] ?? 0;
                                $max_qty = $vendor_item['i']['sku_quantity']-$vendor_item['i']['completed_quantity']+($vendor_item['p']['psku_quantity'] ?? $vendor_item[0]['psku_quantity'] ?? 0);
                                $sku_id = $vendor_item['i']['sku_id'];
                                $disabled = ($vendor_item['i']['is_completed'] == 1) ? 'disabled' : '';
                                
                                $discount = $vendor_item['p']['psku_discount'] ?? $vendor_item[0]['psku_discount'] ?? 0;
                                $discountType = $vendor_item['p']['psku_discount_type'] ?? $vendor_item[0]['psku_discount_type'] ?? 'flat';
                                
                                $base = $sku_price * $sku_quantity;
                                
                                if ($discountType == 'percentage') {
                                    $validDiscount = ($base * $discount) / 100;
                                } else {
                                    $validDiscount = min($discount, $base);
                                }
                                
                                $afterDiscount = $base - $validDiscount;
                                
                                $_p_excise_pu   = floatval($vendor_item['p']['pexcise_pu']     ?? $vendor_item[0]['pexcise_pu']     ?? 0);
                                $_p_addl_exc_pu = floatval($vendor_item['p']['paddl_excise_pu']  ?? $vendor_item[0]['paddl_excise_pu']  ?? 0);
                                $_p_vat_pct     = floatval($vendor_item['p']['pvat_pct']         ?? $vendor_item[0]['pvat_pct']         ?? 0);
                                $_p_tcs_pct     = floatval($vendor_item['p']['ptcs_pct']         ?? $vendor_item[0]['ptcs_pct']         ?? 0);
                                $_p_excise_amt  = round($_p_excise_pu * $sku_quantity, 2);
                                $_p_addl_exc_amt= round($_p_addl_exc_pu * $sku_quantity, 2);
                                
                                $_p_vat_base    = $afterDiscount + $_p_excise_amt + $_p_addl_exc_amt;
                                $_p_vat_amt     = round($_p_vat_base * ($_p_vat_pct / 100), 2);
                                $_p_landed_amt  = round($afterDiscount + $_p_excise_amt + $_p_vat_amt, 2);
                                $_p_tcs_base    = $_p_landed_amt;
                                $_p_tcs_amt     = round($_p_tcs_base * ($_p_tcs_pct / 100), 2);
                                
                                $ssku_tax = floatval($vendor_item['tax']['ssku_tax'] ?? $vendor_item[0]['ssku_tax'] ?? 0);
                                if ($_p_excise_pu > 0 || $_p_vat_pct > 0) {
                                    $total = $_p_landed_amt + $_p_tcs_amt;
                                    $_eff_tax_pct = 0;
                                } else {
                                    $tax = ($afterDiscount * $ssku_tax) / 100;
                                    $total = $afterDiscount + $tax;
                                    $_eff_tax_pct = $ssku_tax;
                                }
                            ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($vendor_item['i']['sku_name']); ?></td>
                                <td><?= h($vendor_item['i']['sku_brand']); ?></td>
                                <td><?= h($vendor_item['i']['sku_unit_name']); ?></td>
                                <td>
                                    <input type="number"
                                           name="prices[<?= $sku_id ?>]"
                                           value="<?= $sku_price ?>"
                                           class="form-control item-price"
                                           step="any" />
                                    <input type="hidden" class="tax-pct" value="<?= $ssku_tax ?>">
                                </td>
                                
                                <td>
                                    <input type="number"
                                           name="excise_duty[<?= $sku_id ?>]"
                                           value="<?= $_p_excise_pu ?>"
                                           class="form-control excise-pu vendor-excise"
                                           step="any" min="0" style="width:90px" />
                                </td>
                                <td>
                                    <input type="number"
                                           name="addl_excise[<?= $sku_id ?>]"
                                           value="<?= $_p_addl_exc_pu ?>"
                                           class="form-control addl-excise-pu vendor-addl-excise"
                                           step="any" min="0" style="width:90px" />
                                </td>
                                <td>
                                    <input type="number"
                                           name="vat_duty[<?= $sku_id ?>]"
                                           value="<?= $_p_vat_pct ?>"
                                           class="form-control vat-pct vendor-vat"
                                           step="any" min="0" max="100" style="width:80px" />
                                </td>
                                <td>
                                    <input type="number"
                                           name="tcs_duty[<?= $sku_id ?>]"
                                           value="<?= $_p_tcs_pct ?>"
                                           class="form-control tcs-pct vendor-tcs"
                                           step="any" min="0" max="100" style="width:80px" />
                                </td>
                                <td><?= number_format($_eff_tax_pct ?? 0, 2); ?> %</td>
                                <td>
                                    <input type="number"
                                           name="quantity[<?= $sku_id ?>]"
                                           value="<?= $sku_quantity ?>"
                                           class="form-control vendor-quantity"
                                           step="any">
                                </td>
                                <td>
                                    <select name="discount_type[<?= $sku_id ?>]" class="form-control discount-type">
                                        <option value="flat" <?= $discountType=='flat'?'selected':'' ?>>Flat</option>
                                        <option value="percentage" <?= $discountType=='percentage'?'selected':'' ?>>Percentage</option>
                                    </select>
                                </td>
                                
                                <td>
                                    <input type="number"
                                       name="discount[<?= $sku_id ?>]"
                                       value="<?= $discount ?>"
                                       class="form-control vendor-discount"
                                       step="any"
                                       min="0">
                                </td>
                                <td class="row-amount"><?= number_format($total,2); ?></td>
                                
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-right mt-3">
                    <a href="po_list" class="btn btn-danger ml-2">Cancel</a>
                    <?= $this->Form->button('Save', [
                        'class' => 'btn btn-success',
                        'id' => 'saveBtn'
                    ]); ?>

                </div>

                <?= $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>

<script>

function showUpdatePOConfirmation(po_date, charge, onConfirm) {

    let html = `
        <table class="table table-bordered table-sm text-start" style="font-size:12px;">
            <thead>
                <tr>
                    <th>Name</th><th>Brand</th><th>Unit</th><th>Price</th><th>Qty</th>
                    <th>Base</th><th>Excise</th><th>VAT</th><th>Discount</th><th>Tax%</th><th>Tax</th><th>TCS</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subtotal      = 0;
    let totalTax      = 0;
    let totalDiscount = 0;
    let totalExcise   = 0;
    let totalVat      = 0;
    let totalTcs      = 0;
    let grandTotalSum = 0;

    const table = $('#example').DataTable();

    table.rows().every(function () {

        const $row = $(this.node());

        const qty = parseFloat($row.find("input[name^='quantity']").val()) || 0;
        if (qty <= 0) return;

        const name         = $row.find('td').eq(1).text().trim();
        const brand        = $row.find('td').eq(2).text().trim();
        const unit         = $row.find('td').eq(3).text().trim();
        const price        = parseFloat($row.find("input[name^='prices']").val()) || 0;
        let   disc         = parseFloat($row.find("input[name^='discount']").val()) || 0;
        const discType     = $row.find("select[name^='discount_type']").val() || 'flat';
        const excisePu     = parseFloat($row.find('.excise-pu').val()) || 0;
        const addlExcisePu = parseFloat($row.find('.addl-excise-pu').val()) || 0;
        const vatPct       = parseFloat($row.find('.vat-pct').val())    || 0;
        const tcsPct       = parseFloat($row.find('.tcs-pct').val())    || 0;
        let taxPct         = parseFloat($row.find('.tax-pct').val())    || 0;
        if (excisePu > 0 || vatPct > 0) taxPct = 0;

        const baseAmount = price * qty;

        let discountValue = 0;
        if (discType === 'percentage') {
            if (disc > 100) disc = 100;
            discountValue = (baseAmount * disc) / 100;
        } else {
            if (disc > baseAmount) disc = baseAmount;
            discountValue = disc;
        }

        const baseAfterDiscount = baseAmount - discountValue;
        const exciseAmt    = excisePu * qty;
        const addlExciseAmt= addlExcisePu * qty;
        const vatBase   = baseAfterDiscount + exciseAmt + addlExciseAmt;
        const vatAmt    = vatBase * vatPct / 100;
        const landed    = baseAfterDiscount + exciseAmt + vatAmt;
        const tcsAmt    = landed * tcsPct / 100;
        const taxValue  = landed * taxPct / 100;
        const total     = landed + taxValue + tcsAmt;

        subtotal      += baseAmount;
        totalDiscount += discountValue;
        totalExcise   += (exciseAmt + addlExciseAmt);
        totalVat      += vatAmt;
        totalTcs      += tcsAmt;
        totalTax      += taxValue;
        grandTotalSum += total;

        html += `
            <tr>
                <td>${name}</td><td>${brand}</td><td>${unit}</td>
                <td>₹${price.toFixed(2)}</td><td>${qty}</td>
                <td>₹${baseAfterDiscount.toFixed(2)}</td>
                <td>₹${(exciseAmt + addlExciseAmt).toFixed(2)}</td>
                <td>₹${vatAmt.toFixed(2)}</td>
                <td>${discType === 'percentage' ? disc + '%' : '₹' + discountValue.toFixed(2)}</td>
                <td>${taxPct}%</td><td>₹${taxValue.toFixed(2)}</td>
                <td>₹${tcsAmt.toFixed(2)}</td>
                <td><b>₹${total.toFixed(2)}</b></td>
            </tr>
        `;
    });

    const chargeVal = parseFloat(charge) || 0;
    const subtotalAfterDiscount = subtotal - totalDiscount;
    const grandTotal = grandTotalSum + chargeVal;

    html += `
        </tbody>
    </table>

    <div class="text-end mt-3">
        <h6>PO Date : ${po_date}</h6>
        <h6>Subtotal : ₹${subtotal.toFixed(2)}</h6>
        <h6>Total Discount : ₹${totalDiscount.toFixed(2)}</h6>
        <h6>Subtotal After Discount : ₹${subtotalAfterDiscount.toFixed(2)}</h6>
        <h6>Total Excise Duty : ₹${totalExcise.toFixed(2)}</h6>
        <h6>Total VAT (irrecoverable, in landed cost) : ₹${totalVat.toFixed(2)}</h6>
        <h6>Total Tax (GST/CGST/SGST) : ₹${totalTax.toFixed(2)}</h6>
        <h6>Total TCS (recoverable advance) : ₹${totalTcs.toFixed(2)}</h6>
        <h6>Charges : ₹${chargeVal.toFixed(2)}</h6>
        <h5 class="mt-2"><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
    </div>
    `;

    Swal.fire({
        title: 'Confirm Update Purchase Order',
        html: html,
        icon: 'warning',
        width: '1100px',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
}


$(document).ready(function () {
    
    function recalculateRow(row) {
        const $row = $(row);
        const price = parseFloat($row.find("input[name^='prices']").val()) || 0;
        const qty = parseFloat($row.find("input[name^='quantity']").val()) || 0;
        const discInput = parseFloat($row.find("input[name^='discount']").val()) || 0;
        const discType = $row.find("select[name^='discount_type']").val() || 'flat';

        const excisePu = parseFloat($row.find('.excise-pu').val()) || 0;
        const addlExcisePu = parseFloat($row.find('.addl-excise-pu').val()) || 0;
        const vatPct = parseFloat($row.find('.vat-pct').val()) || 0;
        const tcsPct = parseFloat($row.find('.tcs-pct').val()) || 0;
        let taxPct = parseFloat($row.find('.tax-pct').val()) || 0;

        const baseAmount = price * qty;
        let discountValue = 0;
        if (discType === 'percentage') {
            discountValue = (baseAmount * Math.min(discInput, 100)) / 100;
        } else {
            discountValue = Math.min(discInput, baseAmount);
        }

        const afterDiscount = baseAmount - discountValue;

        if (excisePu > 0 || vatPct > 0) {
            taxPct = 0;
        }

        const exciseAmt = excisePu * qty;
        const addlExciseAmt = addlExcisePu * qty;
        const vatBase = afterDiscount + exciseAmt + addlExciseAmt;
        const vatAmt = vatBase * (vatPct / 100);
        const tcsBase = afterDiscount + exciseAmt + vatAmt;
        const tcsAmt = tcsBase * (tcsPct / 100);

        const landed = afterDiscount + exciseAmt + vatAmt;
        const taxAmt = landed * (taxPct / 100);

        const total = landed + taxAmt + tcsAmt;
        $row.find('.row-amount').text(total.toFixed(2));
    }

    $(document).on('input change', "input[name^='prices'], input[name^='quantity'], input[name^='discount'], select[name^='discount_type'], input[name^='excise_duty'], input[name^='addl_excise'], input[name^='vat_duty'], input[name^='tcs_duty']", function () {
        const row = $(this).closest('tr');
        recalculateRow(row);
    });

    $(document).on('input', "input[name^='quantity']", function () {

        const row = $(this).closest('tr');
    
        const price = parseFloat(row.find("input[name^='prices']").val()) || 0;
        const qty = parseFloat($(this).val());
    
        if (isNaN(qty)) return;
    
        const base = price * qty;
    
        const discountInput = row.find('.vendor-discount');
        let discount = parseFloat(discountInput.val());
    
        if (isNaN(discount)) discount = 0;
    
        const discountType = row.find('.discount-type').val();
    
        if (discountType === 'flat' && discount > base) {
            discountInput.val(base.toFixed(2));
        }
    
        if (discountType === 'percentage' && discount > 100) {
            discountInput.val(100);
        }
    });
    
    $(document).on('input', '.vendor-discount', function () {

        const row = $(this).closest('tr');
    
        const price = parseFloat(row.find("input[name^='prices']").val()) || 0;
        const qty   = parseFloat(row.find("input[name^='quantity']").val()) || 0;
    
        const baseAmount = price * qty;
        let discount = parseFloat($(this).val()) || 0;
        const discountType = row.find('.discount-type').val();
        
        if (discountType === 'flat' && discount > baseAmount) {
    
            Swal.fire({
                toast: true,
                icon: 'warning',
                title: 'Discount cannot exceed item amount ₹' + baseAmount.toFixed(2),
                timer: 2000,
                showConfirmButton: false
            });
    
            $(this).val(baseAmount.toFixed(2));
        }
        
        if (discountType === 'percentage' && discount > 100) {
        
            Swal.fire({
                toast: true,
                icon: 'warning',
                title: 'Percentage discount cannot exceed 100%',
                timer: 2000,
                showConfirmButton: false
            });
        
            $(this).val(100);
        }
        if (discount < 0) {
            $(this).val(0);
        }
        
        if (qty < 0) {
            $(this).val(0);
        }
    });

    const table = $('#example').DataTable();

    /* ===============================
       HELPER: CHECK ANY QTY > 0
    =============================== */
    function hasAnySelectedQuantity() {
        
        let hasQty = false;
        
        table.rows().every(function () {
            const qty = parseFloat($(this.node()).find("input[name^='quantity']").val()) || 0;
        
            if (qty > 0) {
                hasQty = true;
                return false;
            }
        });
        
        return hasQty;
    }


    /* ===============================
       INITIAL STATE
    =============================== */
    $('#saveBtn').prop('disabled', !hasAnySelectedQuantity());

    /* ===============================
       LIVE QTY CHANGE
    =============================== */
    $(document).on('input', "input[name^='quantity']", function () {
        $('#saveBtn').prop('disabled', !hasAnySelectedQuantity());
    });

    /* ===============================
       FINAL SUBMIT VALIDATION
    =============================== */
    $('form').on('submit', function (e) {
        e.preventDefault(); // ⛔ stop immediate submit
    
        // 🔴 No vendor items
        if (table.rows().count() === 0){
            Swal.fire({
                icon: 'error',
                title: 'No item found for this vendor'
            });
            return;
        }
    
        // 🔴 All quantities zero
        if (!hasAnySelectedQuantity()) {
            Swal.fire({
                icon: 'error',
                title: 'No item selected',
                text: 'Please enter quantity for at least one item'
            });
            return;
        }
    
        const form = this;
        const charge = $('#charge').val();
        const po_date = $('#po_date').val();
    
        // 🔥 CONFIRMATION POPUP
        showUpdatePOConfirmation(po_date, charge, function () {
    
            // 🧹 Remove old hidden inputs
            $(form)
                .find('input[type=hidden][name^="prices["], input[type=hidden][name^="quantity["], input[type=hidden][name^="discount["], input[type=hidden][name^="discount_type["], input[type=hidden][name^="excise_duty["], input[type=hidden][name^="addl_excise["], input[type=hidden][name^="vat_duty["], input[type=hidden][name^="tcs_duty["]')
                .remove();
    
            // 📦 Submit ALL DataTable rows
            table.rows().every(function () {
    
                const $row = $(this.node());
                const priceInput = $row.find('input[name^="prices["]');
                const qtyInput   = $row.find('input[name^="quantity["]');
                const discountInput = $row.find('input[name^="discount["]');
                const discountTypeInput = $row.find('select[name^="discount_type["]');
                const exciseInput = $row.find('.excise-pu');
                const addlExciseInput = $row.find('.addl-excise-pu');
                const vatInput = $row.find('.vat-pct');
                const tcsInput = $row.find('.tcs-pct');
                
                if (!priceInput.length || !qtyInput.length) return;
    
                const skuId = priceInput.attr('name').match(/\[(.*?)\]/)[1];
    
                $('<input>', {
                    type: 'hidden',
                    name: `prices[${skuId}]`,
                    value: priceInput.val()
                }).appendTo(form);
    
                $('<input>', {
                    type: 'hidden',
                    name: `quantity[${skuId}]`,
                    value: qtyInput.val()
                }).appendTo(form);
                
                $('<input>', {
                    type: 'hidden',
                    name: `discount[${skuId}]`,
                    value: discountInput.val()
                }).appendTo(form);
                
                $('<input>', {
                    type: 'hidden',
                    name: `discount_type[${skuId}]`,
                    value: discountTypeInput.val()
                }).appendTo(form);

                $('<input>', {
                    type: 'hidden',
                    name: `excise_duty[${skuId}]`,
                    value: exciseInput.val() || 0
                }).appendTo(form);

                $('<input>', {
                    type: 'hidden',
                    name: `addl_excise[${skuId}]`,
                    value: addlExciseInput.val() || 0
                }).appendTo(form);

                $('<input>', {
                    type: 'hidden',
                    name: `vat_duty[${skuId}]`,
                    value: vatInput.val() || 0
                }).appendTo(form);

                $('<input>', {
                    type: 'hidden',
                    name: `tcs_duty[${skuId}]`,
                    value: tcsInput.val() || 0
                }).appendTo(form);
            });
    
            // ✅ FINAL SUBMIT
            form.submit();
        });
    });


});
</script>

