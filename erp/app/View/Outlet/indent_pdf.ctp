<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html moznomarginboxes mozdisallowselectionprint>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title></title>
    <!-- CORE CSS FRAMEWORK - START -->
    <?php echo $this->Html->css(array('../assets/plugins/pace/pace-theme-flash', '../assets/plugins/bootstrap/css/bootstrap.min', '../assets/plugins/bootstrap/css/bootstrap-theme.min', '../assets/fonts/font-awesome/css/font-awesome', '../assets/css/animate.min', '../assets/plugins/perfect-scrollbar/perfect-scrollbar')); ?>
    <?php echo $this->Html->css(array('../assets/css/style', '../assets/css/responsive')); ?>
    <?php echo $this->Html->script(array('../assets/js/jquery-1.11.2.min')); ?>
    <?php echo $this->Html->script(array('../assets/plugins/daterangepicker/js/moment', '../assets/plugins/daterangepicker/js/daterangepicker')); ?>

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
                        <h2 style="padding:0px !important;margin:5px !important">
                            <?php print_r($locationdetail['Location']['name']); ?>
                        </h2>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line1']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line2']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line3']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line4']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line5']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line6']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line7']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line9']); ?></b>
                        </p>
                        <p style="padding:0px !important;margin:4px !important">
                            <b><?php print_r($locationdetail['Location']['print_line10']); ?></b>
                        </p>

                    </div>
                    <?php if ($indents[0]['inventory_indent']['status'] == 1) {
                        $status = 'Submitted';
                    } elseif ($indents[0]['inventory_indent']['status'] == 2) {
                        $status = 'Approved';
                    } elseif ($indents[0]['inventory_indent']['status'] == 3) {
                        $status = 'Processed';
                    } elseif ($indents[0]['inventory_indent']['status'] == 4) {
                        $status = 'Stocked IN';
                    } ?>
                    <hr style="border: 0.5px solid black;border-collapse: collapse;">
                    <p style="font-size:13px;text-align:center;"><b>INDENT</b></p>
                    <hr style="border: 0.5px solid black;border-collapse: collapse;">
                    <table class="address-table" style="width:100%; margin:auto;">
                        <tr>
                            <td class="table-data" style="width:30%;">
                                <table class="address-table" style="width:50%;padding-top:0px !important;">
                                    <tr>
                                        <td class="address-table address-table-td">
                                            <span style="font-size:15px;"><strong>LOCATION :</strong>
                                                <?php print_r($indents[0]['inventory_indent']['location_name']); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class=" address-table address-table-td"><span><strong>INDENT ID
                                                    :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['indent_id']); ?></span>
                                        </td>
                                    </tr>
                                    <?php if ($indents[0]['inventory_indent']['status'] == 2 || $indents[0]['inventory_indent']['status'] == 3 || $indents[0]['inventory_indent']['status'] == 4) { ?>
                                        <tr>
                                            <td class=" address-table address-table-td"><span><strong>VEHICLE NO
                                                        :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['vehicle_no']); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class=" address-table address-table-td"><span><strong>REMARKS
                                                        :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['remarks']); ?></span>
                                            </td>
                                        </tr>
                                    <?php }
                                    if ($indents[0]['inventory_indent']['status'] == 3 || $indents[0]['inventory_indent']['status'] == 4) { ?>
                                        <tr>
                                            <td class=" address-table address-table-td"><span><strong>CONSIGNMENT ID
                                                        :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['consignment_id']); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class=" address-table address-table-td"><span><strong>SERIAL NO
                                                        :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['serial_number']); ?></span>
                                            </td>
                                        </tr>
                                    <?php }
                                    if ($indents[0]['inventory_indent']['status'] == 4) { ?>

                                        <tr>
                                            <td class=" address-table address-table-td"><span><strong>INVOICE NO
                                                        :</strong>&nbsp<?php print_r($indents[0]['inventory_indent']['invoice_no']); ?></span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </td>
                            <td class="table-data" style="width:25%;">
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>INDENT ID:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;">
                                    <?php print_r($indents[0]['inventory_indent']['indent_id']); ?>
                                </div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Total Amount:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;">
                                    <?php
                                        $disp_total = (!empty($poData) && isset($poData['stockin_amount']) && $poData['stockin_amount'] > 0)
                                            ? $poData['stockin_amount']
                                            : $indents[0]['inventory_indent']['total_amount'];
                                        print_r($disp_total);
                                    ?>
                                </div>
                                <?php if ($indents[0]['inventory_indent']['status'] == 2 || $indents[0]['inventory_indent']['status'] == 3 || $indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Approved Amount:</b>
                                    </div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;">
                                        <?php
                                            $disp_approved = (!empty($poData) && isset($poData['approved_amount']) && $poData['approved_amount'] > 0)
                                                ? $poData['approved_amount']
                                                : $indents[0]['inventory_indent']['approved_amount'];
                                            print_r($disp_approved);
                                        ?>
                                    </div>
                                <?php }
                                if ($indents[0]['inventory_indent']['status'] == 3 || $indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Processed Amount:</b>
                                    </div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;">
                                        <?php
                                            $disp_processed = (!empty($poData) && isset($poData['processed_amount']) && $poData['processed_amount'] > 0)
                                                ? $poData['processed_amount']
                                                : $indents[0]['inventory_indent']['processed_amount'];
                                            print_r($disp_processed);
                                        ?>
                                    </div>
                                <?php }
                                if ($indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Stockin Amount:</b>
                                    </div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;">
                                        <?php print_r($indents[0]['inventory_indent']['stockin_amount']); ?>
                                    </div>
                                <?php } ?>
                            </td>

                            <td class="table-data" style="width:25%;">
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Status:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($status); ?>
                                </div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Date:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;">
                                    <?php print_r(date('Y-m-d H:i:s', strtotime($indents[0]['inventory_indent']['created']))); ?>
                                </div>
                                <?php
                                if ($indents[0]['inventory_indent']['status'] == 2 || $indents[0]['inventory_indent']['status'] == 3 || $indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Approved Date:</b>
                                    </div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;">
                                        <?php print_r(date('Y-m-d H:i:s', strtotime($indents[0]['inventory_indent']['approved_date']))); ?>
                                    </div>
                                <?php }
                                if ($indents[0]['inventory_indent']['status'] == 3 || $indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Processed Date:</b>
                                    </div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;">
                                        <?php print_r(date('Y-m-d H:i:s', strtotime($indents[0]['inventory_indent']['processed_date']))); ?>
                                    </div>
                                <?php }
                                if ($indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Stockin Date/Time:</b></div>
                                    <div style="border: 1px solid black;margin:0px;padding:10px;">
                                        <?php print_r(date('Y-m-d H:i:s', strtotime($indents[0]['inventory_indent']['processed_date']))); ?>
                                    </div>
                                <?php } ?>

                            </td>

                        </tr>
                    </table>
                    <br><br>
                    <table class="address-table" style="width:100%; margin:auto;">
                        <tr>
                            <td class="table-data" style="width:50%;">
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Detail of receiver:</b>
                                </div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php
                                print_r($indents[0]['inventory_indent']['location_name'] . '<br>');
                                print_r($indents[0][0]['print_line1'] . '<br>');
                                print_r($indents[0][0]['print_line2'] . '<br>');
                                print_r($indents[0][0]['print_line3']);
                                ?>
                                </div>
                            </td>
                            <td class="table-data" style="width:50%;">
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><b>Detail of
                                        Consignee:</b></div>
                                <div style="border: 1px solid black;margin:0px;padding:10px;"><?php print_r($indents[0]['inventory_indent']['warehouse_name'] . '<br>');
                                print_r($indents[0][0]['warehouse_print_line1'] . '<br>');
                                print_r($indents[0][0]['warehouse_print_line2'] . '<br>');
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
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Tax %</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Total Tax value</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Total Amount</center></th>
                                    
                                    <?php if ($indents[0]['inventory_indent']['status'] == 2) { ?>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Quantity</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Price</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Tax value</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Approved Amount</center></th>
                                    <?php } ?>
                                    <?php if ($indents[0]['inventory_indent']['status'] == 3) { ?>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Quantity</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Price</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Tax value</center></th>
                                    <th class="table-data" style="border: 1px solid black;margin:0px;padding:10px;"><center>Processed Amount</center></th>
                                    <?php } ?>
                                    <?php if ($indents[0]['inventory_indent']['status'] == 4) { ?>
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
                            $x = 1;
                            $baseSum = $taxSum = $totalSum = 0;

                            $approvedBaseSum = $approvedTaxSum = $approvedTotalSum = 0;
                            $processedBaseSum = $processedTaxSum = $processedTotalSum = 0;
                            $stockinBaseSum = $stockinTaxSum = $stockinTotalSum = 0;



                            foreach ($items as $item) {
                                $amount = $amount + $item['inventory_indent_items']['sku_amount'];
                                $sku_total_tax_value = $sku_total_tax_value + $item['inventory_indent_items']['sku_total_tax_value'];
                                $approved_total_tax_value = $approved_total_tax_value + $item['inventory_indent_items']['approved_tax_value'];
                                $approved_amount = $approved_amount + $item['inventory_indent_items']['approved_amount'];
                                $processed_total_tax_value = $processed_total_tax_value + $item['inventory_indent_items']['processed_tax_value'];
                                $processed_amount = $processed_amount + $item['inventory_indent_items']['processed_amount'];
                                $stockin_total_tax_value = $stockin_total_tax_value + $item['inventory_indent_items']['stockin_tax_value'];
                                $stockin_amount = $stockin_amount + $item['inventory_indent_items']['stockin_amount'];
                                ?>
                                <tr style="color:black;">
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($x); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_name']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_unit_name']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_quantity']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_price']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_total_tax_percentage']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_total_tax_value']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['sku_amount']); ?></center></td>
                                    <?php if ($indents[0]['inventory_indent']['status'] == 2) { ?>
                                    
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_quantity']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_price']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_tax_value']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_amount']); ?></center></td>
                                    <?php } ?>
                                    <?php if ($indents[0]['inventory_indent']['status'] == 3) { ?>
                                    
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['processed_quantity']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_price']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['processed_tax_value']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['processed_amount']); ?></center></td>
                                    <?php } ?>
                                    <?php if ($indents[0]['inventory_indent']['status'] == 4) { ?>
                                    
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['stockin_quantity']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['approved_price']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['stockin_tax_value']); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($item['inventory_indent_items']['stockin_amount']); ?></center></td>
                                    <?php } ?>
                                </tr>
                            <?php $x++;
                            }
                            ?>
                            <tr style="color:black;">
                                    <td colspan="6"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($sku_total_tax_value); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($amount); ?></center></td>
                                    <?php if ($indents[0]['inventory_indent']['status'] == 2) { ?>
                                    
                                    <td colspan="2"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($approved_total_tax_value); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($approved_amount); ?></center></td>
                                    <?php
                                    $amount = $approved_amount;

                                    } ?>
                            <?php if ($indents[0]['inventory_indent']['status'] == 3) { ?>
                                    
                                    <td colspan="2"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($processed_total_tax_value); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($processed_amount); ?></center></td>
                                    <?php
                                    $amount = $processed_amount;

                            } ?>
                            <?php if ($indents[0]['inventory_indent']['status'] == 4) { ?>
                                    
                                    <td colspan="2"></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($stockin_total_tax_value); ?></center></td>
                                    <td style="border: 1px solid black;margin:0px;padding:10px;"><center><?php print_r($stockin_amount); ?></center></td>
                                    <?php
                                    $amount = $stockin_amount;

                            } ?>
                                </tr>
                            <?php
                            $totalamount = $amount;
                            $amount_after_decimal = round($amount - ($num = floor($amount)), 2) * 100;
                            // Check if there is any number after decimal
                            $amt_hundred = null;
                            $count_length = strlen($num);
                            $x = 0;
                            $string = array();
                            $change_words = array(
                                0 => '',
                                1 => 'One',
                                2 => 'Two',
                                3 => 'Three',
                                4 => 'Four',
                                5 => 'Five',
                                6 => 'Six',
                                7 => 'Seven',
                                8 => 'Eight',
                                9 => 'Nine',
                                10 => 'Ten',
                                11 => 'Eleven',
                                12 => 'Twelve',
                                13 => 'Thirteen',
                                14 => 'Fourteen',
                                15 => 'Fifteen',
                                16 => 'Sixteen',
                                17 => 'Seventeen',
                                18 => 'Eighteen',
                                19 => 'Nineteen',
                                20 => 'Twenty',
                                30 => 'Thirty',
                                40 => 'Forty',
                                50 => 'Fifty',
                                60 => 'Sixty',
                                70 => 'Seventy',
                                80 => 'Eighty',
                                90 => 'Ninety'
                            );
                            $here_digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
                            while ($x < $count_length) {
                                $get_divider = ($x == 2) ? 10 : 100;
                                $amount = floor($num % $get_divider);
                                $num = floor($num / $get_divider);
                                $x += $get_divider == 10 ? 1 : 2;
                                if ($amount) {
                                    $add_plural = (($counter = count($string)) && $amount > 9) ? 's' : null;
                                    $amt_hundred = ($counter == 1 && $string[0]) ? ' and ' : null;
                                    $string[] = ($amount < 21) ? $change_words[$amount] . ' ' . $here_digits[$counter] . $add_plural . ' 
                               ' . $amt_hundred : $change_words[floor($amount / 10) * 10] . ' ' . $change_words[$amount % 10] . ' 
                               ' . $here_digits[$counter] . $add_plural . ' ' . $amt_hundred;
                                } else
                                    $string[] = null;
                            }
                            $implode_to_Rupees = implode('', array_reverse($string));
                            $get_paise = ($amount_after_decimal > 0) ? "And " . ($change_words[$amount_after_decimal / 10] . " 
                           " . $change_words[$amount_after_decimal % 10]) . ' Paise' : '';
                            $amountinwords = ($implode_to_Rupees ? $implode_to_Rupees . 'Rupees ' : '') . $get_paise;
                            ?>
                        </tbody>
                    </table>
                    -->
                    <!-- ================= ITEMS TABLE ================= -->

                    <table class="address-table" style="width:100%; margin:auto;">
                        <thead>
                            <tr style="font-size:10px;">
                                <th style="border:1px solid black;padding:8px;">SR</th>
                                <th style="border:1px solid black;padding:8px;">Product</th>
                                <th style="border:1px solid black;padding:8px;">Unit</th>

                                <th style="border:1px solid black;padding:8px;">Qty</th>
                                <th style="border:1px solid black;padding:8px;">Price</th>
                                <th style="border:1px solid black;padding:8px;">Tax %</th>

                                <th style="border:1px solid black;padding:8px;">Base</th>
                                <th style="border:1px solid black;padding:8px;">Tax</th>
                            <th style="border:1px solid black;padding:8px;">Item Disc</th>
                            <th style="border:1px solid black;padding:8px;">Excise (₹)</th>
                            <th style="border:1px solid black;padding:8px;">Addl.Ex (₹)</th>
                            <th style="border:1px solid black;padding:8px;">VAT (₹)</th>
                            <th style="border:1px solid black;padding:8px;">TCS (₹)</th>
                            <th style="border:1px solid black;padding:8px;">Total</th>

                                <?php if ($indents[0]['inventory_indent']['status'] >= 2) { ?>
                                    <th style="border:1px solid black;padding:8px;">Approved Price</th>
                                    <th style="border:1px solid black;padding:8px;">Approved Qty</th>
                                    <th style="border:1px solid black;padding:8px;">Approved Base</th>
                                    <th style="border:1px solid black;padding:8px;">Approved Tax</th>
                                    <th style="border:1px solid black;padding:8px;">Approved Total</th>
                                <?php } ?>

                                <?php if ($indents[0]['inventory_indent']['status'] >= 3) { ?>
                                    <th style="border:1px solid black;padding:8px;">Processed Price</th>
                                    <th style="border:1px solid black;padding:8px;">Processed Qty</th>
                                    <th style="border:1px solid black;padding:8px;">Processed Base</th>
                                    <th style="border:1px solid black;padding:8px;">Processed Tax</th>
                                    <th style="border:1px solid black;padding:8px;">Processed Total</th>
                                <?php } ?>

                                <?php if ($indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <th style="border:1px solid black;padding:8px;">Stockin Qty</th>
                                    <th style="border:1px solid black;padding:8px;">Stockin Base</th>
                                    <th style="border:1px solid black;padding:8px;">Stockin Tax</th>
                                    <th style="border:1px solid black;padding:8px;">Stockin Total</th>
                                <?php } ?>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            $i = 1;

                            /* ===== TOTAL VARIABLES ===== */
                        $baseSum = $taxSum = $totalSum = 0;
                        $itemDiscountTotal = 0;
                        $exciseTotal = $addlExciseTotal = $vatTotal = $tcsTotal = 0;

                        $approvedBaseSum = $approvedTaxSum = $approvedTotalSum = 0;
                        $processedBaseSum = $processedTaxSum = $processedTotalSum = 0;
                        $stockinBaseSum = $stockinTaxSum = $stockinTotalSum = 0;

                            foreach ($items as $item):

                                $it = $item['inventory_indent_items'];

                                $price = $it['sku_price'];
                                $qty = $it['sku_quantity'];
                                $taxP = $it['sku_total_tax_percentage'];

                                /* ---------- ORIGINAL ---------- */
                                $base = $price * $qty;
                                $tax = $it['sku_total_tax_value'];
                                $total = $it['sku_amount'];

                                $baseSum += $base;
                                $taxSum += $tax;
                                $totalSum += $total;


                                /* ---------- APPROVED ---------- */
                                $aq = $it['approved_quantity'];
                                $ap = $it['approved_price'];

                                $ab = $aq * $ap;
                                $at = $it['approved_tax_value'];
                                $atotal = $it['approved_amount'];

                                $approvedBaseSum += $ab;
                                $approvedTaxSum += $at;
                                $approvedTotalSum += $atotal;


                                /* ---------- PROCESSED ---------- */
                                $pq = $it['processed_quantity'];

                                $pb = $pq * $ap;
                                $pt = $it['processed_tax_value'];
                                $ptotal = $it['processed_amount'];

                                $processedBaseSum += $pb;
                                $processedTaxSum += $pt;
                                $processedTotalSum += $ptotal;


                                /* ---------- STOCKIN ---------- */
                                $sq = $it['stockin_quantity'];

                                $sb = $sq * $ap;
                                $st = $it['stockin_tax_value'];
                                $stotal = $it['stockin_amount'];

                                $stockinBaseSum += $sb;
                                $stockinTaxSum += $st;
                                $stockinTotalSum += $stotal;
                                ?>

                                <tr style="font-size:10px;">

                                    <td style="border:1px solid black;padding:6px;"><?= $i++ ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= $it['sku_name'] ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= $it['sku_unit_name'] ?></td>

                                    <td style="border:1px solid black;padding:6px;"><?= $qty ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= number_format($price, 2) ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= $taxP ?>%</td>

                                    <td style="border:1px solid black;padding:6px;"><?= number_format($base, 2) ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= number_format($tax, 2) ?></td>
                                    <?php
                                $itSkuId2 = $it['sku_id'] ?? 0;
                                $piDisc2 = !empty($poItemMap[$itSkuId2]) ? $poItemMap[$itSkuId2] : [];
                                $iDiscVal2 = !empty($piDisc2['sku_discount_value']) ? $piDisc2['sku_discount_value'] : 0;
                                $iDiscType2 = !empty($piDisc2['sku_discount_type']) ? $piDisc2['sku_discount_type'] : '';
                                $itemDiscountTotal += (float) $iDiscVal2;
                                
                                // Get Excise, Addl.Excise, VAT, TCS from poItemMap
                                $exciseAmt = !empty($piDisc2['excise_duty_amt']) ? (float)$piDisc2['excise_duty_amt'] : 0;
                                $addlExciseAmt = !empty($piDisc2['addl_excise_duty_amt']) ? (float)$piDisc2['addl_excise_duty_amt'] : 0;
                                $vatAmt = !empty($piDisc2['vat_amt']) ? (float)$piDisc2['vat_amt'] : 0;
                                $tcsAmt = !empty($piDisc2['tcs_amt']) ? (float)$piDisc2['tcs_amt'] : 0;
                                $exciseTotal += $exciseAmt;
                                $addlExciseTotal += $addlExciseAmt;
                                $vatTotal += $vatAmt;
                                $tcsTotal += $tcsAmt;
                                ?>
                                    <td style="border:1px solid black;padding:6px;">
                                        <?= number_format((float) $iDiscVal2, 2); ?>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;"><?= $exciseAmt > 0 ? number_format($exciseAmt,2) : '—' ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= $addlExciseAmt > 0 ? number_format($addlExciseAmt,2) : '—' ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= $vatAmt > 0 ? number_format($vatAmt,2) : '—' ?></td>
                                    <td style="border:1px solid black;padding:6px;"><?= $tcsAmt > 0 ? number_format($tcsAmt,2) : '—' ?></td>
                                    <td style="border:1px solid black;padding:6px;"><b><?= number_format($total, 2) ?></b>
                                    </td>

                                    <?php if ($indents[0]['inventory_indent']['status'] >= 2) { ?>
                                        <td style="border:1px solid black;padding:6px;"><?= $ap ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= $aq ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= number_format($ab, 2) ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= number_format($at, 2) ?></td>
                                        <td style="border:1px solid black;padding:6px;"><b><?= number_format($atotal, 2) ?></b>
                                        </td>
                                    <?php } ?>

                                    <?php if ($indents[0]['inventory_indent']['status'] >= 3) { ?>
                                        <td style="border:1px solid black;padding:6px;"><?= $ap ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= $pq ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= number_format($pb, 2) ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= number_format($pt, 2) ?></td>
                                        <td style="border:1px solid black;padding:6px;"><b><?= number_format($ptotal, 2) ?></b>
                                        </td>
                                    <?php } ?>

                                    <?php if ($indents[0]['inventory_indent']['status'] == 4) { ?>
                                        <td style="border:1px solid black;padding:6px;"><?= $sq ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= number_format($sb, 2) ?></td>
                                        <td style="border:1px solid black;padding:6px;"><?= number_format($st, 2) ?></td>
                                        <td style="border:1px solid black;padding:6px;"><b><?= number_format($stotal, 2) ?></b>
                                        </td>
                                    <?php } ?>

                                </tr>

                            <?php endforeach; ?>


                            <!-- ================= TOTAL ROW ================= -->

                            <tr style="font-weight:bold;background:#f5f5f5;font-size:11px;">

                                <td colspan="6" style="border:1px solid black;padding:6px;text-align:right;">TOTALS</td>

                                <td style="border:1px solid black;padding:6px;"><?= number_format($baseSum, 2) ?></td>
                                <td style="border:1px solid black;padding:6px;"><?= number_format($taxSum, 2) ?></td>
                                <td style="border:1px solid black;padding:6px;">
                                    <?= number_format($itemDiscountTotal, 2) ?>
                                </td>
                                <td style="border:1px solid black;padding:6px;"><?= number_format($exciseTotal,2) ?></td>
                                <td style="border:1px solid black;padding:6px;"><?= number_format($addlExciseTotal,2) ?></td>
                                <td style="border:1px solid black;padding:6px;"><?= number_format($vatTotal,2) ?></td>
                                <td style="border:1px solid black;padding:6px;"><?= number_format($tcsTotal,2) ?></td>
                                <td style="border:1px solid black;padding:6px;"><?= number_format($totalSum, 2) ?></td>

                                <?php if ($indents[0]['inventory_indent']['status'] >= 2) { ?>
                                    <td colspan="2" style="border:1px solid black;padding:6px;"></td>
                                    <td style="border:1px solid black;padding:6px;">
                                        <?= number_format($approvedBaseSum, 2) ?>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;"><?= number_format($approvedTaxSum, 2) ?>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;">
                                        <?= number_format($approvedTotalSum, 2) ?>
                                    </td>
                                    <?php $totalamount = $approvedTotalSum; ?>
                                <?php } ?>

                                <?php if ($indents[0]['inventory_indent']['status'] >= 3) { ?>
                                    <td colspan="2" style="border:1px solid black;padding:6px;"></td>
                                    <td style="border:1px solid black;padding:6px;">
                                        <?= number_format($processedBaseSum, 2) ?>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;">
                                        <?= number_format($processedTaxSum, 2) ?>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;">
                                        <?= number_format($processedTotalSum, 2) ?>
                                    </td>
                                    <?php $totalamount = $processedTotalSum; ?>
                                <?php } ?>

                                <?php if ($indents[0]['inventory_indent']['status'] == 4) { ?>
                                    <td style="border:1px solid black;padding:6px;"></td>
                                    <td style="border:1px solid black;padding:6px;"><?= number_format($stockinBaseSum, 2) ?>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;"><?= number_format($stockinTaxSum, 2) ?>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;">
                                        <?= number_format($stockinTotalSum, 2) ?>
                                    </td>
                                    <?php $totalamount = $stockinTotalSum; ?>
                                <?php } ?>

                            </tr>

                            <?php 
                                // Correct total column count calculation matching the header
                                $totalCols = 14; // Base columns (SR to Total)
                                if ($indents[0]['inventory_indent']['status'] >= 2) $totalCols +=5;
                                if ($indents[0]['inventory_indent']['status'] >=3) $totalCols +=5;
                                if ($indents[0]['inventory_indent']['status'] ==4) $totalCols +=4;
                                
                                $spanCols = $totalCols -2;
                                
                                // Determine correct grand total value based on status - use the indent's amount that already has discount/charges
                                if (!empty($poData) && isset($poData['stockin_amount']) && $poData['stockin_amount'] > 0) {
                                    $finalGrandTotal = (float)$poData['stockin_amount'];
                                    $totalamount = $finalGrandTotal;
                                } elseif ($indents[0]['inventory_indent']['status'] == 4) {
                                    $finalGrandTotal = (float)$indents[0]['inventory_indent']['stockin_amount'];
                                    $totalamount = $finalGrandTotal;
                                } elseif (!empty($poData) && isset($poData['approved_amount']) && $poData['approved_amount'] > 0) {
                                    $finalGrandTotal = (float)$poData['approved_amount'];
                                    $totalamount = $finalGrandTotal;
                                } elseif ($indents[0]['inventory_indent']['status'] >= 3) {
                                    $finalGrandTotal = (float)$indents[0]['inventory_indent']['processed_amount'];
                                    $totalamount = $finalGrandTotal;
                                } elseif ($indents[0]['inventory_indent']['status'] >= 2) {
                                    $finalGrandTotal = (float)$indents[0]['inventory_indent']['approved_amount'];
                                    $totalamount = $finalGrandTotal;
                                } else {
                                    $finalGrandTotal = (float)$totalamount;
                                }
                                ?>
                                <?php if (!empty($poData)): ?>
                                <?php if (!empty($poData['discount_value']) && $poData['discount_value'] > 0): ?>
                                    <tr style="font-size:11px;">
                                        <td colspan="<?= $spanCols ?>" style="border:none;"></td>
                                        <td style="border:1px solid black;padding:6px;text-align:right;background:#f9f9f9;">
                                            <b>Order Discount (<?= h($poData['discount_type'] ?? 'flat') ?>):</b>
                                        </td>
                                        <td
                                            style="border:1px solid black;padding:6px;color:red;font-weight:bold;text-align:right;">
                                            - <?= number_format((float) $poData['discount_value'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($poData['charge']) && $poData['charge'] > 0): ?>
                                    <tr style="font-size:11px;">
                                        <td colspan="<?= $spanCols ?>" style="border:none;"></td>
                                        <td style="border:1px solid black;padding:6px;text-align:right;background:#f9f9f9;">
                                            <b>Charges:</b>
                                        </td>
                                        <td style="border:1px solid black;padding:6px;font-weight:bold;text-align:right;">+
                                            <?= number_format((float) $poData['charge'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr style="font-size:11px;background:#f0f4f8;">
                                    <td colspan="<?= $spanCols ?>" style="border:none;background:#ffffff;"></td>
                                    <td style="border:1px solid black;padding:6px;text-align:right;"><b>Grand Total:</b>
                                    </td>
                                    <td
                                        style="border:1px solid black;padding:6px;text-align:right;font-weight:bold;color:#000;">
                                        <?= number_format((float) $finalGrandTotal, 2) ?>
                                    </td>
                                </tr>
                                <tr style="font-size:11px;">
                                    <td colspan="<?= $spanCols ?>" style="border:none;"></td>
                                    <td style="border:1px solid black;padding:6px;text-align:right;background:#f9f9f9;">
                                        <b>Invoice No:</b>
                                    </td>
                                    <td style="border:1px solid black;padding:6px;text-align:right;">
                                        <?= h($poData['invoice_no'] ?? '') ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>


                    <?php
                    if (!empty($poData) && isset($poData['stockin_amount'])) {
                        $amount = (float) $poData['stockin_amount'];
                    } else {
                        $amount = (float) $totalamount;
                    }
                    $totalamount = $amount;
                    $amount_after_decimal = round($amount - ($num = floor($amount)), 2) * 100;
                    $amt_hundred = null;
                    $count_length = strlen((string) $num);
                    $x = 0;
                    $string = array();
                    $change_words = array(
                        0 => '',
                        1 => 'One',
                        2 => 'Two',
                        3 => 'Three',
                        4 => 'Four',
                        5 => 'Five',
                        6 => 'Six',
                        7 => 'Seven',
                        8 => 'Eight',
                        9 => 'Nine',
                        10 => 'Ten',
                        11 => 'Eleven',
                        12 => 'Twelve',
                        13 => 'Thirteen',
                        14 => 'Fourteen',
                        15 => 'Fifteen',
                        16 => 'Sixteen',
                        17 => 'Seventeen',
                        18 => 'Eighteen',
                        19 => 'Nineteen',
                        20 => 'Twenty',
                        30 => 'Thirty',
                        40 => 'Forty',
                        50 => 'Fifty',
                        60 => 'Sixty',
                        70 => 'Seventy',
                        80 => 'Eighty',
                        90 => 'Ninety'
                    );
                    $here_digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
                    while ($x < $count_length) {
                        $get_divider = ($x == 2) ? 10 : 100;
                        $amt_floor = floor($num % $get_divider);
                        $num = floor($num / $get_divider);
                        $x += $get_divider == 10 ? 1 : 2;
                        if ($amt_floor) {
                            $add_plural = (($counter = count($string)) && $amt_floor > 9) ? 's' : null;
                            $amt_hundred = ($counter == 1 && $string[0]) ? ' and ' : null;
                            $string[] = ($amt_floor < 21) ? $change_words[$amt_floor] . ' ' . $here_digits[$counter] . $add_plural . ' ' . $amt_hundred : $change_words[floor($amt_floor / 10) * 10] . ' ' . $change_words[$amt_floor % 10] . ' ' . $here_digits[$counter] . $add_plural . ' ' . $amt_hundred;
                        } else {
                            $string[] = null;
                        }
                    }
                    $implode_to_Rupees = implode('', array_reverse($string));
                    $get_paise = ($amount_after_decimal > 0) ? "And " . ($change_words[$amount_after_decimal / 10] . " " . $change_words[$amount_after_decimal % 10]) . ' Paise' : '';
                    $amountinwords = ($implode_to_Rupees ? $implode_to_Rupees . 'Rupees ' : '') . $get_paise;
                    ?>

                    <br><br>
                    <table style="width:100% !important">
                        <tr style="color:black;">
                            <td class="address-table">
                                <p style="font-size:12px"><strong>Total Amount :</strong>
                                    <?php print_r($locationdetail['Location']['currency_symbol'] . ' ' . number_format($totalamount, 2)); ?>
                                </p>
                                <p style="font-size:12px"><strong>Amount in Words :</strong>
                                    <?php print_r($amountinwords); ?></p>
                            </td>
                        </tr>
                    </table>

                    <hr style="border: 0.5px solid black;border-collapse: collapse;">
                    <p style="font-size:13px;text-align:center;">This is Computer Generated Invoice</p>
                    <p style="font-size:13px;text-align:center;"><b>Print Date:</b> <?php print_r(date('d-m-Y')); ?></p>

                    <div style="width:100%; text-align:center;">
                        <p><?php print_r($locationdetail['Location']['footer_msg']); ?></p>
                    </div>
            </div>
        </div>
</body>

</html>