<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Purchase Order Report v2</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <?php echo $this->Form->create('Search', ['url'=>'po_report_v2']); ?>
                    <div class="row">
                        <div class="col-md-3">
                            <?php echo $this->Form->input('store', ['div'=>false,'label'=>'Store','class'=>'form-control','options'=>(['' => 'All Stores'] + Hash::combine($stores, '{n}.Location.id', '{n}.Location.name')),'id'=>'store']); ?>
                        </div>
                        <div class="col-md-3">
                            <?php echo $this->Form->input('vendor', ['div'=>false,'label'=>'Vendor','class'=>'form-control','empty'=>'All Vendors','options'=>$vendors,'id'=>'vendor']); ?>
                        </div>
                        <div class="col-md-3">
                            <?php echo $this->Form->input('status', ['div'=>false,'label'=>'Status','class'=>'form-control','options'=>[''  =>'All',1=>'Submitted',2=>'Approved',3=>'Processed',4=>'Stocked In'],'id'=>'pstatus']); ?>
                        </div>
                        <div class="col-md-3">
                            <?php echo $this->Form->input('daterange_hidden', ['div'=>false,'label'=>'Date Range','class'=>'form-control calendarmultiple','id'=>'startdate','name'=>'daterange','required'=>true,'value'=>$startdate]); ?>
                        </div>
                    </div>
                    <div class="mt-2">
                        <?php echo $this->Form->button('View', ['class'=>'btn btn-info','type'=>'submit']); ?>
                        &nbsp;<button type="button" class="btn btn-success" id="exportBtn">CSV Export</button>
                    </div>
                    <?php echo $this->Form->end(); ?>
                </div>

                <div class="table-responsive mt-3">
                    <table class="display table table-hover table-condensed" id="poTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>PO No</th>
                                <th>Date</th>
                                <th>Store</th>
                                <th>Vendor</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Item Qty</th>
                                <th>Stockin Qty</th>
                                <th>Gross</th>
                                <th>Discount</th>
                                <th>Base</th>
                                <th>Tax</th>
                                <th>TCS</th>
                                <th>Excise</th>
                                <th>Addl. Excise</th>
                                <th>VAT</th>
                                <th>TCS</th>
                                <th>Total</th>
                                <th>Approved (w/tax)</th>
                                <th>Processed (w/tax)</th>
                                <th>Stocked In (w/tax)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; foreach ($pos as $p): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= h($p['po_id']) ?></td>
                                <td><?= h($p['po_date']) ?></td>
                                <td><?= h($p['store']) ?></td>
                                <td><?= h($p['vendor']) ?></td>
                                <td><?= h($p['status']) ?></td>
                                <td><?= h($p['item_count']) ?>
                                    <span
										class="badge badge-primary btn-item-details"
										style="cursor:pointer; font-size:12px;"
										data-po-id="<?= h($p['po_id']) ?>"
										data-po-no="<?= h($p['po_id']) ?>"
										data-toggle="modal"
										data-target="#itemDetailsModal">
										<i class="fa fa-list"></i> Details
									</span>
                                </td>
                                <td><?= number_format($p['qty_total'],   3) ?></td>
                                <td><?= number_format($p['stockin_qty'],   3) ?></td>
                                <td><?= number_format($p['gross'],        2) ?></td>
                                <td><?= number_format($p['discount'],     2) ?></td>
                                <td><?= number_format($p['base'],         2) ?></td>
                                <td><?= number_format($p['tax'],          2) ?></td>
                                <td><?= number_format($p['tcs'],          2) ?></td>
                                <td><?= number_format($p['excise_total'], 2) ?></td>
                                <td><?= number_format($p['addl_excise_total'], 2) ?></td>
                                <td><?= number_format($p['vat_total'],    2) ?></td>
                                <td><?= number_format($p['tcs_total'],    2) ?></td>
                                <td><strong><?= number_format($p['total'], 2) ?></strong></td>
                                <td><?= number_format($p['approved_amt'],  2) ?></td>
                                <td><?= number_format($p['processed_amt'], 2) ?></td>
                                <td><?= number_format($p['stockin_amt'],   2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     Item Details Modal  (loaded on demand via AJAX)
     ============================================================ -->
<div class="modal fade" id="itemDetailsModal" tabindex="-1" role="dialog" aria-labelledby="itemDetailsModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="itemDetailsModalLabel">Item Details — PO <span id="modalPoNo"></span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Loading spinner -->
                <div id="itemDetailsLoader" class="text-center py-4" style="display:none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2 text-muted">Loading item details…</p>
                </div>

                <!-- Error message -->
                <div id="itemDetailsError" class="alert alert-danger" style="display:none;"></div>

                <!-- Results table (hidden until data arrives) -->
                <div id="itemDetailsTableWrap" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-hover" id="itemDetailsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Item Qty</th>
                                    <th>Stockin Qty</th>
                                    <th>Gross</th>
                                    <th>Discount</th>
                                    <th>Base</th>
                                    <th>Tax</th>
                                    <th>TCS</th>
                                    <th>Excise</th>
                                    <th>Addl. Excise</th>
                                    <th>VAT</th>
                                    <th>TCS</th>
                                    <th>Total</th>
                                    <th>Approved (w/tax)</th>
                                    <th>Processed (w/tax)</th>
                                    <th>Stocked In (w/tax)</th>
                                </tr>
                            </thead>
                            <tbody id="itemDetailsBody">
                                <!-- rows injected by JS -->
                            </tbody>
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
    $('#poTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [7] }]
    });

    /* ── CSV Export ── */
    $("#exportBtn").click(function () {
        window.open(
            '<?= $this->base ?>/dashboard/po_report_v2_export'
            + '?sdate='  + encodeURIComponent($("#startdate").val())
            + '&store='  + encodeURIComponent($("#store").val())
            + '&vendor=' + encodeURIComponent($("#vendor").val())
            + '&status=' + encodeURIComponent($("#pstatus").val()),
            '_blank'
        );
    });

    /* ── Item-Details Modal ── */
    var baseUrl = '<?= $this->base ?>';

    $(document).on('click', '.btn-item-details', function () {
        var poId = $(this).data('po-id');
        var poNo = $(this).data('po-no');

        $('#modalPoNo').text(poNo);

        // Reset modal state
        $('#itemDetailsError').hide().text('');
        $('#itemDetailsTableWrap').hide();

        // Destroy existing DataTable instance before clearing rows
        if ($.fn.DataTable.isDataTable('#itemDetailsTable')) {
            $('#itemDetailsTable').DataTable().destroy();
        }

        $('#itemDetailsBody').empty();
        $('#itemDetailsLoader').show();

        $.ajax({
            url: baseUrl + '/dashboard/po_report_v2_items',
            type: 'GET',
            data: { po_id: poId },
            dataType: 'json',
            success: function (response) {
                $('#itemDetailsLoader').hide();

                if (response.error) {
                    $('#itemDetailsError').text(response.error).show();
                    return;
                }

                var items = response.items || [];
                if (items.length === 0) {
                    $('#itemDetailsError').text('No item lines found for this PO.').show();
                    return;
                }

                var fmt2 = function (v) { return parseFloat(v).toFixed(2); };
                var fmt3 = function (v) { return parseFloat(v).toFixed(3); };

                $.each(items, function (idx, item) {
                    var row = '<tr>'
                        + '<td>' + (idx + 1) + '</td>'
                        + '<td>' + $('<span>').text(item.item_name).html() + '</td>'
                        + '<td>' + fmt3(item.qty)           + '</td>'
                        + '<td>' + fmt3(item.stockin_qty)   + '</td>'
                        + '<td>' + fmt2(item.gross)         + '</td>'
                        + '<td>' + fmt2(item.discount)      + '</td>'
                        + '<td>' + fmt2(item.base)          + '</td>'
                        + '<td>' + fmt2(item.tax)           + '</td>'
                        + '<td>' + fmt2(item.tcs)           + '</td>'
                        + '<td>' + fmt2(item.excise_total)  + '</td>'
                        + '<td>' + fmt2(item.addl_excise_total) + '</td>'
                        + '<td>' + fmt2(item.vat_total)     + '</td>'
                        + '<td>' + fmt2(item.tcs_total)     + '</td>'
                        + '<td><strong>' + fmt2(item.total) + '</strong></td>'
                        + '<td>' + fmt2(item.approved_amt)  + '</td>'
                        + '<td>' + fmt2(item.processed_amt) + '</td>'
                        + '<td>' + fmt2(item.stockin_amt)   + '</td>'
                        + '</tr>';
                    $('#itemDetailsBody').append(row);
                });

                $('#itemDetailsTableWrap').show();

                // Initialize DataTable AFTER rows are injected and wrapper is visible
                $('#itemDetailsTable').DataTable({
                    pageLength: 10,
                    order: [[0, 'asc']],
                    scrollX: true,          // handles wide table inside modal
                    autoWidth: false,
                    columnDefs: [
                        { orderable: false, targets: [0] }   // # col not sortable
                    ],
                    language: {
                        emptyTable: 'No items found for this PO.'
                    }
                });
            },
            error: function (xhr) {
                $('#itemDetailsLoader').hide();
                $('#itemDetailsError')
                    .text('Failed to load item details. Please try again. (' + xhr.status + ')')
                    .show();
            }
        });
    });

    // On modal close: destroy DataTable, reset everything
    $('#itemDetailsModal').on('hidden.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#itemDetailsTable')) {
            $('#itemDetailsTable').DataTable().destroy();
        }
        $('#itemDetailsLoader').hide();
        $('#itemDetailsError').hide().text('');
        $('#itemDetailsTableWrap').hide();
        $('#itemDetailsBody').empty();
        $('#modalPoNo').text('');
    });

});
</script>