<div class="row"><div class="col-sm-12"><div class="page-title-box"><h2 class="page-title">Item Wise Purchase Report</h2></div></div></div>
<div class="row"><div class="col-sm-12"><div class="card"><div class="card-body">

    <div class="form-group">
        <?php echo $this->Form->create('Search', ['url'=>'item_wise_purchase_report']); ?>
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
        <table class="display table table-hover table-condensed" id="itemWiseTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th>Type</th>
                    <th># Vendors</th>
                    <th>Qty</th>
                    <th>Wtd Avg Rate</th>
                    <th>Base</th>
                    <th>Tax</th>
                    <th>Excise</th>
                    <th>Addl. Excise</th>
                    <th>VAT</th>
                    <th>TCS</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($itemData as $it): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= h($it['sku']) ?></td>
                    <td><?= h($it['unit']) ?></td>
                    <td>
                        <span class="badge badge-<?= $it['expense_type']==='CAPEX' ? 'primary' : ($it['expense_type']==='OPEX' ? 'warning' : 'secondary') ?>">
                            <?= h($it['expense_type']) ?>
                        </span>
                    </td>
                    <td>
                        <?= h($it['vendor_count']) ?>
                        <span class="badge badge-primary btn-item-vendor-details"
                              style="cursor:pointer; font-size:12px;"
                              data-sku-id="<?= h($it['sku_id']) ?>"
                              data-sku-name="<?= h($it['sku']) ?>"
                              data-toggle="modal"
                              data-target="#itemVendorDetailsModal">
                            <i class="fa fa-list"></i> Details
                        </span>
                    </td>
                    <td><?= number_format($it['qty'],        2) ?></td>
                    <td><?= number_format($it['weighted_avg'], 2) ?></td>
                    <td><?= number_format($it['base'],        2) ?></td>
                    <td><?= number_format($it['tax'],         2) ?></td>
                    <td><?= number_format($it['excise_total'], 2) ?></td>
                    <td><?= number_format($it['addl_excise_total'], 2) ?></td>
                    <td><?= number_format($it['vat_total'], 2) ?></td>
                    <td><?= number_format($it['tcs_total'], 2) ?></td>
                    <td><strong><?= number_format($it['total'], 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div></div></div></div>

<!-- ============================================================
     Item → Vendor Details Modal
     ============================================================ -->
<div class="modal fade" id="itemVendorDetailsModal" tabindex="-1" role="dialog" aria-labelledby="itemVendorDetailsModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="itemVendorDetailsModalLabel">
                    Vendor Breakdown — <span id="modalSkuName"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="itemVendorLoader" class="text-center py-4" style="display:none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2 text-muted">Loading vendor details…</p>
                </div>
                <div id="itemVendorError" class="alert alert-danger" style="display:none;"></div>
                <div id="itemVendorTableWrap" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-hover" id="itemVendorTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Vendor</th>
                                    <th># POs</th>
                                    <th>Qty</th>
                                    <th>Wtd Avg Rate</th>
                                    <th>Base</th>
                                    <th>Tax</th>
                                    <th>Excise</th>
                                    <th>Addl. Excise</th>
                                    <th>VAT</th>
                                    <th>TCS</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="itemVendorBody"></tbody>
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
    $('#itemWiseTable').DataTable({ pageLength: 25, order: [[0, 'asc']] });

    /* ── CSV Export ── */
    $("#exportBtn").click(function () {
        window.open(
            '<?= $this->base ?>/report/item_wise_purchase_export'
            + '?sdate=' + encodeURIComponent($("#startdate").val())
            + '&store=' + encodeURIComponent($("#store").val()),
            '_blank'
        );
    });

    /* ── Vendor Details Modal ── */
    var baseUrl = '<?= $this->base ?>';

    function toMysqlDate(d) {
        var p = d.trim().split('/');
        return p[2] + '-' + p[0] + '-' + p[1];
    }

    $(document).on('click', '.btn-item-vendor-details', function () {
        var skuId   = $(this).data('sku-id');
        var skuName = $(this).data('sku-name');

        var dateRange = $("#startdate").val().split(" - ");
        var startDT   = dateRange[0] ? toMysqlDate(dateRange[0]) + ' 00:00:00' : '';
        var endDT     = dateRange[1] ? toMysqlDate(dateRange[1]) + ' 23:59:59' : '';
        var storeId   = $("#store").val() || 0;

        $('#modalSkuName').text(skuName);

        // Reset
        $('#itemVendorError').hide().text('');
        $('#itemVendorTableWrap').hide();
        if ($.fn.DataTable.isDataTable('#itemVendorTable')) {
            $('#itemVendorTable').DataTable().destroy();
        }
        $('#itemVendorBody').empty();
        $('#itemVendorLoader').show();

        $.ajax({
            url: baseUrl + '/report/item_wise_purchase_vendors',
            type: 'GET',
            data: { sku_id: skuId, start: startDT, end: endDT, store_id: storeId },
            dataType: 'json',
            success: function (response) {
                $('#itemVendorLoader').hide();

                if (response.error) {
                    $('#itemVendorError').text(response.error).show();
                    return;
                }

                var items = response.items || [];
                if (items.length === 0) {
                    $('#itemVendorError').text('No vendor lines found for this SKU.').show();
                    return;
                }

                var fmt2 = function (v) { return parseFloat(v || 0).toFixed(2); };
                var fmt3 = function (v) { return parseFloat(v || 0).toFixed(3); };
                var fmt4 = function (v) { return parseFloat(v || 0).toFixed(4); };

                $.each(items, function (idx, item) {
                    var row = '<tr>'
                        + '<td>' + (idx + 1) + '</td>'
                        + '<td>' + $('<span>').text(item.vendor).html() + '</td>'
                        + '<td>' + item.po_count + '</td>'
                        + '<td>' + fmt2(item.qty) + '</td>'
                        + '<td>' + fmt2(item.weighted_avg) + '</td>'
                        + '<td>' + fmt2(item.base) + '</td>'
                        + '<td>' + fmt2(item.tax) + '</td>'
                        + '<td>' + fmt2(item.excise_total) + '</td>'
                        + '<td>' + fmt2(item.addl_excise_total) + '</td>'
                        + '<td>' + fmt2(item.vat_total) + '</td>'
                        + '<td>' + fmt2(item.tcs_total) + '</td>'
                        + '<td><strong>' + fmt2(item.total) + '</strong></td>'
                        + '</tr>';
                    $('#itemVendorBody').append(row);
                });

                $('#itemVendorTableWrap').show();
                $('#itemVendorTable').DataTable({
                    pageLength: 10,
                    order: [[11, 'desc']],   // sort by Total desc by default
                    scrollX: true,
                    autoWidth: false,
                    columnDefs: [{ orderable: false, targets: [0] }],
                    language: { emptyTable: 'No vendors found for this SKU.' }
                });
            },
            error: function (xhr) {
                $('#itemVendorLoader').hide();
                $('#itemVendorError')
                    .text('Failed to load vendor details. Please try again. (' + xhr.status + ')')
                    .show();
            }
        });
    });

    // On modal close: destroy DataTable, reset everything
    $('#itemVendorDetailsModal').on('hidden.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#itemVendorTable')) {
            $('#itemVendorTable').DataTable().destroy();
        }
        $('#itemVendorLoader').hide();
        $('#itemVendorError').hide().text('');
        $('#itemVendorTableWrap').hide();
        $('#itemVendorBody').empty();
        $('#modalSkuName').text('');
    });

});
</script>