<div class="row align-items-center">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Create Service Purchase Order</h2>
        </div>
    </div>
    <div class="col-sm-1 text-end">
        <a href="service_po_list" class="btn btn-danger">Back</a>
    </div>
</div>

<div id="alert-box"></div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <!-- BASIC DETAILS -->
                <div class="row">
                    <div class="col-md-4">
                        <label>Vendor <span class="text-danger">*</span></label>
                        <select id="vendor_id" class="form-control select2">
                            <option value="">-- Select Vendor --</option>
                            <?php foreach($vendors as $vid=>$vname): ?>
                                <option value="<?= $vid ?>"><?= h($vname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Store / Location <span class="text-danger">*</span></label>
                        <select id="location_id" class="form-control select2">
                            <option value="">-- Select Store --</option>
                            <?php foreach($stores as $s): ?>
                                <option value="<?= $s['Location']['id'] ?>">
                                    <?= h($s['Location']['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Service Type <span class="text-danger">*</span></label>
                        <select id="service_type_id" class="form-control select2">
                            <option value="">-- Select Service Type --</option>
                            <?php foreach($services as $sid=>$sname): ?>
                                <option value="<?= $sid ?>"><?= h($sname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <label>Period From</label>
                        <input type="date" id="period_from" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label>Period To</label>
                        <input type="date" id="period_to" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label>Description</label>
                        <input type="text" id="description" class="form-control" placeholder="Brief description">
                    </div>
                </div>

                <hr>

                <!-- LINE ITEMS -->
                <h5>Line Items</h5>

                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Qty</th>
                                <th>Unit Price (₹)</th>
                                <th>Discount</th>
                                <th>Tax %</th>
                                <th>Tax Amt</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody id="itemsBody"></tbody>

                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Totals</strong></td>
                                <td id="foot-tax">0.00</td>
                                <td id="foot-total">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                    + Add Line Item
                </button>

                <!-- REMARKS -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label>Remarks</label>
                        <textarea id="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="row mt-4">
                    <div class="col-md-12 text-end">
                        <a href="service_po_list" class="btn btn-danger">Cancel</a>
                        <button type="button" class="btn btn-success" id="saveSpoBtn">
                            Submit Service PO
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
$('.select2').select2({
    width: '100%',
    placeholder: '-- Select --'
});
var UNIT_OPTIONS = ['Lump Sum','Monthly','Annual','Per Visit','Hours','Per Unit','Quarterly'];

function buildItemRow(idx) {
    var unitOpts = UNIT_OPTIONS.map(function(u){
        return '<option value="'+u+'">'+u+'</option>';
    }).join('');
    return '<tr data-idx="'+idx+'">' +
        '<td><input type="text" class="form-control input-sm svc-name" placeholder="Service name"></td>' +
        '<td><input type="text" class="form-control input-sm svc-desc" placeholder="Details"></td>' +
        '<td><select class="form-control input-sm svc-unit">'+unitOpts+'</select></td>' +
        '<td><input type="number" class="form-control input-sm svc-qty" value="1" min="0.01" step="0.01"></td>' +
        '<td><input type="number" class="form-control input-sm svc-price" value="0" min="0" step="0.01"></td>' +
        '<td><input type="number" class="form-control input-sm svc-disc" value="0" min="0" step="0.01"></td>' +
        '<td><input type="number" class="form-control input-sm svc-tax" value="18" min="0" step="0.01"></td>' +
        '<td class="text-right svc-taxamt">0.00</td>' +
        '<td class="text-right svc-rowttl">0.00</td>' +
        '<td><button type="button" class="btn btn-xs btn-danger removeRow"><i class="fa fa-trash"></i></button></td>' +
        '</tr>';
}

var rowCount = 0;
function addRow() {
    rowCount++;
    $('#itemsBody').append(buildItemRow(rowCount));
    bindRowCalc($('#itemsBody tr:last'));
}

function bindRowCalc($row) {
    $row.find('.svc-qty,.svc-price,.svc-disc,.svc-tax').on('input change', function(){
        calcRow($row);
        calcFooter();
    });
    $row.find('.removeRow').on('click', function(){
        $row.remove();
        calcFooter();
    });
}

function calcRow($row) {
    var qty   = parseFloat($row.find('.svc-qty').val())   || 0;
    var price = parseFloat($row.find('.svc-price').val()) || 0;
    var disc  = parseFloat($row.find('.svc-disc').val())  || 0;
    var taxPct= parseFloat($row.find('.svc-tax').val())   || 0;
    var base  = (qty * price) - disc;
    var taxAmt= base * taxPct / 100;
    var total = base + taxAmt;
    $row.find('.svc-taxamt').text(taxAmt.toFixed(2));
    $row.find('.svc-rowttl').text(total.toFixed(2));
}

function calcFooter() {
    var totalTax = 0, totalAmt = 0;
    $('#itemsBody tr').each(function(){
        totalTax += parseFloat($(this).find('.svc-taxamt').text()) || 0;
        totalAmt += parseFloat($(this).find('.svc-rowttl').text()) || 0;
    });
    $('#foot-tax').text(totalTax.toFixed(2));
    $('#foot-total').text(totalAmt.toFixed(2));
}

$(function(){
    if (typeof $.fn.select2 === 'function') {
        $('.select2').select2({ placeholder: '-- Select --', allowClear: true });
    }
    addRow(); // start with one empty row

    $('#addItemBtn').on('click', addRow);

    $('#saveSpoBtn').on('click', function(){

    var btn = $(this);
    var items = [];
    var valid = true;

    $('#itemsBody tr').each(function(){
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

    // ✅ Validation with Swal
    if (!$('#vendor_id').val()) {
        Swal.fire('Error', 'Please select a vendor.', 'error');
        return;
    }
    if (!$('#location_id').val()) {
        Swal.fire('Error', 'Please select a store.', 'error');
        return;
    }
    if (!$('#service_type_id').val()) {
        Swal.fire('Error', 'Please select a service type.', 'error');
        return;
    }
    if (!valid || items.length===0) {
        Swal.fire('Error', 'All line items need a Service Name.', 'error');
        return;
    }

    // ✅ Confirmation Popup BEFORE submit
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to submit this Service PO?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Submit',
        reverseButtons: true
    }).then((result) => {

        if (result.isConfirmed) {

            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'add_service_po']); ?>',
                type: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify({
                    vendor_id:       $('#vendor_id').val(),
                    location_id:     $('#location_id').val(),
                    service_type_id: $('#service_type_id').val(),
                    period_from:     $('#period_from').val(),
                    period_to:       $('#period_to').val(),
                    description:     $('#description').val().trim(),
                    remarks:         $('#remarks').val().trim(),
                    items:           items
                }),

                success: function(res){

                    if (res.status) {

                        // ✅ SUCCESS Swal
                        Swal.fire({
                            title: 'Success!',
                            text: res.spo_id + ' created successfully',
                            icon: 'success',
                            confirmButtonColor: '#28a745'
                        }).then(() => {
                            window.location = 'service_po_list';
                        });

                    } else {

                        // ❌ ERROR Swal
                        Swal.fire({
                            title: 'Failed',
                            text: res.message,
                            icon: 'error'
                        });

                        btn.prop('disabled',false).html('Submit Service PO');
                    }
                },

                error: function(){

                    Swal.fire({
                        title: 'Server Error',
                        text: 'Please try again.',
                        icon: 'error'
                    });

                    btn.prop('disabled',false).html('Submit Service PO');
                }
            });
        }
    });
});
});
</script>
