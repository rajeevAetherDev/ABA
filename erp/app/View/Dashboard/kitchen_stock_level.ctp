<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">KITCHEN Stock Level</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('StockLevel', ['url' => 'kitchen_stock_level']); ?>
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php

                        $options = [];
                            
                        if ($this->Session->read('Auth.User.role') == 'admin') {
                            $options[''] = 'All Stores';
                        }
                        foreach($users as $user){
                            $options[$user['Location']['id']] = $user['Location']['name'];
                        }
                        
                        echo $this->Form->input('cashier', array('div'=>array('class'=>'form-group'), 'label'=>'Select Store', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'cashier'));
                        
                        echo $this->Form->input('stocklevel_daterange', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'stocklevel_daterange','name'=>'daterange','required'=>'required'	, 'value' => $stocklevel_daterange )); 
                        
                        echo $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit', 'name' => 'submit_stocklevel']);
                        // echo "&nbsp;" . $this->Form->button('Export', ['class' => 'btn btn-info', 'type' => 'button', 'id' => 'exportdata']);
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
                                <th>Quantity</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $x = 1; foreach ($transactions as $transaction):     ?>
                                <tr>
                                    <td><?= h($x++) ?></td>
                                    <td><?= h($transaction['sl1']['sku_name']) ?></td>
                                    <td><?= h($transaction['sl1']['unit_name']) ?></td>
                                    <td><?= h($transaction['sl1']['sku_price']) ?></td>
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
            <h2 class="page-title">SKU Ledger</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('SkuLedger', ['url' => 'kitchen_stock_level']); ?>
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php
                        $options = [];
                        foreach ($skus as $sku) {
                            $options[$sku['inventory_skus']['id']] = $sku['inventory_skus']['name'];
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
                                <th>SKU Name</th>
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
                                    <td><?= h($txn['stock_ledger']['sku_name']) ?></td>
                                    <td><?= h($txn['stock_ledger']['record_id']) ?></td>
                                    <td><?= h($txn['stock_ledger']['record_type']) ?></td>
                                    <td><?= h($txn['stock_ledger']['quantity']) ?></td>
                                    <td><?= h($txn['stock_ledger']['actual_balance']) ?></td>
                                    <td><?= h($txn['stock_ledger']['quantity_type']) ?></td>
                                    <td><?= h($txn['stock_ledger']['created']) ?></td>
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
            const url = '<?= $this->base; ?>/dashboard/kitchen_stock_levelexport?sdate=' + encodeURIComponent(sdate);
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

