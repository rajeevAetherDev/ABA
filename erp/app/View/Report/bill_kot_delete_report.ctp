<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Bill & KOT Delete Report</h2>
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
                            echo $this->Form->create('Search', ['url'=>'bill_kot_delete_report']); 
                            
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

                    <?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'bill_kot_delete_report_pdf', 'target'=>'_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <div class="table-responsive">
					<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>KOT Number</th>
                                <th>Item</th>
                                <th>billNo</th>
                                <th>Order Time</th>
                                <th>Delete Time</th>
                                <th>Rate</th>
                                <th>Qty</th>
                                <th>Total Tax Value</th>
                                <th>Table No</th>
                                <th>Comment</th>
                                <th>Username</th>
                                <th>Delete User</th>
                                <th>Settle User</th>
                                <th>Server</th>
                                <th>Customer Name</th>
                                <th>Customer Mobile</th>
                                <th>Customer Address</th>
                                <th>Type</th>
                                <th>Bill Settlement Status</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php
                        
                        foreach ($rows as $r): 			
							$d = $r[0]; 
							$t = $r['t']; 
							$h = $r['h']; 
                            
                        ?>
                            <tr>
                                <td><?= h($d['report_date']); ?></td>
                                <td><?= h($h['kot_no']); ?></td>
                                <td><?= h($h['item_name']); ?></td>
                                <td><?= h($t['bill_no']); ?></td>
                                <td><?= date('d-m-Y H:i:s A', strtotime($d['order_time'])); ?></td>
                                <td><?= date('d-m-Y H:i:s A', strtotime($h['delete_time'])); ?></td>
                                <td><?= number_format($h['rate'], 2); ?></td>
                                <td><?= h($h['qty']); ?></td>
                                <td><?= number_format($d['total_tax_value'], 2); ?></td>
                                <td><?= h($h['table_no']); ?></td>
                                <td><?= h($h['comment']); ?></td>
                                <td><?= h($d['username']); ?></td>
                                <td><?= h($d['delete_user']); ?></td>
                                <td><?= h($d['settle_user']); ?></td>
                                <td><?= h($d['server']); ?></td>
                                <td><?= h($d['customer_name']); ?></td>
                                <td><?= h($d['customer_mobile']); ?></td>
                                <td><?= h($d['customer_address']); ?></td>
                                <td><?= h($d['delete_type']); ?></td>
                                <td><?= h($d['bill_settlement_status']); ?></td>
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
    window.open('<?php echo $this->base; ?>/report/bill_kot_delete_report_export?sdate='+sdate+'&cashier='+cashier, '_blank');
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



