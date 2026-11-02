<div class="row"><div class="col-sm-12"><div class="page-title-box"><h2 class="page-title">Vendor Wise Purchase Report</h2></div></div></div>
<div class="row"><div class="col-sm-12"><div class="card"><div class="card-body">

    <div class="form-group">
        <?php echo $this->Form->create('Search', ['url'=>'vendor_wise_purchase_report']); ?>
        <div class="row">
            <div class="col-md-4">
                <?php echo $this->Form->input('store', ['div'=>false,'label'=>'Store','class'=>'form-control','options'=>(['' => 'All Stores'] + Hash::combine($stores, '{n}.Location.id', '{n}.Location.name')),'id'=>'store']); ?>
            </div>
            <div class="col-md-4">
                <?php echo $this->Form->input('daterange_h', ['div'=>false,'label'=>'Date Range','class'=>'form-control calendarmultiple','id'=>'startdate','name'=>'daterange','required'=>true,'value'=>$startdate]); ?>
            </div>
        </div>
        <div class="mt-2">
            <?php echo $this->Form->button('View', ['class'=>'btn btn-info','type'=>'submit']); ?>
            &nbsp;<button type="button" class="btn btn-success" id="exportBtn">CSV Export</button>
        </div>
        <?php echo $this->Form->end(); ?>
    </div>

    <div class="table-responsive mt-3">
        <table class="display table table-hover table-condensed" id="vendorTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vendor</th>
                    <th># POs</th>
                    <th>Items</th>
                    <th>Qty</th>
                    <th>Base</th>
                    <th>Tax</th>
                    <th>Excise</th>
                    <th>Addl. Excise</th>
                    <th>VAT</th>
                    <th>TCS</th>
                    <th>Total</th>
                    <th>CAPEX</th>
                    <th>OPEX</th>
                    <th>Unclassified</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($vendorData as $v): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= h($v['vendor']) ?></td>
                    <td><?= h($v['po_count']) ?></td>
                    <td>
                        <?= h($v['item_count']) ?>
                        <span class="badge badge-primary btn-vendor-item-details"
                              style="cursor:pointer; font-size:12px;"
                              data-vendor-id="<?= h($v['vendor_id']) ?>"
                              data-vendor-name="<?= h($v['vendor']) ?>"
                              data-toggle="modal"
                              data-target="#vendorItemDetailsModal">
                            <i class="fa fa-list"></i> Details
                        </span>
                    </td>
                    <td><?= number_format($v['qty'],        3) ?></td>
                    <td><?= number_format($v['base'],       2) ?></td>
                    <td><?= number_format($v['tax'],        2) ?></td>
                    <td><?= number_format($v['excise_total'], 2) ?></td>
                    <td><?= number_format($v['addl_excise_total'], 2) ?></td>
                    <td><?= number_format($v['vat_total'], 2) ?></td>
                    <td><?= number_format($v['tcs_total'], 2) ?></td>
                    <td><strong><?= number_format($v['total'], 2) ?></strong></td>
                    <td><?= number_format($v['capex_total'],  2) ?></td>
                    <td><?= number_format($v['opex_total'],   2) ?></td>
                    <td><?= number_format($v['unclassified'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div></div></div></div>

<!-- ============================================================
     Vendor Item Details Modal
     ============================================================ -->
<div class="modal fade" id="vendorItemDetailsModal" tabindex="-1" role="dialog" aria-labelledby="vendorItemDetailsModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="vendorItemDetailsModalLabel">
                    Item Details — <span id="modalVendorName"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="vendorItemLoader" class="text-center py-4" style="display:none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2 text-muted">Loading item details…</p>
                </div>
                <div id="vendorItemError" class="alert alert-danger" style="display:none;"></div>
                <div id="vendorItemTableWrap" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-hover" id="vendorItemTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Qty</th>
                                    <th>Base</th>
                                    <th>Tax</th>
                                    <th>Excise</th>
                                    <th>Addl. Excise</th>
                                    <th>VAT</th>
                                    <th>TCS</th>
                                    <th>Total</th>
                                    <th>CAPEX</th>
                                    <th>OPEX</th>
                                    <th>Unclassified</th>
                                </tr>
                            </thead>
                            <tbody id="vendorItemBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    /* ── DataTable ── */
    $('#vendorTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']]
    });

    /* ── CSV Export ── */
    $("#exportBtn").click(function () {
        window.open(
            '<?= $this->base ?>/report/vendor_wise_purchase_export'
            + '?sdate=' + encodeURIComponent($("#startdate").val())
            + '&store=' + encodeURIComponent($("#store").val()),
            '_blank'
        );
    });

    /* ── Vendor Item-Details Modal ── */
    var baseUrl = '<?= $this->base ?>';

    $(document).on('click', '.btn-vendor-item-details', function () {
        var vendorId   = $(this).data('vendor-id');
        var vendorName = $(this).data('vendor-name');

        // Parse date range from the filter input (format: "mm/dd/yyyy - mm/dd/yyyy")
        var dateRange = $("#startdate").val().split(" - ");
        var startDT   = dateRange[0] ? dateRange[0].trim() + ' 00:00:00' : '';
        var endDT     = dateRange[1] ? dateRange[1].trim() + ' 23:59:59' : '';
        var storeId   = $("#store").val() || 0;

        $('#modalVendorName').text(vendorName);

        // Reset modal state
        $('#vendorItemError').hide().text('');
        $('#vendorItemTableWrap').hide();
        if ($.fn.DataTable.isDataTable('#vendorItemTable')) {
            $('#vendorItemTable').DataTable().destroy();
        }
        $('#vendorItemBody').empty();
        $('#vendorItemLoader').show();

        $.ajax({
            url: baseUrl + '/report/vendor_wise_purchase_items',
            type: 'GET',
            data: {
                vendor_id : vendorId,
                start     : startDT,
                end       : endDT,
                store_id  : storeId
            },
            dataType: 'json',
            success: function (response) {
                $('#vendorItemLoader').hide();

                if (response.error) {
                    $('#vendorItemError').text(response.error).show();
                    return;
                }

                var items = response.items || [];
                if (items.length === 0) {
                    $('#vendorItemError').text('No item lines found for this vendor.').show();
                    return;
                }

                var fmt2 = function (v) { return parseFloat(v || 0).toFixed(2); };
                var fmt3 = function (v) { return parseFloat(v || 0).toFixed(3); };

                $.each(items, function (idx, item) {
                    var row = '<tr>'
                        + '<td>' + (idx + 1) + '</td>'
                        + '<td>' + $('<span>').text(item.item_name).html() + '</td>'
                        + '<td>' + fmt3(item.qty)          + '</td>'
                        + '<td>' + fmt2(item.base)         + '</td>'
                        + '<td>' + fmt2(item.tax)          + '</td>'
                        + '<td>' + fmt2(item.excise_total) + '</td>'
                        + '<td>' + fmt2(item.addl_excise_total) + '</td>'
                        + '<td>' + fmt2(item.vat_total)    + '</td>'
                        + '<td>' + fmt2(item.tcs_total)    + '</td>'
                        + '<td><strong>' + fmt2(item.total) + '</strong></td>'
                        + '<td>' + fmt2(item.capex_total)  + '</td>'
                        + '<td>' + fmt2(item.opex_total)   + '</td>'
                        + '<td>' + fmt2(item.unclassified) + '</td>'
                        + '</tr>';
                    $('#vendorItemBody').append(row);
                });

                $('#vendorItemTableWrap').show();

                $('#vendorItemTable').DataTable({
                    pageLength: 10,
                    order: [[0, 'asc']],
                    scrollX: true,
                    autoWidth: false,
                    columnDefs: [{ orderable: false, targets: [0] }],
                    language: { emptyTable: 'No items found for this vendor.' }
                });
            },
            error: function (xhr) {
                $('#vendorItemLoader').hide();
                $('#vendorItemError')
                    .text('Failed to load item details. Please try again. (' + xhr.status + ')')
                    .show();
            }
        });
    });

    // On modal close: destroy DataTable, reset everything
    $('#vendorItemDetailsModal').on('hidden.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#vendorItemTable')) {
            $('#vendorItemTable').DataTable().destroy();
        }
        $('#vendorItemLoader').hide();
        $('#vendorItemError').hide().text('');
        $('#vendorItemTableWrap').hide();
        $('#vendorItemBody').empty();
        $('#modalVendorName').text('');
    });

});
</script>