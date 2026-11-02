<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Employeewise Customer Info Report</title>

    <?php echo $this->Html->css([
        '../assets/plugins/pace/pace-theme-flash',
        '../assets/plugins/bootstrap/css/bootstrap.min',
        '../assets/plugins/bootstrap/css/bootstrap-theme.min',
        '../assets/fonts/font-awesome/css/font-awesome',
        '../assets/css/animate.min',
        '../assets/plugins/perfect-scrollbar/perfect-scrollbar',
        '../assets/css/style',
        '../assets/css/responsive'
    ]); ?>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
            background: #fff;
            padding: 15px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3b5998;
            padding-bottom: 12px;
        }
        .report-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0 0 6px 0;
        }
        .meta-line {
            font-size: 13px;
            color: #4b5563;
            margin-bottom: 4px;
        }
        .meta-line strong {
            color: #111827;
        }
        .kpi-box {
            display: inline-block;
            margin: 5px 12px;
            padding: 8px 16px;
            background: #f3f4f6;
            border-radius: 6px;
            font-size: 13px;
        }
        .kpi-box strong {
            font-size: 15px;
            color: #1e3a8a;
        }
        .table-pdf {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 15px;
        }
        .table-pdf th {
            background-color: #3b5998 !important;
            color: #ffffff !important;
            font-weight: 700;
            padding: 8px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .table-pdf td {
            padding: 6px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .table-pdf tr:nth-child(even) td {
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: landscape;
                margin: 10mm;
            }
        }
    </style>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Employeewise Customer Info Report</h1>
        <div class="meta-line">
            <span><strong>Store:</strong> <?php echo h($storeName); ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
            <span><strong>Cashier:</strong> <?php echo h($selectedCashierName ?? 'All Cashiers'); ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
            <span><strong>Date Range:</strong> <?php echo h($daterangeValue); ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
            <span><strong>Generated:</strong> <?php echo date('d M Y, h:i A'); ?></span>
        </div>
        <div style="margin-top: 8px;">
            <div class="kpi-box">Total Orders: <strong><?php echo number_format($summary['total_orders'] ?? 0); ?></strong></div>
            <div class="kpi-box">With Customer Info: <strong><?php echo number_format($summary['with_customer_info'] ?? 0); ?></strong></div>
            <div class="kpi-box">Unique Customers: <strong><?php echo number_format($summary['unique_customers'] ?? 0); ?></strong></div>
            <div class="kpi-box">Active Cashiers: <strong><?php echo number_format($summary['total_cashiers'] ?? 0); ?></strong></div>
        </div>
    </div>

    <table class="table-pdf">
        <thead>
            <tr>
                <th style="width:30px; text-align:center;">#</th>
                <th style="width:130px;">Store Name</th>
                <th style="width:130px;">Order Number</th>
                <th style="width:120px;">Order Start By</th>
                <th style="width:130px;">Customer Name</th>
                <th style="width:100px;">Phone</th>
                <th>Address</th>
                <th style="width:120px;">Bill Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($records)): ?>
                <?php $sr = 1; foreach ($records as $r): ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $sr++; ?></td>
                        <td style="font-weight:600;"><?php echo h($r['store_name']); ?></td>
                        <td style="font-weight:600;"><?php echo h($r['Ordernumber']); ?></td>
                        <td style="font-weight:600;"><?php echo h($r['Orderstartby']); ?></td>
                        <td><?php echo !empty(trim($r['name'])) ? h($r['name']) : '-'; ?></td>
                        <td><?php echo !empty(trim($r['phone'])) ? h($r['phone']) : '-'; ?></td>
                        <td><?php echo !empty(trim($r['address'])) ? h($r['address']) : '-'; ?></td>
                        <td><?php echo !empty($r['Order date']) ? date('d-m-Y H:i', strtotime($r['Order date'])) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:20px; color:#6b7280;">
                        No customer information records found for the selected criteria.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>