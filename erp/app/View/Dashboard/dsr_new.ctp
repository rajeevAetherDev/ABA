<!-- CORRECTED VIEW TEMPLATE -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">DSR Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php 
                        echo $this->Form->create('Search', array('url' => 'dsr_report')); 
                        
                        $options[''] = 'All Stores';
                        foreach($users as $user) {
                            $options[$user['Location']['id']] = $user['Location']['name'];
                        }
                        
                        echo $this->Form->input('cashier', array(
                            'div' => array('class' => 'form-group'), 
                            'label' => 'Select Store', 
                            'class' => 'form-control', 
                            'between' => '<span class="desc"></span><div class="controls">', 
                            'after' => '</div>',
                            'options' => $options,
                            'id' => 'cashier'
                        ));
                        echo $this->Form->input('startdate', array(
                            'div' => array('class' => 'form-group'), 
                            'label' => array('class' => 'form-label'), 
                            'class' => 'form-control calendarmultiple', 
                            'between' => '<span class="desc"></span><div class="controls">', 
                            'after' => '</div>', 
                            'label' => 'Select Date',
                            'id' => 'startdate',
                            'name' => 'daterange',
                            'required' => 'required',
                            'value' => $startdate
                        )); 
                        echo $this->Form->button('View', array('class' => 'btn btn-info', 'type' => 'submit'));
                        echo "&nbsp;" . $this->Form->button('Export', array('class' => 'btn btn-info', 'type' => 'button', 'id' => 'exportdata'));
                        echo "&nbsp;" . $this->Form->button('Print', array('class' => 'btn btn-info', 'type' => 'button', 'id' => 'btn-pdf'));
                        echo $this->Form->end(); 
                        ?>
                    </div>
                    <div style="height:30px; width: 100%" class="clearfix"></div>
                    <?php echo $this->Form->create('Report', array('id' => 'pdfform', 'url' => 'dsr_reportpdf', 'target' => '_blank')); ?>
                    <?php echo $this->Form->end(); ?>
                </div>
                
                <div style="height:30px; width: 100%" class="clearfix"></div>
                
                <div class="row">
                    <!-- LEFT COLUMN - Payment Data -->
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <h3>Payment Data</h3>
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <th>Store</th>
                                    <td><?php echo h($cashiername); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Collection</th>
                                    <!-- FIXED: Now uses correct total from SQL query (excludes no_charge) -->
                                    <td><?php echo number_format(round($payments[0][0]['total_amount'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Cash</th>
                                    <td><?php echo number_format(round($payments[0][0]['cash'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Card</th>
                                    <td><?php echo number_format(round($payments[0][0]['card'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>UPI</th>
                                    <td><?php echo number_format(round($payments[0][0]['upi'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Online</th>
                                    <td><?php echo number_format(round($payments[0][0]['online'], 2), 2); ?></td>
                                </tr>
                                <?php foreach($onlinepayments as $onlinepayment): ?>
                                <tr>
                                    <th><?php echo h($onlinepayment['order_payments']['type_value']); ?></th>
                                    <td><?php echo number_format(round($onlinepayment[0]['total_amount'], 2), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th>Storecard</th>
                                    <td><?php echo number_format(round($payments[0][0]['storecard'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Hold Bill</th>
                                    <td><?php echo number_format(round($payments[0][0]['hold'], 2), 2); ?></td>
                                </tr>
                                <!-- FIXED: Removed redundant "No Charge" row - now only shows "No Charge (Net)" -->
                                <tr>
                                    <th>No Charge (Net)</th>
                                    <td><?php echo number_format(round($payments[0][0]['no_charge'], 2), 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3><br>Taxes</h3>
                        <table id="example2" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <?php foreach($taxes as $tax): ?>
                                <tr>
                                    <th><?php echo h($tax['order_tax']['tax_name'] . '(' . $tax['order_tax']['tax_percentage'] . '%)'); ?></th>
                                    <td><?php echo number_format(round($tax[0]['tax_value'], 2), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <h3><br>Sales Aggregation</h3>
                        <table id="example3" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <th><?php echo h($sale['final']['kds_name'] . ' (' . $sale['final']['tax_percentage'] . '%)'); ?></th>
                                    <td><?php echo number_format(round($sale['final']['value'], 2), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <h3><br>NC Sales Aggregation</h3>
                        <table class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <?php foreach ($ncsales as $ncsale): ?>
                                <tr>
                                    <th><?php echo h($ncsale['final']['kds_name'] . ' (' . $ncsale['final']['tax_percentage'] . '%)'); ?></th>
                                    <td><?php echo number_format(round($ncsale['final']['value'], 2), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <h3><br>Dish Type Sales</h3>
                        <table class="display table table-hover table-condensed" width="100%">
                            <tbody>
                                <?php foreach ($dish_sales as $row): 
                                    $dishTypeMap = [
                                        0 => 'Food',
                                        1 => 'Beverage',
                                        2 => 'Liquor',
                                        3 => 'Others'
                                    ];
                                ?>
                                <tr>
                                    <th><?php echo $dishTypeMap[$row['cp']['dish_type']] ?? $row['cp']['dish_type']; ?></th>
                                    <td><?php echo number_format(round($row[0]['total_amount'], 2), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- RIGHT COLUMN - Total Data -->
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <h3>Total Data</h3>
                        <table id="example4" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <th>Subtotal</th>
                                    <td><?php echo number_format(round($transactionsubtotal[0][0]['price'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Discount</th>
                                    <td><?php echo number_format(round($transactionsubtotal[0][0]['discount'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Taxable Amount</th>
                                    <td><?php echo number_format(round($taxable[0][0]['total_taxable'] - $transactionsubtotal[0][0]['discount'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Non Taxable Amount</th>
                                    <td><?php echo number_format(round($nontaxable[0][0]['total_nontaxable'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>NC Taxable Amount</th>
                                    <td><?php echo number_format(round($nctaxable[0][0]['total_taxable'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>NC Non Taxable Amount</th>
                                    <td><?php echo number_format(round($ncnontaxable[0][0]['total_nontaxable'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>NC Net Sale</th>
                                    <td><?php echo number_format(round($ncnet_amount_query[0][0]['net_amount'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>NC Total Tax</th>
                                    <td><?php echo number_format(round($transactionsnc[0][0]['total_tax'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Service Charge</th>
                                    <td><?php echo number_format(round($transactions[0][0]['servicecharge'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Delivery Charge</th>
                                    <td><?php echo number_format(round($transactions[0][0]['delivery_charge'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Packing Charge</th>
                                    <td><?php echo number_format(round($transactions[0][0]['packing_charge'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Net Sale</th>
                                    <td><?php echo number_format(round($net_amount_query[0][0]['net_amount'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Tax</th>
                                    <td><?php echo number_format(round($transactions[0][0]['total_tax'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Final Amount</th>
                                    <td><?php echo number_format(round($transactions[0][0]['amount'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Round</th>
                                    <td><?php echo number_format(round($transactions[0][0]['round'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Bill Count</th>
                                    <td><?php echo number_format(round($transactions[0][0]['total_count'], 2), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total PAX</th>
                                    <td><?php echo number_format(round($transactions[0][0]['pax'], 2), 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>        
            </div>
        </div>
    </div>
</div>

<script>
    $("#exportdata").click(function() {
        var sdate = $("#startdate").val();
        var cashier = $("#cashier").val();
        window.open('<?php echo $this->base; ?>/dashboard/dsr_reportexport?sdate=' + sdate + '&cashier=' + cashier, '_blank');
    });

    $("#btn-pdf").click(function() {
        var pdfform = document.getElementById('pdfform');
        $('#pdfform').hide();
        var form_data = new FormData(pdfform);
        
        var sdate = $("#startdate").val();
        var cashier = $("#cashier").val();
        
        var sdateelement = document.createElement("input"); 
        sdateelement.value = sdate;
        sdateelement.name = "sdate";
        pdfform.appendChild(sdateelement); 
        
        var cashierelement = document.createElement("input"); 
        cashierelement.value = cashier;
        cashierelement.name = "cashier";
        pdfform.appendChild(cashierelement); 
        pdfform.submit(); 
    });
</script>

