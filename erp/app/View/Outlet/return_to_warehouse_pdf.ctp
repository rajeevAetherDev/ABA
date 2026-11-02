<!DOCTYPE HTML>
<html>
<head>

<title>Return To Warehouse PDF</title>

<?php echo $this->Html->css(array(
'../assets/plugins/bootstrap/css/bootstrap.min'
)); ?>

<script>
window.print();
</script>

</head>

<body>

<?php

function getIndianCurrency($number) {

    $no = floor($number);

    $decimal = round($number - $no, 2) * 100;

    $digits_length = strlen($no);

    $i = 0;
    $str = array();

    $words = array(
        0 => '', 1 => 'One', 2 => 'Two',
        3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six',
        7 => 'Seven', 8 => 'Eight',
        9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve',
        13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen',
        17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty',
        50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty',
        90 => 'Ninety'
    );

    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');

    while ($i < $digits_length) {

        $divider = ($i == 2) ? 10 : 100;

        $number_part = floor($no % $divider);

        $no = floor($no / $divider);

        $i += ($divider == 10) ? 1 : 2;

        if ($number_part) {

            $plural = ((count($str) && $number_part > 9) ? '' : null);

            $hundred = (count($str) == 1 && $str[0]) ? ' and ' : null;

            $str[] = ($number_part < 21)

                ? $words[$number_part] . " " . $digits[count($str)] . $plural . " " . $hundred

                : $words[floor($number_part / 10) * 10]
                . " " . $words[$number_part % 10] . " "
                . $digits[count($str)] . $plural . " " . $hundred;

        } else {

            $str[] = null;

        }

    }

    $Rupees = implode('', array_reverse($str));

    $Paise = ($decimal)

        ? "And " . ($words[$decimal - $decimal % 10]) . " " . ($words[$decimal % 10]) . " Paise"

        : '';

    return ($Rupees ? $Rupees . ' Rupees ' : '') . $Paise . " Only";

}

?>

