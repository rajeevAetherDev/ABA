<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Removed Service Charges Report</h2>
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
                            echo $this->Form->create('Search', ['url'=>'removed_service_charges_report']); 
                            
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

                    <?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'removed_service_charges_report_pdf', 'target'=>'_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <div class="table-responsive">
					<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Bill Number</th>
                                <th>Tax Name</th>
                                <th>Tax Amount</th>
                                <th>Removed At</th>
                                <th>Bill Print Time</th>
                                <th>Settled Time</th>
                                <th>Removed By(User Id)</th>
                                <th>Username</th>
                                <th>Waiter Username</th>
                                <th>Customer Name</th>
                                <th>Customer Mobile</th>
                                <th>Edit Bill Comment</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php 
                        // echo "<pre/>";
                        // print_r($removedChargesData);exit;
                        foreach ($removedChargesData as $r): 			
							$row = $r[0];
							$t = $r['t'];
							$tax = $r['tax'];
							$billNo = $t['bill_no'] ?? '';
                        ?>
                            <tr>
                                <td><?= h($row['report_date']); ?></td>
                                <td><?= h($billNo); ?></td>
                                <td><?= h($row['tax_name']); ?></td>
                                <td><?= number_format($row['tax_amount'], 2); ?></td>
                                <td><?= date('H:i:s A', strtotime($row['removed_at'])); ?></td>
                                <td><?= date('H:i:s A', strtotime($t['bill_print_time'])); ?></td>
                                <td><?= !empty($row['settled_time']) ? date('H:i:s A', strtotime($row['settled_time'])) : '-'; ?></td>
                                <td><?= h($row['removed_by_user']); ?></td>
                                <td><?= h($row['username']); ?></td>
                                <td><?= h($row['waiter_username'] ?: '-'); ?></td>
                                <td><?= h($row['customer_name'] ?: '-'); ?></td>
                                <td><?= h($row['customer_mobile'] ?: '-'); ?></td>
                                <td><?= h($row['edit_bill_comment'] ?: '-'); ?></td>
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
    var sdate=$("#startdate").val();
    var cashier=$("#cashier").val();
    window.open('<?php echo $this->base; ?>/report/removed_service_charges_report_export?sdate='+sdate+'&cashier='+cashier, '_blank');
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

