<!DOCTYPE HTML>
<html>
<head>

<title>Stock Transfer PDF</title>

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

    return ($Rupees ? $Rupees . 'Rupees ' : '') . $Paise . " Only";
}

?>

<div class="container-fluid">
    <div style="text-align:center;padding:0px !important;margin:0px !important">
        <h2 style="padding:0px !important;margin:5px !important"><?php print_r($locationdetail['Location']['name']);?></h2>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line1']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line2']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line3']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line4']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line5']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line6']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line7']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line9']);?></b></p>
        <p style="padding:0px !important;margin:4px !important"><b><?php print_r($locationdetail['Location']['print_line10']);?></b></p>
        
    </div>
    
    <div style="text-align:center;">
        <h2>STOCK TRANSFER</h2>
        <hr>
    </div>


    <table style="width:100%;margin-bottom:20px;">
        <tr>
            <td><strong>Transfer ID:</strong> <?= $transfer['Stock_transfer']['transfer_id']; ?></td>
            <td><strong>Created:</strong> <?= date('d-m-Y h:i A', strtotime($transfer['Stock_transfer']['created'])); ?></td>
        </tr>

        <tr>
            <td><strong>Transfer From:</strong> <?= $transfer['Stock_transfer']['transfer_from_name']; ?></td>
            <td><strong>Transfer To:</strong> <?= $transfer['Stock_transfer']['transfer_to_name']; ?></td>
        </tr>

        <tr>
            <td><strong>Total Tax:</strong> <?= number_format($transfer['Stock_transfer']['tax'],2); ?></td>
            <td><strong>Total Amount:</strong> <?= number_format($transfer['Stock_transfer']['amount'],2); ?></td>
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
            </tr>
        </thead>

        <tbody>

            <?php $i=1; ?>

            <?php foreach($items as $item): ?>

            <tr>
                <td><?= $i++; ?></td>
                <td><?= $item['Stock_transfer_item']['sku_name']; ?></td>
                <td><?= $item['Stock_transfer_item']['sku_brand']; ?></td>
                <td><?= $item['Stock_transfer_item']['sku_unit_name']; ?></td>
                <td><?= number_format($item['Stock_transfer_item']['sku_price'],2); ?></td>
                <td><?= $item['Stock_transfer_item']['tax_percent']; ?>%</td>
                <td><?= number_format($item['Stock_transfer_item']['tax_value'],2); ?></td>
                <td><?= $item['Stock_transfer_item']['sku_quantity']; ?></td>
                <td><?= number_format($item['Stock_transfer_item']['amount'],2); ?></td>
            </tr>

            <?php endforeach; ?>

        </tbody>

    </table>


    <br><br>

    <div style="text-align:right;">
        <p><strong>Total Tax:</strong> <?= number_format($transfer['Stock_transfer']['tax'],2); ?></p>
        <p><strong>Grand Total:</strong> <?= number_format($transfer['Stock_transfer']['amount'],2); ?></p>
    </div>


    <div style="margin-top:20px;">
        <p>
            <strong>Amount In Words:</strong>
            <?= getIndianCurrency($transfer['Stock_transfer']['amount']); ?>
        </p>
    </div>


    <hr>

    <div style="text-align:center;">
        <p>This is Computer Generated Document</p>
        <p>Print Date: <?= date('d-m-Y'); ?></p>
    </div>

</div>

</body>
</html>