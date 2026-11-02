<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">OUTLET Stock Level</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('StockLevel', ['url' => 'stock_level']); ?>
                <div class="form-group row">
                    <div class="col-sm-8">
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <label>Select Date</label>
                                <input type="date" name="sel_date" class="form-control"
                                       value="<?= h($sel_date) ?>" max="<?= h($today) ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label>Select Time</label>
                                <input type="time" name="sel_time" class="form-control"
                                       value="<?= h($sel_time) ?>" required>
                            </div>
                        </div>
                        
                        <!-- Store selected date + time for export -->
                        <input type="hidden" id="stocklevel_daterange" value="<?= h($sel_date.' '.$sel_time.':00') ?>">
                        
                    <?php
                        
                        echo $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit', 'name' => 'submit_stocklevel']);
                        echo "&nbsp;" . $this->Form->button('Export', ['class' => 'btn btn-info', 'type' => 'button', 'id' => 'exportdata']);
                        ?>
                    </div>
                </div>
                <?php echo $this->Form->end(); ?>

                <div class="table-responsive mt-3">
                    <table id="stockTable" class="display table table-hover table-condensed" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>SKU Name</th>
                                <th>Unit Name</th>
                                <th>Price</th>
                                <th>Required Quantity</th>
                                <th>Available Quantity</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $x = 1; foreach ($transactions as $transaction):     ?>
                            <?php
                                $actual = $transaction['sl1']['actual_balance'];
                                $stock  = $transaction['s']['stock'] ?? 0; // avoid undefined index
                                $rowColor = ($actual < $stock) ? 'background-color:#ffcccc;' : 'background-color:#ccffcc;';
                            ?>
                                <tr style="<?= $rowColor ?>">
                                    <td><?= h($x++) ?></td>
                                    <td><?= h($transaction['sl1']['sku_name']) ?></td>
                                    <td><?= h($transaction['sl1']['unit_name']) ?></td>
                                    <td><?= h($transaction['sl1']['sku_price']) ?></td>
                                    <td><?= h($stock) ?></td>
                                    <td><?= h($transaction['sl1']['actual_balance']) ?></td>
                                    <td><?= number_format($transaction['sl1']['sku_price'] * $transaction['sl1']['actual_balance'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- SKU Ledger Section -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Semifinished Ledger</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('SkuLedger', ['url' => 'stock_level']); ?>
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php
                        $options = [];
                        foreach ($skus as $sku) {
                            $options[$sku['inventory_semifinished']['id']] = $sku['inventory_semifinished']['name'];
                        }
                        echo $this->Form->input('sku', [
                            'label' => 'Select SKU',
                            'class' => 'form-control',
                            'options' => $options,
                            'id' => 'sku'
                        ]);
                        echo '<br>'.$this->Form->input('skuledger_daterange', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'skuledger_daterange','name'=>'daterange','required'=>'required'	, 'value' => $skuledger_daterange )); 
                            
                        echo $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit', 'name' => 'submit_skutransactions']);
                        ?>
                    </div>
                </div>
                <?php echo $this->Form->end(); ?>

                <div class="table-responsive mt-3">
                    <table id="skuLedgerTable" class="display table table-hover table-condensed" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Semifinished Name</th>
                                <th>Vendor Name</th>
                                <th>Record ID</th>
                                <th>Record Type</th>
                                <th>Quantity</th>
                                <th>Actual Balance</th>
                                <th>Quantity Type</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $x = 1; foreach ($skutransactions as $txn): ?>
                                <tr>
                                    <td><?= h($x++) ?></td>
                                    <td><?= h($txn['sl']['sku_name']) ?></td>
                                    <td><?= $txn['ipo']['vendor_name'] ?? $txn['ii']['warehouse_name']  ?></td>
                                    <td><?= h($txn['sl']['record_id']) ?></td>
                                    <td><?= h($txn['sl']['record_type']) ?></td>
                                    <td><?= h($txn['sl']['quantity']) ?></td>
                                    <td><?= h($txn['sl']['actual_balance']) ?></td>
                                    <td><?= h($txn['sl']['quantity_type']) ?></td>
                                    <td><?= h($txn['sl']['created']) ?></td>
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
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('exportdata').addEventListener('click', function () {
        const sdate = document.getElementById('stocklevel_daterange').value.trim();
        if (sdate) {
            const url = '<?= $this->base; ?>/outlet/stock_levelexport?sdate=' + encodeURIComponent(sdate);
            window.open(url, '_blank');
        } else {
            alert('Please select a date first.');
        }
    });
});
</script>
<script>
$(document).ready(function() {
    $('#stockTable').DataTable({
        pageLength: 10,
        ordering: true,
        lengthChange: true
    });

    $('#skuLedgerTable').DataTable({
        pageLength: 10,
        ordering: true,
        lengthChange: true
    });
});
</script>

