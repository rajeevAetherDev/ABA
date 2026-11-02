<div class="row align-items-center">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Create PO</h2>
        </div>
    </div>
    <div class="col-sm-1 text-end">
        <a href="po_list" class="btn btn-danger">Back</a>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?= $this->Form->create('User', ['type' => 'file']) ?>

                <!-- Location -->
                <div class="form-group">
                    <?= $this->Form->input('location', [
                        'label' => 'Select Location',
                        'class' => 'form-control',
                        'id' => 'location',
                        'empty' => '- Select Location -',
                        'options' => $locationOptions,
                        'required' => true,
                        'div' => false
                    ]); ?>
                </div>

                <!-- Indent -->
                <div class="form-group">
                    <?= $this->Form->input('indent', [
                        'label' => 'Select Indent',
                        'class' => 'form-control',
                        'id' => 'select_indent',
                        'empty' => '- Select Indent -',
                        'options' => [],
                        'required' => true,
                        'div' => false,
                        'disabled' => true
                    ]); ?>
                </div>

                <!-- Loader -->
                <div id="indent_loader" class="mb-2" style="display: none;">
                    <i class="fa fa-spinner fa-spin text-primary"></i> Loading indents...
                </div>

                <!-- Vendor -->
                <div class="form-group">
                    <?= $this->Form->input('vendor', [
                        'label' => 'Select Vendor',
                        'class' => 'form-control',
                        'id' => 'select_vendor',
                        'empty' => '- Select Vendor -',
                        'options' => $vendoroptions,
                        'required' => true,
                        'div' => false,
                        'disabled' => true
                    ]); ?>
                </div>
                
                <?php $today = date('Y-m-d'); ?>
                <div class="form-group row">
                    <div class="col-sm-6">
                        <?php
                            echo $this->Form->input('charge', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Charges',
                                'class' => 'form-control',
                                'required' => true,
                                'type' => 'number',
                                'default' => '0',
                                'step' => 'any',   // âœ… allows decimal values
                                'min' => '0'        // optional: no negative values
                            ));
        
                        ?>
                    </div>
                    <div class="col-sm-6">
                        <label>Select Date</label>
                        <input type="date" id="po_date" name="po_date" class="form-control" max="<?= h($today) ?>" value="<?= h($today) ?>"  required>
                    </div>
                    
                </div>
                <br>
                <h5>Indent Items</h5>
                <table id="indent_table" class="display table table-hover table-condensed" width="100%">
                    <thead>
                        <tr>
                            <th>#</th><th>Name</th><th>Brand</th><th>Unit</th><th>Price</th><th>Tax</th><th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody id="indent_items"></tbody>
                </table>

                <br>
                <h5>Items Served by Vendor</h5>
                <table id="vendor_table" class="display table table-hover table-condensed" width="100%">
                    <thead>
                        <tr>
                            <th>#</th><th>Name</th><th>Brand</th><th>Unit</th><th>Price</th><th>Tax%</th><th>Qty</th><th>Excise ₹/u</th><th>Addl.Ex ₹/u</th><th>VAT%</th><th>TCS%</th><th>Discount</th>
                        </tr>
                    </thead>
                    <tbody id="vendor_items"></tbody>
                </table>

                <!-- Buttons -->
                <div class="row mt-3" style="float:right">
                    <div class="col-md-12 text-end">
                        <a href="po_list" class="btn btn-danger">Cancel</a>
                        <?= $this->Form->button('Save', [
                            'class' => 'btn btn-success',
                            'id' => 'saveBtn',
                            'disabled' => true // disabled by default
                        ]) ?>
                    </div>
                </div>

                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>

