<?php
$s     = $spo['Service_purchase_order'];
$UNITS = ['Lump Sum', 'Monthly', 'Annual', 'Per Visit', 'Hours', 'Per Unit', 'Quarterly'];
?>

<!-- Page Header -->
<div class="row mb-3">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Update Service Purchase Order</h2>
        </div>
    </div>
    <div class="col-sm-1 text-end">
        <a href="view_service_po?id=<?= h($s['spo_id']) ?>" class="btn btn-danger">Back</a>
    </div>
</div>

<input type="hidden" id="spo_id" value="<?= h($s['spo_id']) ?>">

<!-- Header Fields Card -->
<div class="row mb-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-row">

                    <div class="form-group col-md-3">
                        <label class="form-label">SPO ID</label>
                        <input type="text" class="form-control" value="<?= h($s['spo_id']) ?>" readonly>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select id="vendor_id" class="form-control select2" style="width:100%">
                            <option value="">-- Select Vendor --</option>
                            <?php foreach ($vendors as $vid => $vname): ?>
                            <option value="<?= $vid ?>" <?= $s['vendor_id'] == $vid ? 'selected' : '' ?>><?= h($vname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="form-label">Store / Location <span class="text-danger">*</span></label>
                        <select id="location_id" class="form-control select2" style="width:100%">
                            <option value="">-- Select Store --</option>
                            <?php foreach ($stores as $st): ?>
                            <option value="<?= $st['Location']['id'] ?>" <?= $s['location_id'] == $st['Location']['id'] ? 'selected' : '' ?>><?= h($st['Location']['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="form-label">Service Type <span class="text-danger">*</span></label>
                        <select id="service_type_id" class="form-control">
                            <option value="">-- Select Service Type --</option>
                            <?php foreach ($services as $sid => $sname): ?>
                            <option value="<?= $sid ?>" <?= $s['service_type_id'] == $sid ? 'selected' : '' ?>><?= h($sname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="form-label">Period From</label>
                        <input type="date" id="period_from" class="form-control" value="<?= $s['period_from'] ?>">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="form-label">Period To</label>
                        <input type="date" id="period_to" class="form-control" value="<?= $s['period_to'] ?>">
                    </div>

                    <div class="form-group col-md-6">
                        <label class="form-label">Description</label>
                        <input type="text" id="description" class="form-control" value="<?= h($s['description']) ?>">
                    </div>

                    <div class="form-group col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea id="remarks" class="form-control" rows="2"><?= h($s['remarks']) ?></textarea>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Line Items Card -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <h5>Service Line Items</h5>

                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="itemsTable" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Service / Item Name <span class="text-danger">*</span></th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Qty</th>
                                <th>Unit Price (₹)</th>
                                <th>Discount (₹)</th>
                                <th>Tax %</th>
                                <th>Tax Amt (₹)</th>
                                <th>Total (₹)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php $n = 1;
                            foreach ($items as $item):
                                $i = $item['Service_purchase_order_item']; ?>
                            <tr>
                                <td><?= $n++ ?></td>
                                <td><input type="text" class="form-control svc-name" value="<?= h($i['service_name']) ?>"></td>
                                <td><input type="text" class="form-control svc-desc" value="<?= h($i['description']) ?>"></td>
                                <td>
                                    <select class="form-control svc-unit">
                                        <?php foreach ($UNITS as $u): ?>
                                        <option value="<?= $u ?>" <?= $i['unit'] == $u ? 'selected' : '' ?>><?= $u ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control svc-qty" value="<?= $i['quantity'] ?>" step="0.01"></td>
                                <td><input type="number" class="form-control svc-price" value="<?= $i['unit_price'] ?>" step="0.01"></td>
                                <td><input type="number" class="form-control vendor-discount svc-disc" value="<?= $i['discount_value'] ?>" step="0.01" min="0"></td>
                                <td><input type="number" class="form-control svc-tax" value="<?= $i['tax_percentage'] ?>" step="0.01"></td>
                                <td class="svc-taxamt"><?= number_format($i['tax_value'], 2) ?></td>
                                <td class="svc-rowttl"><b><?= number_format($i['total_amount'], 2) ?></b></td>
                                <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-end"><strong>Totals</strong></td>
                                <td id="foot-tax" class="text-end fw-bold">0.00</td>
                                <td id="foot-total" class="text-end fw-bold">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" class="btn btn-secondary btn-sm mt-2" id="addItemBtn">
                    <i class="fa fa-plus"></i> Add Line Item
                </button>

                <div class="text-end mt-3">
                    <a href="view_service_po?id=<?= h($s['spo_id']) ?>" class="btn btn-danger me-2">Cancel</a>
                    <button type="button" class="btn btn-success" id="updateSpoBtn">
                        <i class="fa fa-save"></i> Save
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
var UNIT_OPTIONS = ['Lump Sum', 'Monthly', 'Annual', 'Per Visit', 'Hours', 'Per Unit', 'Quarterly'];

function buildItemRow() {
    var n = $('#itemsBody tr').length + 1;
    var unitOpts = UNIT_OPTIONS.map(function(u) { return '<option value="' + u + '">' + u + '</option>'; }).join('');
    return '<tr>' +
        '<td>' + n + '</td>' +
        '<td><input type="text" class="form-control svc-name"></td>' +
        '<td><input type="text" class="form-control svc-desc"></td>' +
        '<td><select class="form-control svc-unit">' + unitOpts + '</select></td>' +
        '<td><input type="number" class="form-control svc-qty" value="1" step="0.01"></td>' +
        '<td><input type="number" class="form-control svc-price" value="0" step="0.01"></td>' +
        '<td><input type="number" class="form-control vendor-discount svc-disc" value="0" step="0.01" min="0"></td>' +
        '<td><input type="number" class="form-control svc-tax" value="18" step="0.01"></td>' +
        '<td class="svc-taxamt">0.00</td>' +
        '<td class="svc-rowttl"><b>0.00</b></td>' +
        '<td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button></td>' +
        '</tr>';
}

function renumberRows() {
    $('#itemsBody tr').each(function(i) { $(this).find('td:first').text(i + 1); });
}

function calcRow($row) {
    var qty    = parseFloat($row.find('.svc-qty').val())   || 0;
    var price  = parseFloat($row.find('.svc-price').val()) || 0;
    var disc   = parseFloat($row.find('.svc-disc').val())  || 0;
    var taxPct = parseFloat($row.find('.svc-tax').val())   || 0;
    var base   = (qty * price) - disc;
    var taxAmt = base * taxPct / 100;
    $row.find('.svc-taxamt').text(taxAmt.toFixed(2));
    $row.find('.svc-rowttl').html('<b>' + (base + taxAmt).toFixed(2) + '</b>');
}

function calcFooter() {
    var t = 0, ta = 0;
    $('#itemsBody tr').each(function () {
        ta += parseFloat($(this).find('.svc-taxamt').text().replace(/,/g, '')) || 0;
        t  += parseFloat($(this).find('.svc-rowttl').text().replace(/,/g, '')) || 0;
    });
    $('#foot-tax').text(ta.toFixed(2));
    $('#foot-total').text(t.toFixed(2));
}

function bindRow($row) {
    $row.find('.svc-qty, .svc-price, .svc-disc, .svc-tax').on('input change', function () { calcRow($row); calcFooter(); });
    $row.find('.removeRow').on('click', function () { $row.remove(); renumberRows(); calcFooter(); });
}

function showSPOConfirmation(onConfirm) {
    var html = '<table class="table table-bordered table-sm text-start">' +
        '<thead><tr><th>Service</th><th>Qty</th><th>Price</th><th>Base</th><th>Discount</th><th>Tax %</th><th>Tax</th><th>Total</th></tr></thead><tbody>';

    var subtotal = 0, totalTax = 0, totalDiscount = 0;

    $('#itemsBody tr').each(function () {
        var qty   = parseFloat($(this).find('.svc-qty').val())   || 0;
        if (qty <= 0) return;
        var name  = $(this).find('.svc-name').val() || '';
        var price = parseFloat($(this).find('.svc-price').val()) || 0;
        var disc  = parseFloat($(this).find('.svc-disc').val())  || 0;
        var taxPct= parseFloat($(this).find('.svc-tax').val())   || 0;
        var base  = price * qty;
        var after = base - disc;
        var tax   = after * taxPct / 100;
        var total = after + tax;
        subtotal      += base;
        totalDiscount += disc;
        totalTax      += tax;
        html += '<tr><td>' + name + '</td><td>' + qty + '</td><td>₹' + price.toFixed(2) +
            '</td><td>₹' + base.toFixed(2) + '</td><td>₹' + disc.toFixed(2) +
            '</td><td>' + taxPct + '%</td><td>₹' + tax.toFixed(2) +
            '</td><td><b>₹' + total.toFixed(2) + '</b></td></tr>';
    });

    var grandTotal = (subtotal - totalDiscount) + totalTax;
    html += '</tbody></table><div class="text-end mt-3">' +
        '<h6>Subtotal : ₹' + subtotal.toFixed(2) + '</h6>' +
        '<h6>Total Discount : ₹' + totalDiscount.toFixed(2) + '</h6>' +
        '<h6>Total Tax : ₹' + totalTax.toFixed(2) + '</h6>' +
        '<h5 class="mt-2"><b>Grand Total : ₹' + grandTotal.toFixed(2) + '</b></h5></div>';

    Swal.fire({
        title: 'Confirm Update Service PO',
        html: html,
        icon: 'warning',
        width: '1000px',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) { onConfirm(); }
    });
}

$(function () {
    if (typeof $.fn.select2 === 'function') { $('.select2').select2({ allowClear: true }); }

    $('#itemsBody tr').each(function () { bindRow($(this)); });
    calcFooter();

    $('#addItemBtn').on('click', function () {
        var $r = $(buildItemRow());
        $('#itemsBody').append($r);
        bindRow($r);
    });

    // Discount validation
    $(document).on('input', '.vendor-discount', function () {
        var $row     = $(this).closest('tr');
        var price    = parseFloat($row.find('.svc-price').val()) || 0;
        var qty      = parseFloat($row.find('.svc-qty').val())   || 0;
        var baseAmt  = price * qty;
        var discount = parseFloat($(this).val()) || 0;
        if (discount > baseAmt) {
            Swal.fire({ toast: true, icon: 'warning', title: 'Discount cannot exceed item amount (₹' + baseAmt.toFixed(2) + ')', showConfirmButton: false, timer: 2000 });
            $(this).val(baseAmt.toFixed(2));
        }
        if (discount < 0) { $(this).val(0); }
    });

    $('#updateSpoBtn').on('click', function () {
        var items = [], valid = true;
        $('#itemsBody tr').each(function () {
            var name = $(this).find('.svc-name').val().trim();
            if (!name) { valid = false; return; }
            items.push({
                service_name:   name,
                description:    $(this).find('.svc-desc').val().trim(),
                unit:           $(this).find('.svc-unit').val(),
                quantity:       $(this).find('.svc-qty').val(),
                unit_price:     $(this).find('.svc-price').val(),
                discount_value: $(this).find('.svc-disc').val(),
                tax_percentage: $(this).find('.svc-tax').val()
            });
        });

        if (!$('#vendor_id').val())       { Swal.fire({ icon: 'error', title: 'Please select a vendor' });       return; }
        if (!$('#location_id').val())     { Swal.fire({ icon: 'error', title: 'Please select a store' });        return; }
        if (!$('#service_type_id').val()) { Swal.fire({ icon: 'error', title: 'Please select a service type' }); return; }
        if (!valid || items.length === 0) { Swal.fire({ icon: 'error', title: 'Each line item needs a name' });  return; }

        showSPOConfirmation(function () {
            var btn = $('#updateSpoBtn');
            btn.prop('disabled', true).text('Saving...');
            $.ajax({
                url:         'update_service_po',
                type:        'POST',
                contentType: 'application/json',
                dataType:    'json',
                data: JSON.stringify({
                    spo_id:          $('#spo_id').val(),
                    vendor_id:       $('#vendor_id').val(),
                    location_id:     $('#location_id').val(),
                    service_type_id: $('#service_type_id').val(),
                    period_from:     $('#period_from').val(),
                    period_to:       $('#period_to').val(),
                    description:     $('#description').val(),
                    remarks:         $('#remarks').val(),
                    items:           items
                }),
                success: function (res) {
                    if (res.status) {
                        Swal.fire({ icon: 'success', title: 'Updated successfully.', timer: 1500, showConfirmButton: false })
                            .then(function () { window.location = 'view_service_po?id=' + $('#spo_id').val(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                        btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save');
                    }
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Something went wrong. Please try again.' });
                    btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save');
                }
            });
        });
    });
});
</script>
