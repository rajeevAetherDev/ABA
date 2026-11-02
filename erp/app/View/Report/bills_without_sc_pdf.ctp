<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Service Charge Removal Report</title>

    <?php echo $this->Html->css([
        '../assets/plugins/bootstrap/css/bootstrap.min',
        '../assets/plugins/bootstrap/css/bootstrap-theme.min',
        '../assets/css/style',
    ]); ?>

    <style>
        body  { font-size: 12px; font-family: Arial, sans-serif; }
        table { font-size: 11px; width: 100%; }
        th    { background-color: #f5f5f5; }

        .report-header  { text-align: center; margin-bottom: 10px; }
        .summary-wrap   { text-align: center; margin: 8px 0 12px 0; }
        .summary-box    {
            display: inline-block;
            border: 1px solid #ccc;
            padding: 5px 16px;
            margin: 3px 6px;
            background: #f9f9f9;
            border-radius: 3px;
        }
        .summary-box small { display: block; font-size: 10px; color: #666; }
        .summary-box strong { font-size: 14px; }

        .grand-total td {
            font-weight: bold;
            background: #e0e0e0 !important;
        }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>

    <script>
        window.onload = function() { window.print(); }
    </script>
</head>
<body>

<div class="container-fluid">

    <!-- Report Header -->
    <div class="report-header">
        <h4>Service Charge Removal Report</h4>
        <h6>
            <?php echo h($startdate); ?> &nbsp;to&nbsp; <?php echo h($enddate); ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Store: <strong><?php echo h($store_name); ?></strong>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Generated: <?php echo date('Y-m-d H:i:s'); ?>
        </h6>
    </div>

    <!-- Summary Cards -->
    <div class="summary-wrap">
        <span class="summary-box">
            <small>Total Bills</small>
            <strong><?php echo (int)$totalBills; ?></strong>
        </span>
        <span class="summary-box">
            <small>Total SC Removed</small>
            <strong><?php echo number_format((float)$totalSC, 2); ?></strong>
        </span>
    </div>

    <?php if (!empty($datas)): ?>

    <table class="table table-bordered table-condensed table-hover">
        <thead>
            <tr style="background-color:#f5f5f5;">
                <th>#</th>
                <th>Order Number</th>
                <th>Location</th>
                <th>SC Removed</th>
                <th>Payment Mode</th>
                <th>SC Removed At</th>
                <th>Bill Print Time</th>
                <th>Bill Settle Time</th>
                <th>Removed By</th>
                <th>Cashier</th>
                <th>Waiter</th>
                <th>Customer Name</th>
                <th>Customer Mobile</th>
                <th>Comment</th>
                <th>Guests</th>
            </tr>
        </thead>
        <tbody>

        <?php
            $i = 1;
            foreach ($datas as $r):
            
                // Normalize like view
                $row = isset($r[0]) ? $r[0] : $r;
            
                $scAmount = (float)($row['sc_amount'] ?? 0);
                $scRate   = (float)($row['store_sc_rate'] ?? 0);
            ?>
            <tr>
                <td class="text-center"><?= $i++; ?></td>
                
                <td><?= h($r['t']['order_number'] ?? '-') ?></td>
                <td><?= h($r['l']['location_name'] ?? '-') ?></td>
                
                <!-- ✅ SAME FORMAT AS VIEW -->
                <td class="text-right">
                    <strong><?= number_format($scAmount, 2) ?></strong>
                    <small>(<?= number_format($scRate, 1) ?>%)</small>
                </td>
            
                <td><?= h($r['pay']['payment_modes'] ?? '-') ?></td>
                
                <td>
                    <?= !empty($row['sc_removed_time'])
                        ? date('d-m-Y h:i:s A', strtotime($row['sc_removed_time']))
                        : '-' ?>
                </td>
                
                <td>
                    <?= !empty($r['t']['bill_print_time'])
                        ? date('d-m-Y h:i:s A', strtotime($r['t']['bill_print_time']))
                        : '-' ?>
                </td>
                
                <td>
                    <?= !empty($r['pay']['bill_settle_time'])
                        ? date('d-m-Y h:i:s A', strtotime($r['pay']['bill_settle_time']))
                        : '-' ?>
                </td>
            
                <td><?= h($row['removed_by_user'] ?? '-') ?></td>
                <td><?= h($row['cashier_username'] ?? '-') ?></td>
                <td><?= h($row['waiter_username'] ?? '-') ?></td>
                <td><?= h($row['customer_name'] ?? '-') ?></td>
                <td><?= h($row['customer_mobile'] ?? '-') ?></td>
                <td><?= h($row['edit_bill_comment'] ?? '-') ?></td>
                <td class="text-center"><?= h($r['t']['guest_count'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>

        <!-- Grand Total row -->
        <tr class="grand-total">
            <td colspan="3" class="text-right">Grand Total</td>
            <td class="text-right"><?php echo number_format((float)$totalSC, 2); ?></td>
            <td colspan="1">Total Bills: <?php echo (int)$totalBills; ?></td>
            <td></td>
        </tr>

        </tbody>
    </table>

    <?php else: ?>
        <div style="text-align:center; margin-top:40px; color:#c00;">
            <h5>No removed service charges found for the selected filters.</h5>
        </div>
    <?php endif; ?>

</div><!-- /container-fluid -->

</body>
</html>