<script>
/*
function showCreatePOConfirmation(charge, onConfirm) {

    let html = `
        <table class="table table-bordered table-sm text-start">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Base</th>
                    <th>Tax %</th>
                    <th>Tax</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subtotal = 0;
    let totalTax = 0;

    const table = $('#vendor_table').DataTable();

    table.rows().every(function () {
    
        const rowNode = this.node(); // gets row even if not visible
        const $row = $(rowNode);
    
        const qty = parseFloat($row.find("input[name^='quantity']").val()) || 0;
        if (qty <= 0) return;
    
        const name  = $row.find('td').eq(1).text().trim();
        const brand = $row.find('td').eq(2).text().trim();
        const unit  = $row.find('td').eq(3).text().trim();
    
        const price  = parseFloat($row.find("input[name^='prices']").val()) || 0;
        const disc   = parseFloat($row.find("input[name^='sku_discount']").val()) || 0;
        const taxPct = parseFloat($row.find('td').eq(5).text()) || 0;
    
        const baseAmount = price * qty;
        const baseAfterDiscount = baseAmount - disc;
        const taxValue   = (baseAfterDiscount * taxPct) / 100;
        const total      = baseAfterDiscount + taxValue;
    
        subtotal += baseAfterDiscount;
        totalTax += taxValue;
    
        html += `
            <tr>
                <td>${name}</td>
                <td>${brand}</td>
                <td>${unit}</td>
                <td>₹${price.toFixed(2)}</td>
                <td>${qty}</td>
                <td>₹${baseAmount.toFixed(2)}</td>
                <td>${taxPct}%</td>
                <td>₹${taxValue.toFixed(2)}</td>
                <td><b>₹${total.toFixed(2)}</b></td>
            </tr>
        `;
    });


    const chargeVal = parseFloat(charge) || 0;
    const grandTotal = subtotal + totalTax + chargeVal;

    html += `
        </tbody>
    </table>

    <div class="text-end mt-3">
        <h6>Subtotal : ₹${subtotal.toFixed(2)}</h6>
        <h6>Total Tax : ₹${totalTax.toFixed(2)}</h6>
        <h6>Charges : ₹${chargeVal.toFixed(2)}</h6>
        <h5 class="mt-2"><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
    </div>
    `;

    Swal.fire({
        title: 'Confirm Create Purchase Order',
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

function showCreatePOConfirmation(charge, onConfirm) {

    let html = `
        <table class="table table-bordered table-sm text-start">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Base</th>
                    <th>Discount Type</th>
                    <th>Discount</th>
                    <th>Tax %</th>
                    <th>Tax</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subtotal = 0;
    let totalTax = 0;
    let totalDiscount = 0;

    const table = $('#vendor_table').DataTable();

    table.rows().every(function () {

        const rowNode = this.node();
        const $row = $(rowNode);

        const qty = parseFloat($row.find("input[name^='quantity']").val()) || 0;
        if (qty <= 0) return;

        const name  = $row.find('td').eq(1).text().trim();
        const brand = $row.find('td').eq(2).text().trim();
        const unit  = $row.find('td').eq(3).text().trim();

        const price  = parseFloat($row.find("input[name^='prices']").val()) || 0;
        const disc   = parseFloat($row.find("input[name^='sku_discount']").val()) || 0;
        const taxPct = parseFloat($row.find('td').eq(5).text()) || 0;

        const baseAmount = price * qty;
        const baseAfterDiscount = baseAmount - disc;
        const taxValue = (baseAfterDiscount * taxPct) / 100;
        const total = baseAfterDiscount + taxValue;

        subtotal += baseAmount;
        totalDiscount += disc;
        totalTax += taxValue;

        html += `
            <tr>
                <td>${name}</td>
                <td>${brand}</td>
                <td>${unit}</td>
                <td>₹${price.toFixed(2)}</td>
                <td>${qty}</td>
                <td>₹${baseAmount.toFixed(2)}</td>
                <td>₹${disc.toFixed(2)}</td>
                <td>${taxPct}%</td>
                <td>₹${taxValue.toFixed(2)}</td>
                <td><b>₹${total.toFixed(2)}</b></td>
            </tr>
        `;
    });

    const chargeVal = parseFloat(charge) || 0;

    const subtotalAfterDiscount = subtotal - totalDiscount;
    const grandTotal = subtotalAfterDiscount + totalTax + chargeVal;

    html += `
        </tbody>
    </table>

    <div class="text-end mt-3">
        <h6>Subtotal : ₹${subtotal.toFixed(2)}</h6>
        <h6>Total Discount : ₹${totalDiscount.toFixed(2)}</h6>
        <h6>Subtotal After Discount : ₹${subtotalAfterDiscount.toFixed(2)}</h6>
        <h6>Total Tax : ₹${totalTax.toFixed(2)}</h6>
        <h6>Charges : ₹${chargeVal.toFixed(2)}</h6>
        <h5 class="mt-2"><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
    </div>
    `;

    Swal.fire({
        title: 'Confirm Create Purchase Order',
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
*/

