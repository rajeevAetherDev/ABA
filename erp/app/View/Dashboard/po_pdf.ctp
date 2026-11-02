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
            <div class="col-md-12" style="margin:3px !important;">

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
                                            <span style="font-size:15px;"><strong>SUPPLIER NAME :</strong> <?php print_r($indents[0]['inventory_purchase_order']['vendor_name']);?></span>
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
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_purchase_order']['p_o_id']);?></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Status:</b></div>
                            
                            <?php if($indents[0]['inventory_purchase_order']['status']==1){
                                $status='Submitted';
                                $date=$indents[0]['inventory_purchase_order']['created']; 
                            }elseif($indents[0]['inventory_purchase_order']['status']==2){
                                $status='Approved';
                                $date=$indents[0]['inventory_purchase_order']['approved_on']; 
                            }elseif($indents[0]['inventory_purchase_order']['status']==3){
                                $status='Processed';
                                $date=$indents[0]['inventory_purchase_order']['processed_on']; 
                            }elseif($indents[0]['inventory_purchase_order']['status']==4){
                                $status='Stocked IN';
                                $date=$indents[0]['inventory_purchase_order']['stockin_on']; 
                            }?>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($status);?></div>
                            </td>
                            <td class="table-data" style="width:25%;" >
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Indent No:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_purchase_order']['internal_indent_id']);?></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Date:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r(date('Y-m-d', strtotime($date)));?></div>
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
                                    
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Pur. Rate</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>TAX Rate</center></th>
                                    
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Requested Qty</center></th>
                                    
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Discount</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Excise (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Addl.Ex (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>VAT (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>TCS (₹)</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Landed (₹)</center></th>
                                    
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Total Tax</center></th>
                                    
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Amount</center></th>
                                    
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==2 || $indents[0]['inventory_purchase_order']['status']==3 || $indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Qty</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==3  || $indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Qty</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Stockin Qty</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==2  || $indents[0]['inventory_purchase_order']['status']==3 || $indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Tax</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==3  || $indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Tax</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>StockIn Tax</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==2  || $indents[0]['inventory_purchase_order']['status']==3 || $indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Amount</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==3  || $indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Amount</center></th>
                                    <?php } ?>
                                    
                                    <?php if($indents[0]['inventory_purchase_order']['status']==4){ ?>
                                        <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>StockIn Amount</center></th>
                                    <?php } ?>
                                    
                                </b>
                            </tr>
                        </thead>
                        <tbody>
<?php 
    $x = 1;
    $sku_amount = $sku_tax = 0;
    $app_qty = $app_tax = $app_amount = 0;
    $pro_qty = $pro_tax = $pro_amount = 0;
    $stockin_qty = $stockin_tax = $stockin_amount = 0;
    $total_discount = 0;
    $total_excise = 0;
    $total_addl_excise = 0;
    $total_vat = 0;
    $total_tcs = 0;
    $total_landed = 0;
    $total_gst_tax = 0;
    $status = $indents[0]['inventory_purchase_order']['status'];
    $stockin_qty        = 0;


    // UPDATED: pick the correct discount column based on stage
    if ($status == 2)      $discountCol = 'approve_discount_value';
    elseif ($status == 3)  $discountCol = 'process_discount_value';
    elseif ($status == 4)  $discountCol = 'stockin_discount_value';
    else                   $discountCol = 'sku_discount_value';
    
    foreach ($items as $item) {
        $price                    = $item['inventory_purchase_order_items']['sku_price'];
        $sku_total_tax_percentage = $item['inventory_purchase_order_items']['sku_total_tax_percentage'];
        $qty                      = $item['inventory_purchase_order_items']['sku_quantity'];
        $sku_tax_val              = $item['inventory_purchase_order_items']['sku_total_tax_value'] ?: 0;
        $approved_qty             = $item['inventory_purchase_order_items']['approved_quantity'] ?: 0;
        $approved_tax             = $item['inventory_purchase_order_items']['approved_total_tax_value'] ?: 0;
        $processed_qty            = $item['inventory_purchase_order_items']['processed_quantity'] ?: 0;
        $processed_tax            = $item['inventory_purchase_order_items']['processed_total_tax_value'] ?: 0;
        $stockin_qty              = $item['inventory_purchase_order_items']['stockin_quantity'] ?: 0;
        $stockin_tax_val          = $item['inventory_purchase_order_items']['stockin_total_tax_value'] ?: 0;

        // UPDATED: read discount from the stage-correct column passed by controller
        $row_discount     = $item['inventory_purchase_order_items'][$discountCol]        ?: 0;
        $excise_duty_amt  = $item['inventory_purchase_order_items']['excise_duty_amt']      ?? 0;
        $addl_excise_amt  = $item['inventory_purchase_order_items']['addl_excise_duty_amt'] ?? 0;
        $vat_amt          = $item['inventory_purchase_order_items']['vat_amt']              ?? 0;
        $tcs_amt          = $item['inventory_purchase_order_items']['tcs_amt']              ?? 0;
        $landed_amt       = $item['inventory_purchase_order_items']['landed_amt']           ?? 0;
        $total_discount  += $row_discount;
        $total_excise    += $excise_duty_amt;
        $total_addl_excise += $addl_excise_amt;
        $total_vat       += $vat_amt;
        $total_tcs       += $tcs_amt;
        $total_landed    += $landed_amt;

        // Accumulate totals — use stored amounts which include excise/VAT/TCS, fallback if stored is 0
        $stored_sku_amount     = (float)($item['inventory_purchase_order_items']['sku_amount']       ?? 0);
        $stored_app_amount     = (float)($item['inventory_purchase_order_items']['approved_amount']  ?? 0);
        $stored_pro_amount     = (float)($item['inventory_purchase_order_items']['processed_amount'] ?? 0);
        $stored_sin_amount     = (float)($item['inventory_purchase_order_items']['stockin_amount']   ?? 0);
        
        // Calculate fallback values if stored amounts are zero
        $fallback_sku_amount = $price * $qty - $row_discount + $excise_duty_amt + $addl_excise_amt + $vat_amt + $tcs_amt;
        $fallback_app_amount = $price * $approved_qty - $row_discount + $excise_duty_amt + $addl_excise_amt + $vat_amt + $tcs_amt;
        $fallback_pro_amount = $price * $processed_qty - $row_discount + $excise_duty_amt + $addl_excise_amt + $vat_amt + $tcs_amt;
        $fallback_sin_amount = $price * $stockin_qty - $row_discount + $excise_duty_amt + $addl_excise_amt + $vat_amt + $tcs_amt;
        
        // For Total Tax: ONLY use the CGST/SGST/IGST (stored tax values)
        $final_sku_gst_tax = (float)$sku_tax_val;
        $final_app_gst_tax = (float)$approved_tax;
        $final_pro_gst_tax = (float)$processed_tax;
        $final_sin_gst_tax = (float)$stockin_tax_val;
        
        // Also keep the old $final_*_tax if needed (but we'll use $final_*_gst_tax for display)
        $final_sku_tax = $final_sku_gst_tax;
        $final_app_tax = $final_app_gst_tax;
        $final_pro_tax = $final_pro_gst_tax;
        $final_sin_tax = $final_sin_gst_tax;
        
        $final_sku_amount = ($stored_sku_amount > 0) ? $stored_sku_amount : $fallback_sku_amount;
        $final_app_amount = ($stored_app_amount > 0) ? $stored_app_amount : $fallback_app_amount;
        $final_pro_amount = ($stored_pro_amount > 0) ? $stored_pro_amount : $fallback_pro_amount;
        $final_sin_amount = ($stored_sin_amount > 0) ? $stored_sin_amount : $fallback_sin_amount;
        
        // Always accumulate sku_amount and sku_tax for all statuses
        $sku_amount += $final_sku_amount;
        $sku_tax    += $final_sku_tax;
        
        if ($status == 1) {
            $total_gst_tax += $final_sku_gst_tax;
        } elseif ($status == 2) {
            $app_amount += $final_app_amount;
            $app_tax    += $final_app_tax;
            $app_qty    += $approved_qty;
            $total_gst_tax += $final_app_gst_tax;
        } elseif ($status == 3) {
            // Also accumulate approved totals for status 3!
            $app_amount += $final_app_amount;
            $app_tax    += $final_app_tax;
            $app_qty    += $approved_qty;
            $pro_amount += $final_pro_amount;
            $pro_tax    += $final_pro_tax;
            $pro_qty    += $processed_qty;
            $total_gst_tax += $final_pro_gst_tax;
        } elseif ($status == 4) {
            // Also accumulate approved and processed totals for status 4!
            $app_amount += $final_app_amount;
            $app_tax    += $final_app_tax;
            $app_qty    += $approved_qty;
            $pro_amount += $final_pro_amount;
            $pro_tax    += $final_pro_tax;
            $pro_qty    += $processed_qty;
            $stockin_amount += $final_sin_amount;
            $stockin_tax    += $final_sin_tax;
            $stockinqty    += $stockin_qty;
            $total_gst_tax += $final_sin_gst_tax;
            // $stockin_qty    += $item['inventory_purchase_order_items']['stockin_quantity'] ?: 0;
        }
?>
<tr style="color:black;">
    <td style="border: 1px solid black;padding:10px;"><center><?= $x++; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= h($item['inventory_purchase_order_items']['sku_name']); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= h($item['inventory_purchase_order_items']['sku_unit_name']); ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $price; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $sku_total_tax_percentage.' %'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $qty; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $row_discount; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $excise_duty_amt > 0 ? number_format($excise_duty_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $addl_excise_amt > 0 ? number_format($addl_excise_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $vat_amt > 0 ? number_format($vat_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $tcs_amt > 0 ? number_format($tcs_amt,2) : '—'; ?></center></td>
    <td style="border: 1px solid black;padding:10px;"><center><?= $landed_amt > 0 ? number_format($landed_amt,2) : number_format($price * $qty - $row_discount, 2); ?></center></td>
    <!-- Total Tax: stage appropriate -->
    <?php if ($status == 1): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $final_sku_tax; ?></center></td>
    <?php elseif ($status == 2): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $final_app_tax; ?></center></td>
    <?php elseif ($status == 3): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $final_pro_tax; ?></center></td>
    <?php elseif ($status == 4): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $final_sin_tax; ?></center></td>
    <?php endif; ?>
    <!-- BASE COLUMN 14: Amount (show final_sku_amount for all statuses) -->
    <td style="border: 1px solid black;padding:10px;"><center><?= number_format((float)$final_sku_amount, 2); ?></center></td>

    <!-- NOW FOLLOW EXACT HEADER ORDER: Approved Qty (15), Processed Qty (18), Stockin Qty (21), Approved Tax (16), Processed Tax (19), Stockin Tax (22), Approved Amount (17), Processed Amount (20), Stockin Amount (23) -->
    <?php if ($status >= 2): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $approved_qty; ?></center></td>
    <?php endif; ?>
    <?php if ($status >= 3): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $processed_qty; ?></center></td>
    <?php endif; ?>
    <?php if ($status == 4): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $stockin_qty; ?></center></td>
    <?php endif; ?>
    <?php if ($status >= 2): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $final_app_tax; ?></center></td>
    <?php endif; ?>
    <?php if ($status >= 3): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $final_pro_tax; ?></center></td>
    <?php endif; ?>
    <?php if ($status == 4): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= $final_sin_tax; ?></center></td>
    <?php endif; ?>
    <?php if ($status >= 2): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= number_format((float)$final_app_amount, 2); ?></center></td>
    <?php endif; ?>
    <?php if ($status >= 3): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= number_format((float)$final_pro_amount, 2); ?></center></td>
    <?php endif; ?>
    <?php if ($status == 4): ?>
        <td style="border: 1px solid black;padding:10px;"><center><?= number_format((float)$final_sin_amount, 2); ?></center></td>
    <?php endif; ?>
</tr>
<?php } ?>

<!-- Totals Row -->
<tr style="color:black;">
    <?php if ($status == 1): ?>
        <!-- Status 1: cols 1‑14 (7 empty + Excise-AddlEx-VAT-TCS-Landed-TotalTax-Amount) -->
        <td colspan="7"></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_addl_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_vat,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_tcs,2); ?></center></td>
        <!--<td style="border:1px solid black;padding:10px;"><center><?= number_format($total_landed,2); ?></center></td>-->
        <td></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_gst_tax,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($sku_amount,2); ?></center></td>
    <?php elseif ($status == 2): ?>
        <!-- Status 2: cols 1‑17 (7 empty + Excise-AddlEx-VAT-TCS-Landed-TotalTax-Amount (sku_amount) + ApprovedQty-ApprovedTax-ApprovedAmount) -->
        <td colspan="7"></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_addl_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_vat,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_tcs,2); ?></center></td>
        <!--<td style="border:1px solid black;padding:10px;"><center><?= number_format($total_landed,2); ?></center></td>-->
        <td></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $app_tax; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($sku_amount,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $app_qty; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $app_tax; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($app_amount,2); ?></center></td>
    <?php elseif ($status == 3): ?>
        <!-- Status 3: cols 1‑20 (7 empty + Excise-AddlEx-VAT-TCS-Landed-TotalTax-Amount (sku_amount) + ApprovedQty-ProcessedQty-ApprovedTax-ProcessedTax-ApprovedAmount-ProcessedAmount) -->
        <td colspan="7"></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_addl_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_vat,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_tcs,2); ?></center></td>
        <!--<td style="border:1px solid black;padding:10px;"><center><?= number_format($total_landed,2); ?></center></td>-->
        <td></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $pro_tax; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($sku_amount,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $app_qty; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $pro_qty; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $app_tax; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $pro_tax; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($app_amount,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($pro_amount,2); ?></center></td>
    <?php elseif ($status == 4): ?>
        <!-- Status 4: cols 1‑23 (7 empty + Excise-AddlEx-VAT-TCS-Landed-TotalTax-Amount (sku_amount) + ApprovedQty-ProcessedQty-StockinQty-ApprovedTax-ProcessedTax-StockinTax-ApprovedAmount-ProcessedAmount-StockinAmount) -->
        <td colspan="7"></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_addl_excise,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_vat,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_tcs,2); ?></center></td>
        <td></td>
        <!--<td style="border:1px solid black;padding:10px;"><center><?= number_format($total_landed,2); ?></center></td>-->
        <td style="border:1px solid black;padding:10px;"><center><?= $stockin_tax; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($sku_amount,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $app_qty; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $pro_qty; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= $stockinqty; ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_gst_tax,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_gst_tax,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($total_gst_tax,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($app_amount,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($pro_amount,2); ?></center></td>
        <td style="border:1px solid black;padding:10px;"><center><?= number_format($stockin_amount,2); ?></center></td>
    <?php endif; ?>
</tr>

<!-- ================= TAX SEGREGATION TABLE ================= -->
<br><br>
<table style="width:20%; border-collapse:collapse; color:black;">
    <thead>
        <tr>
            <th style="border:1px solid black; padding:8px;">Tax %</th>
            <th style="border:1px solid black; padding:8px;">Taxable Amount</th>
            <?php if ($isIGST == 0) { ?>
                <th style="border:1px solid black; padding:8px;">CGST</th>
                <th style="border:1px solid black; padding:8px;">SGST</th>
            <?php } else { ?>
                <th style="border:1px solid black; padding:8px;">IGST</th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
        <?php 
            foreach ($taxSummary as $tax) { ?>
            <tr>
                <td style="border:1px solid black; padding:8px; text-align:center;">
                    <?= $tax[0]['tax_name']; ?>
                </td>
                <td style="border:1px solid black; padding:8px; text-align:right;">
                    <?= number_format($tax[0]['taxable'], 2); ?>
                </td>

                <?php if ($isIGST == 0) { ?>
                    <td style="border:1px solid black; padding:8px; text-align:right;">
                        <?= number_format($tax[0]['cgst'], 2); ?>
                    </td>
                    <td style="border:1px solid black; padding:8px; text-align:right;">
                        <?= number_format($tax[0]['sgst'], 2); ?>
                    </td>
                <?php } else { ?>
                    <td style="border:1px solid black; padding:8px; text-align:right;">
                        <?= number_format($tax[0]['igst'], 2); ?>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
        <?php
            $gtTaxable = $gtCGST = $gtSGST = $gtIGST = 0;
            
            foreach ($taxSummary as $tax) {
                $gtTaxable += $tax[0]['taxable'];
                $gtCGST    += $tax[0]['cgst'];
                $gtSGST    += $tax[0]['sgst'];
                $gtIGST    += $tax[0]['igst'];
            }
            ?>
            
            <tr style="font-weight:bold;">
                <td style="border:1px solid black; padding:8px; text-align:center;">
                    Grand Total
                </td>
            
                <td style="border:1px solid black; padding:8px; text-align:right;">
                    <?= number_format($gtTaxable, 2); ?>
                </td>
            
                <?php if ($isIGST == 0) { ?>
                    <td style="border:1px solid black; padding:8px; text-align:right;">
                        <?= number_format($gtCGST, 2); ?>
                    </td>
                    <td style="border:1px solid black; padding:8px; text-align:right;">
                        <?= number_format($gtSGST, 2); ?>
                    </td>
                <?php } else { ?>
                    <td style="border:1px solid black; padding:8px; text-align:right;">
                        <?= number_format($gtIGST, 2); ?>
                    </td>
                <?php } ?>
            </tr>

    </tbody>
</table>
<!-- ================= END TAX SEGREGATION ================= -->

<?php
// Final total amount based on status
if ($status == 1)      $totalAmount = $sku_amount;
elseif ($status == 2)  $totalAmount = $app_amount;
elseif ($status == 3)  $totalAmount = $pro_amount;
elseif ($status == 4)  $totalAmount = $stockin_amount;

if ($status == 1)      $charge = $indents[0]['inventory_purchase_order']['charge'];
elseif ($status == 2)  $charge = $indents[0]['inventory_purchase_order']['approved_charge'];
elseif ($status == 3)  $charge = $indents[0]['inventory_purchase_order']['processed_charge'];
elseif ($status == 4)  $charge = $indents[0]['inventory_purchase_order']['stockin_charge'];

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
            				    <!-- UPDATED: show stage-correct total discount accumulated from per-row $row_discount -->
            				    <p style="font-size:12px"><strong>Total Discount :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.$total_discount);?></p>
            				    <!-- New: Show Excise, Addl Ex, VAT, TCS totals -->
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
                                <p style="font-size:12px"><strong>Total Tax :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.number_format($total_gst_tax, 2));?></p>
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
