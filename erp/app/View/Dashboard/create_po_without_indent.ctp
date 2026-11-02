<div class="row align-items-center">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Create PO Without Indent</h2>
        </div>
    </div>
    <div class="col-sm-2 text-end">
        <a href="po_list" class="btn btn-danger">Back to PO List</a>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?= $this->Form->create('User', ['type' => 'file', 'id' => 'createPoWithoutIndentForm']) ?>

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

                <!-- Vendor -->
                <div class="form-group">
                    <?= $this->Form->input('vendor', [
                        'label' => 'Select Vendor',
                        'class' => 'form-control',
                        'id' => 'select_vendor',
                        'empty' => '- Select Vendor -',
                        'options' => [],
                        'required' => true,
                        'div' => false,
                        'disabled' => true
                    ]); ?>
                </div>

                <!-- Vendor Loader -->
                <div id="vendor_loader" class="mb-2" style="display: none;">
                    <i class="fa fa-spinner fa-spin text-primary"></i> Loading vendors for location...
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
                                'step' => 'any',
                                'min' => '0'
                            ));
                        ?>
                    </div>
                    <div class="col-sm-6">
                        <label>Select Date</label>
                        <input type="date" id="po_date" name="po_date" class="form-control" max="<?= h($today) ?>" value="<?= h($today) ?>" required>
                    </div>
                </div>

                <br>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Items Served by Vendor</h5>
                </div>

                <div id="table_loading_overlay" class="alert alert-info text-center my-3" style="display: none; padding: 15px; border-left: 5px solid #007bff;">
                    <i class="fa fa-spinner fa-spin fa-2x align-middle text-primary"></i>
                    <span class="ml-2 font-weight-bold text-primary" style="font-size: 16px;">Loading vendor items, please wait...</span>
                </div>

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
                        <?= $this->Form->button('Save PO', [
                            'class' => 'btn btn-success',
                            'id' => 'saveBtn',
                            'disabled' => true
                        ]) ?>
                    </div>
                </div>

                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>

<script>
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
        const vatBase      = baseAfterDiscount + exciseAmt + addlExciseAmt;
        const vatAmt       = vatBase * vatPct / 100;
        const tcsAmt       = (baseAfterDiscount + exciseAmt + vatAmt) * tcsPct / 100;
        const landed       = baseAfterDiscount + exciseAmt + vatAmt;
        const taxValue     = landed * taxPct / 100;
        const total        = landed + taxValue + tcsAmt;

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
        <h6>Total VAT : ₹${totalVat.toFixed(2)}</h6>
        <h6>Total Tax (GST) : ₹${totalTax.toFixed(2)}</h6>
        <h6>Total TCS : ₹${totalTcs.toFixed(2)}</h6>
        <h6>Charges : ₹${chargeVal.toFixed(2)}</h6>
        <h5 class="mt-2"><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
        <div class="alert alert-info text-start mt-2 p-2" style="font-size:12px;">
            <i class="fa fa-info-circle"></i> An internal indent will be automatically generated and linked to this PO upon confirmation.
        </div>
    </div>
    `;

    Swal.fire({
        title: 'Confirm Create Purchase Order Without Indent',
        html: html,
        icon: 'warning',
        width: '1100px',
        showCancelButton: true,
        confirmButtonText: 'Confirm & Create PO',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            onConfirm();
        }
    });
}

$(document).ready(function () {
    const vendorTable = $('#vendor_table').DataTable({
        pageLength: 50
    });

    function hasAnySelectedQuantity() {
        let hasQty = false;
        vendorTable.rows().every(function () {
            const qty = parseFloat($(this.node()).find("input[name^='quantity']").val()) || 0;
            if (qty > 0) {
                hasQty = true;
                return false;
            }
        });
        return hasQty;
    }

    /* ===============================
       LOCATION → VENDORS
    =============================== */
    $('#location').change(function () {
        const locationId = $(this).val();
        const $vendor = $('#select_vendor');

        $vendor.html('<option value="">- Select Vendor -</option>').prop('disabled', true);
        vendorTable.clear().draw();
        $('#saveBtn').prop('disabled', true);

        if (!locationId) return;

        $('#vendor_loader').show();

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_vendors_for_po_without_indent']) ?>',
            type: 'POST',
            data: { location_id: locationId },
            success: function (response) {
                $('#vendor_loader').hide();
                const vendors = JSON.parse(response);

                if (Object.keys(vendors).length === 0) {
                    Swal.fire('Info', 'No active vendors found for this location', 'info');
                    return;
                }

                $.each(vendors, function (id, name) {
                    $vendor.append(`<option value="${id}">${name}</option>`);
                });

                $vendor.prop('disabled', false);
            },
            error: function () {
                $('#vendor_loader').hide();
                Swal.fire('Error', 'Failed to load vendors', 'error');
            }
        });
    });

    /* ===============================
       VENDOR → VENDOR ASSIGNED ITEMS
    =============================== */
    $('#select_vendor').change(function () {
        const vendorId = $(this).val();
        const locationId = $('#location').val();

        vendorTable.clear().draw();
        $('#saveBtn').prop('disabled', true);

        if (!vendorId || !locationId) return;

        $('#table_loading_overlay').show();

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_vendor_assigned_items']) ?>',
            type: 'POST',
            data: {
                vendor_id: vendorId,
                location_id: locationId
            },
            success: function (data) {
                $('#table_loading_overlay').hide();
                $('#vendor_items').html(data);

                if ($("#vendor_items tr").length === 0 || $("input[name^='quantity']").length === 0) {
                    Swal.fire('Info', 'No items assigned to this vendor', 'info');
                    $('#saveBtn').prop('disabled', true);
                    return;
                }

                vendorTable.rows.add($('#vendor_items tr')).draw();
                $('#saveBtn').prop('disabled', !hasAnySelectedQuantity());
            },
            error: function () {
                $('#table_loading_overlay').hide();
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
    $('#createPoWithoutIndentForm').on('submit', function (e) {
        e.preventDefault();

        if ($("input[name^='quantity']").length === 0) {
            Swal.fire('Error', 'No items available for this vendor', 'error');
            return;
        }

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

        showCreatePOConfirmation(po_date, charge, function () {
            // Ensure DataTables rows on other pages are included in form POST
            vendorTable.rows().every(function () {
                $(this.node()).find('input, select, textarea').each(function () {
                    if (!$.contains(document, this)) {
                        $(this).clone().appendTo(form);
                    }
                });
            });

            form.submit();
        });
    });
});
</script>
