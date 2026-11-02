<div class="row">
	<div class="col-sm-12">
		<div class="page-title-box">
			<h2 class="page-title">Bill Wise Sales Report</h2>
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
							echo $this->Form->create('Search', ['url'=>'bill_wise_sales_report']); 
							
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

					<?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'bill_wise_sales_report_pdf', 'target'=>'_blank']); ?>
					<?php echo $this->Form->end(); ?>
				</div>

				<div class="table-responsive">
					<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
						<thead>
							<tr>
								<th>Bill Number</th>
								<th>Order ID</th>
								<th>Bill Date</th>
								<th>Bill Time</th>
								<th>Print Time</th>
								<th>Close Time</th>
								<th>Table Number</th>
								<th>Covers</th>
								<th>Total</th>
								<th>Discount</th>
								<th>Net Total</th>
								<th>Gross Bill Amount</th>
								<th>Total Service Charge</th>
                                <?php if (!empty($taxColumns)) : ?>
                                    <?php foreach ($taxColumns as $tc) : ?>
                                        <th><?= h($tc); ?></th>
                                    <?php endforeach; ?>
                                <?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php
                            foreach ($rows as $r):
                            $t = $r['t'];
                            ?>
								<tr>
									<td><?= h($t['bill_number']); ?></td>
									<td><?= h($t['order_id']); ?></td>
									<td><?= h($r[0]['bill_date']); ?></td>
									<td><?= h($r[0]['bill_time']); ?></td>
									<td><?= h($t['print_time']); ?></td>
									<td><?= h($r[0]['close_time']); ?></td>
									<td><?= h($r[0]['table_number']); ?></td>
									<td><?= h($r[0]['covers']); ?></td>
									<td><?= number_format($r[0]['total'], 2); ?></td>
									<td><?= number_format($r[0]['discount'], 2); ?></td>
									<td><?= number_format($r[0]['net_total'], 2); ?></td>
									<td><?= number_format($r[0]['gross_bill_amount'], 2); ?></td>
									<td><?= number_format($r[0]['total_service_charge'], 2); ?></td>
                                    <?php
                                        if (!empty($taxColumns)) {
                                            $rowTaxValues = array_fill_keys($taxColumns, 0.0);
                                            $tax_map = isset($r[0]['tax_map']) ? $r[0]['tax_map'] : '';
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
	var sdate=$("#startdate").val();
	var cashier=$("#cashier").val();
	window.open('<?php echo $this->base; ?>/report/bill_wise_sales_report_export?sdate='+sdate+'&cashier='+cashier, '_blank');
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