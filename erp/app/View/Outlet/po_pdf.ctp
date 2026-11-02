<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html  moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title></title>
    <!-- CORE CSS FRAMEWORK - START -->
      <?php echo $this->Html->css(array('../assets/plugins/pace/pace-theme-flash', '../assets/plugins/bootstrap/css/bootstrap.min', '../assets/plugins/bootstrap/css/bootstrap-theme.min','../assets/fonts/font-awesome/css/font-awesome', '../assets/css/animate.min', '../assets/plugins/perfect-scrollbar/perfect-scrollbar')); ?>
	<?php echo $this->Html->css(array('../assets/css/style', '../assets/css/responsive')); ?>
        <?php  echo $this->Html->script(array('../assets/js/jquery-1.11.2.min')); ?>
		<?php  echo $this->Html->script(array('../assets/plugins/daterangepicker/js/moment','../assets/plugins/daterangepicker/js/daterangepicker')); ?>
  
<script>
    window.print();
</script>
</head>
<body lang="en-US" dir="ltr">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <body style="margin:3px !important">
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
                    <hr style="border: 0.5px solid black;border-collapse: collapse;"  >
                        <p style="font-size:13px;text-align:center;"><b>PURCHASE ORDER</b></p>
                    <hr style="border: 0.5px solid black;border-collapse: collapse;" >
                    <table class="address-table" style="width:100%; margin:auto;">
                        <tr>
                            <td class="table-data" style="width:30%;" >
                                <table class="address-table" style="width:70%;padding-top:0px !important;">
                                    <tr>
                                        <td class="address-table address-table-td">
                                            <span style="font-size:15px;"><strong>Stockin ID :</strong> <?php print_r($indents[0]['inventory_stockin']['stockin_id']);?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="address-table address-table-td">
                                            <span style="font-size:15px;"><strong>SUPPLIER NAME :</strong> <?php print_r($indents[0]['inventory_stockin']['vendor_name']);?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td"  style="font-size:15px;"><b>Address - </b><?php print_r(wordwrap($indents[0]['inventory_vendors']['office_address'].' '.$indents[0]['inventory_vendors']['state'], 45, "\n"));?></td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td">
                                            <span style="padding-top:0px !important;"><strong>GST NO&nbsp:</strong>&nbsp<?php print_r($indents[0]['inventory_vendors']['gst']);?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>Email&nbsp:</strong>&nbsp<?php print_r($indents[0]['inventory_vendors']['email']);?></span></td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>Mobile No&nbsp:</strong>&nbsp<?php print_r($indents[0]['inventory_vendors']['mobile']);?></span></td>
                                    </tr>
                                </table>
                            </td>
                            <td class="table-data" style="width:25%;" >
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>PO ID:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_stockin']['p_o_id']);?></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Status:</b></div>
                            <?php
                                $status = 'Stocked IN';
                                $date   = $indents[0]['inventory_purchase_order']['stockin_date']; 
                                
                            ?>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($status);?></div>
                            </td>
                            <td class="table-data" style="width:25%;" >
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Indent No:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_stockin']['indent_id']);?></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Date/Time:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r(date('Y-m-d H:i:s', strtotime($date)));?></div>
                            </td>
        
                        </tr>
                    </table>
                    <br><br>
                    <table class="address-table" style="width:100%; margin:auto;">
                        <thead> 
                            <tr class="table-data" style="font-size:10px;">
                                <b style=";color:black;">
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>SR NO</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Product Name</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Unit</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Rate</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Qty</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Discount</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Excise (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Addl.Ex (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>VAT (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>TCS (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Total Tax</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Amount</center></th>
                                </b>
                            </tr>
                        </thead>
                        <tbody>
<?php 
    $x = 1;
    $stockin_amount     = 0;
    $stockin_tax        = 0;
    $total_discount_sum = 0;
    $total_excise       = 0;
    $total_addl_excise  = 0;
    $total_vat          = 0;
    $total_tcs          = 0;
	

    foreach ($items as $item) {
        $price           = $item['inventory_stockin_items']['sku_price'];
        $stockin_qty     = $item['inventory_stockin_items']['stockin_quantity'];
        $stockin_tax_val = $item['inventory_stockin_items']['stockin_total_tax_value'] ?: 0;
        $excise_amt      = (float)($item['inventory_purchase_order_items']['excise_duty_amt']      ?? 0);
        $addl_excise_amt = (float)($item['inventory_purchase_order_items']['addl_excise_duty_amt'] ?? 0);
        $vat_amt         = (float)($item['inventory_purchase_order_items']['vat_amt']              ?? 0);
        $tcs_amt         = (float)($item['inventory_purchase_order_items']['tcs_amt']              ?? 0);

        // stockin_discount_value is already the final computed ₹ amount — use directly
        $sku_discount = (float)($item['inventory_purchase_order_items']['stockin_discount_value'] ?? 0);

        $total_discount_sum += $sku_discount;
        $total_excise       += $excise_amt;
        $total_addl_excise  += $addl_excise_amt;
        $total_vat          += $vat_amt;
        $total_tcs          += $tcs_amt;

        // Use stored stockin_amount when available (includes all taxes)
        $stored_sin_amt = (float)($item['inventory_purchase_order_items']['stockin_amount'] ?? 0);
        $line_amount    = $stored_sin_amt > 0
            ? $stored_sin_amt
            : round(($price * $stockin_qty) - $sku_discount + $excise_amt + $addl_excise_amt + $vat_amt + $tcs_amt + $stockin_tax_val, 2);

        $stockin_amount += $line_amount;
        $stockin_tax    += $stockin_tax_val;
?>
<tr style="color:black;">
    <td style="border: 1px solid black;padding:10px;"><center><?= $x++; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= h($item['inventory_stockin_items']['sku_name']); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= h($item['inventory_stockin_items']['sku_unit_name']); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $price; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $stockin_qty; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $sku_discount; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $excise_amt > 0 ? number_format($excise_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $addl_excise_amt > 0 ? number_format($addl_excise_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $vat_amt > 0 ? number_format($vat_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $tcs_amt > 0 ? number_format($tcs_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $stockin_tax_val; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format($line_amount, 2); ?></center></td>
</tr>
<?php } ?>

<!-- Totals Row -->
<tr style="color:black;">
    <!-- cols 1‑5: empty with colspan 5 -->
    <td colspan="5"></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format($total_discount_sum,2); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format($total_excise,2); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format($total_addl_excise,2); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format($total_vat,2); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format($total_tcs,2); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $stockin_tax; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format($stockin_amount,2); ?></center></td>
</tr>

<?php

$totalAmount = $stockin_amount;

$charge = $indents[0]['inventory_stockin']['stockin_charge'];

$totalAmountwithcharge = $totalAmount + $charge;

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

$amountinwords = convertNumberToWords($totalAmountwithcharge);
?>

</tbody>

                    </table>
                    <br><br>
                    <table style="width:100% !important">
            			<tr style="color:black;">
            				<td class="address-table">
            				    <p style="font-size:12px"><strong>Amount :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.$totalAmount);?></p>
            				    <p style="font-size:12px"><strong>Charges :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.$charge);?></p>
            				    <p style="font-size:12px"><strong>Total Discount :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.number_format($total_discount_sum, 2));?></p>
            				    <?php if ($total_excise > 0): ?>
                                    <p style="font-size:12px"><strong>Total Excise :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.number_format($total_excise, 2));?></p>
                                <?php endif; ?>
                                <?php if ($total_addl_excise > 0): ?>
                                    <p style="font-size:12px"><strong>Total Addl. Excise :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.number_format($total_addl_excise, 2));?></p>
                                <?php endif; ?>
                                <?php if ($total_vat > 0): ?>
                                    <p style="font-size:12px"><strong>Total VAT :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.number_format($total_vat, 2));?></p>
                                <?php endif; ?>
                                <?php if ($total_tcs > 0): ?>
                                    <p style="font-size:12px"><strong>Total TCS :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.number_format($total_tcs, 2));?></p>
                                <?php endif; ?>
            				    <p style="font-size:12px"><strong>Total Amount :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.$totalAmountwithcharge);?></p>
            				    <p style="font-size:12px"><strong>Amount in Words :</strong> <?php print_r($amountinwords);?></p>
            				</td>
            			</tr>
            		</table>
                    
                    <hr style="border: 0.5px solid black;border-collapse: collapse;" >
                    <p style="font-size:13px;text-align:center;">This is Computer Generated Purchase Order</p>
    				<p style="font-size:13px;text-align:center;"><b>Print Date:</b> <?php print_r(date('d-m-Y'));?></p>
               
        			<div style="width:100%; text-align:center;">
                        <p><?php print_r($locationdetail['Location']['footer_msg']);?></p>
                    </div>
        </div>
    </div>
</body>
</html>
