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
        <a href="po_list" class="btn btn-danger">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <?= $this->Form->input('po_ids', [
                            'label'    => 'PO Id',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['p_o_id']
                        ]); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Indent Id', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['internal_indent_id']
                        ]); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Location Name', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['location_name']
                        ]); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Vendor Name', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['vendor_name']
                        ]); ?>
                    </div>

                    <div class="col-sm-3"><br>
                        <?php
                            $status = $indents[0]['inventory_purchase_order']['status'];

                            if ($status == 4) {
                                $charge   = $indents[0]['inventory_purchase_order']['stockin_charge'];
                                $disabled = true;
                            } elseif ($status == 3) {
                                $charge   = $indents[0]['inventory_purchase_order']['processed_charge'];
                                $disabled = true;
                            } elseif ($status == 2) {
                                $charge   = $indents[0]['inventory_purchase_order']['approved_charge'];
                                $disabled = false;
                            } else {
                                $charge   = $indents[0]['inventory_purchase_order']['charge'];
                                $disabled = false;
                            }

                            echo $this->Form->input('charge', [
                                'div'      => ['class' => 'form-group'],
                                'id'       => 'charge',
                                'label'    => 'Charges',
                                'class'    => 'form-control',
                                'required' => true,
                                'type'     => 'number',
                                'value'    => $charge,
                                'step'     => 'any',
                                'min'      => '0',
                                'disabled' => $disabled
                            ]);
                        ?>
                    </div>

                    <div class="col-sm-3">
                        <?= '<br>' . $this->Form->input('PO Date', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['po_date']
                        ]); ?>
                    </div>
                    <div class="col-sm-3">
                        <?= '<br>' . $this->Form->input('Total Amount', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['total_amount']
                        ]); ?>
                    </div>

                    <?php if ($indents[0]['inventory_purchase_order']['approved_amount']) { ?>
                    <div class="col-sm-3"><br>
                        <?= $this->Form->input('Approved Amount', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['approved_amount']
                        ]); ?>
                    </div>
                    <?php } ?>

                    <?php /* UPDATED: show approved_discount_value from its own PO header column */ ?>
                    <?php if ($indents[0]['inventory_purchase_order']['approved_discount_value']) { ?>
                    <div class="col-sm-3"><br>
                        <?= $this->Form->input('Approved Discount', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['approved_discount_value']
                        ]); ?>
                    </div>
                    <?php } ?>

                    <?php if ($indents[0]['inventory_purchase_order']['approved_on']) { ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Approved Date', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['approved_on']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Approved By', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['approved_by_name']
                            ]); ?>
                        </div>
                    <?php } ?>

                    <?php if ($indents[0]['inventory_purchase_order']['processed_amount']) { ?>
                    <div class="col-sm-3"><br>
                        <?= $this->Form->input('Processed Amount', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['processed_amount']
                        ]); ?>
                    </div>
                    <?php } ?>

                    <?php /* UPDATED: show processed_discount_value from its own PO header column */ ?>
                    <?php if ($indents[0]['inventory_purchase_order']['processed_discount_value']) { ?>
                    <div class="col-sm-3"><br>
                        <?= $this->Form->input('Processed Discount', [
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $indents[0]['inventory_purchase_order']['processed_discount_value']
                        ]); ?>
                    </div>
                    <?php } ?>

                    <?php if ($indents[0]['inventory_purchase_order']['processed_on']) { ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Processed Date', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['processed_on']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Processed By', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['processed_by_name']
                            ]); ?>
                        </div>
                    <?php } ?>

                    <?php if ($indents[0]['inventory_purchase_order']['stockin_id']) { ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In Id', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['stockin_id']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In Amount', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['stockin_amount']
                            ]); ?>
                        </div>
                        <?php /* UPDATED: show stockin_discount_value from its own PO header column */ ?>
                        <?php if ($indents[0]['inventory_purchase_order']['stockin_discount_value']) { ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stockin Discount', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['stockin_discount_value']
                            ]); ?>
                        </div>
                        <?php } ?>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In Date', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['stockin_on']
                            ]); ?>
                        </div>
                        <div class="col-sm-3"><br>
                            <?= $this->Form->input('Stock In By', [
                                'class'    => 'form-control',
                                'readonly' => 'readonly',
                                'value'    => $indents[0]['inventory_purchase_order']['stockin_by_name']
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

/*
 * COLUMN RULES PER STAGE
 * ──────────────────────────────────────────────────────────────────────
 * Status 1 (Approve stage):
 *   Editable: 'Approve Discount Type', 'Approve Discount'
 *   Pre-filled from: sku_discount_type / sku_discount  (original create values — for reference)
 *   Saved to:        approve_discount_type / approve_discount / approve_discount_value
 *
 * Status 2 (Process stage):
 *   Editable: 'Process Discount Type', 'Process Discount'
 *   Pre-filled from: approve_discount_type / approve_discount  (what approver set)
 *   Saved to:        process_discount_type / process_discount / process_discount_value
 *   Read-only view:  approve_discount_value shown as 'Approve Discount'
 *
 * Status 3 (read-only):
 *   Show approve_discount_value and process_discount_value
 *
 * Status 4 (Stockin stage):
 *   Editable: 'Stockin Discount Type', 'Stockin Discount'
 *   Pre-filled from: process_discount_type / process_discount  (what processor set)
 *   Saved to:        stockin_discount_type / stockin_discount / stockin_discount_value
 *   Read-only view:  approve_discount_value, process_discount_value, stockin_discount_value
 * ──────────────────────────────────────────────────────────────────────
 */
$columns = [
    // UPDATED: approve stage — editable 'Approve Discount Type' + 'Approve Discount'
    1 => [
        'Sku Name', 'Sku Unit', 'Sku Price', 'Sku Tax',
        'Excise/unit', 'Addl.Excise/unit', 'VAT %', 'TCS %',
        'Requested Quantity', 'Quantity',
        'Approve Discount Type', 'Approve Discount',    // editable, saved to approve_discount_*
        'Total Amount'
    ],
    // UPDATED: process stage — show approve result read-only, editable 'Process Discount Type' + 'Process Discount'
    2 => [
        'Sku Name', 'Sku Unit', 'Sku Price', 'Sku Tax',
        'Excise/unit', 'Addl.Excise/unit', 'VAT %', 'TCS %',
        'Requested Quantity', 'Quantity',
        'Process Discount Type', 'Process Discount',    // editable, saved to process_discount_*
        'Total Amount',
        'Approved Quantity', 'Approve Discount Value', 'Approved Amount'  // read-only, from approve stage
    ],
    // Status 3: process stage read-only results + editable Stockin Discount Type + Stockin Discount
    3 => [
        'Sku Name', 'Sku Unit', 'Sku Price', 'Sku Tax',
        'Excise/unit', 'Addl.Excise/unit', 'VAT %', 'TCS %',
        'Requested Quantity', 'Quantity', 'Total Amount',
        'Approved Quantity', 'Approve Discount Value', 'Approved Amount',
        'Processed Quantity', 'Process Discount Value', 'Processed Amount'
    ],
    // Status 4: fully read-only — all three stage discount values shown
    4 => [
        'Sku Name', 'Sku Unit', 'Sku Price', 'Sku Tax',
        'Excise/unit', 'Addl.Excise/unit', 'VAT %', 'TCS %',
        'Requested Quantity', 'Quantity', 'Total Amount',
        'Approved Quantity', 'Approve Discount Value', 'Approved Amount',
        'Processed Quantity', 'Process Discount Value', 'Processed Amount',
        'Stockin Quantity', 'Stockin Discount Value', 'Stockin Amount'
    ]
];
?>

<div class="card">
    <div class="card-body">
        <h4 class="form-section-title">Purchase Order Items</h4>

        <?php if (isset($columns[$status])): ?>
            <!-- Scrollable table wrapper -->
            <div style="overflow-x: auto; width: 100%;">
            <table class="table table-bordered table-hover table-sm align-middle mb-0" style="min-width: max-content;">
                <thead class="table-light">
                    <tr>
                        <?php foreach ($columns[$status] as $i => $label): ?>
                            <th class="text-nowrap px-2 py-2" style="min-width: <?= $i == 0 ? '180px' : '120px' ?>;">
                                <?= $label ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>

            <!-- Rows -->
            <?php foreach ($items as $item): ?>
                <?php
                    $_poi       = $item['inventory_purchase_order_items'];
                    $_origQty   = (float)($_poi['sku_quantity'] ?? 1);
                    $_excisePu  = (float)$_poi['excise_per_unit'] > 0
                                    ? (float)$_poi['excise_per_unit']
                                    : ($_origQty > 0 ? round((float)$_poi['excise_duty_amt'] / $_origQty, 6) : 0);
                    $_addlExPu  = (float)$_poi['addl_excise_per_unit'] > 0
                                    ? (float)$_poi['addl_excise_per_unit']
                                    : ($_origQty > 0 ? round((float)$_poi['addl_excise_duty_amt'] / $_origQty, 6) : 0);
                    // Derive vat%/tcs% from stored amounts when not stored as percentages
                    $_vatPct    = (float)$_poi['vat_percentage'];
                    $_tcsPct    = (float)$_poi['tcs_percentage'];
                    if ($_excisePu > 0 && $_vatPct == 0 && (float)$_poi['vat_amt'] > 0) {
                        $_discVal   = (float)($_poi['sku_discount_value'] ?? 0);
                        $_vatBase   = (float)$_poi['sku_price'] * $_origQty - $_discVal
                                      + (float)$_poi['excise_duty_amt']
                                      + (float)$_poi['addl_excise_duty_amt'];
                        $_vatPct    = ($_vatBase > 0) ? round((float)$_poi['vat_amt'] / $_vatBase * 100, 4) : 0;
                    }
                    if ($_excisePu > 0 && $_tcsPct == 0 && (float)$_poi['tcs_amt'] > 0) {
                        $_discVal   = (float)($_poi['sku_discount_value'] ?? 0);
                        $_tcsBase   = (float)$_poi['sku_price'] * $_origQty - $_discVal
                                      + (float)$_poi['excise_duty_amt']
                                      + (float)$_poi['vat_amt'];
                        $_tcsPct    = ($_tcsBase > 0) ? round((float)$_poi['tcs_amt'] / $_tcsBase * 100, 4) : 0;
                    }
                ?>
                <tr
                    data-excise-pu="<?= h($_excisePu) ?>"
                    data-addl-excise-pu="<?= h($_addlExPu) ?>"
                    data-vat-pct="<?= h($_vatPct) ?>"
                    data-tcs-pct="<?= h($_tcsPct) ?>"
                >
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

                                case 'Sku Tax':
                                    $val = $item['inventory_purchase_order_items']['sku_total_tax_percentage'] . ' %';
                                    break;

                                case 'Excise/unit':
                                    if ($status <= 3) {
                                        // Editable at approve (1), process (2), and stockin (3)
                                        echo '<td>
                                            <input type="number" class="form-control form-control-sm excise-input"
                                                data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                                data-sku-id="' . h($item['inventory_purchase_order_items']['sku_id']) . '"
                                                step="0.01" min="0"
                                                value="' . h(number_format($_excisePu, 2, '.', '')) . '"
                                                placeholder="Excise ₹/u"
                                                style="min-width:90px;">
                                        </td>';
                                        continue 2;
                                    }
                                    $val = $_excisePu > 0 ? '₹' . number_format($_excisePu, 2) : '—';
                                    break;

                                case 'Addl.Excise/unit':
                                    if ($status <= 3) {
                                        // Editable at approve (1), process (2), and stockin (3)
                                        echo '<td>
                                            <input type="number" class="form-control form-control-sm addlex-input"
                                                data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                                data-sku-id="' . h($item['inventory_purchase_order_items']['sku_id']) . '"
                                                step="0.01" min="0"
                                                value="' . h(number_format($_addlExPu, 2, '.', '')) . '"
                                                placeholder="Addl.Ex ₹/u"
                                                style="min-width:90px;">
                                        </td>';
                                        continue 2;
                                    }
                                    $val = $_addlExPu > 0 ? '₹' . number_format($_addlExPu, 2) : '—';
                                    break;

                                case 'VAT %':
                                    if ($status <= 3) {
                                        // Editable at approve (1), process (2), and stockin (3)
                                        echo '<td>
                                            <input type="number" class="form-control form-control-sm vat-input"
                                                data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                                data-sku-id="' . h($item['inventory_purchase_order_items']['sku_id']) . '"
                                                step="0.01" min="0" max="100"
                                                value="' . h(number_format($_vatPct, 2, '.', '')) . '"
                                                placeholder="VAT %"
                                                style="min-width:80px;">
                                        </td>';
                                        continue 2;
                                    }
                                    $val = $_vatPct > 0 ? number_format($_vatPct, 2) . '%' : '—';
                                    break;

                                case 'TCS %':
                                    if ($status <= 3) {
                                        // Editable at approve (1), process (2), and stockin (3)
                                        echo '<td>
                                            <input type="number" class="form-control form-control-sm tcs-input"
                                                data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                                data-sku-id="' . h($item['inventory_purchase_order_items']['sku_id']) . '"
                                                step="0.01" min="0" max="100"
                                                value="' . h(number_format($_tcsPct, 2, '.', '')) . '"
                                                placeholder="TCS %"
                                                style="min-width:80px;">
                                        </td>';
                                        continue 2;
                                    }
                                    $val = $_tcsPct > 0 ? number_format($_tcsPct, 2) . '%' : '—';
                                    break;

                                case 'Requested Quantity':
                                    $val = $item['inventory_purchase_order_items']['sku_quantity'];
                                    break;

                                case 'Quantity':
                                    if ($status == 1) {
                                        // Approve: editable, capped at original ordered qty
                                        echo '<td>
                                            <input type="number" class="form-control form-control-sm po-quantity"
                                                data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                                data-max="'     . h($item['inventory_purchase_order_items']['sku_quantity']) . '"
                                                max="'          . h($item['inventory_purchase_order_items']['sku_quantity']) . '"
                                                value="'        . h($item['inventory_purchase_order_items']['sku_quantity']) . '"
                                                style="min-width:90px;">
                                        </td>';
                                        continue 2;
                                    }
                                    if ($status == 2) {
                                        // Process: editable, capped at approved qty
                                        echo '<td>
                                            <input type="number" class="form-control form-control-sm po-quantity"
                                                data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                                data-max="'     . h($item['inventory_purchase_order_items']['approved_quantity']) . '"
                                                max="'          . h($item['inventory_purchase_order_items']['approved_quantity']) . '"
                                                value="'        . h($item['inventory_purchase_order_items']['approved_quantity']) . '"
                                                style="min-width:90px;">
                                        </td>';
                                        continue 2;
                                    }
                                    $val = $item['inventory_purchase_order_items']['sku_quantity'];
                                    break;

                                /*
                                 * UPDATED: 'Approve Discount Type' — status 1 only (editable)
                                 * Pre-filled from sku_discount_type (the value set at create, shown as a starting reference)
                                 * When saved → goes into approve_discount_type
                                 */
                                case 'Approve Discount Type':
                                    $savedType = $item['inventory_purchase_order_items']['sku_discount_type'] ?? 'flat';
                                    echo '<td>
                                        <select class="form-control form-control-sm po-discount-type"
                                            data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                            style="min-width:110px;">
                                            <option value="flat"'       . ($savedType === 'flat'       ? ' selected' : '') . '>Flat</option>
                                            <option value="percentage"' . ($savedType === 'percentage' ? ' selected' : '') . '>Percentage</option>
                                        </select>
                                    </td>';
                                    continue 2;

                                /*
                                 * UPDATED: 'Approve Discount' — status 1 only (editable)
                                 * Pre-filled from sku_discount (raw input set at create, shown as starting reference)
                                 * When saved → goes into approve_discount (raw) and approve_discount_value (calculated ₹)
                                 */
                                case 'Approve Discount':
                                    $savedDisc = $item['inventory_purchase_order_items']['sku_discount'] ?? 0;
                                    echo '<td>
                                        <input type="number"
                                            class="form-control form-control-sm po-discount"
                                            data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                            step="any" min="0"
                                            style="min-width:100px;"
                                            value="' . h($savedDisc) . '">
                                    </td>';
                                    continue 2;

                                /*
                                 * UPDATED: 'Process Discount Type' — status 2 only (editable)
                                 * Pre-filled from approve_discount_type (what the approver set)
                                 * When saved → goes into process_discount_type
                                 */
                                case 'Process Discount Type':
                                    $savedType = $item['inventory_purchase_order_items']['approve_discount_type'] ?? 'flat';
                                    echo '<td>
                                        <select class="form-control form-control-sm po-discount-type"
                                            data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                            style="min-width:110px;">
                                            <option value="flat"'       . ($savedType === 'flat'       ? ' selected' : '') . '>Flat</option>
                                            <option value="percentage"' . ($savedType === 'percentage' ? ' selected' : '') . '>Percentage</option>
                                        </select>
                                    </td>';
                                    continue 2;

                                /*
                                 * UPDATED: 'Process Discount' — status 2 only (editable)
                                 * Pre-filled from approve_discount (raw input the approver set)
                                 * When saved → goes into process_discount (raw) and process_discount_value (calculated ₹)
                                 */
                                case 'Process Discount':
                                    $savedDisc = $item['inventory_purchase_order_items']['approve_discount'] ?? 0;
                                    echo '<td>
                                        <input type="number"
                                            class="form-control form-control-sm po-discount"
                                            data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                            step="any" min="0"
                                            style="min-width:100px;"
                                            value="' . h($savedDisc) . '">
                                    </td>';
                                    continue 2;

                                case 'Total Amount':
                                    $val = $item['inventory_purchase_order_items']['sku_amount'];
                                    break;

                                case 'Approved Quantity':
                                    $val = $item['inventory_purchase_order_items']['approved_quantity'];
                                    break;

                                // UPDATED: read-only, from approve_discount_value column
                                case 'Approve Discount Value':
                                    $val = number_format((float)($item['inventory_purchase_order_items']['approve_discount_value'] ?? 0), 2);
                                    break;

                                case 'Approved Amount':
                                    $val = $item['inventory_purchase_order_items']['approved_amount'];
                                    break;

                                case 'Processed Quantity':
                                    $val = $item['inventory_purchase_order_items']['processed_quantity'];
                                    break;

                                // UPDATED: read-only, from process_discount_value column
                                case 'Process Discount Value':
                                    $val = number_format((float)($item['inventory_purchase_order_items']['process_discount_value'] ?? 0), 2);
                                    break;

                                case 'Processed Amount':
                                    $val = $item['inventory_purchase_order_items']['processed_amount'];
                                    break;

                                case 'Stockin Quantity':
                                    if ($status == 3) {
                                        // Stockin: editable, capped at processed qty
                                        echo '<td>
                                            <input type="number" class="form-control form-control-sm po-quantity"
                                                data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                                data-max="'     . h($item['inventory_purchase_order_items']['processed_quantity']) . '"
                                                max="'          . h($item['inventory_purchase_order_items']['processed_quantity']) . '"
                                                value="'        . h($item['inventory_purchase_order_items']['processed_quantity']) . '"
                                                style="min-width:90px;">
                                        </td>';
                                        continue 2;
                                    }
                                    $val = $item['inventory_purchase_order_items']['stockin_quantity'];
                                    break;

                                /*
                                 * 'Stockin Discount Type' — status 3 only (editable)
                                 * Pre-filled from process_discount_type (what the processor set)
                                 * When saved → goes into stockin_discount_type
                                 */
                                case 'Stockin Discount Type':
                                    $savedType = $item['inventory_purchase_order_items']['process_discount_type'] ?? 'flat';
                                    echo '<td>
                                        <select class="form-control form-control-sm po-discount-type"
                                            data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                            style="min-width:110px;">
                                            <option value="flat"'       . ($savedType === 'flat'       ? ' selected' : '') . '>Flat</option>
                                            <option value="percentage"' . ($savedType === 'percentage' ? ' selected' : '') . '>Percentage</option>
                                        </select>
                                    </td>';
                                    continue 2;

                                /*
                                 * 'Stockin Discount' — status 3 only (editable)
                                 * Pre-filled from process_discount (raw input the processor set)
                                 * When saved → goes into stockin_discount (raw) and stockin_discount_value (calculated ₹)
                                 */
                                case 'Stockin Discount':
                                    $savedDisc = $item['inventory_purchase_order_items']['process_discount'] ?? 0;
                                    echo '<td>
                                        <input type="number"
                                            class="form-control form-control-sm po-discount"
                                            data-item-id="' . h($item['inventory_purchase_order_items']['id']) . '"
                                            step="any" min="0"
                                            style="min-width:100px;"
                                            value="' . h($savedDisc) . '">
                                    </td>';
                                    continue 2;

                                // UPDATED: read-only, from stockin_discount_value column
                                case 'Stockin Discount Value':
                                    $val = number_format((float)($item['inventory_purchase_order_items']['stockin_discount_value'] ?? 0), 2);
                                    break;

                                case 'Stockin Amount':
                                    $val = $item['inventory_purchase_order_items']['stockin_amount'];
                                    break;
                            }
                        ?>
                        <td class="px-2">
                            <input type="text" class="form-control form-control-sm" value="<?= h($val) ?>" readonly style="min-width:100px;" />
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>

                </tbody>
            </table>
            </div><!-- end overflow-x wrapper -->

        <?php else: ?>
            <p class="text-danger">No data available for the current status (<?= h($status) ?>).</p>
        <?php endif; ?>

        <?php if (!empty($stockinData)) { ?>
        <br><h4 class="form-section-title">Stockin Details</h4>
        <?php foreach ($stockinData as $stock): ?>
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <strong>Stockin ID:</strong> <?= h($stock['stockin']['stockin_id']) ?> |
                    <strong>Invoice:</strong>    <?= h($stock['stockin']['invoice_no']) ?> |
                    <strong>Charge:</strong>     <?= h($stock['stockin']['stockin_charge']) ?> |
                    <strong>Date:</strong>       <?= h($stock['stockin']['created']) ?> |
                    <strong>Amount:</strong>     ₹<?= h($stock['stockin']['stockin_amount']) ?>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto; width: 100%;">
                    <table class="table table-bordered table-sm align-middle mb-0" style="min-width: max-content;">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:180px;">Sku Name</th>
                                <th style="min-width:100px;">Unit</th>
                                <th style="min-width:100px;">Price</th>
                                <th style="min-width:100px;">Quantity</th>
                                <th style="min-width:100px;">Tax</th>
                                <th style="min-width:110px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php foreach ($stock['items'] as $sitem): ?>
                        <tr>
                            <td><input type="text" class="form-control form-control-sm" value="<?= h($sitem['Inventory_stockin_item']['sku_name']) ?>" readonly></td>
                            <td><input type="text" class="form-control form-control-sm" value="<?= h($sitem['Inventory_stockin_item']['sku_unit_name']) ?>" readonly></td>
                            <td><input type="text" class="form-control form-control-sm" value="<?= h($sitem['Inventory_stockin_item']['sku_price']) ?>" readonly></td>
                            <td><input type="text" class="form-control form-control-sm" value="<?= h($sitem['Inventory_stockin_item']['stockin_quantity']) ?>" readonly></td>
                            <td><input type="text" class="form-control form-control-sm" value="<?= h($sitem['Inventory_stockin_item']['stockin_total_tax_value']) ?>" readonly></td>
                            <td><input type="text" class="form-control form-control-sm" value="<?= h($sitem['Inventory_stockin_item']['stockin_amount']) ?>" readonly></td>
                        </tr>
                    <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div><!-- end overflow-x wrapper -->
                </div>
            </div>
        <?php endforeach; ?>
        <?php } ?>

        <!-- Action Button -->
        <div class="text-end mt-3">
            <?php if ($status == 1): ?>
                <button class="btn btn-success approve-status" data-id="<?= h($indents[0]['inventory_purchase_order']['p_o_id']) ?>">Approve PO</button>
            <?php elseif ($status == 2): ?>
                <button class="btn btn-success mark-status" data-id="<?= h($indents[0]['inventory_purchase_order']['p_o_id']) ?>">Process PO</button>
            <?php elseif ($status == 3): ?>
            <button class="btn btn-warning stockin-status" disabled>Processed</button>
            <?php elseif ($status == 4): ?>
                <button class="btn btn-danger" disabled>Stocked IN</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>

function showPOConfirmation(charge, onConfirm) {

    let html = `
        <table class="table table-bordered table-sm text-start">
            <thead>
                <tr>
                    <th>SKU</th><th>Price</th><th>Qty</th><th>Base</th>
                    <th>Disc Type</th><th>Discount</th>
                    <th>Tax %</th><th>Tax</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subtotal           = 0;
    let totalTax           = 0;
    let totalDiscount      = 0;
    let totalExcise        = 0;
    let totalAddlExcise    = 0;
    let totalVat           = 0;
    let totalTcs           = 0;
    let totalSum           = 0;

    $('.po-quantity').each(function () {

        const row        = $(this).closest('tr');
        const tds        = row.find('td');

        // Column order: Sku Name(0), Sku Unit(1), Sku Price(2), Sku Tax(3), Req Qty(4), Qty(5) ...
        const sku        = tds.eq(0).find('input').val() || '';
        const price      = parseFloat(tds.eq(2).find('input').val()) || 0;
        const qty        = parseFloat($(this).val()) || 0;

        if (qty <= 0) return;

        const taxStr     = tds.eq(3).find('input').val() || '';
        const taxPct     = parseFloat(taxStr) || 0;
        const baseAmount = price * qty;

        const discType   = row.find('.po-discount-type').val() || 'flat';
        let   discInput  = parseFloat(row.find('.po-discount').val()) || 0;

        let discountValue = 0;
        if (discType === 'percentage') {
            if (discInput > 100) discInput = 100;
            discountValue = (baseAmount * discInput) / 100;
        } else {
            if (discInput > baseAmount) discInput = baseAmount;
            discountValue = discInput;
        }

        const afterDiscount = baseAmount - discountValue;

        // Read excise/VAT/TCS from input fields instead of data attributes
        const excisePu   = parseFloat(row.find('.excise-input').val())       || 0;
        const addlExcPu  = parseFloat(row.find('.addlex-input').val())   || 0;
        const vatPct     = parseFloat(row.find('.vat-input').val())          || 0;
        const tcsPct     = parseFloat(row.find('.tcs-input').val())          || 0;
        // Zero out GST when excise/VAT applies
        const effectiveTaxPct = (excisePu > 0 || vatPct > 0) ? 0 : taxPct;

        const exciseAmt  = Math.round(excisePu  * qty * 100) / 100;
        const addlExAmt  = Math.round(addlExcPu * qty * 100) / 100;
        const vatBase    = afterDiscount + exciseAmt + addlExAmt;
        const vatAmt     = Math.round(vatBase * vatPct / 100 * 100) / 100;
        // TCS = (base_after_disc + excise + VAT) x TCS%
        const tcsAmt     = Math.round((afterDiscount + exciseAmt + vatAmt) * tcsPct / 100 * 100) / 100;
        const landed     = afterDiscount + exciseAmt + vatAmt;
        const taxAmount  = Math.round(landed * effectiveTaxPct / 100 * 100) / 100;
        const total      = Math.round((landed + taxAmount + tcsAmt) * 100) / 100;

        subtotal        += baseAmount;
        totalDiscount   += discountValue;
        totalExcise     += exciseAmt;
        totalAddlExcise += addlExAmt;
        totalVat        += vatAmt;
        totalTcs        += tcsAmt;
        totalTax        += taxAmount;
        totalSum        += total;

        html += `
            <tr>
                <td>${sku}</td>
                <td>₹${price.toFixed(2)}</td>
                <td>${qty}</td>
                <td>₹${baseAmount.toFixed(2)}</td>
                <td>${discType === 'percentage' ? 'Percentage' : 'Flat'}</td>
                <td>${discType === 'percentage' ? discInput + '%' : '₹' + discountValue.toFixed(2)}</td>
                <td>${effectiveTaxPct}%</td>
                <td>₹${taxAmount.toFixed(2)}</td>
                <td><b>₹${total.toFixed(2)}</b></td>
            </tr>
        `;
    });

    const chargeVal             = parseFloat(charge) || 0;
    const subtotalAfterDiscount = subtotal - totalDiscount;
    const grandTotal            = totalSum + chargeVal;

    html += `
        </tbody></table>
        <div class="text-end mt-3">
            <h6>Subtotal : ₹${subtotal.toFixed(2)}</h6>
            <h6>Total Discount : ₹${totalDiscount.toFixed(2)}</h6>
            <h6>Subtotal After Discount : ₹${subtotalAfterDiscount.toFixed(2)}</h6>
            ${totalExcise > 0 ? `<h6>Excise Duty Total : ₹${totalExcise.toFixed(2)}</h6>` : ''}
            ${totalAddlExcise > 0 ? `<h6>Addl. Excise Total : ₹${totalAddlExcise.toFixed(2)}</h6>` : ''}
            ${totalVat > 0 ? `<h6>VAT Total (in landed cost) : ₹${totalVat.toFixed(2)}</h6>` : ''}
            ${totalTcs > 0 ? `<h6>TCS Total (recoverable) : ₹${totalTcs.toFixed(2)}</h6>` : ''}
            <h6>Total Tax (GST) : ₹${totalTax.toFixed(2)}</h6>
            <h6>Charges : ₹${chargeVal.toFixed(2)}</h6>
            <h5 class="mt-2"><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
        </div>
    `;

    Swal.fire({
        title: 'Confirm Purchase Order',
        html: html,
        icon: 'warning',
        width: '1100px',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) { onConfirm(); }
    });
}


$(document).ready(function () {

    // Discount validation: percentage capped at 100, flat capped at base amount
    $(document).on('input', '.po-discount', function () {
        const row        = $(this).closest('tr');
        const tds        = row.find('td');
        const price      = parseFloat(tds.eq(2).find('input').val()) || 0;
        const qty        = parseFloat(row.find('.po-quantity').val()) || 0;
        const baseAmount = price * qty;
        const discType   = row.find('.po-discount-type').val() || 'flat';
        let   discVal    = parseFloat($(this).val()) || 0;

        if (discType === 'percentage') {
            if (discVal > 100) {
                Swal.fire({ toast: true, icon: 'warning', title: 'Percentage discount cannot exceed 100%', showConfirmButton: false, timer: 2000 });
                $(this).val(100);
            }
        } else {
            if (discVal > baseAmount) {
                Swal.fire({ toast: true, icon: 'warning', title: 'Discount cannot exceed item amount (₹' + baseAmount.toFixed(2) + ')', showConfirmButton: false, timer: 2000 });
                $(this).val(baseAmount.toFixed(2));
            }
        }
        if (discVal < 0) { $(this).val(0); }
    });

    // Re-validate when discount type changes
    $(document).on('change', '.po-discount-type', function () {
        $(this).closest('tr').find('.po-discount').trigger('input');
    });

    $(document).on('input', '.po-quantity', function () {
        const max = parseFloat($(this).data('max'));
        const val = parseFloat($(this).val());
        if (val > max) {
            Swal.fire({ toast: true, icon: 'warning', title: `You cannot enter more than ${max}`, showConfirmButton: false, timer: 2000 });
            $(this).val(max);
        } else if (val < 0) {
            $(this).val(0);
        }
    });

    // Update row data attributes when excise/VAT/TCS inputs change
    $(document).on('input', '.excise-input, .addlex-input, .vat-input, .tcs-input', function() {
        const row = $(this).closest('tr');
        row.data('excise-pu', row.find('.excise-input').val());
        row.data('addl-excise-pu', row.find('.addlex-input').val());
        row.data('vat-pct', row.find('.vat-input').val());
        row.data('tcs-pct', row.find('.tcs-input').val());
    });

    /*
     * Process PO — sends discount as process_discount_* fields
     */
    $('.mark-status').click(function () {
        const id     = $(this).data('id');
        const charge = parseFloat($('#charge').val()) || 0;
        const items  = [];

        $('.po-quantity').each(function () {
            const row = $(this).closest('tr');
            items.push({
                item_id:       $(this).data('item-id'),
                quantity:      $(this).val(),
                discount:      row.find('.po-discount').val() || 0,
                discount_type: row.find('.po-discount-type').val() || 'flat',
                excise_per_unit: row.find('.excise-input').val() || 0,
                addl_excise_per_unit: row.find('.addlex-input').val() || 0,
                vat_percentage: row.find('.vat-input').val() || 0,
                tcs_percentage: row.find('.tcs-input').val() || 0
            });
        });

        showPOConfirmation(charge, function () {
            $.ajax({
                url: '<?= $this->Html->url(["controller" => "Dashboard", "action" => "process_po"]) ?>',
                type: 'POST', dataType: 'json',
                data: { _id: id, _charge: charge, _items: items },
                success: function (response) {
                    if (response.status) {
                        Swal.fire('Success', 'Purchase Order Processed', 'success').then(() => window.location.href = 'po_list');
                    } else {
                        Swal.fire('Failed', 'Processing Failed', 'error');
                    }
                }
            });
        });
    });

    /*
     * Approve PO — sends discount as approve_discount_* fields
     */
    $('.approve-status').click(function () {
        const id     = $(this).data('id');
        const charge = parseFloat($('#charge').val()) || 0;
        const items  = [];

        $('.po-quantity').each(function () {
            const row = $(this).closest('tr');
            items.push({
                item_id:       $(this).data('item-id'),
                quantity:      $(this).val(),
                discount:      row.find('.po-discount').val() || 0,
                discount_type: row.find('.po-discount-type').val() || 'flat',
                excise_per_unit: row.find('.excise-input').val() || 0,
                addl_excise_per_unit: row.find('.addlex-input').val() || 0,
                vat_percentage: row.find('.vat-input').val() || 0,
                tcs_percentage: row.find('.tcs-input').val() || 0
            });
        });

        showPOConfirmation(charge, function () {
            $.ajax({
                url: '<?= $this->Html->url(["controller" => "Dashboard", "action" => "approve_po"]) ?>',
                type: 'POST', dataType: 'json',
                data: { _id: id, _charge: charge, _items: items },
                success: function (response) {
                    if (response.status) {
                        Swal.fire('Success', 'Purchase Order Approved', 'success').then(() => window.location.href = 'po_list');
                    } else {
                        Swal.fire('Failed', 'Approval Failed', 'error');
                    }
                }
            });
        });
    });

    /*
     * Stockin PO — status 3 — sends discount as stockin_discount_* fields
     * Same pattern as approve/process handlers above
     */
    $('.stockin-status').click(function () {
        const id     = $(this).data('id');
        const charge = parseFloat($('#charge').val()) || 0;
        const items  = [];

        $('.po-quantity').each(function () {
            const row = $(this).closest('tr');
            items.push({
                item_id:       $(this).data('item-id'),
                quantity:      $(this).val(),
                discount:      row.find('.po-discount').val() || 0,
                discount_type: row.find('.po-discount-type').val() || 'flat',
                excise_per_unit: row.find('.excise-input').val() || 0,
                addl_excise_per_unit: row.find('.addlex-input').val() || 0,
                vat_percentage: row.find('.vat-input').val() || 0,
                tcs_percentage: row.find('.tcs-input').val() || 0
            });
        });

        showPOConfirmation(charge, function () {
            $.ajax({
                url: '<?= $this->Html->url(["controller" => "Dashboard", "action" => "stockin_po_view"]) ?>',
                type: 'POST', dataType: 'json',
                data: { _id: id, _charge: charge, _items: items },
                success: function (response) {
                    if (response.status) {
                        Swal.fire('Success', 'Purchase Order Stocked In', 'success').then(() => window.location.href = 'po_list');
                    } else {
                        Swal.fire('Failed', 'Stockin Failed', 'error');
                    }
                }
            });
        });
    });

});
</script>
