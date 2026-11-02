<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>

<?php echo $this->Html->css([
    '../assets/plugins/bootstrap/css/bootstrap.min',
    '../assets/css/style'
]); ?>

<script>
    window.print();
</script>

<style>
    body { font-size: 12px; color:black; }
    .table-data { border:1px solid black; padding:8px; text-align:center; }
</style>

</head>

<body>
<div class="container-fluid">

<!-- ================= HEADER ================= -->
<div style="text-align:center;">
    <h2><?= $locationdetail['Location']['name']; ?></h2>

    <?php for($i=1;$i<=10;$i++): ?>
        <?php if(!empty($locationdetail['Location']["print_line$i"])): ?>
            <p><b><?= $locationdetail['Location']["print_line$i"]; ?></b></p>
        <?php endif; ?>
    <?php endfor; ?>
</div>

<hr>
<p style="text-align:center;"><b>INTERNAL INDENT</b></p>
<hr>

<!-- ================= INDENT INFO ================= -->
<table style="width:100%;">
<tr>
    <td style="width:50%;">
        <b>Indent ID:</b> <?= $indents[0]['Inventory_internal_indent']['indent_id']; ?><br>
        <b>Location:</b> <?= $indents[0]['Inventory_internal_indent']['location_name']; ?><br>
        <b>Status:</b> 
        <?= ($indents[0]['Inventory_internal_indent']['is_completed']==1)?'Completed':'Pending'; ?>
    </td>

    <td style="width:50%;">
        <b>Date:</b> <?= date('d-m-Y', strtotime($indents[0]['Inventory_internal_indent']['created'])); ?><br>
        <b>Total Amount:</b> <?= number_format($indents[0]['Inventory_internal_indent']['total_amount'],2); ?>
    </td>
</tr>
</table>

<br><br>

<!-- ================= ITEMS TABLE ================= -->
<table style="width:100%;">
<thead>
<tr>
    <th class="table-data">SR NO</th>
    <th class="table-data">Item Name</th>
    <th class="table-data">Unit</th>
    <th class="table-data">Price</th>
    <th class="table-data">Tax %</th>
    <th class="table-data">Qty</th>
    <th class="table-data">Completed Qty</th>
    <th class="table-data">Tax</th>
    <th class="table-data">Amount</th>
    <th class="table-data">Completed Status</th>
</tr>
</thead>

<tbody>
<?php
$i=1;
$totalAmount=0;
$totalTax=0;

foreach($items as $item):
$data=$item['Inventory_internal_indent_items'];

$amount = $data['sku_amount'] ?: 0;
$tax    = $data['sku_total_tax_value'] ?: 0;

$totalAmount += $amount;
$totalTax += $tax;
?>

<tr>
<td class="table-data"><?= $i++; ?></td>
<td class="table-data"><?= h($data['sku_name']); ?></td>
<td class="table-data"><?= $data['sku_unit_name']; ?></td>
<td class="table-data"><?= $data['sku_price']; ?></td>
<td class="table-data"><?= $data['sku_total_tax_percentage']; ?>%</td>
<td class="table-data"><?= $data['sku_quantity']; ?></td>
<td class="table-data"><?= $data['completed_quantity']; ?></td>
<td class="table-data"><?= $tax; ?></td>
<td class="table-data"><?= $amount; ?></td>
<td class="table-data">
    <?= ($data['is_completed']==1)?'Yes':'No'; ?>
</td>
</tr>

<?php endforeach; ?>

<!-- TOTAL ROW -->
<tr>
<td colspan="7"></td>
<td class="table-data"><b><?= number_format($totalTax,2); ?></b></td>
<td class="table-data"><b><?= number_format($totalAmount,2); ?></b></td>
<td></td>
</tr>

</tbody>
</table>

<br><br>

<!-- ================= TAX SUMMARY (OPTIONAL SAFE) ================= -->
<?php if(!empty($taxSummary)): ?>
<table style="width:30%;">
<tr>
    <th class="table-data">Tax %</th>
    <th class="table-data">Taxable</th>
    <th class="table-data">Tax</th>
</tr>

<?php foreach($taxSummary as $tax): ?>
<tr>
    <td class="table-data"><?= $tax[0]['tax_name']; ?></td>
    <td class="table-data"><?= number_format($tax[0]['taxable'],2); ?></td>
    <td class="table-data"><?= number_format($tax[0]['tax_value'],2); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<br><br>

<!-- ================= TOTAL SECTION ================= -->
<?php

// Convert amount to words
function convertNumberToWords($number) {
    $no = floor($number);
    $decimal = round($number - $no, 2) * 100;
    $digits_length = strlen($no);
    $i = 0;
    $str = [];
    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight',
        9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
        13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    ];
    $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number_part = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number_part) {
            $str[] = ($number_part < 21)
                ? $words[$number_part] . ' ' . $digits[count($str)]
                : $words[floor($number_part / 10) * 10] . ' ' . $words[$number_part % 10] . ' ' . $digits[count($str)];
        } else {
            $str[] = null;
        }
    }

    $rupees = implode(' ', array_reverse(array_filter($str)));
    $paise = ($decimal)
        ? 'and ' . $words[floor($decimal / 10) * 10] . ' ' . $words[$decimal % 10] . ' Paise'
        : '';

    return ($rupees ? $rupees . ' Rupees ' : '') . $paise . ' Only';
}

$amountWords = convertNumberToWords($totalAmount);
?>

<table style="width:100%;">
<tr>
<td>
    <p><b>Total Tax:</b> <?= $locationdetail['Location']['currency_symbol'].' '.number_format($totalTax,2); ?></p>
    <p><b>Total Amount:</b> <?= $locationdetail['Location']['currency_symbol'].' '.number_format($totalAmount,2); ?></p>
    <p><b>Amount in Words:</b> <?= $amountWords; ?></p>
</td>
</tr>
</table>

<hr>

<!-- ================= FOOTER ================= -->
<p style="text-align:center;">This is Computer Generated Internal Indent</p>
<p style="text-align:center;"><b>Print Date:</b> <?= date('d-m-Y'); ?></p>

<div style="text-align:center;">
    <p><?= $locationdetail['Location']['footer_msg']; ?></p>
</div>

</div>
</body>
</html>