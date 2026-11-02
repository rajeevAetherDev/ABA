<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Damage</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box text-right">
            <a href="stock_damage">
                <button class="btn btn-danger" type="button">Back</button>
            </a>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <?= $this->Form->input('indent_ids', [
                            'label' => 'Damage ID',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_damage']['damage_id']
                        ]) ?>
                      
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Damage', [
                            'label' => 'Location Name',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_damage']['location_name']
                        ]) ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Damage', [
                            'label' => 'Damage Date',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_damage']['damage_date']
                        ]) ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('total_amount', [
                            'label' => 'Total Amount',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => number_format($indents[0]['inventory_damage']['amount'], 2)
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title mb-3">Damage Items</h4>
                <div class="">
                    <table  id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?= $item['inventory_damage_items']['item_name']; ?>
                                    </td>
                                    <td>
                                        <?= $item['inventory_damage_items']['unit_name']; ?>
                                    </td>
                                    <td>
                                        <?= $item['inventory_damage_items']['item_price']; ?>
                                    </td>
                                    <td>
                                        <?= $item['inventory_damage_items']['quantity']; ?>
                                    </td>
                                    <td>
                                        <?= $item['inventory_damage_items']['amount']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

