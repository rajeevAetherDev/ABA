<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Reprint Report</h2>
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
                            echo $this->Form->create('Search', ['url'=>'reprint_report']); 
                            
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

                    <?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'reprint_report_pdf', 'target'=>'_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <div class="table-responsive">
					<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Bill No</th>
                                <th>Originally Printed By</th>
                                <th>Re Print Time</th>
                                <th>Re Print Bill Amount</th>
                                <th>Actual Print Time</th>
                                <th>Original Bill Amount</th>
                                <th>User</th>
                                <th>Comment</th>
                                <th>Customer</th>
                                <th>Customer Number</th>
                            </tr>
                        </thead>
                        <tbody>
						<?php 
                        foreach ($reprintData as $r): 			
							$row = $r[0];
							$t = $r['t'];
                            
                            ?>
                            <tr>
                                <td><?= h($row['report_date']); ?></td>
                                <td><?= h($t['bill_no']); ?></td>
                                <td><?= h($row['originally_printed_by']); ?></td>
                                <td><?= date('d-m-Y H:i:s A', strtotime($row['reprint_time'])); ?></td>
                                <td><?= number_format($row['reprint_bill_amount'], 2); ?></td>
                                <td><?= date('d-m-Y H:i:s A', strtotime($t['original_print_time'])); ?></td>
                                <td><?= number_format($t['original_bill_amount'], 2); ?></td>
                                <td><?= h($row['user']); ?></td>
                                <td><?= h($row['comment']); ?></td>
                                <td><?= h($row['customer_name']); ?></td>
                                <td><?= h($row['customer_number']); ?></td>
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
    window.open('<?php echo $this->base; ?>/report/reprint_report_export?sdate='+sdate+'&cashier='+cashier, '_blank');
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

