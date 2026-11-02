<?php
$s      = $spo['Service_purchase_order'];
$badges = [1 => 'warning', 2 => 'info', 3 => 'success', 0 => 'danger'];
$bc     = $badges[(int)$s['status']] ?? 'secondary';
$labels = [1 => 'Submitted', 2 => 'Approved', 3 => 'Processed', 0 => 'Cancelled'];
?>

<style>
    .form-section-title {
        margin-top: 1rem;
        font-weight: 600;
        font-size: 1.25rem;
    }
    .spo-badge {
        font-size: 0.75rem;
        vertical-align: middle;
    }
    .financials-summary {
        font-size: 0.9rem;
    }
    .financials-summary tr.grand-total th,
    .financials-summary tr.grand-total td {
        font-size: 1rem;
        font-weight: 700;
        border-top: 2px solid #dee2e6;
    }
</style>

<!-- Page Header -->
<div class="row align-items-center mb-3">
    <div class="col-sm-10">
        <h2 class="page-title mb-0">
            View Service PO
            <span class="badge bg-<?= $bc ?> spo-badge ms-2"><?= $labels[(int)$s['status']] ?></span>
        </h2>
        <nav aria-label="breadcrumb" class="mt-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="home"><i class="fa fa-home"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="service_po_list">Service POs</a></li>
                <li class="breadcrumb-item active"><?= h($s['spo_id']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="col-sm-2 text-end">
        <a href="service_po_list" class="btn btn-danger">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div id="alert-box"></div>

<!-- PO Header Details -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <!-- Row 1: Basic Info -->
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label class="form-label">SPO ID</label>
                        <input type="text" class="form-control" value="<?= h($s['spo_id']) ?>" readonly>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Vendor</label>
                        <input type="text" class="form-control" value="<?= h($s['vendor_name']) ?>" readonly>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Store / Location</label>
                        <input type="text" class="form-control" value="<?= h($s['location_name']) ?>" readonly>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Service Type</label>
                        <input type="text" class="form-control" value="<?= h($s['service_type_name']) ?>" readonly>
                    </div>

                    <div class="col-sm-6 mt-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" value="<?= h($s['description']) ?>" readonly>
                    </div>
                    <div class="col-sm-6 mt-3">
                        <label class="form-label">Period</label>
                        <input type="text" class="form-control"
                               value="<?= $s['period_from'] ? date('d M Y', strtotime($s['period_from'])) . ' — ' . date('d M Y', strtotime($s['period_to'])) : '—' ?>"
                               readonly>
                    </div>

                    <div class="col-sm-6 mt-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" class="form-control" value="<?= h($s['remarks']) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Created By</label>
                        <input type="text" class="form-control" value="<?= h($s['created_by_name']) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Created On</label>
                        <input type="text" class="form-control" value="<?= date('d M Y H:i', strtotime($s['created'])) ?>" readonly>
                    </div>

                    <!-- Financials -->
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Sub-total (₹)</label>
                        <input type="text" class="form-control" value="<?= number_format($s['subtotal'], 2) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Discount (₹)</label>
                        <input type="text" class="form-control" value="<?= number_format($s['discount'], 2) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Total Tax (₹)</label>
                        <input type="text" class="form-control" value="<?= number_format($s['total_tax'], 2) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label"><strong>Total Amount (₹)</strong></label>
                        <input type="text" class="form-control fw-bold" value="<?= number_format($s['total_amount'], 2) ?>" readonly>
                    </div>

                    <?php if ($s['status'] >= 2): ?>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Approved By</label>
                        <input type="text" class="form-control" value="<?= h($s['approved_by_name']) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Approved On</label>
                        <input type="text" class="form-control" value="<?= date('d M Y', strtotime($s['approved_on'])) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Approved Amount (₹)</label>
                        <input type="text" class="form-control text-primary fw-semibold" value="<?= number_format($s['approved_amount'], 2) ?>" readonly>
                    </div>
                    <?php endif; ?>

                    <?php if ($s['status'] >= 3): ?>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Invoice No</label>
                        <input type="text" class="form-control" value="<?= h($s['invoice_no']) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Invoice Date</label>
                        <input type="text" class="form-control" value="<?= $s['invoice_date'] ? date('d M Y', strtotime($s['invoice_date'])) : '—' ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Processed By</label>
                        <input type="text" class="form-control" value="<?= h($s['processed_by_name']) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Processed On</label>
                        <input type="text" class="form-control" value="<?= date('d M Y', strtotime($s['processed_on'])) ?>" readonly>
                    </div>
                    <div class="col-sm-3 mt-3">
                        <label class="form-label">Processed Amount (₹)</label>
                        <input type="text" class="form-control text-success fw-semibold" value="<?= number_format($s['processed_amount'], 2) ?>" readonly>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Line Items Table -->
<div class="card mt-3">
    <div class="card-body">
        <h4 class="form-section-title">Service PO Line Items</h4>

        <div style="overflow-x: auto; width: 100%;">
            <table class="table table-bordered table-hover table-sm align-middle mb-0" style="min-width: max-content;">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:40px;">#</th>
                        <th style="min-width:180px;">Service / Item</th>
                        <th style="min-width:180px;">Description</th>
                        <th style="min-width:80px;">Unit</th>
                        <th style="min-width:80px;">Qty</th>
                        <th style="min-width:110px;">Unit Price (₹)</th>
                        <th style="min-width:110px;">Discount (₹)</th>
                        <th style="min-width:80px;">Tax %</th>
                        <th style="min-width:110px;">Tax Amt (₹)</th>
                        <th style="min-width:120px;">Total (₹)</th>
                        <?php if ($s['status'] >= 2): ?>
                        <th style="min-width:130px;">Approved Amt (₹)</th>
                        <?php endif; ?>
                        <?php if ($s['status'] >= 3): ?>
                        <th style="min-width:130px;">Processed Amt (₹)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $n = 1;
                    foreach ($items as $item):
                        $i = $item['Service_purchase_order_item']; ?>
                        <tr>
                            <td class="px-2"><?= $n++ ?></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm" value="<?= h($i['service_name']) ?>" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm" value="<?= h($i['description']) ?>" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm" value="<?= h($i['unit']) ?>" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm" value="<?= $i['quantity'] ?>" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm text-end" value="<?= number_format($i['unit_price'], 2) ?>" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm text-end" value="<?= number_format($i['discount_value'], 2) ?>" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm text-end" value="<?= $i['tax_percentage'] ?>%" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm text-end" value="<?= number_format($i['tax_value'], 2) ?>" readonly></td>
                            <td class="px-2"><input type="text" class="form-control form-control-sm text-end fw-bold" value="<?= number_format($i['total_amount'], 2) ?>" readonly></td>
                            <?php if ($s['status'] >= 2): ?>
                            <td class="px-2"><input type="text" class="form-control form-control-sm text-end text-primary" value="<?= number_format($i['approved_amount'], 2) ?>" readonly></td>
                            <?php endif; ?>
                            <?php if ($s['status'] >= 3): ?>
                            <td class="px-2"><input type="text" class="form-control form-control-sm text-end text-success" value="<?= number_format($i['processed_amount'], 2) ?>" readonly></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- APPROVE SECTION (status == 1) -->
        <?php if ($s['status'] == 1): ?>
        <div class="mt-4">
            <h4 class="form-section-title"><i class="fa fa-check text-success"></i> Approve This SPO</h4>
            <p class="text-muted">Review and set approved amount per line item, then click Approve.</p>
            <div style="overflow-x: auto; width: 100%;">
                <table class="table table-bordered table-sm align-middle" id="approveTable" style="min-width: max-content;">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:40px;">#</th>
                            <th style="min-width:200px;">Service / Item</th>
                            <th style="min-width:130px;">Total Amt (₹)</th>
                            <th style="min-width:160px;">Approved Amt (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n = 1;
                        foreach ($items as $item):
                            $i = $item['Service_purchase_order_item']; ?>
                            <tr>
                                <td><?= $n++ ?></td>
                                <td><input type="text" class="form-control form-control-sm" value="<?= h($i['service_name']) ?>" readonly></td>
                                <td><input type="text" class="form-control form-control-sm text-end" value="<?= number_format($i['total_amount'], 2) ?>" readonly></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm approve-amt"
                                           data-id="<?= $i['id'] ?>"
                                           value="<?= $i['total_amount'] ?>" step="0.01" min="0"
                                           style="min-width:130px;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- PROCESS SECTION (status == 2) -->
        <?php if ($s['status'] == 2): ?>
        <div class="mt-4">
            <h4 class="form-section-title"><i class="fa fa-cog text-info"></i> Process This SPO</h4>
            <div class="row mb-3">
                <div class="col-sm-3">
                    <label class="form-label">Invoice No <span class="text-danger">*</span></label>
                    <input type="text" id="invoice_no" class="form-control" placeholder="e.g. INV-2026-001">
                </div>
                <div class="col-sm-3">
                    <label class="form-label">Invoice Date</label>
                    <input type="date" id="invoice_date" class="form-control">
                </div>
            </div>
            <div style="overflow-x: auto; width: 100%;">
                <table class="table table-bordered table-sm align-middle" id="processTable" style="min-width: max-content;">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:40px;">#</th>
                            <th style="min-width:200px;">Service / Item</th>
                            <th style="min-width:140px;">Approved Amt (₹)</th>
                            <th style="min-width:160px;">Processed Amt (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n = 1;
                        foreach ($items as $item):
                            $i = $item['Service_purchase_order_item']; ?>
                            <tr>
                                <td><?= $n++ ?></td>
                                <td><input type="text" class="form-control form-control-sm" value="<?= h($i['service_name']) ?>" readonly></td>
                                <td><input type="text" class="form-control form-control-sm text-end" value="<?= number_format($i['approved_amount'], 2) ?>" readonly></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm process-amt"
                                           data-id="<?= $i['id'] ?>"
                                           value="<?= $i['approved_amount'] ?>" step="0.01" min="0"
                                           style="min-width:130px;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="text-end mt-3">
            <a href="service_po_pdf?id=<?= $spo['Service_purchase_order']['spo_id'] ?>" target="_blank"
               class="btn btn-danger me-2">
                <i class="fa fa-file-pdf-o"></i> Print / PDF
            </a>
            <?php if ($s['status'] == 1): ?>
                <a href="edit_service_po?id=<?= $s['spo_id'] ?>" class="btn btn-primary me-2">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <button type="button" class="btn btn-success" id="approveBtn">
                    <i class="fa fa-check"></i> Approve SPO
                </button>
            <?php elseif ($s['status'] == 2): ?>
                <button type="button" class="btn btn-info" id="processBtn">
                    <i class="fa fa-cog"></i> Process SPO
                </button>
            <?php elseif ($s['status'] == 3): ?>
                <button class="btn btn-success" disabled>
                    <i class="fa fa-check-circle"></i> Processed
                </button>
            <?php elseif ($s['status'] == 0): ?>
                <button class="btn btn-danger" disabled>
                    <i class="fa fa-ban"></i> Cancelled
                </button>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
$(function () {

    $('#approveBtn').on('click', function () {
        var btn   = $(this);
        var items = {};
        $('.approve-amt').each(function () {
            items[$(this).data('id')] = $(this).val();
        });
        btn.prop('disabled', true).text('Approving...');
        $.ajax({
            url:         'approve_service_po',
            type:        'POST',
            contentType: 'application/json',
            dataType:    'json',
            data: JSON.stringify({ spo_id: '<?= $s['spo_id'] ?>', items: items }),
            success: function (res) {
                if (res.status) {
                    console.log(res);
                    Swal.fire({
                        icon: 'success', title: 'Approved!',
                        text: 'SPO approved successfully.',
                        timer: 1500, showConfirmButton: false
                    }).then(function () { window.location.href = 'service_po_list'; });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Approve SPO');
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
                btn.prop('disabled', false).html('<i class="fa fa-check"></i> Approve SPO');
            }
        });
    });

    $('#processBtn').on('click', function () {
        var btn   = $(this);
        var invNo = $('#invoice_no').val().trim();
        // if (!invNo) { alert('Please enter Invoice No.'); return; }
        if (!invNo) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Invoice Number',
                text: 'Please enter Invoice No.',
                confirmButtonText: 'OK'
            });
            return;
        }
        var items = {};
        $('.process-amt').each(function () {
            items[$(this).data('id')] = $(this).val();
        });
        btn.prop('disabled', true).text('Processing...');
        $.ajax({
            url:         'process_service_po',
            type:        'POST',
            contentType: 'application/json',
            dataType:    'json',
            data: JSON.stringify({
                spo_id:       '<?= $s['spo_id'] ?>',
                invoice_no:   invNo,
                invoice_date: $('#invoice_date').val(),
                items:        items
            }),
            success: function (res) {
                console.log(res);
                if (res.status) {
                    Swal.fire({
                        icon: 'success', title: 'Processed!',
                        text: 'SPO processed successfully.',
                        timer: 1500, showConfirmButton: false
                    }).then(function () { window.location.href = 'service_po_list'; });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    btn.prop('disabled', false).html('<i class="fa fa-cog"></i> Process SPO');
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
                btn.prop('disabled', false).html('<i class="fa fa-cog"></i> Process SPO');
            }
        });
    });

});
</script>