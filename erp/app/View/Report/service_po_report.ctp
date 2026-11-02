<?php $this->Html->css(array('bootstrap.min','dataTables.bootstrap','daterangepicker'), array('block'=>true)); ?>
<?php $this->Html->script(array('jquery.min','bootstrap.min','jquery.dataTables.min','dataTables.bootstrap.min','moment.min','daterangepicker'), array('block'=>true)); ?>

<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Service PO Report</h2>
        </div>
    </div>
</div>
    
<!-- ── Summary Cards ── -->
<div class="row mt-3">
    <div class="col-md-4 col-lg-4">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Total Submitted Value</p>
                        <h3 class="my-3">&#8377; <?= number_format($grandTotal['submitted'],2) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-4">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Total Approved Value</p>
                        <h3 class="my-3">&#8377; <?= number_format($grandTotal['approved'],2) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-4">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Total Processed Value</p>
                        <h3 class="my-3">&#8377; <?= number_format($grandTotal['processed'],2) ?></h3>
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
        
                <!-- ── Filters ── -->
                <div class="form-group">
                    <?php echo $this->Form->create(false, array('url'=>'service_po_report','method'=>'post')); ?>
                    <div class="row">
                        <div class="col-md-3">
                            <label>Date Range</label>
                            <input type="text" name="daterange" id="daterange" class="form-control"
                                value="<?= date('m/d/Y',strtotime($startDate)).' - '.date('m/d/Y',strtotime($endDate)) ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Store</label>
                            <select name="data[Search][store]" class="form-control" id="store">
                                <option value="">All Stores</option>
                                <?php foreach($stores as $st): ?>
                                <option value="<?= $st['Location']['id'] ?>"><?= h($st['Location']['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Vendor</label>
                            <select name="data[Search][vendor]" class="form-control" id="vendor">
                                <option value="">All Vendors</option>
                                <?php foreach($vendors as $vid=>$vname): ?>
                                <option value="<?= $vid ?>"><?= h($vname) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Service Type</label>
                            <select name="data[Search][service_type]" class="form-control" id="service_type">
                                <option value="">All Types</option>
                                <?php foreach($services as $sid=>$sname): ?>
                                <option value="<?= $sid ?>"><?= h($sname) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Status</label>
                            <select name="data[Search][status]" class="form-control" id="spstatus">
                                <option value="">All</option>
                                <option value="1">Submitted</option>
                                <option value="2">Approved</option>
                                <option value="3">Processed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <?php echo $this->Form->button('View', array('class'=>'btn btn-info','type'=>'submit')); ?>
                        &nbsp;<button type="button" class="btn btn-success" id="exportBtn">
                            <i class="fa fa-download"></i> CSV Export
                        </button>
                    </div>
                    <?php echo $this->Form->end(); ?>
                </div>

                <!-- ── Report Table ── -->
                <div class="table-responsive mt-3">
                    <table class="display table table-hover table-condensed" id="reportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>SPO ID</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Store</th>
                                <th>Service Type</th>
                                <th>Period</th>
                                <th>Invoice No</th>
                                <th>Lines</th>
                                <th>Total (&#8377;)</th>
                                <th>Approved (&#8377;)</th>
                                <th>Processed (&#8377;)</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $n=1; $totals=array('total'=>0,'approved'=>0,'processed'=>0);
                        foreach($spos as $spo): ?>
                            <tr>
                                <td><?= $n++ ?></td>
                                <td><a href="view_service_po?id=<?= $spo['spo_id'] ?>"><?= h($spo['spo_id']) ?></a></td>
                                <td><?= date('d M Y',strtotime($spo['created'])) ?></td>
                                <td><?= h($spo['vendor']) ?></td>
                                <td><?= h($spo['store']) ?></td>
                                <td><?= h($spo['service_type']) ?></td>
                                <td>
                                    <?php if($spo['period_from']): ?>
                                        <?= date('d M Y',strtotime($spo['period_from'])) ?> to <?= date('d M Y',strtotime($spo['period_to'])) ?>
                                    <?php else: echo '—'; endif; ?>
                                </td>
                                <td><?= h($spo['invoice_no']) ?: '—' ?></td>
                                <td>
                                    <?= $spo['line_count'] ?>
                                    <span
                                        class="badge badge-primary btn-spo-details"
                                        style="cursor:pointer; font-size:12px;"
                                        data-spo-id="<?= h($spo['spo_id']) ?>"
                                        data-toggle="modal"
                                        data-target="#spoItemDetailsModal"
                                        title="View line details">
                                        <i class="fa fa-list"></i> Details
                                    </span>
                                </td>
                                <td><?= number_format($spo['total_amount'],2) ?></td>
                                <td><?= $spo['status']>=2 ? number_format($spo['approved_amount'],2) : '—' ?></td>
                                <td><?= $spo['status']>=3 ? number_format($spo['processed_amount'],2) : '—' ?></td>
                                <td>
                                    <?php $bc=array(1=>'warning',2=>'info',3=>'success')[$spo['status']]??'default'; ?>
                                    <span class="label label-<?= $bc ?>"><?= $spo['status_label'] ?></span>
                                </td>
                                <td>
                                    <?= date('d M Y',strtotime($spo['created'])) ?><br>
                                    <small><?= h($spo['created_by']) ?></small>
                                </td>
                            </tr>
                        <?php
                            $totals['total']     += $spo['total_amount'];
                            $totals['approved']  += $spo['approved_amount'];
                            $totals['processed'] += $spo['processed_amount'];
                        endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(empty($spos)): ?>
                    <p class="text-center text-muted mt-2">No records found for selected filters.</p>
                    <?php endif; ?>
                </div>

            </div><!-- /.card-body -->
        </div><!-- /.card -->
    </div>
</div>


<!-- ================================================================
     SPO Item Details Modal
     ================================================================ -->
<div class="modal fade" id="spoItemDetailsModal" tabindex="-1" role="dialog" aria-labelledby="spoItemDetailsModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="spoItemDetailsModalLabel">
                    Line Details &mdash; SPO <span id="spoModalId"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <!-- Spinner -->
                <div id="spoItemLoader" class="text-center py-4" style="display:none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2 text-muted">Loading line details&hellip;</p>
                </div>

                <!-- Error -->
                <div id="spoItemError" class="alert alert-danger" style="display:none;"></div>

                <!-- Items table -->
                <div id="spoItemTableWrap" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-hover" id="spoItemTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Service Name</th>
                                    <th>Description</th>
                                    <th>Unit</th>
                                    <th>Qty</th>
                                    <th>Unit Price (&#8377;)</th>
                                    <th>Discount (&#8377;)</th>
                                    <th>Tax %</th>
                                    <th>Tax Value (&#8377;)</th>
                                    <th>Total (&#8377;)</th>
                                    <th>Approved (&#8377;)</th>
                                    <th>Processed (&#8377;)</th>
                                </tr>
                            </thead>
                            <tbody id="spoItemBody"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Totals</strong></td>
                                    <td id="sf_qty"></td>
                                    <td></td>
                                    <td id="sf_discount"></td>
                                    <td></td>
                                    <td id="sf_tax_value"></td>
                                    <td id="sf_total"></td>
                                    <td id="sf_approved"></td>
                                    <td id="sf_processed"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>


<script>
$(document).ready(function () {

    /* ── Main report DataTable ── */
    $('#reportTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [8] }]
    });

    /* ── Date-range picker ── */
    $('#daterange').daterangepicker({
        locale: { format: 'MM/DD/YYYY' },
        startDate: '<?= date('m/d/Y',strtotime($startDate)) ?>',
        endDate:   '<?= date('m/d/Y',strtotime($endDate)) ?>'
    });

    /* ── CSV Export ── */
    $('#exportBtn').click(function () {
        window.open(
            '<?= $this->request->base ?>/report/service_po_export'
            + '?from='         + encodeURIComponent('<?= $startDate ?>')
            + '&to='           + encodeURIComponent('<?= $endDate ?>')
            + '&store='        + encodeURIComponent($('#store').val())
            + '&vendor='       + encodeURIComponent($('#vendor').val())
            + '&service_type=' + encodeURIComponent($('#service_type').val())
            + '&status='       + encodeURIComponent($('#spstatus').val()),
            '_blank'
        );
    });

    /* ── Open modal: fetch item lines ── */
    $(document).on('click', '.btn-spo-details', function () {
        var spoId = $(this).data('spo-id');

        $('#spoModalId').text(spoId);

        // Reset modal state
        $('#spoItemError').hide().text('');
        $('#spoItemTableWrap').hide();

        if ($.fn.DataTable.isDataTable('#spoItemTable')) {
            $('#spoItemTable').DataTable().destroy();
        }

        $('#spoItemBody').empty();
        $('#sf_qty,#sf_discount,#sf_tax_value,#sf_total,#sf_approved,#sf_processed').text('');
        $('#spoItemLoader').show();

        $.ajax({
            url:      '<?php echo $this->request->base; ?>/report/service_po_report_items',
            type:     'GET',
            data:     { spo_id: spoId },
            dataType: 'json',

            success: function (response) {
                $('#spoItemLoader').hide();

                if (response.error) {
                    $('#spoItemError').text(response.error).show();
                    return;
                }

                var items = response.items || [];
                if (!items.length) {
                    $('#spoItemError').text('No line items found for this SPO.').show();
                    return;
                }

                var f2 = function (v) { return parseFloat(v || 0).toFixed(2); };
                var f3 = function (v) { return parseFloat(v || 0).toFixed(3); };

                var totals = { qty: 0, discount: 0, tax_value: 0, total: 0, approved: 0, processed: 0 };

                $.each(items, function (idx, item) {
                    totals.qty       += parseFloat(item.quantity         || 0);
                    totals.discount  += parseFloat(item.discount_value   || 0);
                    totals.tax_value += parseFloat(item.tax_value        || 0);
                    totals.total     += parseFloat(item.total_amount     || 0);
                    totals.approved  += parseFloat(item.approved_amount  || 0);
                    totals.processed += parseFloat(item.processed_amount || 0);

                    var row = '<tr>'
                        + '<td>'  + (idx + 1) + '</td>'
                        + '<td>'  + $('<span>').text(item.service_name).html() + '</td>'
                        + '<td>'  + $('<span>').text(item.description).html()  + '</td>'
                        + '<td>'  + $('<span>').text(item.unit).html()         + '</td>'
                        + '<td>'  + f3(item.quantity)        + '</td>'
                        + '<td>'  + f2(item.unit_price)      + '</td>'
                        + '<td>'  + f2(item.discount_value)  + '</td>'
                        + '<td>'  + f2(item.tax_percentage) + '%</td>'
                        + '<td>'  + f2(item.tax_value)       + '</td>'
                        + '<td><strong>' + f2(item.total_amount)    + '</strong></td>'
                        + '<td>'  + f2(item.approved_amount)  + '</td>'
                        + '<td>'  + f2(item.processed_amount) + '</td>'
                        + '</tr>';
                    $('#spoItemBody').append(row);
                });

                /* footer totals */
                $('#sf_qty').text(totals.qty.toFixed(3));
                $('#sf_discount').text(f2(totals.discount));
                $('#sf_tax_value').text(f2(totals.tax_value));
                $('#sf_total').html('<strong>' + f2(totals.total) + '</strong>');
                $('#sf_approved').text(f2(totals.approved));
                $('#sf_processed').text(f2(totals.processed));

                $('#spoItemTableWrap').show();

                $('#spoItemTable').DataTable({
                    pageLength: 10,
                    order:      [[0, 'asc']],
                    scrollX:    true,
                    autoWidth:  false,
                    columnDefs: [{ orderable: false, targets: [0] }],
                    language:   { emptyTable: 'No items found for this SPO.' }
                });
            },

            error: function (xhr) {
                $('#spoItemLoader').hide();
                $('#spoItemError')
                    .text('Failed to load line details. Please try again. (' + xhr.status + ')')
                    .show();
            }
        });
    });

    /* ── Clean up on modal close ── */
    $('#spoItemDetailsModal').on('hidden.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#spoItemTable')) {
            $('#spoItemTable').DataTable().destroy();
        }
        $('#spoItemLoader').hide();
        $('#spoItemError').hide().text('');
        $('#spoItemTableWrap').hide();
        $('#spoItemBody').empty();
        $('#spoModalId').text('');
        $('#sf_qty,#sf_discount,#sf_tax_value,#sf_total,#sf_approved,#sf_processed').text('');
    });

});
</script>
