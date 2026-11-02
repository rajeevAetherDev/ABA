<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Internal Indent</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box text-right">
            <a href="internal_indent_list">
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
                    <div class="col-sm-4">
                        <?= $this->Form->input('indent_ids', [
                            'label' => 'Indent ID',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_internal_indent']['indent_id']
                        ]) ?>
                      
                    </div>
                    <div class="col-sm-4">
                        <?= $this->Form->input('location_name', [
                            'label' => 'Location Name',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_internal_indent']['location_name']
                        ]) ?>
                    </div>
                    <div class="col-sm-4">
                        <?= $this->Form->input('total_amount', [
                            'label' => 'Total Amount',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => number_format($indents[0]['inventory_internal_indent']['total_amount'], 2)
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
                <h4 class="page-title mb-3">Internal Indent Items</h4>
                <?php foreach ($items as $item): ?>
                    <div class="form-group row">
                        <div class="col-md-2">
                            <?= $this->Form->input('sku_name', [
                                'label' => 'Name',
                                'div' => false,
                                'class' => 'form-control',
                                'readonly' => true,
                                'value' => $item['inventory_internal_indent_items']['sku_name']
                            ]) ?>
                        </div>
                        <div class="col-md-2">
                            <?= $this->Form->input('unit_name', [
                                'label' => 'Unit',
                                'div' => false,
                                'class' => 'form-control',
                                'readonly' => true,
                                'value' => $item['inventory_internal_indent_items']['sku_unit_name']
                            ]) ?>
                        </div>
                        <div class="col-md-2">
                            <?= $this->Form->input('sku_price', [
                                'label' => 'Price',
                                'div' => false,
                                'class' => 'form-control',
                                'readonly' => true,
                                'value' => $item['inventory_internal_indent_items']['sku_price']
                            ]) ?>
                        </div>
                        <div class="col-md-2">
                            <?= $this->Form->input('sku_quantity', [
                                'label' => 'Quantity',
                                'div' => false,
                                'class' => 'form-control',
                                'readonly' => true,
                                'value' => $item['inventory_internal_indent_items']['sku_quantity']
                            ]) ?>
                        </div>
                        <div class="col-md-2">
                            <?= $this->Form->input('sku_tax', [
                                'label' => 'Tax',
                                'div' => false,
                                'class' => 'form-control',
                                'readonly' => true,
                                'value' => $item['inventory_internal_indent_items']['sku_total_tax_value']
                            ]) ?>
                        </div>
                        <div class="col-md-2">
                            <?= $this->Form->input('sku_quantity', [
                                'label' => 'Amount',
                                'div' => false,
                                'class' => 'form-control',
                                'readonly' => true,
                                'value' => $item['inventory_internal_indent_items']['sku_amount']
                            ]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
