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
        <h2 class="page-title">View Purchase Order</h2>
    </div>
    <div class="col-sm-1 text-end">
        <a href="po_approve_list" class="btn btn-danger">Back</a>
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
                        <?= $this->Form->input('Indent Id', [
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['internal_indent_id']
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
                        <?= '<br>'.$this->Form->input('Total Amount', [
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['total_amount']
                        ]); ?>
                    </div>
                    <?php if ($indents[0]['inventory_purchase_order']['approved_amount']) { ?>
                    <div class="col-sm-3">
                        <?= '<br>'.$this->Form->input('Approved Amount', [
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['approved_amount']
                        ]); ?>
                    </div>
                    <?php } ?>
                    
                    <?php if ($indents[0]['inventory_purchase_order']['approved_on']) { ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Approved Date', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['approved_on']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Approved By', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['approved_by_name']
                            ]); ?>
                        </div>
                    <?php } ?>
                    <?php if ($indents[0]['inventory_purchase_order']['processed_amount']) { ?>
                    <div class="col-sm-3">
                        <?= '<br>'.$this->Form->input('Processed Amount', [
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['inventory_purchase_order']['processed_amount']
                        ]); ?>
                    </div>
                    <?php } ?>
                    
                    <?php if ($indents[0]['inventory_purchase_order']['processed_on']) { ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Processed Date', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['processed_on']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Processed By', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['processed_by_name']
                            ]); ?>
                        </div>
                    <?php } ?>

                    <?php if ($indents[0]['inventory_purchase_order']['stockin_id']) { ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In Id', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['stockin_id']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In Amount', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['stockin_amount']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In Date', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['stockin_on']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In By', [
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $indents[0]['inventory_purchase_order']['stockin_by_name']
                            ]); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$status = $indents[0]['inventory_purchase_order']['status'];

$columns = [
    1 => ['Sku Name', 'Sku Unit', 'Sku Price', 'Sku Quantity', 'Total Amount'],
    2 => ['Sku Name', 'Sku Unit', 'Sku Price', 'Sku Quantity', 'Total Amount', 'Approved Quantity', 'Approved Amount'],
    3 => ['Sku Name', 'Sku Unit', 'Sku Price', 'Sku Quantity', 'Total Amount', 'Approved Quantity', 'Approved Amount', 'Processed Quantity', 'Processed Amount'],
    4 => ['Sku Name', 'Sku Unit', 'Sku Price', 'Sku Quantity', 'Total Amount', 'Approved Quantity', 'Approved Amount', 'Processed Quantity', 'Processed Amount', 'Stockin Quantity', 'Stockin Amount']
];
?>

<div class="card">
    <div class="card-body">
        <h4 class="form-section-title">Purchase Order Items</h4>

        <?php if (isset($columns[$status])): ?>
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
                                                value="'. h($item['inventory_purchase_order_items']['sku_quantity']) .'" disabled>
                                        </div>';
                                        continue 2;
                                    }
                                    if ($status == 2) {
                                        echo '<div class="col">
                                            <input type="number" class="form-control po-quantity" 
                                                data-item-id="'. h($item['inventory_purchase_order_items']['id']) .'"
                                                data-max="'. h($item['inventory_purchase_order_items']['sku_quantity']) .'"
                                                max="'. h($item['inventory_purchase_order_items']['sku_quantity']) .'"
                                                value="'. h($item['inventory_purchase_order_items']['sku_quantity']) .'" disabled>
                                        </div>';
                                        continue 2;
                                    } else {
                                        $val = $item['inventory_purchase_order_items']['sku_quantity'];
                                    }
                                    break;

                                case 'Total Amount':
                                    $val = ($status == 2) 
                                        ? $item['inventory_purchase_order_items']['sku_amount'] 
                                        : $item['inventory_purchase_order_items']['sku_amount'];
                                    break;

                                case 'Approved Quantity': 
                                    $val = $item['inventory_purchase_order_items']['approved_quantity']; 
                                    break;

                                case 'Approved Amount': 
                                    $val = $item['inventory_purchase_order_items']['approved_amount']; 
                                    break;

                                case 'Processed Quantity': 
                                    $val = $item['inventory_purchase_order_items']['processed_quantity']; 
                                    break;

                                case 'Processed Amount': 
                                    $val = $item['inventory_purchase_order_items']['processed_amount']; 
                                    break;

                                case 'Stockin Quantity': 
                                    $val = $item['inventory_purchase_order_items']['stockin_quantity']; 
                                    break;

                                case 'Stockin Amount': 
                                    $val = $item['inventory_purchase_order_items']['stockin_amount']; 
                                    break;
                            }
                        ?>
                        <div class="col">
                            <input type="text" class="form-control" value="<?= h($val) ?>" readonly />
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-danger">No data available for the current status (<?= h($status) ?>).</p>
        <?php endif; 
        ?>

        <!-- Action Button -->
        <div class="text-end mt-3">
            <?php if ($status == 1): ?>
                <button class="btn btn-success approve-status" data-id="<?= h($indents[0]['inventory_purchase_order']['p_o_id']) ?>">Approve PO</button>
            <?php elseif ($status == 2): ?>
                <button class="btn btn-success disabled">Approved</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $(document).on('input', '.po-quantity', function () {
        const max = parseFloat($(this).data('max'));
        const val = parseFloat($(this).val());

        if (val > max) {
            Swal.fire({
                toast: true,
                icon: 'warning',
                title: `You cannot enter more than ${max}`,
                showConfirmButton: false,
                timer: 2000
            });
            $(this).val(max);
        } else if (val < 0) {
            $(this).val(0);
        }
    });

    $('.approve-status').click(function () {
        var id = $(this).data('id');
        var items = [];

        $('.po-quantity').each(function () {
            const itemId = $(this).data('item-id');
            const quantity = $(this).val();
            items.push({ item_id: itemId, quantity: quantity });
        });

        $.ajax({
            url: '<?= $this->Html->url(["controller" => "Dashboard", "action" => "update_approve_po"]) ?>',
            type: 'POST',
            data: {
                _id: id,
                _items: items
            },
            dataType: 'json',
            success: function (response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Purchase Order approved successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'po_approve_list';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Failed to process Approve Order.',
                        confirmButtonText: 'Close'
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while Approving the PO.',
                    confirmButtonText: 'Close'
                });
                console.log(xhr.responseText);
            }
        });
    });
});
</script>