function showCreatePOConfirmation(po_date, charge, onConfirm) {

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

    const table = $('#vendor_table').DataTable();

    table.rows().every(function () {

        const rowNode = this.node();
        const $row = $(rowNode);

        const qty = parseFloat($row.find("input[name^='quantity']").val()) || 0;
        if (qty <= 0) return;

        const name         = $row.find('td').eq(1).text().trim();
        const brand        = $row.find('td').eq(2).text().trim();
        const unit         = $row.find('td').eq(3).text().trim();
        const price        = parseFloat($row.find("input[name^='prices']").val()) || 0;
        let   disc         = parseFloat($row.find("input[name^='sku_discount']").val()) || 0;
        const discType     = $row.find("select[name^='discount_type']").val() || 'flat';
        const excisePu     = parseFloat($row.find("input[name^='excise_duty']").val()) || 0;
        const addlExcisePu = parseFloat($row.find("input[name^='addl_excise']").val()) || 0;
        const vatPct       = parseFloat($row.find("input[name^='vat_duty']").val())    || 0;
        const tcsPct       = parseFloat($row.find("input[name^='tcs_duty']").val())    || 0;
        let taxPct         = parseFloat($row.find('td').eq(5).text()) || 0;
        if (excisePu > 0 || vatPct > 0) taxPct = 0; // excise/VAT replaces legacy GST

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
        // VAT base = base + excise + addl_excise
        const vatBase   = baseAfterDiscount + exciseAmt + addlExciseAmt;
        const vatAmt    = vatBase * vatPct / 100;
        // TCS base = base + excise + VAT (addl_excise excluded)
        const tcsAmt    = (baseAfterDiscount + exciseAmt + vatAmt) * tcsPct / 100;
        // Landed = base + excise + VAT (addl_excise absorbed into VAT base, not separate line)
        const landed    = baseAfterDiscount + exciseAmt + vatAmt;
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
        title: 'Confirm Create Purchase Order',
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

    const indentTable = $('#indent_table').DataTable();
    const vendorTable = $('#vendor_table').DataTable();

    /* ===============================
       HELPER: CHECK ANY QTY > 0
    =============================== */
    function hasAnySelectedQuantity() {
        const table = $('#vendor_table').DataTable();
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
       LOCATION â†’ INDENTS
    =============================== */
    $('#location').change(function () {

        const locationId = $(this).val();
        const $indent = $('#select_indent');
        const $vendor = $('#select_vendor');

        $indent.html('<option value="">- Select Indent -</option>').prop('disabled', true);
        $vendor.prop('disabled', true).val('');

        indentTable.clear().draw();
        vendorTable.clear().draw();
        $('#saveBtn').prop('disabled', true);

        if (!locationId) return;

        $('#indent_loader').show();

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_indents_by_location']) ?>',
            type: 'POST',
            data: { location_id: locationId },
            success: function (response) {
                $('#indent_loader').hide();
                const indents = JSON.parse(response);

                $.each(indents, function (id, name) {
                    $indent.append(`<option value="${id}">${name}</option>`);
                });

                $indent.prop('disabled', false);
            },
            error: function () {
                $('#indent_loader').hide();
                Swal.fire('Error', 'Failed to load indents', 'error');
            }
        });
    });

    /* ===============================
       INDENT â†’ INDENT ITEMS
    =============================== */
    $('#select_indent').change(function () {

        const indentId = $(this).val();
        const $vendor  = $('#select_vendor');
        const locationId = $('#location').val();

        indentTable.clear().draw();
        vendorTable.clear().draw();
        $('#saveBtn').prop('disabled', true);
    
        $vendor.html('<option value="">- Select Vendor -</option>').prop('disabled', true);
    
        if (!indentId) return;
    
        /* -----------------------
           FETCH INDENT ITEMS
        ----------------------- */
        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_indent_items']) ?>',
            type: 'POST',
            data: { indent_id: indentId },
            success: function (data) {
                $('#indent_items').html(data);
                indentTable.rows.add($('#indent_items tr')).draw();
            }
        });
    
        /* -----------------------
           FETCH VENDORS BY INDENT
        ----------------------- */
        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_vendors_by_indent']) ?>',
            type: 'POST',
            data: { indent_id: indentId,location_id: locationId },
            success: function (response) {
    
                const vendors = JSON.parse(response);
    
                if (Object.keys(vendors).length === 0) {
                    Swal.fire('Error', 'No vendor serves these indent items for this location', 'error');
                    return;
                }
    
                $.each(vendors, function (id, name) {
                    $vendor.append(`<option value="${id}">${name}</option>`);
                });
    
                $vendor.prop('disabled', false);
            }
        });
    });

    /* ===============================
       VENDOR â†’ VENDOR ITEMS
    =============================== */
    $('#select_vendor').change(function () {

        const vendorId = $(this).val();
        const indentId = $('#select_indent').val();

        vendorTable.clear().draw();
        $('#saveBtn').prop('disabled', true);

        if (!vendorId || !indentId) return;

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_vendor_items']) ?>',
            type: 'POST',
            data: { vendor_id: vendorId, indent_id: indentId, location_id: $('#location').val() },
            success: function (data) {

                $('#vendor_items').html(data);

                // ðŸ”´ No vendor items
                if ($("input[name^='quantity']").length === 0) {
                    Swal.fire('Error', 'No item found for this vendor', 'error');
                    $('#saveBtn').prop('disabled', true);
                    return;
                }

                vendorTable.rows.add($('#vendor_items tr')).draw();
                $('#saveBtn').prop('disabled', !hasAnySelectedQuantity());
            },
            error: function () {
                Swal.fire('Error', 'Failed to retrieve vendor items', 'error');
                $('#saveBtn').prop('disabled', true);
            }
        });
    });

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
        if ($("input[name^='quantity']").length === 0) {
            Swal.fire('Error', 'No item found for this vendor', 'error');
            return;
        }
    
        // 🔴 All quantities = 0
        if (!hasAnySelectedQuantity()) {
            Swal.fire({
                icon: 'error',
                title: 'No item selected',
                text: 'Please enter quantity for at least one item'
            });
            return;
        }
    
        const form   = this;
        const charge = $('#UserCharge').val();
        const po_date = $('#po_date').val();
    
        // 🔥 CONFIRMATION POPUP
        showCreatePOConfirmation(po_date, charge, function () {
    
            // ✅ Ensure DataTables hidden rows are submitted
            indentTable.rows().every(function () {
                $(this.node()).find('input, select, textarea').each(function () {
                    if (!$.contains(document, this)) {
                        $(this).clone().appendTo(form);
                    }
                });
            });
    
            vendorTable.rows().every(function () {
                $(this.node()).find('input, select, textarea').each(function () {
                    if (!$.contains(document, this)) {
                        $(this).clone().appendTo(form);
                    }
                });
            });
    
            // 🚀 FINAL SUBMIT
            form.submit();
        });
    });


});
</script>
