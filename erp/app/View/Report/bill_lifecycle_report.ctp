<style>
.lifecycle-timeline {
    position: relative;
    padding-left: 30px;
    margin-top: 15px;
    margin-bottom: 15px;
    border-left: 3px solid #dee2e6;
}
.timeline-event {
    position: relative;
    margin-bottom: 20px;
    padding-left: 15px;
}
.timeline-event::before {
    content: '';
    position: absolute;
    left: -38px;
    top: 3px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background-color: #007bff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #007bff;
}
.timeline-event.event-danger::before { background-color: #dc3545; box-shadow: 0 0 0 2px #dc3545; }
.timeline-event.event-warning::before { background-color: #ffc107; box-shadow: 0 0 0 2px #ffc107; }
.timeline-event.event-success::before { background-color: #28a745; box-shadow: 0 0 0 2px #28a745; }
.timeline-event.event-info::before { background-color: #17a2b8; box-shadow: 0 0 0 2px #17a2b8; }
.timeline-event.event-purple::before { background-color: #6f42c1; box-shadow: 0 0 0 2px #6f42c1; }
.timeline-event.event-secondary::before { background-color: #6c757d; box-shadow: 0 0 0 2px #6c757d; }
.timeline-event.event-dark::before { background-color: #343a40; box-shadow: 0 0 0 2px #343a40; }

.timeline-time {
    font-size: 12px;
    color: #6c757d;
    font-weight: 600;
}
.timeline-title {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 2px;
}
.timeline-desc {
    font-size: 13px;
    color: #333;
}
.badge-custom {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
}
.badge-purple {
    background-color: #6f42c1;
    color: #fff;
}
.card-bill-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}
#example {
    width: 100% !important;
    font-size: 12px;
}
#example th {
    padding: 8px 6px;
    font-size: 12px;
    background-color: #f1f4f8;
    vertical-align: middle;
}
#example td {
    padding: 6px 6px;
    vertical-align: middle;
    font-size: 12px;
}
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Bill Lifecycle Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <?php 
                            echo $this->Form->create('Search', ['url'=>'bill_lifecycle_report', 'class'=>'form-inline']); 
                            
                            $options = ['' => 'All Stores'];
                            if (!empty($users)) {
                                foreach($users as $user){
                                    $options[$user['Location']['id']] = $user['Location']['name'];
                                }
                            }

                            echo $this->Form->input('cashier', [
                                'div'=>['class'=>'form-group mr-3 mb-2'],
                                'label'=>'Select Store: &nbsp;',
                                'class'=>'form-control',
                                'options'=>$options,
                                'id'=>'cashier',
                                'value'=>$cashier_id
                            ]);

                            echo $this->Form->input('daterange', [
                                'div'=>['class'=>'form-group mr-3 mb-2'],
                                'label'=>'Select Date: &nbsp;',
                                'class'=>'form-control calendarmultiple',
                                'id'=>'startdate',
                                'name'=>'daterange',
                                'required'=>'required',
                                'value'=>$startdate
                            ]);

                            echo $this->Form->input('orderno', [
                                'div'=>['class'=>'form-group mr-3 mb-2'],
                                'label'=>'Bill / Order No: &nbsp;',
                                'class'=>'form-control',
                                'id'=>'orderno',
                                'placeholder'=>'Search Order No...',
                                'value'=>$orderno_search
                            ]);

                            echo $this->Form->button('View Report', ['class'=>'btn btn-info mb-2', 'type'=>'submit']);
                            echo "&nbsp;" . $this->Form->button('Export Excel', ['class'=>'btn btn-success mb-2', 'type'=>'button', 'id'=>'exportdata']);
                            echo "&nbsp;" . $this->Form->button('Export PDF', ['class'=>'btn btn-danger mb-2', 'type'=>'button', 'id'=>'btn-pdf']);
                            echo $this->Form->end();
                        ?>
                    </div>

                    <?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'bill_lifecycle_report_pdf', 'target'=>'_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <?php 
                $totalBills = count($bills);
                $totalAmount = 0;
                $activeItemCount = 0;
                $deletedItemCount = 0;
                foreach ($bills as $b) {
                    $totalAmount += $b['summary']['amount'];
                    $activeItemCount += $b['summary']['active_item_count'];
                    $deletedItemCount += $b['summary']['deleted_item_count'];
                }
                ?>

                <div class="row mb-3" style="background:#f4f6f9; padding:15px; border-radius:5px;">
                    <div class="col-sm-3">
                        <strong>Total Bills:</strong> <span class="badge badge-primary"><?php echo $totalBills; ?></span>
                    </div>
                    <div class="col-sm-3">
                        <strong>Total Value:</strong> <span class="badge badge-success">₹<?php echo number_format($totalAmount, 2); ?></span>
                    </div>
                    <div class="col-sm-3">
                        <strong>Active Items:</strong> <span class="badge badge-info"><?php echo $activeItemCount; ?></span>
                    </div>
                    <div class="col-sm-3">
                        <strong>Deleted/Removed Items:</strong> <span class="badge badge-danger"><?php echo $deletedItemCount; ?></span>
                    </div>
                </div>

                <?php if (empty($bills)): ?>
                    <div class="alert alert-warning">No bills found for the selected filter criteria.</div>
                <?php else: ?>
                    <div class="mt-3">
                        <table id="example" class="table table-bordered table-striped table-hover table-sm display" style="width:100%">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Bill No</th>
                                    <th>Reference ID</th>
                                    <th>Table</th>
                                    <th>Store</th>
                                    <th>Cashier</th>
                                    <th>Date/Time</th>
                                    <th>Amount (₹)</th>
                                    <th>Status</th>
                                    <th>Events</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bills as $index => $bill): 
                                    $s = $bill['summary'];
                                    $billId = "bill_" . $s['id'];
                                    $statusBadge = 'badge-success';
                                    if ($s['status_text'] === 'Cancelled / Void') {
                                        $statusBadge = 'badge-danger';
                                    } elseif ($s['status_text'] === 'Preparing') {
                                        $statusBadge = 'badge-warning';
                                    } elseif ($s['status_text'] === 'Settled / Paid') {
                                        $statusBadge = 'badge-success';
                                    } elseif ($s['status_text'] === 'Unbilled') {
                                        $statusBadge = 'badge-info';
                                    } elseif ($s['status'] == 0) {
                                        $statusBadge = 'badge-danger';
                                    } elseif ($s['status'] == 3) {
                                        $statusBadge = 'badge-warning';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo ($index + 1); ?></td>
                                        <td>
                                            <strong><?php echo h($s['orderno']); ?></strong>
                                            <?php if (!empty($s['is_split'])): ?>
                                                &nbsp;<span class="badge" style="background:#6f42c1; color:#fff;"><i class="fas fa-columns"></i> Split (<?php echo $s['split_count']; ?> Bills)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo h($s['reference_id']); ?></small></td>
                                        <td>Table <?php echo h($s['table_number']); ?></td>
                                        <td><?php echo h($s['store_name']); ?></td>
                                        <td><?php echo h($s['cashier_name']); ?></td>
                                        <td><?php echo date('d-m-Y h:i A', strtotime($s['created'])); ?></td>
                                        <td><strong>₹<?php echo number_format($s['amount'], 2); ?></strong></td>
                                        <td><span class="badge <?php echo $statusBadge; ?>"><?php echo h($s['status_text']); ?></span></td>
                                        <td class="text-center"><span class="badge badge-secondary"><?php echo count($bill['timeline']); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info btn-view-lifecycle" type="button" data-bill-id="<?php echo $billId; ?>">
                                                <i class="fas fa-eye"></i> View Lifecycle
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Lifecycle Detail Modals (hidden, rendered once per bill) -->
                    <?php foreach ($bills as $index => $bill): 
                        $s = $bill['summary'];
                        $billId = "bill_" . $s['id'];
                        $statusBadge = 'badge-success';
                        if ($s['status_text'] === 'Cancelled / Void') {
                            $statusBadge = 'badge-danger';
                        } elseif ($s['status_text'] === 'Preparing') {
                            $statusBadge = 'badge-warning';
                        } elseif ($s['status_text'] === 'Settled / Paid') {
                            $statusBadge = 'badge-success';
                        } elseif ($s['status_text'] === 'Unbilled') {
                            $statusBadge = 'badge-info';
                        } elseif ($s['status'] == 0) {
                            $statusBadge = 'badge-danger';
                        } elseif ($s['status'] == 3) {
                            $statusBadge = 'badge-warning';
                        }
                    ?>
                    <div class="modal fade" id="modal_<?php echo $billId; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header" style="background:#f4f6f9;">
                                    <h5 class="modal-title">
                                        Bill Lifecycle: <span class="text-primary"><?php echo h($s['orderno']); ?></span>
                                        <?php if (!empty($s['is_split'])): ?>
                                            &nbsp;<span class="badge" style="background:#6f42c1; color:#fff;"><i class="fas fa-columns"></i> Split (<?php echo $s['split_count']; ?> Sub-Bills)</span>
                                        <?php endif; ?>
                                        &nbsp;<span class="badge <?php echo $statusBadge; ?>"><?php echo h($s['status_text']); ?></span>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!-- Bill Summary Row -->
                                    <div class="row mb-3" style="background:#f8f9fa; padding:10px; border-radius:5px;">
                                        <div class="col-md-2"><strong>Table:</strong> Table <?php echo h($s['table_number']); ?></div>
                                        <div class="col-md-2"><strong>Store:</strong> <?php echo h($s['store_name']); ?></div>
                                        <div class="col-md-2"><strong>Cashier:</strong> <?php echo h($s['cashier_name']); ?></div>
                                        <div class="col-md-2"><strong>Server / Waiter:</strong> <?php echo h($s['waiter_name'] ?? 'N/A'); ?></div>
                                        <div class="col-md-2"><strong>Manager:</strong> <?php echo h($s['manager_name'] ?? 'N/A'); ?></div>
                                        <div class="col-md-2"><strong>Amount:</strong> ₹<?php echo number_format($s['amount'], 2); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <?php if (!empty($s['customer_name']) || !empty($s['customer_phone'])): ?>
                                            <div class="col-md-6">
                                                <strong>Customer:</strong> 
                                                <?php 
                                                    $custDisplay = [];
                                                    if (!empty($s['customer_name'])) $custDisplay[] = h($s['customer_name']);
                                                    if (!empty($s['customer_phone'])) $custDisplay[] = '(' . h($s['customer_phone']) . ')';
                                                    if (!empty($s['customer_pax']) && $s['customer_pax'] > 1) $custDisplay[] = '[' . h($s['customer_pax']) . ' PAX]';
                                                    echo implode(' ', $custDisplay);
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($s['payment_modes'])): ?>
                                            <div class="col-md-6"><strong>Payment:</strong> <?php echo h($s['payment_modes']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tabs -->
                                    <ul class="nav nav-tabs" id="tab_<?php echo $billId; ?>" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="timeline-tab-<?php echo $billId; ?>" data-toggle="tab" href="#timeline-sec-<?php echo $billId; ?>" role="tab">Chronological Timeline</a>
                                        </li>
                                        <?php if (!empty($bill['split_bills'])): ?>
                                            <li class="nav-item">
                                                <a class="nav-link" id="split-bills-tab-<?php echo $billId; ?>" data-toggle="tab" href="#split-bills-sec-<?php echo $billId; ?>" role="tab" style="color:#6f42c1; font-weight:600;"><i class="fas fa-columns"></i> Split Bills (<?php echo count($bill['split_bills']); ?>)</a>
                                            </li>
                                        <?php endif; ?>
                                        <li class="nav-item">
                                            <a class="nav-link" id="active-items-tab-<?php echo $billId; ?>" data-toggle="tab" href="#active-items-sec-<?php echo $billId; ?>" role="tab">Active Items (<?php echo count($bill['active_items']); ?>)</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-danger" id="deleted-items-tab-<?php echo $billId; ?>" data-toggle="tab" href="#deleted-items-sec-<?php echo $billId; ?>" role="tab">Deleted Items (<?php echo count($bill['deleted_items']); ?>)</a>
                                        </li>
                                    </ul>

                                    <div class="tab-content pt-3" id="tabContent_<?php echo $billId; ?>">
                                        <!-- TIMELINE TAB -->
                                        <div class="tab-pane fade show active" id="timeline-sec-<?php echo $billId; ?>" role="tabpanel">
                                            <div class="lifecycle-timeline">
                                                <?php foreach ($bill['timeline'] as $evt): 
                                                    $badgeClass = 'event-' . ($evt['badge'] ?? 'secondary');
                                                ?>
                                                    <div class="timeline-event <?php echo $badgeClass; ?>">
                                                        <div class="timeline-time"><?php echo date('d-m-Y h:i:s A', strtotime($evt['timestamp'])); ?></div>
                                                        <div class="timeline-title">
                                                            <span class="badge badge-<?php echo h($evt['badge']); ?> badge-custom"><?php echo h($evt['label']); ?></span>
                                                        </div>
                                                        <div class="timeline-desc"><?php echo h($evt['description']); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($bill['split_bills'])): ?>
                                        <!-- SPLIT BILLS BREAKDOWN TAB -->
                                        <div class="tab-pane fade" id="split-bills-sec-<?php echo $billId; ?>" role="tabpanel">
                                            <div class="row">
                                                <?php foreach ($bill['split_bills'] as $sb): 
                                                    $sbStatusBadge = ($sb['status'] == 2) ? 'badge-success' : (($sb['status'] == 0) ? 'badge-danger' : 'badge-warning');
                                                    $sbStatusText = ($sb['status'] == 2) ? 'Settled' : (($sb['status'] == 0) ? 'Cancelled' : 'Unbilled / Active');
                                                ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card border-purple shadow-sm" style="border: 2px solid #6f42c1; border-radius: 8px;">
                                                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #f8f5fc; color: #492a80;">
                                                            <div>
                                                                <h6 class="mb-0 font-weight-bold">
                                                                    <i class="fas fa-file-invoice"></i> Sub-Bill <?php echo $sb['split_index']; ?>: <?php echo h($sb['orderno']); ?>
                                                                </h6>
                                                            </div>
                                                            <div>
                                                                <span class="badge <?php echo $sbStatusBadge; ?>"><?php echo $sbStatusText; ?></span>
                                                                <span class="badge badge-purple" style="background:#6f42c1; color:#fff; font-size:13px;">₹<?php echo number_format($sb['amount'], 2); ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="card-body p-2">
                                                            <div class="p-2 mb-2 bg-light rounded text-muted" style="font-size: 12px; line-height: 1.6;">
                                                                <span><strong>Price:</strong> ₹<?php echo number_format($sb['price'], 2); ?></span> | 
                                                                <span><strong>Discount:</strong> ₹<?php echo number_format($sb['discount'], 2); ?><?php echo ($sb['discount_type'] == 2 ? ' (Flat)' : ($sb['discount_type'] == 1 ? ' (' . $sb['discount_type_value'] . '%)' : '')); ?></span> | 
                                                                <span><strong>Service Charge:</strong> ₹<?php echo number_format($sb['servicecharge'], 2); ?></span> | 
                                                                <span><strong>Taxes:</strong> ₹<?php echo number_format($sb['total_tax'], 2); ?></span> | 
                                                                <span class="text-dark font-weight-bold"><strong>Total:</strong> ₹<?php echo number_format($sb['amount'], 2); ?></span>
                                                            </div>

                                                            <h6 class="font-weight-bold mb-1" style="font-size: 12px; color: #6f42c1;">Assigned Items (<?php echo count($sb['items']); ?>):</h6>
                                                            <?php if (empty($sb['items'])): ?>
                                                                <p class="text-muted small mb-0">No specific items assigned to this split bill.</p>
                                                            <?php else: ?>
                                                                <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                                                                    <thead class="thead-light">
                                                                        <tr>
                                                                            <th>Item</th>
                                                                            <th>KOT #</th>
                                                                            <th>Qty</th>
                                                                            <th>Rate</th>
                                                                            <th>Total</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($sb['items'] as $sItem): ?>
                                                                            <tr>
                                                                                <td><?php echo h($sItem['product_name']); ?></td>
                                                                                <td><?php echo h($sItem['kot_no'] ?: 'N/A'); ?></td>
                                                                                <td><?php echo h($sItem['quantity']); ?></td>
                                                                                <td>₹<?php echo number_format($sItem['price'], 2); ?></td>
                                                                                <td>₹<?php echo number_format($sItem['total_price'], 2); ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- ACTIVE ITEMS TAB -->
                                        <div class="tab-pane fade" id="active-items-sec-<?php echo $billId; ?>" role="tabpanel">
                                            <?php if (empty($bill['active_items'])): ?>
                                                <p class="text-muted">No active items found for this order.</p>
                                            <?php else: ?>
                                                <table class="table table-bordered table-sm">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Item Name</th>
                                                            <?php if (!empty($bill['split_bills'])): ?>
                                                                <th>Assigned Split Bill</th>
                                                            <?php endif; ?>
                                                            <th>KOT #</th>
                                                            <th>Quantity</th>
                                                            <th>Rate (₹)</th>
                                                            <th>Total (₹)</th>
                                                            <th>Ordered By</th>
                                                            <th>Timestamp</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($bill['active_items'] as $item): ?>
                                                            <tr>
                                                                <td><strong><?php echo h($item['product_name']); ?></strong></td>
                                                                <?php if (!empty($bill['split_bills'])): ?>
                                                                    <td>
                                                                        <?php if (!empty($item['split_sub_bill_no'])): ?>
                                                                            <span class="badge" style="background:#6f42c1; color:#fff;"><i class="fas fa-receipt"></i> Sub-Bill <?php echo $item['split_sub_bill_idx']; ?> (#<?php echo h($item['split_sub_bill_no']); ?>)</span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-light">Main Order</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                <?php endif; ?>
                                                                <td><?php echo h($item['kot_no'] ? $item['kot_no'] : 'N/A'); ?></td>
                                                                <td><?php echo h($item['quantity']); ?></td>
                                                                <td><?php echo number_format($item['price'], 2); ?></td>
                                                                <td><?php echo number_format($item['total_price'], 2); ?></td>
                                                                <td><?php echo h($item['user_name']); ?></td>
                                                                <td><?php echo date('d-m-Y h:i A', strtotime($item['created'])); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>

                                        <!-- DELETED ITEMS TAB -->
                                        <div class="tab-pane fade" id="deleted-items-sec-<?php echo $billId; ?>" role="tabpanel">
                                            <?php if (empty($bill['deleted_items'])): ?>
                                                <p class="text-muted">No items were deleted/removed from this order.</p>
                                            <?php else: ?>
                                                <table class="table table-bordered table-sm table-striped">
                                                    <thead class="thead-dark">
                                                        <tr>
                                                            <th>Removed Item</th>
                                                            <th>KOT #</th>
                                                            <th>Quantity</th>
                                                            <th>Rate (₹)</th>
                                                            <th>Total Value (₹)</th>
                                                            <th>Removed By</th>
                                                            <th>Timestamp</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($bill['deleted_items'] as $item): ?>
                                                            <tr class="table-danger">
                                                                <td><strong><?php echo h($item['product_name']); ?></strong></td>
                                                                <td><?php echo h($item['kot_no'] ? $item['kot_no'] : 'N/A'); ?></td>
                                                                <td><?php echo h($item['quantity']); ?></td>
                                                                <td><?php echo number_format($item['price'], 2); ?></td>
                                                                <td><?php echo number_format($item['total_price'], 2); ?></td>
                                                                <td><?php echo h($item['user_name']); ?></td>
                                                                <td><?php echo date('d-m-Y h:i A', strtotime($item['created'])); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#example')) {
        $('#example').DataTable().destroy();
    }
    $('#example').DataTable({
        pageLength: 10,
        order: [[0, 'asc']]
    });

    // View Lifecycle button opens the modal for the corresponding bill
    $(document).on('click', '.btn-view-lifecycle', function() {
        var billId = $(this).data('bill-id');
        $('#modal_' + billId).modal('show');
    });

    $('#exportdata').click(function() {
        var sdate = $('#startdate').val();
        var cashier = $('#cashier').val();
        var orderno = $('#orderno').val();
        window.open('<?php echo $this->base; ?>/report/bill_lifecycle_report_export?sdate='+encodeURIComponent(sdate)+'&cashier='+encodeURIComponent(cashier)+'&orderno='+encodeURIComponent(orderno), '_blank');
    });

    $('#btn-pdf').click(function() {
        var sdate = $('#startdate').val();
        var cashier = $('#cashier').val();
        var orderno = $('#orderno').val();
        window.open('<?php echo $this->base; ?>/report/bill_lifecycle_report_pdf?sdate='+encodeURIComponent(sdate)+'&cashier='+encodeURIComponent(cashier)+'&orderno='+encodeURIComponent(orderno), '_blank');
    });
});
</script>

