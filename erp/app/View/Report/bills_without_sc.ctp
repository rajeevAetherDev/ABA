<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Service Charge Removal Report</h2>
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
                            echo $this->Form->create('Search', ['url'=>'bills_without_sc']); 
                            
                            $options = ['' => 'All Stores'];
                            foreach($users as $user){
                                $options[$user['Location']['id']] = $user['Location']['name'];
                            }

                            echo $this->Form->input('cashier', [
                                'div'=>['class'=>'form-group'],
                                'label'=>'Select Store',
                                'class'=>'form-control',
                                'options'=>$options,
                                'id'=>'cashier',
                                'value'=>$cashier_id
                            ]);

                            echo $this->Form->input('daterange', [
                                'div'=>['class'=>'form-group'],
                                'label'=>'Select Date',
                                'class'=>'form-control calendarmultiple',
                                'id'=>'startdate',
                                'name'=>'daterange',
                                'required'=>'required',
                                'value'=>$yourStartDateValue
                            ]);

                            echo $this->Form->button('View', ['class'=>'btn btn-info', 'type'=>'submit']);
                            echo "&nbsp;" . $this->Form->button('Excel', ['class'=>'btn btn-info', 'type'=>'button', 'id'=>'exportdata']);
                            echo "&nbsp;" . $this->Form->button('PDF', ['class'=>'btn btn-info', 'type'=>'button', 'id'=>'btn-pdf']);
                            echo $this->Form->end();
                        ?>
                    </div>

                    <?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'bills_without_sc_pdf', 'target'=>'_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

					<!-- SUMMARY CARDS -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <div class="card bg-light text-center p-2">
                            <small>Total Bills</small>
                            <strong><?= (int)$totalBills ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-warning text-center p-2">
                            <small>Total SC Removed (<?= $scRateLabel ?>)</small>
                            <strong><?= number_format($totalSC, 2) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="table-responsive">
                    <table id="example" class="display table table-hover table-condensed table-bordered" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order Number</th>
                                <th>Location</th>
                                <th>SC Removed</th>
                                <th>Payment Mode</th>
                                <th>SC Removed At</th>
                                <th>Bill Print Time</th>
                                <th>Bill Settle Time</th>
                                <th>Removed By</th>
                                <th>Cashier</th>
                                <th>Waiter</th>
                                <th>Customer Name</th>
                                <th>Customer Mobile</th>
                                <th>Comment</th>
                                <th>Guests</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Uncomment to debug raw data structure:
                        // echo "<pre>"; print_r($removedChargesData); exit;

                        if (!empty($removedChargesData)):
                            $i = 1;
                            foreach ($removedChargesData as $r):
                                // Normalize: CakePHP raw query returns all fields in $r[0]
                                $row = isset($r[0]) ? $r[0] : $r;
                                // print_r($r);exit;
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= h($r['t']['order_number']          ?? '-') ?></td>
                                <td><?= h($r['l']['location_name']         ?? '-') ?></td>
                                <td class="text-right">
                                    <strong><?= number_format((float)($row['sc_amount'] ?? 0), 2) ?></strong>
                                    <small class="text-muted">(<?= number_format((float)($row['store_sc_rate'] ?? 0), 1) ?>%)</small>
                                </td>
                                <td><?= h($r['pay']['payment_modes']         ?? '-') ?></td>
                                <td><?= !empty($row['sc_removed_time']) ? date('d-m-Y h:i:s A', strtotime($row['sc_removed_time'])) : '-' ?></td>
                                <td><?= !empty($r['t']['bill_print_time']) ? date('d-m-Y h:i:s A', strtotime($r['t']['bill_print_time'])) : '-' ?></td>
                                <td><?= !empty($r['pay']['bill_settle_time']) ? date('d-m-Y h:i:s A', strtotime($r['pay']['bill_settle_time'])) : '-' ?></td>
                                <td><?= h($row['removed_by_user']       ?? '-') ?></td>
                                <td><?= h($row['cashier_username']      ?? '-') ?></td>
                                <td><?= h($row['waiter_username']       ?? '-') ?></td>
                                <td><?= h($row['customer_name']         ?? '-') ?></td>
                                <td><?= h($row['customer_mobile']       ?? '-') ?></td>
                                <td><?= h($row['edit_bill_comment']     ?? '-') ?></td>
                                <td class="text-center"><?= h($r['t']['guest_count'] ?? '-') ?></td>
                            </tr>
                        <?php
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="15" class="text-center text-muted py-3">
                                    No records found for selected date range.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$("#exportdata").click(function(){
    var sdate=$("#startdate").val();
    var cashier=$("#cashier").val();
    window.open('<?php echo $this->base; ?>/report/bills_without_sc_export?sdate='+sdate+'&cashier='+cashier, '_blank');
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

