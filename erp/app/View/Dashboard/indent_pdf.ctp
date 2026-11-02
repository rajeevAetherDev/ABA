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
                    <?php if($indents[0]['inventory_indent']['status']==1){
                                    $status='Submitted';
                                }elseif($indents[0]['inventory_indent']['status']==2){
                                    $status='Approved';
                                }elseif($indents[0]['inventory_indent']['status']==3){
                                    $status='Processed';
                                }elseif($indents[0]['inventory_indent']['status']==4){
                                    $status='Stocked IN';
                                }?>
                    <hr style="border: 0.5px solid black;border-collapse: collapse;"  >
                        <p style="font-size:13px;text-align:center;"><b>INDENT</b></p>
                    <hr style="border: 0.5px solid black;border-collapse: collapse;" >
                    <table class="address-table" style="width:100%; margin:auto;">
                        <tr>
                            <td class="table-data" style="width:30%;" >
                                <table class="address-table" style="width:50%;padding-top:0px !important;">
                                    <tr>
                                        <td class="address-table address-table-td">
                                            <span style="font-size:15px;"><strong>LOCATION :</strong> <?php print_r($indents[0]['inventory_indent']['location_name']);?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>INVOICE NUMBER :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['indent_id']);?></span></td>
                                    </tr>
                                    <?php if($indents[0]['inventory_indent']['status']==2 || $indents[0]['inventory_indent']['status']==3 || $indents[0]['inventory_indent']['status']==4){ ?>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>VEHICLE NO :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['vehicle_no']);?></span></td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>REMARKS :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['remarks']);?></span></td>
                                    </tr>
                                    <?php } if($indents[0]['inventory_indent']['status']==3 || $indents[0]['inventory_indent']['status']==4){ ?>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>CONSIGNMENT ID :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['consignment_id']);?></span></td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>SERIAL NO :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['serial_number']);?></span></td>
                                    </tr>
                                    <?php } if($indents[0]['inventory_indent']['status']==4){ ?>
                                    
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>INVOICE NO :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['invoice_no']);?></span></td>
                                    </tr>
                                    <?php } ?>
                                </table>
                            </td>
                            <td class="table-data" style="width:25%;" >
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>INDENT ID:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_indent']['indent_id']);?></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Total Amount:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_indent']['total_amount']);?></div>
                                <?php if($indents[0]['inventory_indent']['status']==2 || $indents[0]['inventory_indent']['status']==3 || $indents[0]['inventory_indent']['status']==4){ ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Approved Amount:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_indent']['approved_amount']);?></div>
                                <?php }
                                if($indents[0]['inventory_indent']['status']==3 || $indents[0]['inventory_indent']['status']==4){ ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Processed Amount:</b></div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_indent']['processed_amount']);?></div>
                                <?php }
                                if($indents[0]['inventory_indent']['status']==4){ ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Stockin Amount:</b></div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_indent']['stockin_amount']);?></div>
                                <?php } ?>  
                            </td>
                            
                            <td class="table-data" style="width:25%;" >
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Status:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($status);?></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Date:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r(date('Y-m-d h:i:s', strtotime($indents[0]['inventory_indent']['created'])));?></div>
                                <?php 
                                if($indents[0]['inventory_indent']['status']==2 || $indents[0]['inventory_indent']['status']==3 || $indents[0]['inventory_indent']['status']==4){ ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Approved Date:</b></div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r(date('Y-m-d h:i:s', strtotime($indents[0]['inventory_indent']['approved_date'])));?></div>
                                <?php }
                                if($indents[0]['inventory_indent']['status']==3 || $indents[0]['inventory_indent']['status']==4){ ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Processed Date:</b></div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r(date('Y-m-d h:i:s', strtotime($indents[0]['inventory_indent']['processed_date'])));?></div>
                                <?php }
                                    if($indents[0]['inventory_indent']['status']==4){ ?>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Stockin Date:</b></div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r(date('Y-m-d h:i:s', strtotime($indents[0]['inventory_indent']['stockin_date'])));?></div>
                                <?php } ?>  
                                
                            </td>
                            
                        </tr>
                    </table>
                    <br><br>
                    <table class="address-table" style="width:100%; margin:auto;">
                    <tr>
                            <td class="table-data" style="width:50%;" >
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Detail of receiver:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php 
                                print_r($indents[0]['inventory_indent']['location_name'].'<br>');
                                print_r($indents[0][0]['print_line1'].'<br>');
                                print_r($indents[0][0]['print_line2'].'<br>');
                                print_r($indents[0][0]['print_line3']);
                                ?>
                                </div>
                            </td>
                            <td class="table-data" style="width:50%;" >
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Detail of Consignee:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_indent']['warehouse_name'].'<br>');
                                print_r($indents[0][0]['warehouse_print_line1'].'<br>');
                                print_r($indents[0][0]['warehouse_print_line2'].'<br>');
                                print_r($indents[0][0]['warehouse_print_line3']);
                                ?></div>
                            </td>
                        </tr>
                    </table>
                    
                    <br><br>
                    <!--
                    <table class="address-table" style="width:100%; margin:auto;">
                        <thead> 
                            <tr class="table-data" style="font-size:10px;">
                                <b style=";color:black;">
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>SR NO</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Product Name</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Unit</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Qty</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Price</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Total Tax value</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Total Amount</center></th>
                                    
                                    <?php if($indents[0]['inventory_indent']['status']==2){ ?>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Quantity</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Price</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Tax value</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Amount</center></th>
                                    <?php } ?>
                                    <?php if($indents[0]['inventory_indent']['status']==3){ ?>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Quantity</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Price</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Tax value</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Amount</center></th>
                                    <?php } ?>
                                    <?php if($indents[0]['inventory_indent']['status']==4){ ?>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Stockin Quantity</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Stockin Price</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Stockin Tax value</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Stockin Amount</center></th>
                                    <?php } ?>
                                </b>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $x=1;
                            $amount=0;
                            $sku_total_tax_value=0;
                            $approved_total_tax_value=0;
                            $approved_amount=0;
                            $processed_total_tax_value=0;
                            $processed_amount=0;
                            $stockin_total_tax_value=0;
                            $stockin_amount=0;
                            
                            
                            foreach($items as $item){
                                $amount=$amount+$item['inventory_indent_items']['sku_amount'];
                                $sku_total_tax_value=$sku_total_tax_value+$item['inventory_indent_items']['sku_total_tax_value'];
                                $approved_total_tax_value=$approved_total_tax_value+$item['inventory_indent_items']['approved_tax_value'];
                                $approved_amount=$approved_amount+$item['inventory_indent_items']['approved_amount'];
                                $processed_total_tax_value=$processed_total_tax_value+$item['inventory_indent_items']['processed_tax_value'];
                                $processed_amount=$processed_amount+$item['inventory_indent_items']['processed_amount'];
                                $stockin_total_tax_value=$stockin_total_tax_value+$item['inventory_indent_items']['stockin_tax_value'];
                                $stockin_amount=$stockin_amount+$item['inventory_indent_items']['stockin_amount'];
                            ?>
                                <tr style="color:black;">
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($x);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_name']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_unit_name']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_quantity']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_price']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_total_tax_value']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_amount']);?></center></td>
                                    <?php if($indents[0]['inventory_indent']['status']==2){ ?>
                                    
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_quantity']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_price']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_tax_value']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_amount']);?></center></td>
                                    <?php } ?>
                                    <?php if($indents[0]['inventory_indent']['status']==3){ ?>
                                    
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['processed_quantity']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_price']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['processed_tax_value']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['processed_amount']);?></center></td>
                                    <?php } ?>
                                    <?php if($indents[0]['inventory_indent']['status']==4){ ?>
                                    
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['stockin_quantity']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_price']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['stockin_tax_value']);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['stockin_amount']);?></center></td>
                                    <?php } ?>
                                </tr>
                            <?php $x++;
                            } 
                            ?>
                            <tr style="color:black;">
                                    <td colspan="5"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($sku_total_tax_value);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($amount);?></center></td>
                                    <?php if($indents[0]['inventory_indent']['status']==2){ ?>
                                    
                                    <td colspan="2"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($approved_total_tax_value);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($approved_amount);?></center></td>
                                    <?php 
                                    $amount=$approved_amount;
                            
                            } ?>
                            <?php if($indents[0]['inventory_indent']['status']==3){ ?>
                                    
                                    <td colspan="2"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($processed_total_tax_value);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($processed_amount);?></center></td>
                                    <?php 
                                    $amount=$processed_amount;
                            
                            } ?>
                            <?php if($indents[0]['inventory_indent']['status']==4){ ?>
                                    
                                    <td colspan="2"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($stockin_total_tax_value);?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($stockin_amount);?></center></td>
                                    <?php 
                                    $amount=$stockin_amount;
                            
                            } ?>
                                </tr>
                            <?php
                            $totalamount=$amount;
                            $amount_after_decimal = round($amount - ($num = floor($amount)), 2) * 100;
                           // Check if there is any number after decimal
                           $amt_hundred = null;
                           $count_length = strlen($num);
                           $x = 0;
                           $string = array();
                           $change_words = array(0 => '', 1 => 'One', 2 => 'Two',
                             3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
                             7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
                             10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
                             13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
                             16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
                             19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
                             40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
                             70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety');
                            $here_digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
                            while( $x < $count_length ) {
                              $get_divider = ($x == 2) ? 10 : 100;
                              $amount = floor($num % $get_divider);
                              $num = floor($num / $get_divider);
                              $x += $get_divider == 10 ? 1 : 2;
                              if ($amount) {
                               $add_plural = (($counter = count($string)) && $amount > 9) ? 's' : null;
                               $amt_hundred = ($counter == 1 && $string[0]) ? ' and ' : null;
                               $string [] = ($amount < 21) ? $change_words[$amount].' '. $here_digits[$counter]. $add_plural.' 
                               '.$amt_hundred:$change_words[floor($amount / 10) * 10].' '.$change_words[$amount % 10]. ' 
                               '.$here_digits[$counter].$add_plural.' '.$amt_hundred;
                                }
                           else $string[] = null;
                           }
                           $implode_to_Rupees = implode('', array_reverse($string));
                           $get_paise = ($amount_after_decimal > 0) ? "And " . ($change_words[$amount_after_decimal / 10] . " 
                           " . $change_words[$amount_after_decimal % 10]) . ' Paise' : '';
                           $amountinwords=($implode_to_Rupees ? $implode_to_Rupees . 'Rupees ' : '') . $get_paise;
                           ?>
                        </tbody>
                    </table>
                    -->
                    
                    <!-- ================= ITEMS TABLE ================= -->

<table style="width:100%; border-collapse:collapse; font-size:10px;">

<thead>
<tr>
    <th style="border:1px solid black;padding:6px;">SR</th>
    <th style="border:1px solid black;padding:6px;">Product</th>
    <th style="border:1px solid black;padding:6px;">Unit</th>

    <th style="border:1px solid black;padding:6px;">Qty</th>
    <th style="border:1px solid black;padding:6px;">Price</th>
    <th style="border:1px solid black;padding:6px;">Tax %</th>

    <th style="border:1px solid black;padding:6px;">Base</th>
    <th style="border:1px solid black;padding:6px;">Tax</th>
    <th style="border:1px solid black;padding:6px;">Excise (₹)</th>
    <th style="border:1px solid black;padding:6px;">Addl.Ex (₹)</th>
    <th style="border:1px solid black;padding:6px;">VAT (₹)</th>
    <th style="border:1px solid black;padding:6px;">TCS (₹)</th>
    <th style="border:1px solid black;padding:6px;">Total</th>

    <?php if($indents[0]['inventory_indent']['status'] >= 2){ ?>
        <th style="border:1px solid black;padding:6px;">Approved Price</th>
        <th style="border:1px solid black;padding:6px;">Approved Quantity</th>
        <th style="border:1px solid black;padding:6px;">Approved Total</th>
    <?php } ?>

    <?php if($indents[0]['inventory_indent']['status'] >= 3){ ?>
        <th style="border:1px solid black;padding:6px;">Processed Price</th>
        <th style="border:1px solid black;padding:6px;">Processed Quantity</th>
        <th style="border:1px solid black;padding:6px;">Processed Total</th>
    <?php } ?>

    <?php if($indents[0]['inventory_indent']['status'] == 4){ ?>
        <th style="border:1px solid black;padding:6px;">Stockin Price</th>
        <th style="border:1px solid black;padding:6px;">Stockin Quantity</th>
        <th style="border:1px solid black;padding:6px;">Stockin Total</th>
    <?php } ?>
</tr>
</thead>


<tbody>

<?php
$i=1;

$baseSum=$taxSum=$totalSum=0;
$exciseTotal=$addlExciseTotal=$vatTotal=$tcsTotal=0;
$approvedSum=$processedSum=$stockinSum=0;

foreach($items as $item):

$it = $item['inventory_indent_items'];

$price = $it['sku_price'];
$qty   = $it['sku_quantity'];
$taxP  = $it['sku_total_tax_percentage'];

/* ===== ORIGINAL ===== */
$base  = $price*$qty;
$tax   = ($base*$taxP)/100;
$total = $base+$tax;

$baseSum+=$base;
$taxSum+=$tax;
$totalSum+=$total;


/* ===== APPROVED ===== */
$aq=$it['approved_quantity'];
$ap=$it['approved_price'];
$approvedTotal = ($aq*$ap) + (($aq*$ap*$taxP)/100);
$approvedSum+=$approvedTotal;


/* ===== PROCESSED ===== */
$pq=$it['processed_quantity'];
$processedTotal = ($pq*$ap) + (($pq*$ap*$taxP)/100);
$processedSum+=$processedTotal;


/* ===== STOCKIN ===== */
$sq=$it['stockin_quantity'];
$stockinTotal = ($sq*$ap) + (($sq*$ap*$taxP)/100);
$stockinSum+=$stockinTotal;

// Get Excise, Addl.Excise, VAT, TCS from poItemMap
$itSkuId = $it['sku_id'] ?? 0;
$pi = !empty($poItemMap[$itSkuId]) ? $poItemMap[$itSkuId] : [];
$exciseAmt = !empty($pi['excise_duty_amt']) ? (float)$pi['excise_duty_amt'] : 0;
$addlExciseAmt = !empty($pi['addl_excise_duty_amt']) ? (float)$pi['addl_excise_duty_amt'] : 0;
$vatAmt = !empty($pi['vat_amt']) ? (float)$pi['vat_amt'] : 0;
$tcsAmt = !empty($pi['tcs_amt']) ? (float)$pi['tcs_amt'] : 0;
$exciseTotal += $exciseAmt;
$addlExciseTotal += $addlExciseAmt;
$vatTotal += $vatAmt;
$tcsTotal += $tcsAmt;
?>

<tr>

<td style="border:1px solid black;padding:6px;"><?= $i++ ?></td>
<td style="border:1px solid black;padding:6px;"><?= $it['sku_name'] ?></td>
<td style="border:1px solid black;padding:6px;"><?= $it['sku_unit_name'] ?></td>

<td style="border:1px solid black;padding:6px;"><?= $qty ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($price,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= $taxP ?>%</td>

<td style="border:1px solid black;padding:6px;"><?= number_format($base,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($tax,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= $exciseAmt > 0 ? number_format($exciseAmt,2) : '—' ?></td>
<td style="border:1px solid black;padding:6px;"><?= $addlExciseAmt > 0 ? number_format($addlExciseAmt,2) : '—' ?></td>
<td style="border:1px solid black;padding:6px;"><?= $vatAmt > 0 ? number_format($vatAmt,2) : '—' ?></td>
<td style="border:1px solid black;padding:6px;"><?= $tcsAmt > 0 ? number_format($tcsAmt,2) : '—' ?></td>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($total,2) ?></b></td>

<?php if($indents[0]['inventory_indent']['status'] >= 2){ ?>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($ap,2) ?></b></td>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($aq,2) ?></b></td>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($approvedTotal,2) ?></b></td>
<?php } ?>

<?php if($indents[0]['inventory_indent']['status'] >= 3){ ?>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($ap,2) ?></b></td>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($pq,2) ?></b></td>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($processedTotal,2) ?></b></td>
<?php } ?>

<?php if($indents[0]['inventory_indent']['status'] == 4){ ?>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($ap,2) ?></b></td>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($sq,2) ?></b></td>
<td style="border:1px solid black;padding:6px;"><b><?= number_format($stockinTotal,2) ?></b></td>
<?php } ?>

</tr>

<?php endforeach; ?>


<!-- ===== FOOTER TOTALS ===== -->
<tr style="font-weight:bold;background:#f3f3f3;">
<td colspan="6" style="border:1px solid black;padding:6px;text-align:right;">Totals :</td>

<td style="border:1px solid black;padding:6px;"><?= number_format($baseSum,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($taxSum,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($exciseTotal,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($addlExciseTotal,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($vatTotal,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($tcsTotal,2) ?></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($totalSum,2) ?></td>

<?php if($indents[0]['inventory_indent']['status'] >= 2){ ?>
<td colspan="2"></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($approvedSum,2) ?></td>
<?php } ?>

<?php if($indents[0]['inventory_indent']['status'] >= 3){ ?>
<td colspan="2"></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($processedSum,2) ?></td>
<?php } ?>

<?php if($indents[0]['inventory_indent']['status'] == 4){ ?>
<td colspan="2"></td>
<td style="border:1px solid black;padding:6px;"><?= number_format($stockinSum,2) ?></td>
<?php } ?>

</tr>

</tbody>
</table>

                    
                    <br><br>
                    <table style="width:100% !important">
            			<tr style="color:black;">
            				<td class="address-table">
            				    <p style="font-size:12px"><strong>Total Amount :</strong> <?php print_r($locationdetail['Location']['currency_symbol'].' '.$totalamount);?></p>
            				    <p style="font-size:12px"><strong>Amount in Words :</strong> <?php print_r($amountinwords);?></p>
            				</td>
            			</tr>
            		</table>
                    
                    <hr style="border: 0.5px solid black;border-collapse: collapse;" >
                    <p style="font-size:13px;text-align:center;">This is Computer Generated Invoice</p>
    				<p style="font-size:13px;text-align:center;"><b>Print Date:</b> <?php print_r(date('d-m-Y'));?></p>
               
        			<div style="width:100%; text-align:center;">
                        <p><?php print_r($locationdetail['Location']['footer_msg']);?></p>
                    </div>
        </div>
    </div>
</body>
</html>
