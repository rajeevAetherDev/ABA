<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Bill Item Detail Report</h2>
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
                            echo $this->Form->create('Search', ['url' => 'bill_item_detail_report']);

                            $options = ['' => 'All Stores'];
                            foreach ($users as $user) {
                                $options[$user['Location']['id']] = $user['Location']['name'];
                            }

                            echo $this->Form->input('cashier', [
                                'div' => ['class' => 'form-group'],
                                'label' => 'Select Store',
                                'class' => 'form-control',
                                'options' => $options,
                                'id' => 'cashier',
                                'value' => $cashier_id
                            ]);

                            echo $this->Form->input('daterange', [
                                'div' => ['class' => 'form-group'],
                                'label' => 'Select Date',
                                'class' => 'form-control calendarmultiple',
                                'id' => 'startdate',
                                'name' => 'daterange',
                                'required' => 'required',
                                'value' => $yourStartDateValue
                            ]);

                            echo $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit']);
                            echo "&nbsp;" . $this->Form->button('Excel', ['class' => 'btn btn-info', 'type' => 'button', 'id' => 'exportdata']);
                            echo "&nbsp;" . $this->Form->button('PDF', ['class' => 'btn btn-info', 'type' => 'button', 'id' => 'btn-pdf']);
                            echo $this->Form->end();
                        ?>
                    </div>

                    <?php echo $this->Form->create('Report', ['id' => 'pdfform', 'url' => 'bill_item_detail_report_pdf', 'target' => '_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <div class="table-responsive">
                    <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>State</th>
                                <th>Deployment</th>
                                <th>Bill Number</th>
                                <th>Order Id</th>
                                <th>Day Serial</th>
                                <th>Date</th>
                                <th>Tab</th>
                                <th>Table No</th>
                                <th>Open Time</th>
                                <th>Close Time</th>
                                <th>Category</th>
                                <th>Item Name</th>
                                <th>Item Number</th>
                                <th>Item Section</th>
                                <th>Item Classification</th>
                                <th>Rate</th>
                                <th>Qty</th>
                                <th>Amount</th>
                                <th>Discount Amount</th>
                                <th>Discount %</th>
                                <th>Taxable Amount</th>
                                <th>Net Amount</th>
                                <th>Source</th>
                                <?php if (!empty($taxColumns)) : ?>
                                    <?php foreach ($taxColumns as $tc) : ?>
                                        <th><?= h($tc); ?></th>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // echo "<pre/>";
                            // print_r($rows);exit;
                            foreach ($rows as $row): 
                            $r = $row[0];
                            $loc = $row['loc'];
                            $t = $row['t'];
                            $h = $row['h'];
                            // print_r($row);exit;
                            
                            ?>
                                <?php
                                    $openTime = !empty($r['open_time']) ? date('H:i:s A', strtotime($r['open_time'])) : '-';
                                    $closeTime = !empty($r['close_time']) ? date('H:i:s A', strtotime($r['close_time'])) : '-';
                                ?>
                                <tr>
                                    <td><?= h($r['state']); ?></td>
                                    <td><?= h($loc['deployment']); ?></td>
                                    <td><?= h($t['bill_no']); ?></td>
                                    <td><?= h($t['order_id']); ?></td>
                                    <td><?= h($t['day_serial']); ?></td>
                                    <td><?= h($r['bill_date']); ?></td>
                                    <td><?= h($r['tab_name']); ?></td>
                                    <td><?= h($h['table_no']); ?></td>
                                    <td><?= h($openTime); ?></td>
                                    <td><?= h($closeTime); ?></td>
                                    <td><?= h($h['category']); ?></td>
                                    <td><?= h($h['item_name']); ?></td>
                                    <td><?= h($h['item_number']); ?></td>
                                    <td><?= h($r['item_section']); ?></td>
                                    <td><?= h($r['item_classification']); ?></td>
                                    <td><?= number_format((float)$r['rate'], 2); ?></td>
                                    <td><?= number_format((float)$h['qty'], 2); ?></td>
                                    <td><?= number_format((float)$r['amount'], 2); ?></td>
                                    <td><?= number_format((float)$r['discount_amount'], 2); ?></td>
                                    <td><?= number_format((float)$r['discount_percent'], 2); ?></td>
                                    <td><?= number_format((float)$r['taxable_amount'], 2); ?></td>
                                    <td><?= number_format((float)$r['net_amount'], 2); ?></td>
                                    <td><?= h($r['source']); ?></td>
                                    <?php
                                        if (!empty($taxColumns)) {
                                            $rowTaxValues = array_fill_keys($taxColumns, 0.0);
                                            $tax_map = isset($r['tax_map']) ? $r['tax_map'] : '';
                                            if (!empty($tax_map)) {
                                                $pieces = explode('||', $tax_map);
                                                foreach ($pieces as $pc) {
                                                    if (trim($pc) === '') {
                                                        continue;
                                                    }
                                                    $parts = explode('|', $pc);
                                                    if (count($parts) >= 3) {
                                                        $tname = $parts[0];
                                                        $tperc = $parts[1];
                                                        $tval  = (float)$parts[2];
                                                        $key   = $tname . "(" . $tperc . "%)";
                                                        if (array_key_exists($key, $rowTaxValues)) {
                                                            $rowTaxValues[$key] += round($tval, 2);
                                                        }
                                                    }
                                                }
                                            }

                                            foreach ($taxColumns as $tc) {
                                                echo '<td>' . number_format($rowTaxValues[$tc], 2) . '</td>';
                                            }
                                        }
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$("#exportdata").click(function(){
    var sdate = $("#startdate").val();
    var cashier = $("#cashier").val();
    window.open('<?php echo $this->base; ?>/report/bill_item_detail_report_export?sdate=' + sdate + '&cashier=' + cashier, '_blank');
});

$("#btn-pdf").click(function(){
    var pdfform = document.getElementById('pdfform');
    $('#pdfform').hide();
    var sdate = $("#startdate").val();
    var cashier = $("#cashier").val();

    var sdateelement = document.createElement("input");
    sdateelement.value = sdate;
    sdateelement.name = "sdate";

    var cashierelement = document.createElement("input");
    cashierelement.value = cashier;
    cashierelement.name = "cashier";

    pdfform.appendChild(sdateelement);
    pdfform.appendChild(cashierelement);
    pdfform.submit();
});
</script>


