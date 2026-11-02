<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Bill & KOT Delete Report</title>

    <?php echo $this->Html->css([
        '../assets/plugins/bootstrap/css/bootstrap.min',
        '../assets/fonts/font-awesome/css/font-awesome',
        '../assets/css/style'
    ]); ?>

    <style>
        body { font-size: 12px; }
        .table th, .table td { padding: 4px; vertical-align: middle; }
        .table thead th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .bill-header {
            background-color: #d0d0d0;
            font-weight: bold;
            padding: 6px;
        }
        .bill-total {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-data { color: red; text-align: center; font-size: 14px; margin-top: 20px; }
    </style>

    <script>
        window.print();
    </script>
</head>
<body lang="en-US" dir="ltr">
    <div class="container-fluid">
        <div class="row">
            <h3 class="text-center">
                Bill & KOT Delete Report From <?php echo $startdate; ?> To <?php echo $enddate; ?>  &nbsp;&nbsp; | &nbsp;&nbsp;
            </h3>
            <h5 class="text-center">
                Store: <?php echo !empty($store_name) ? h($store_name) : 'All Stores'; ?> &nbsp;&nbsp; | &nbsp;&nbsp;
            </h5>
            <h5 class="text-center">
                Generated On: <?php echo date('Y-m-d H:i:s A'); ?>
            </h5>

            <?php if (!empty($datas)) : ?>
                <table class="table table-bordered table-condensed" width="100%">
                    <thead>
                        <tr>
                            <th>KOT Number</th>
                            <th>Item</th>
                            <th>Order Time</th>
                            <th>Delete Time</th>
                            <th class="text-right">Rate</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Total</th>
                            <th>Tab Name</th>
                            <th>Table No</th>
                            <th>Comment</th>
                            <th>Username</th>
                            <th>Deletion User</th>
                            <th>Settle User</th>
                            <th>Server</th>
                            <th>Customer Name</th>
                            <th>Customer Mobile</th>
                            <th>Customer Address</th>
                            <th>Type</th>
                            <th>Bill Settlement Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
						
                        $groupedData = [];

                        foreach ($datas as $r) {
                            $d = $r[0];
                            $t = isset($r['t']) ? $r['t'] : [];
                            $h = isset($r['h']) ? $r['h'] : [];

                            $billNo = $t['bill_no'] ?? $d['bill_no'];
                            $billCloseTime = $d['bill_close_time'] ?? '';
                            $key = $billNo . '_' . $billCloseTime;

                            if (!isset($groupedData[$key])) {
                                $groupedData[$key] = [
                                    'bill_no' => $billNo,
                                    'bill_close_time' => $billCloseTime,
                                    'items' => []
                                ];
                            }

                            $groupedData[$key]['items'][] = [
                                'd' => $d,
                                't' => $t,
                                'h' => $h
                            ];
                        }

                        uasort($groupedData, function ($a, $b) {
                            $billCompare = strcmp($a['bill_no'], $b['bill_no']);
                            if ($billCompare !== 0) {
                                return $billCompare;
                            }
                            return strcmp($a['bill_close_time'], $b['bill_close_time']);
                        });

                        foreach ($groupedData as $group):
                            $billNo = $group['bill_no'];
                            $billCloseTime = $group['bill_close_time'];
                            $items = $group['items'];
                            $billTotal = 0;
                        ?>
                            <tr class="bill-header">
                                <td colspan="19">
                                    Bill Number: <?php echo h($billNo); ?>
                                    &nbsp;&nbsp; | &nbsp;&nbsp;
                                    Bill Close Time:
                                    <?php echo !empty($billCloseTime) ? date('h:i:s A', strtotime($billCloseTime)) : ''; ?>
                                </td>
                            </tr>

                            <?php foreach ($items as $item): 
                                $d = $item['d'];
                                $t = $item['t'];
                                $h = $item['h'];

                                $kotNo = $h['kot_no'] ?? $d['kot_no'] ?? '';
                                $itemName = $h['item_name'] ?? $d['item_name'] ?? '';
                                $orderTime = $d['order_time'] ?? '';
                                $deleteTime = $h['delete_time'] ?? $d['delete_time'] ?? '';
                                $rate = $h['rate'] ?? $d['rate'] ?? 0;
                                $qty = $h['qty'] ?? $d['qty'] ?? 0;
                                $total = $d['total'] ?? ($rate * $qty);
                                $tabName = $d['tab_name'] ?? '';
                                $tableNo = $h['table_no'] ?? $d['table_no'] ?? '';
                                $comment = $h['comment'] ?? $d['comment'] ?? '';
                                $username = $d['username'] ?? '';
                                $deleteUser = $d['delete_user'] ?? '';
                                $settleUser = $d['settle_user'] ?? '';
                                $server = $d['server'] ?? '';
                                $customerName = $d['customer_name'] ?? 'NA';
                                $customerMobile = $d['customer_mobile'] ?? 'NA';
                                $customerAddress = $d['customer_address'] ?? 'NA';
                                $deleteType = $d['delete_type'] ?? '';
                                $billSettlementStatus = $d['bill_settlement_status'] ?? '';

                                $billTotal += (float)$total;
                            ?>
                                <tr>
                                    <td><?php echo h($kotNo); ?></td>
                                    <td><?php echo h($itemName); ?></td>
                                    <td><?php echo !empty($orderTime) ? date('d-m-Y h:i:s A', strtotime($orderTime)) : ''; ?></td>
                                    <td><?php echo !empty($deleteTime) ? date('d-m-Y h:i:s A', strtotime($deleteTime)) : ''; ?></td>
                                    <td class="text-right"><?php echo number_format($rate, 2); ?></td>
                                    <td class="text-right"><?php echo h($qty); ?></td>
                                    <td class="text-right"><?php echo number_format($total, 2); ?></td>
                                    <td><?php echo h($tabName); ?></td>
                                    <td><?php echo h($tableNo); ?></td>
                                    <td><?php echo h($comment); ?></td>
                                    <td><?php echo h($username); ?></td>
                                    <td><?php echo h($deleteUser); ?></td>
                                    <td><?php echo h($settleUser); ?></td>
                                    <td><?php echo h($server); ?></td>
                                    <td><?php echo h($customerName); ?></td>
                                    <td><?php echo h($customerMobile); ?></td>
                                    <td><?php echo h($customerAddress); ?></td>
                                    <td><?php echo h($deleteType); ?></td>
                                    <td><?php echo h($billSettlementStatus); ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr class="bill-total">
                                <td colspan="6" class="text-right">Bill Total</td>
                                <td class="text-right"><?php echo number_format($billTotal, 2); ?></td>
                                <td colspan="12"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    No delete data found for the selected filters.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