<div class="container-fluid">

    <div style="text-align:center;">
        <h2><?= $locationdetail['Location']['name']; ?></h2>

        <p><b><?= $locationdetail['Location']['print_line1']; ?></b></p>
        <p><b><?= $locationdetail['Location']['print_line2']; ?></b></p>
        <p><b><?= $locationdetail['Location']['print_line3']; ?></b></p>
    </div>

    <div style="text-align:center;">
        <h2>RETURN TO WAREHOUSE</h2>
        <hr>
    </div>

    <?php
        $statusArr = [
            0 => ['Pending', '#ffc107', '#000', '⏳'],
            1 => ['Approved', '#28a745', '#fff', '✔'],
            2 => ['Rejected', '#dc3545', '#fff', '✖']
        ];
    
        $admin_status = $transfer['Inventory_return']['admin_status'] ?? 0;
        $s = $statusArr[$admin_status] ?? ['Unknown', '#6c757d', '#fff', '?'];
    ?>
    
    <table style="width:100%;margin-bottom:20px;">
        <tr>
            <td><strong>Return ID:</strong> <?= $transfer['Inventory_return']['return_id']; ?></td>

            <td><strong>Created:</strong>
                <?= date('d-m-Y h:i A', strtotime($transfer['Inventory_return']['created'])); ?>
            </td>
        </tr>

        <tr>
            <td><strong>Outlet:</strong> <?= $transfer['Inventory_return']['location_name']; ?></td>

            <td><strong>Warehouse:</strong> <?= $transfer['Inventory_return']['warehouse_name']; ?></td>
        </tr>

        <tr>
            <td><strong>Remarks:</strong> <?= $transfer['Inventory_return']['remarks']; ?></td>
            <td>
                <strong>Status:</strong>
                <?php
                    $status = $transfer['Inventory_return']['admin_status'];
                
                    if ($status == 0) {
                        echo '<span style="background-color:#ffc107;color:#000;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:600;">⏳ Pending</span>';
                    } elseif ($status == 1) {
                        echo '<span style="background-color:#28a745;color:#fff;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:600;">✔ Approved</span>';
                    } elseif ($status == 2) {
                        echo '<span style="background-color:#dc3545;color:#fff;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:600;">✖ Rejected</span>';
                    }
                ?>
            </td>
        </tr>
        <tr>
            <td><strong>Warehouse Remarks:</strong> <?= $transfer['Inventory_return']['admin_remarks']; ?></td>
            <td>
                <strong>Warehouse Updated Date:</strong>
                <?= $transfer['Inventory_return']['admin_updated_date']
                    ? date('d-m-Y h:i A', strtotime($transfer['Inventory_return']['admin_updated_date']))
                    : '-' ?>
            </td>
        </tr>
    </table>


    <table style="width:100%;border-collapse:collapse;" border="1">

        <thead>
            <tr>
                <th>Sr</th>
                <th>SKU Name</th>
                <th>Brand</th>
                <th>Unit</th>
                <th>Price</th>
                <th>Tax %</th>
                <th>Tax Value</th>
                <th>Qty</th>
                <th>Total</th>
                <th><?= $s[3]; ?> <?= $s[0]; ?> Qty</th>
                <th><?= $s[3]; ?> <?= $s[0]; ?> Tax</th>
                <th><?= $s[3]; ?> <?= $s[0]; ?> Amount</th>
            </tr>
        </thead>

        <tbody>

            <?php $i = 1; ?>

            <?php foreach($items as $item): ?>

            <tr>
                <td><?= $i++; ?></td>
                <td><?= $item['Inventory_return_items']['sku_name']; ?></td>
                <td><?= $item['Inventory_return_items']['sku_brand']; ?></td>
                <td><?= $item['Inventory_return_items']['sku_unit_name']; ?></td>
                <td><?= number_format($item['Inventory_return_items']['sku_price'],2); ?></td>
                <td><?= $item['Inventory_return_items']['tax_percent']; ?>%</td>
                <td><?= number_format($item['Inventory_return_items']['tax_value'],2); ?></td>
                <td><?= $item['Inventory_return_items']['sku_quantity']; ?></td>
                <td><?= number_format($item['Inventory_return_items']['amount'],2); ?></td>
                <td><?= $item['Inventory_return_items']['admin_updated_quantity']; ?></td>
                <td><?= number_format($item['Inventory_return_items']['admin_updated_tax'],2); ?></td>
                <td><?= number_format($item['Inventory_return_items']['admin_updated_amount'],2); ?></td>
            
            </tr>

            <?php endforeach; ?>

        </tbody>

    </table>


    <br><br>

    <div style="text-align:right;">
        <p>
            <strong>Grand Total:</strong>
            <?= number_format($transfer['Inventory_return']['total_amount'],2); ?>
        </p>
        <?php if($admin_status != 0): ?>
        <p>
            <strong><?= $s[3]; ?> <?= $s[0]; ?> Warehouse Amount:</strong>
            <?= number_format($transfer['Inventory_return']['admin_updated_amount'],2); ?>
        </p>
        <?php endif; ?>
    </div>


    <div style="margin-top:20px;">
        <p>
            <strong>Amount In Words:</strong>
            <?= getIndianCurrency($transfer['Inventory_return']['total_amount']); ?>
        </p>
        <?php if($admin_status != 0): ?>
        <p>
            <strong><?= $s[3]; ?> <?= $s[0]; ?> Warehouse Amount In Words:</strong>
            <?= getIndianCurrency($transfer['Inventory_return']['admin_updated_amount']); ?>
        </p>
        <?php endif; ?>
    </div>


    <hr>

    <div style="text-align:center;">
        <p>This is Computer Generated Document</p>
        <p>Print Date: <?= date('d-m-Y'); ?></p>
    </div>

</div>

</body>
</html>