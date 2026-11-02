<div class="row">
	<div class="col-sm-12">
		<div class="page-title-box">
			<h2 class="page-title">KOT Detail Report</h2>
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
							echo $this->Form->create('Search', ['url'=>'kot_detail_report']); 
							
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

					<?php echo $this->Form->create('Report', ['id'=>'pdfform', 'url'=>'kot_detail_report_pdf', 'target'=>'_blank']); ?>
					<?php echo $this->Form->end(); ?>
				</div>

				<div class="table-responsive">
					<table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
						<thead>
							<tr>
								<th>Date</th>
								<th>Bill Serial Number</th>
								<th>Edit Bill Comment</th>
								<th>KOT Number</th>
								<th>KOT Date</th>
								<th>KOT Time</th>
								<th>KOT Comment</th>
								<th>Item Name</th>
								<th>Rate</th>
								<th>Quantity</th>
								<th>Amount</th>
								<th>Item Comment</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rows as $bill): ?>
								<tr>
									<td><?= h($bill['report_date']); ?></td>
									<td><?= h($bill['bill_serial_number']); ?></td>
									<td><?= h($bill['edit_bill_comment'] ?: '-'); ?></td>
									<td><?= implode('<br>', array_map('h', $bill['kot_numbers'])); ?></td>
									<td><?= implode('<br>', array_map('h', $bill['kot_dates'])); ?></td>
									<td><?= implode('<br>', array_map('h', $bill['kot_times'])); ?></td>
									<td><?= implode('<br>', array_map('h', $bill['kot_comments'])); ?></td>
									<td><?= implode('<br>', array_map('h', $bill['item_names'])); ?></td>
									<td><?= implode('<br>', array_map('h', $bill['rates'])); ?></td>
									<td><?= implode('<br>', array_map('h', $bill['qtys'])); ?></td>
									<td>
										<?= implode('<br>', array_map('h', $bill['amounts'])); ?>
										<br><strong>Total: <?= number_format($bill['total_amount'], 2); ?></strong>
									</td>
									<td><?= implode('<br>', array_map('h', $bill['item_comments'])); ?></td>
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
	window.open('<?php echo $this->base; ?>/report/kot_detail_report_export?sdate='+sdate+'&cashier='+cashier, '_blank');
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