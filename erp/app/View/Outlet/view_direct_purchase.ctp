<style>
    .form-section-title {
        margin-top: 1rem;
        font-weight: 600;
        font-size: 1.25rem;
    }
    .readonly-input input {
        background-color: #f8f9fa;
    }
</style>

<div class="row align-items-center mb-3">
    <div class="col-sm-11">
        <h2 class="page-title">View Direct Purchase</h2>
    </div>
    <div class="col-sm-1 text-end">
        <a href="direct_purchase" class="btn btn-danger">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <?= $this->Form->input('po_ids', [
                            'label' => 'PO Id',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['p_o_id']
                        ]); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Location Name', [
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['location_name']
                        ]); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Vendor Name', [
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['vendor_name']
                        ]); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Total Amount', [
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['total_amount']
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$status = $indents[0]['inventory_purchase_order']['status'];

$columns = [
    4 => ['Sku Name', 'Sku Unit', 'Sku Price', 'Sku Quantity', 'Total Amount']
];
?>

<div class="card">
    <div class="card-body">
        <h4 class="form-section-title">Purchase Order Items</h4>

        <!-- Header -->
        <div class="row g-2 mb-2">
            <?php foreach ($columns[$status] as $label): ?>
                <div class="col">
                    <label class="form-label fw-bold small"><?= $label ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Rows -->
        <?php foreach ($items as $item): ?>
            <div class="row g-2 mb-2">
                <?php foreach ($columns[$status] as $field): ?>
                    <?php
                        $val = '';
                        switch ($field) {
                            case 'Sku Name': 
                                $val = $item['inventory_purchase_order_items']['sku_name']; 
                                break;

                            case 'Sku Unit': 
                                $val = $item['inventory_purchase_order_items']['sku_unit_name']; 
                                break;

                            case 'Sku Price': 
                                $val = $item['inventory_purchase_order_items']['sku_price']; 
                                break;

                            case 'Sku Quantity':
                                if ($status == 1) {
                                    echo '<div class="col">
                                        <input type="number" class="form-control po-quantity" 
                                            data-item-id="'. h($item['inventory_purchase_order_items']['id']) .'"
                                            data-max="'. h($item['inventory_purchase_order_items']['sku_quantity']) .'"
                                            max="'. h($item['inventory_purchase_order_items']['sku_quantity']) .'"
                                            value="'. h($item['inventory_purchase_order_items']['sku_quantity']) .'">
                                    </div>';
                                    continue 2;
                                }
                                $val = $item['inventory_purchase_order_items']['sku_quantity'];
                                break;

                            case 'Total Amount':
                                $val = $item['inventory_purchase_order_items']['sku_amount'];
                                break;
                        }
                    ?>
                    <div class="col">
                        <input type="text" class="form-control" value="<?= h($val) ?>" readonly />
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <br>
    </div>
</div>
