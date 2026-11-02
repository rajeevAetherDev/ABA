<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Bill Lifecycle Report</title>

    <?php echo $this->Html->css([
        '../assets/plugins/bootstrap/css/bootstrap.min',
        '../assets/fonts/font-awesome/css/font-awesome',
        '../assets/css/style'
    ]); ?>

    <style>
        body { font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif; color: #333; background: #fff; }
        .table th, .table td { padding: 5px 6px; vertical-align: middle; }
        .table thead th {
            background-color: #f4f6f9;
            font-weight: 700;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        .stat-card {
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 15px;
            color: #fff;
            text-align: center;
        }
        .stat-card h3 { margin: 0; font-size: 18px; font-weight: bold; }
        .stat-card p { margin: 0; font-size: 11px; opacity: 0.9; }
        .bill-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 25px;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .bill-header-pdf {
            background-color: #f8fafc;
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .timeline-table td {
            font-size: 10px;
        }
        .badge-status {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success-custom { background: #28a745; color: #fff; }
        .badge-warning-custom { background: #ffc107; color: #212529; }
        .badge-danger-custom { background: #dc3545; color: #fff; }
        .badge-info-custom { background: #17a2b8; color: #fff; }
        .badge-purple-custom { background: #6f42c1; color: #fff; }
        .badge-secondary-custom { background: #6c757d; color: #fff; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-weight-bold { font-weight: bold; }
        @media print {
            .no-print { display: none; }
            body { font-size: 10px; }
            .bill-card { page-break-inside: avoid; }
        }
    </style>

    <script>
        window.print();
    </script>
</head>
<body lang="en-US" dir="ltr">
    <div class="container-fluid" style="padding: 15px;">
        <!-- Header -->
        <div class="row mb-3">
            <div class="col-xs-12 text-center">
                <h2 style="margin-top:0; margin-bottom:5px; font-weight:bold; color:#1a202c;">Bill Lifecycle Report</h2>
                <h4 style="margin-top:0; color:#4a5568;">Date Range: <?php echo h($startdate); ?></h4>
                <p style="color:#718096; margin-bottom:15px;"><small>Generated On: <?php echo date('d-m-Y h:i A'); ?></small></p>
            </div>
        </div>

        <?php 
        $totalBills = count($bills);
        $totalValue = 0;
        $totalActiveItems = 0;
        $totalDeletedItems = 0;
        foreach ($bills as $b) {
            $totalValue += (float)($b['summary']['amount'] ?? 0);
            $totalActiveItems += count($b['active_items'] ?? []);
            $totalDeletedItems += count($b['deleted_items'] ?? []);
        }
        ?>

        <!-- Top Metric Summary Cards -->
        <table width="100%" class="mb-4" style="margin-bottom: 20px;">
            <tr>
                <td width="24%" style="padding-right: 1%;">
                    <div class="stat-card" style="background: #ffc107; color: #212529;">
                        <h3><?php echo $totalBills; ?></h3>
                        <p>Total Bills</p>
                    </div>
                </td>
                <td width="24%" style="padding-right: 1%;">
                    <div class="stat-card" style="background: #20c997;">
                        <h3>₹<?php echo number_format($totalValue, 2); ?></h3>
                        <p>Total Value</p>
                    </div>
                </td>
                <td width="24%" style="padding-right: 1%;">
                    <div class="stat-card" style="background: #17a2b8;">
                        <h3><?php echo $totalActiveItems; ?></h3>
                        <p>Active Items</p>
                    </div>
                </td>
                <td width="24%">
                    <div class="stat-card" style="background: #e53e3e;">
                        <h3><?php echo $totalDeletedItems; ?></h3>
                        <p>Deleted / Removed Items</p>
                    </div>
                </td>
            </tr>
        </table>

        <?php if (empty($bills)): ?>
            <div class="alert alert-warning text-center">No bills found for the selected criteria.</div>
        <?php else: ?>
            <!-- Master Overview Table (Matching View Table) -->
            <h4 style="font-weight:bold; margin-bottom:10px; color:#2d3748;">Bills Summary Overview</h4>
            <table class="table table-bordered mb-4" style="margin-bottom: 30px;">
                <thead>
                    <tr>
                        <th width="4%">#</th>
                        <th width="15%">Bill / Order No</th>
                        <th width="12%">Reference ID</th>
                        <th width="8%">Table</th>
                        <th width="16%">Store</th>
                        <th width="9%">Cashier</th>
                        <th width="12%">Date/Time</th>
                        <th width="9%" class="text-right">Amount (₹)</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="5%" class="text-center">Events</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bills as $index => $bill): 
                        $s = $bill['summary'];
                        $badgeClass = 'badge-success-custom';
                        if ($s['status_text'] === 'Cancelled / Void') $badgeClass = 'badge-danger-custom';
                        elseif ($s['status_text'] === 'Preparing') $badgeClass = 'badge-warning-custom';
                        elseif ($s['status_text'] === 'Unbilled') $badgeClass = 'badge-info-custom';
                    ?>
                        <tr>
                            <td><?php echo ($index + 1); ?></td>
                            <td>
                                <strong><?php echo h($s['orderno']); ?></strong>
                                <?php if (!empty($s['is_split'])): ?>
                                    <br><span class="badge-status badge-purple-custom" style="font-size:9px;">Split (<?php echo $s['split_count']; ?> Bills)</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo h($s['reference_id']); ?></small></td>
                            <td>Table <?php echo h($s['table_number']); ?></td>
                            <td><?php echo h($s['store_name']); ?></td>
                            <td><?php echo h($s['cashier_name']); ?></td>
                            <td><?php echo date('d-m-Y h:i A', strtotime($s['created'])); ?></td>
                            <td class="text-right"><strong>₹<?php echo number_format($s['amount'], 2); ?></strong></td>
                            <td class="text-center"><span class="badge-status <?php echo $badgeClass; ?>"><?php echo h($s['status_text']); ?></span></td>
                            <td class="text-center"><span class="badge-status badge-secondary-custom"><?php echo count($bill['timeline']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="page-break-before: always;"></div>

            <!-- Detailed Per-Bill Section -->
            <h4 style="font-weight:bold; margin-bottom:15px; color:#2d3748;">Detailed Bill Lifecycle Audit & Timelines</h4>
            <?php foreach ($bills as $index => $bill): 
                $s = $bill['summary'];
                $statusBadge = 'badge-success-custom';
                if ($s['status_text'] === 'Cancelled / Void') $statusBadge = 'badge-danger-custom';
                elseif ($s['status_text'] === 'Preparing') $statusBadge = 'badge-warning-custom';
                elseif ($s['status_text'] === 'Unbilled') $statusBadge = 'badge-info-custom';
            ?>
                <div class="bill-card">
                    <div class="bill-header-pdf">
                        <table width="100%">
                            <tr>
                                <td width="50%">
                                    <span style="font-size:13px; font-weight:bold; color:#1a202c;">
                                        #<?php echo ($index + 1); ?>. Bill: <?php echo h($s['orderno']); ?>
                                    </span>
                                    <?php if (!empty($s['is_split'])): ?>
                                        &nbsp;<span class="badge-status badge-purple-custom">Split (<?php echo $s['split_count']; ?> Sub-Bills)</span>
                                    <?php endif; ?>
                                    &nbsp;<span class="badge-status <?php echo $statusBadge; ?>"><?php echo h($s['status_text']); ?></span>
                                    <br><small style="color:#718096;">Ref ID: <?php echo h($s['reference_id']); ?></small>
                                </td>
                                <td width="50%" class="text-right">
                                    <span style="font-size:14px; font-weight:bold; color:#2d3748;">Total Amount: ₹<?php echo number_format($s['amount'], 2); ?></span>
                                    <br><small style="color:#718096;">Created: <?php echo date('d-m-Y h:i A', strtotime($s['created'])); ?></small>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div style="padding: 10px 12px;">
                        <!-- Summary Metadata Grid -->
                        <table width="100%" style="background:#f8fafc; border:1px solid #edf2f7; border-radius:4px; padding:6px; margin-bottom:12px;">
                            <tr>
                                <td width="25%"><strong>Table:</strong> Table <?php echo h($s['table_number']); ?></td>
                                <td width="25%"><strong>Store:</strong> <?php echo h($s['store_name']); ?></td>
                                <td width="25%"><strong>Cashier:</strong> <?php echo h($s['cashier_name']); ?></td>
                                <td width="25%"><strong>Server / Waiter:</strong> <?php echo h($s['waiter_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Manager:</strong> <?php echo h($s['manager_name'] ?? 'N/A'); ?></td>
                                <td><strong>Customer:</strong> <?php echo !empty($s['customer_name']) ? h($s['customer_name']) . (!empty($s['customer_phone']) ? ' (' . h($s['customer_phone']) . ')' : '') : 'N/A'; ?></td>
                                <td colspan="2"><strong>Payment Modes:</strong> <?php echo h($s['payment_modes'] ?? 'Pending Settlement'); ?></td>
                            </tr>
                        </table>

                        <!-- Chronological Event Timeline -->
                        <h5 style="font-weight:bold; margin-bottom:6px; font-size:11px; color:#4a5568;"><i class="fa fa-history"></i> Chronological Timeline Events (<?php echo count($bill['timeline']); ?>)</h5>
                        <table class="table table-bordered timeline-table" style="margin-bottom:12px;">
                            <thead>
                                <tr>
                                    <th width="20%">Timestamp</th>
                                    <th width="25%">Event Type</th>
                                    <th width="55%">Details & Audit Log</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bill['timeline'] as $evt): 
                                    $evtBadge = 'badge-secondary-custom';
                                    if ($evt['badge'] === 'success') $evtBadge = 'badge-success-custom';
                                    elseif ($evt['badge'] === 'warning') $evtBadge = 'badge-warning-custom';
                                    elseif ($evt['badge'] === 'danger') $evtBadge = 'badge-danger-custom';
                                    elseif ($evt['badge'] === 'info') $evtBadge = 'badge-info-custom';
                                    elseif ($evt['badge'] === 'purple') $evtBadge = 'badge-purple-custom';
                                    elseif ($evt['badge'] === 'primary') $evtBadge = 'badge-info-custom';
                                ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y h:i:s A', strtotime($evt['timestamp'])); ?></td>
                                        <td><span class="badge-status <?php echo $evtBadge; ?>"><?php echo h($evt['label']); ?></span></td>
                                        <td><?php echo h($evt['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Split Bills Breakdown (if split) -->
                        <?php if (!empty($bill['split_bills'])): ?>
                            <h5 style="font-weight:bold; color:#6f42c1; margin-bottom:6px; font-size:11px;"><i class="fa fa-columns"></i> Split Sub-Bills & Items Breakdown (<?php echo count($bill['split_bills']); ?>)</h5>
                            <?php foreach ($bill['split_bills'] as $sb): ?>
                                <div style="border: 1px solid #d6bcfa; border-radius: 4px; padding: 6px 8px; margin-bottom: 10px; background: #faf5ff;">
                                    <table width="100%" style="font-size: 10px; margin-bottom: 5px;">
                                        <tr>
                                            <td width="35%"><strong>Part <?php echo $sb['split_index']; ?> (Sub-Bill #<?php echo h($sb['orderno']); ?>)</strong></td>
                                            <td width="18%">Price: ₹<?php echo number_format($sb['price'], 2); ?></td>
                                            <td width="15%">Tax: ₹<?php echo number_format($sb['total_tax'], 2); ?></td>
                                            <td width="15%">Disc: ₹<?php echo number_format($sb['discount'], 2); ?></td>
                                            <td width="17%" class="text-right"><strong>Amount: ₹<?php echo number_format($sb['amount'], 2); ?></strong></td>
                                        </tr>
                                    </table>
                                    <?php if (!empty($sb['items'])): ?>
                                        <table class="table table-bordered timeline-table" style="margin-bottom: 0; background: #fff;">
                                            <thead>
                                                <tr style="background: #f3e8ff;">
                                                    <th width="45%">Assigned Item Name</th>
                                                    <th width="15%">KOT #</th>
                                                    <th width="10%">Qty</th>
                                                    <th width="15%">Rate (₹)</th>
                                                    <th width="15%">Total (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sb['items'] as $sItem): ?>
                                                    <tr>
                                                        <td><strong><?php echo h($sItem['product_name']); ?></strong></td>
                                                        <td><?php echo h($sItem['kot_no'] ? $sItem['kot_no'] : 'N/A'); ?></td>
                                                        <td><?php echo h($sItem['quantity']); ?></td>
                                                        <td>₹<?php echo number_format($sItem['price'], 2); ?></td>
                                                        <td><strong>₹<?php echo number_format($sItem['total_price'], 2); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p style="font-size: 9px; color: #718096; margin: 0;">No specific items recorded.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Active Items -->
                        <?php if (!empty($bill['active_items'])): ?>
                            <h5 style="font-weight:bold; color:#28a745; margin-bottom:6px; font-size:11px;"><i class="fa fa-check-circle"></i> Active / Billed Items (<?php echo count($bill['active_items']); ?>)</h5>
                            <table class="table table-bordered timeline-table" style="margin-bottom:12px;">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>KOT #</th>
                                        <th>Qty</th>
                                        <th>Rate (₹)</th>
                                        <th>Total (₹)</th>
                                        <th>Punched By</th>
                                        <th>Punched Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bill['active_items'] as $aItem): ?>
                                        <tr>
                                            <td><strong><?php echo h($aItem['product_name']); ?></strong><?php echo !empty($aItem['split_sub_bill_no']) ? ' <span style="color:#6f42c1;">[Sub-Bill #' . h($aItem['split_sub_bill_no']) . ']</span>' : ''; ?></td>
                                            <td><?php echo h($aItem['kot_no'] ? $aItem['kot_no'] : 'N/A'); ?></td>
                                            <td><?php echo h($aItem['quantity']); ?></td>
                                            <td>₹<?php echo number_format($aItem['price'], 2); ?></td>
                                            <td><strong>₹<?php echo number_format($aItem['total_price'], 2); ?></strong></td>
                                            <td><?php echo h($aItem['user_name']); ?></td>
                                            <td><?php echo date('d-m-Y h:i A', strtotime($aItem['created'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <!-- Deleted / Removed Items -->
                        <?php if (!empty($bill['deleted_items'])): ?>
                            <h5 style="font-weight:bold; color:#d9534f; margin-bottom:6px; font-size:11px;"><i class="fa fa-trash"></i> Deleted / Removed Items (<?php echo count($bill['deleted_items']); ?>)</h5>
                            <table class="table table-bordered timeline-table" style="background:#fff5f5;">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>KOT #</th>
                                        <th>Qty</th>
                                        <th>Rate (₹)</th>
                                        <th>Total (₹)</th>
                                        <th>Removed By</th>
                                        <th>Punched Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bill['deleted_items'] as $dItem): ?>
                                        <tr style="color:#c53030;">
                                            <td><strong><?php echo h($dItem['product_name']); ?></strong></td>
                                            <td><?php echo h($dItem['kot_no'] ? $dItem['kot_no'] : 'N/A'); ?></td>
                                            <td><?php echo h($dItem['quantity']); ?></td>
                                            <td>₹<?php echo number_format($dItem['price'], 2); ?></td>
                                            <td>₹<?php echo number_format($dItem['total_price'], 2); ?></td>
                                            <td><?php echo h($dItem['user_name']); ?></td>
                                            <td><?php echo date('d-m-Y h:i A', strtotime($dItem['created'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
