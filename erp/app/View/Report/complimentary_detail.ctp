<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Complimentary Detail</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php 
                            echo $this->Form->create('Search', ['url'=>'complimentary_detail']); 
                            
                            $options = ['' => 'All Stores'];
                            foreach($users as $user){
                                $options[$user['Location']['id']] = $user['Location']['name'];
                            }

                            echo $this->Form->input('cashier', [
                                'div'=>['class'=>'form-group'],
                                'label'=>'Select Store',
                                'class'=>'form-control',
                                'options'=>$options,
                                'id'=>'cashier',
                                'value'=>$cashier_id
                            ]);

                            echo $this->Form->input('daterange', [
                                'div'=>['class'=>'form-group'],
                                'label'=>'Select Date',
                                'class'=>'form-control calendarmultiple',
                                'id'=>'startdate',
                                'name'=>'daterange',
                                'required'=>'required',
                                'value'=>$yourStartDateValue
                            ]);

                            echo $this->Form->button('View', ['class'=>'btn btn-info', 'type'=>'submit']);
                            echo "&nbsp;" . $this->Form->button('Excel', ['class'=>'btn btn-info', 'type'=>'button', 'id'=>'exportdata']);
                            echo "&nbsp;" . $this->Form->button('PDF', ['class'=>'btn btn-info', 'type'=>'button', 'id'=>'btn-pdf']);
                            echo $this->Form->end();
                        ?>
                    </div>

                    <?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'complimentary_detail_pdf', 'target'=>'_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <?php
                // Normalize $complimentaryData into 1 row per bill with item details
                $bills = [];
                $billItemsMap = [];

                foreach ($complimentaryData as $r) {
                    $get = function($keys, $default = '') use ($r) {
                        if (!is_array($keys)) $keys = [$keys];
                        foreach ($keys as $k) {
                            if (isset($r[$k]) && !is_array($r[$k]) && $r[$k] !== '') return $r[$k];
                            foreach ($r as $bucket) {
                                if (is_array($bucket) && isset($bucket[$k]) && $bucket[$k] !== '') return $bucket[$k];
                            }
                        }
                        return $default;
                    };

                    // Already grouped bill structure with 'items'
                    if (isset($r['items']) && is_array($r['items'])) {
                        $ref = $r['reference_id'] ?: ($r['bill_no'] ?: uniqid());
                        $bills[$ref] = $r;
                        $billItemsMap[$ref] = $r['items'];
                        if (!empty($r['bill_no'])) {
                            $billItemsMap[$r['bill_no']] = $r['items'];
                        }
                        continue;
                    }

                    // Grouped structure with parallel arrays
                    if (isset($r['item_names']) && is_array($r['item_names'])) {
                        $ref = $r['reference_id'] ?? ($r['bill_no'] ?? uniqid());
                        if (!isset($bills[$ref])) {
                            $bills[$ref] = [
                                'report_date'        => $r['report_date'] ?? '',
                                'store_name'         => $r['store_name'] ?? ($r['deployment_name'] ?? ''),
                                'bill_no'            => $r['bill_no'] ?? '',
                                'reference_id'       => $r['reference_id'] ?? '',
                                'bill_open_time'     => $r['bill_open_time'] ?? '',
                                'bill_print_time'    => $r['bill_print_time'] ?? '',
                                'bill_settle_time'   => $r['bill_settle_time'] ?? '',
                                'kot_numbers'        => $r['kot_numbers'] ?? '',
                                'kot_print_time'     => $r['kot_print_time'] ?? '',
                                'tableno'            => $r['tableno'] ?? '',
                                'total_pax'          => $r['total_pax'] ?? '',
                                'customer_name'      => $r['customer_name'] ?? ($r['name'] ?? ''),
                                'customer_phone'     => $r['customer_phone'] ?? ($r['phone'] ?? ''),
                                'remarks'            => $r['remarks'][0] ?? ($r['manager_remarks'] ?? ($r['complimentary_head'] ?? '-')),
                                'items'              => [],
                                'item_count'         => 0,
                                'total_qty'          => 0,
                                'total_amount'       => 0,
                            ];
                        }
                        $count = count($r['item_names']);
                        for ($j = 0; $j < $count; $j++) {
                            $rateVal = isset($r['rates'][$j]) ? (float)str_replace(',', '', (string)$r['rates'][$j]) : 0;
                            $qtyVal  = isset($r['qtys'][$j]) ? (float)$r['qtys'][$j] : 0;
                            $amtVal  = isset($r['amounts'][$j]) ? (float)str_replace(',', '', (string)$r['amounts'][$j]) : ($rateVal * $qtyVal);
                            $bills[$ref]['items'][] = [
                                'kot_no'     => $r['kot_nos'][$j] ?? ($r['kot_numbers'] ?? '-'),
                                'category'   => $r['categories'][$j] ?? '',
                                'item_name'  => $r['item_names'][$j] ?? '',
                                'rate'       => $rateVal,
                                'qty'        => $qtyVal,
                                'amount'     => $amtVal,
                                'remarks'    => $r['remarks'][$j] ?? '-'
                            ];
                            $bills[$ref]['total_qty'] += $qtyVal;
                            $bills[$ref]['total_amount'] += $amtVal;
                        }
                        $bills[$ref]['item_count'] = count($bills[$ref]['items']);
                        $billItemsMap[$ref] = $bills[$ref]['items'];
                        if (!empty($bills[$ref]['bill_no'])) {
                            $billItemsMap[$bills[$ref]['bill_no']] = $bills[$ref]['items'];
                        }
                        continue;
                    }

                    // Flat/raw rows
                    $ref = $get(['reference_id']);
                    if (empty($ref)) {
                        $ref = $get(['bill_no', 'orderno']) ?: uniqid();
                    }

                    if (!isset($bills[$ref])) {
                        $compHead = $get(['complimentary_head']);
                        $mgrRem   = $get(['manager_remarks']);
                        $billRem  = ($compHead !== '') ? $compHead : (($mgrRem !== '') ? $mgrRem : '-');

                        $bills[$ref] = [
                            'report_date'        => $get(['report_date']),
                            'store_name'         => $get(['store_name', 'deployment_name']),
                            'bill_no'            => $get(['bill_no', 'orderno']),
                            'reference_id'       => $ref,
                            'bill_open_time'     => $get(['bill_open_time']),
                            'bill_print_time'    => $get(['bill_print_time']),
                            'bill_settle_time'   => $get(['bill_settle_time']),
                            'kot_numbers'        => $get(['kot_numbers']),
                            'kot_print_time'     => $get(['kot_print_time']),
                            'tableno'            => $get(['tableno', 'table_number']),
                            'total_pax'          => $get(['total_pax', 'pax']),
                            'customer_name'      => $get(['customer_name', 'name']),
                            'customer_phone'     => $get(['customer_phone', 'phone']),
                            'remarks'            => $billRem,
                            'items'              => [],
                            'item_count'         => 0,
                            'total_qty'          => 0,
                            'total_amount'       => 0,
                        ];
                    }

                    $rateVal = (float)str_replace(',', '', (string)$get(['rate', 'price'], 0));
                    $qtyVal  = (float)$get(['qty', 'quantity'], 0);
                    $amtRaw  = $get(['amount']);
                    $amtVal  = ($amtRaw !== '') ? (float)str_replace(',', '', (string)$amtRaw) : ($rateVal * $qtyVal);
                    $compComment = $get(['complimentary_comment']);
                    $itemRem = ($compComment !== '') ? $compComment : ($get(['remarks']) ?: '-');
                    $itemKot = $get(['kot_no', 'kot_number']) ?: ($get(['kot_numbers']) ?: '-');

                    $bills[$ref]['items'][] = [
                        'kot_no'    => $itemKot,
                        'category'  => $get(['category', 'category_name']),
                        'item_name' => $get(['item_name', 'product_name']),
                        'rate'      => $rateVal,
                        'qty'       => $qtyVal,
                        'amount'    => $amtVal,
                        'remarks'   => $itemRem
                    ];
                    $bills[$ref]['total_qty'] += $qtyVal;
                    $bills[$ref]['total_amount'] += $amtVal;
                    $bills[$ref]['item_count'] = count($bills[$ref]['items']);

                    $billItemsMap[$ref] = $bills[$ref]['items'];
                    if (!empty($bills[$ref]['bill_no'])) {
                        $billItemsMap[$bills[$ref]['bill_no']] = $bills[$ref]['items'];
                    }
                }

                $bills = array_values($bills);
                ?>

                <div class="table-responsive mt-3">
					<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th>Bill No</th>
                                <th>Date</th>
                                <th>Store Name</th>
                                <th>Bill Open Time</th>
                                <th>Bill Print Time</th>
                                <th>Bill Settle Time</th>
                                <th>Table</th>
                                <th>Remarks</th>
                                <th>Items</th>
                                <th>Total Qty</th>
                                <th>Total Amount</th>
                                <th>PAX</th>
                                <th>Customer Name</th>
                                <th>Customer Phone</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php 
                        $grandTotalQty = 0;
                        $grandTotalAmt = 0;
                        $i = 1;
                        foreach ($bills as $b): 
                            $grandTotalQty += (float)$b['total_qty'];
                            $grandTotalAmt += (float)$b['total_amount'];
                            $itemCount = count($b['items']);
                        ?>
                            <tr>
                                <td><?= $i++; ?></td>
                                <td><strong><?= h($b['bill_no']); ?></strong></td>
                                <td><?= h($b['report_date']); ?></td>
                                <td><?= h($b['store_name']); ?></td>
                                <td><?= h($b['bill_open_time']); ?></td>
                                <td><?= h($b['bill_print_time']); ?></td>
                                <td><?= h($b['bill_settle_time']); ?></td>
                                <td><?= h($b['tableno']); ?></td>
                                <td><?= h($b['remarks']); ?></td>
                                <td>
                                    <?= $itemCount; ?>
                                    <span
										class="badge badge-warning btn-item-details"
										style="cursor:pointer; font-size:12px; background-color:#ffc107; color:#212529; font-weight:600; padding:4px 8px; border-radius:4px; margin-left:6px;"
										data-bill-no="<?= h($b['bill_no']); ?>"
										data-ref-id="<?= h($b['reference_id']); ?>"
										data-kot-numbers="<?= h($b['kot_numbers']); ?>"
										data-toggle="modal"
										data-target="#itemDetailsModal">
										<i class="fa fa-list"></i> Details
									</span>
                                </td>
                                <td style="text-align:center;"><?= $b['total_qty']; ?></td>
                                <td style="text-align:right; font-weight:600; white-space:nowrap;"><?= number_format((float)$b['total_amount'], 2); ?></td>
                                <td style="text-align:center;"><?= h($b['total_pax']); ?></td>
                                <td><?= h($b['customer_name']); ?></td>
                                <td><?= h($b['customer_phone']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f8f9fa; font-weight:bold;">
                                <th colspan="10" style="text-align:right;">Grand Total:</th>
                                <th style="text-align:center;"><?= $grandTotalQty; ?></th>
                                <th style="text-align:right; white-space:nowrap;"><?= number_format($grandTotalAmt, 2); ?></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     Item Details Modal (KOT & Item Details with 100% Matching Alignment)
     ============================================================ -->
<div class="modal fade" id="itemDetailsModal" tabindex="-1" role="dialog" aria-labelledby="itemDetailsModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="itemDetailsModalLabel">
                    Item Details — Bill <span id="modalBillNo"></span>
                    <span style="font-size:14px; font-weight:normal; margin-left:15px; color:#555;">
                        (KOT No: <strong id="modalKotNo" class="text-primary"></strong>)
                    </span>
                </h4>
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

                <!-- Results table -->
                <div id="itemDetailsTableWrap" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="itemDetailsTable" style="width:100% !important;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:40px; text-align:center;">#</th>
                                    <th style="width:90px; text-align:center;">KOT No</th>
                                    <th>Category</th>
                                    <th>Item Name</th>
                                    <th style="width:80px; text-align:right;">Rate</th>
                                    <th style="width:60px; text-align:center;">Qty</th>
                                    <th style="width:90px; text-align:right;">Amount</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="itemDetailsBody">
                                <!-- Injected by JS -->
                            </tbody>
                            <tfoot class="thead-light" style="background:#f8f9fa; font-weight:bold;">
                                <tr>
                                    <th colspan="5" style="text-align:right;">Total:</th>
                                    <th id="modalTotalQty" style="text-align:center;">0</th>
                                    <th id="modalTotalAmt" style="text-align:right;">0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
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
var billItemsMap = <?= json_encode($billItemsMap) ?>;
var baseUrl = '<?= $this->base ?>';

$(document).ready(function () {

    /* ── Item-Details Modal ── */
    $(document).on('click', '.btn-item-details', function () {
        var billNo = $(this).data('bill-no') + '';
        var refId  = $(this).data('ref-id') + '';
        var kotNos = $(this).data('kot-numbers') || '-';

        $('#modalBillNo').text(billNo);
        $('#modalKotNo').text(kotNos);

        // Reset modal state
        $('#itemDetailsError').hide().text('');
        $('#itemDetailsTableWrap').hide();

        if ($.fn.DataTable.isDataTable('#itemDetailsTable')) {
            $('#itemDetailsTable').DataTable().destroy();
        }
        $('#itemDetailsBody').empty();

        var renderItems = function (items) {
            if (!items || items.length === 0) {
                $('#itemDetailsError').text('No item lines found for this Bill.').show();
                return;
            }

            var sumQty = 0;
            var sumAmt = 0;

            $.each(items, function (idx, item) {
                var rate = parseFloat(item.rate || 0);
                var qty  = parseFloat(item.qty || 0);
                var amt  = parseFloat(item.amount !== undefined ? item.amount : (rate * qty));
                var itemKot = item.kot_no || item.kot_number || kotNos;
                sumQty += qty;
                sumAmt += amt;

                var row = '<tr>'
                    + '<td style="text-align:center;">' + (idx + 1) + '</td>'
                    + '<td style="text-align:center;">' + $('<span>').text(itemKot).html() + '</td>'
                    + '<td>' + $('<span>').text(item.category || '').html() + '</td>'
                    + '<td><strong>' + $('<span>').text(item.item_name || '').html() + '</strong></td>'
                    + '<td style="text-align:right;">' + rate.toFixed(2) + '</td>'
                    + '<td style="text-align:center;">' + qty + '</td>'
                    + '<td style="text-align:right; font-weight:600;">' + amt.toFixed(2) + '</td>'
                    + '<td>' + $('<span>').text(item.remarks || '-').html() + '</td>'
                    + '</tr>';
                $('#itemDetailsBody').append(row);
            });

            $('#modalTotalQty').text(sumQty);
            $('#modalTotalAmt').text(sumAmt.toFixed(2));

            $('#itemDetailsTableWrap').show();

            // Note: scrollX:false ensures thead and tbody remain in the EXACT SAME <table>, preventing header/body misalignment
            $('#itemDetailsTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                scrollX: false,
                autoWidth: false,
                columnDefs: [
                    { orderable: false, targets: [0] }
                ],
                language: {
                    emptyTable: 'No items found for this Bill.'
                }
            });
        };

        // Try local map first for instantaneous opening
        var cached = billItemsMap[refId] || billItemsMap[billNo];
        if (cached && cached.length > 0) {
            renderItems(cached);
            return;
        }

        // AJAX fallback
        $('#itemDetailsLoader').show();
        $.ajax({
            url: baseUrl + '/report/complimentary_detail_items',
            type: 'GET',
            data: { reference_id: refId, bill_no: billNo },
            dataType: 'json',
            success: function (response) {
                $('#itemDetailsLoader').hide();
                if (response.error) {
                    $('#itemDetailsError').text(response.error).show();
                    return;
                }
                renderItems(response.items || []);
            },
            error: function (xhr) {
                $('#itemDetailsLoader').hide();
                $('#itemDetailsError').text('Failed to load item details. Please try again. (' + xhr.status + ')').show();
            }
        });
    });

    // Recalculate columns when modal finishes opening animation
    $('#itemDetailsModal').on('shown.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#itemDetailsTable')) {
            $('#itemDetailsTable').DataTable().columns.adjust();
        }
    });

    // Cleanup on modal close
    $('#itemDetailsModal').on('hidden.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#itemDetailsTable')) {
            $('#itemDetailsTable').DataTable().destroy();
        }
        $('#itemDetailsLoader').hide();
        $('#itemDetailsError').hide().text('');
        $('#itemDetailsTableWrap').hide();
        $('#itemDetailsBody').empty();
        $('#modalBillNo').text('');
        $('#modalKotNo').text('');
    });

    /* ── Exports ── */
    $("#exportdata").click(function(){
        var sdate=$("#startdate").val();
        var cashier=$("#cashier").val();
        window.open('<?= $this->base ?>/report/complimentary_detail_export?sdate='+encodeURIComponent(sdate)+'&cashier='+encodeURIComponent(cashier), '_blank');
    });

    $("#btn-pdf").click(function(){
        var pdfform = document.getElementById('pdfform');
        $('#pdfform').hide();
        var sdate = $("#startdate").val();
        var cashier = $("#cashier").val();

        var sdateelement = document.createElement("input");
        sdateelement.value = sdate;
        sdateelement.name = "sdate";

        var cashierelement = document.createElement("input");
        cashierelement.value = cashier;
        cashierelement.name = "cashier";

        pdfform.appendChild(sdateelement);
        pdfform.appendChild(cashierelement);
        pdfform.submit();
    });
});
</script>
