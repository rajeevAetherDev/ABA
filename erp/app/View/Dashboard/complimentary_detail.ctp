<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Complimentary Detail</h2>
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
                            echo $this->Form->create('Search', ['url'=>'complimentary_detail']); 
                            
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

                    <?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'complimentary_detail_pdf', 'target'=>'_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <div class="table-responsive">
					<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Store Name</th>
                                <th>Bill No</th>
                                <th>Bill Open Time</th>
                                <th>Bill Print Time</th>
                                <th>Bill Settle Time</th>
                                <th>Remarks</th>
                                <th>KOT No</th>
                                <th>Category</th>
                                <th>Item</th>
                                <th>Rate</th>
                                <th>Qty</th>
                                <th>Amount</th>
                                <th>Table</th>
                                <th>No of PAX</th>
                                <th>Customer Name</th>
                                <th>Customer Phone</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php foreach ($complimentaryData as $r): 			
							$d = $r[0];
							$t = $r['t'];
							$p = $r['p'];
							$h = $r['h']; ?>
                            <tr>
                                <td><?= h($d['report_date']); ?></td>
                                <td><?= h($d['store_name']); ?></td>
                                <td><?= h($t['bill_no']); ?></td>
                                <td><?= h($d['bill_open_time']); ?></td>
                                <td><?= h($t['bill_print_time']); ?></td>
                                <td><?= h($p['bill_settle_time']); ?></td>
                                <td><?= h($h['complimentary_comment']); ?></td>
                                <td><?= h($d['kot_numbers']); ?></td>
                                <td><?= h($h['category']); ?></td>
                                <td><?= h($h['item_name']); ?></td>
                                <td><?= number_format($h['rate'], 2); ?></td>
                                <td><?= $h['qty']; ?></td>
                                <td><?= number_format($d['amount'], 2); ?></td>
                                <td><?= h($h['tableno']); ?></td>
                                <td><?= h($t['total_pax']); ?></td>
                                <td><?= h($t['customer_name'] ?? ''); ?></td>
                                <td><?= h($t['customer_phone'] ?? ''); ?></td>
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
    window.open('<?php echo $this->base; ?>/dashboard/complimentary_detail_export?sdate='+sdate+'&cashier='+cashier, '_blank');
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
