<?php
$indent = $indents[0]['inventory_indent'];
?>

<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Indent</h2>
        </div>
    </div>
    <div class="col-sm-1 text-right">
        <div class="page-title-box">
            <a href="indent"><button class="btn btn-danger" type="button">Back</button></a>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <?php
                    $formInputs = [
                        ['label' => 'Indent ID', 'value' => $indent['indent_id']],
                        ['label' => 'Location Name', 'value' => $indent['location_name']],
                        ['label' => 'Expiry', 'value' => $indent['expiry'], 'condition' => !empty($indent['expiry'])],
                        ['label' => 'Total Amount', 'value' => number_format($indent['total_amount'], 2)],
                        ['label' => 'Approved Amount', 'value' => number_format($indent['approved_amount'], 2), 'condition' => !empty($indent['approved_amount'])],
                        ['label' => 'Processed Amount', 'value' => number_format($indent['processed_amount'], 2), 'condition' => !empty($indent['processed_amount'])],
                        ['label' => 'Remarks', 'value' => $indent['remarks'], 'condition' => !empty($indent['remarks'])],
                        ['label' => 'Vehicle Number', 'value' => $indent['vehicle_no'], 'condition' => !empty($indent['vehicle_no'])],
                    ];

                    foreach ($formInputs as $input) {
                        if (!isset($input['condition']) || $input['condition']) {
                            echo '<div class="col-sm-3 mb-2">';
                            echo $this->Form->input('', [
                                'label' => $input['label'],
                                'class' => 'form-control',
                                'readonly' => 'readonly',
                                'value' => $input['value'],
                            ]);
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Items Section -->
<div class="card mt-4">
    <div class="card-body">

        <h4 class="mb-3">Indent Items</h4>

        <div class="table-responsive">

            <table class="table table-bordered table-sm text-center align-middle" id="example">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Tax %</th>
                        <th>Qty</th>
                        <th>Base</th>
                        <th>Tax</th>
                        <th>Total</th>
                        
                        <?php if($indent['status'] >= 2): ?>
                            <th>Approved Qty</th>
                            <th>Approved Base</th>
                            <th>Approved Tax</th>
                            <th>Approved Total</th>
                        <?php endif; ?>
                        
                        <?php if($indent['status'] == 3): ?>
                            <th>Processed Qty</th>
                            <th>Processed Base</th>
                            <th>Processed Tax</th>
                            <th>Processed Total</th>
                        <?php endif; ?>

                    </tr>
                </thead>

                <tbody>

                <?php
                $baseSum = $taxSum = $totalSum = 0;
                $approvedBaseSum = $approvedTaxSum = $approvedTotalSum = 0;
                $processedBaseSum = $processedTaxSum = $processedTotalSum = 0;

                $i = 1;

                foreach ($items as $item):

                    $it = $item['inventory_indent_items'];

                    $price = (float)$it['sku_price'];
                    $qty   = (float)$it['sku_quantity'];
                    $taxP  = (float)$it['sku_total_tax_percentage'];

                    $base  = $price * $qty;
                    $taxV  = ($base * $taxP) / 100;
                    $total = $base + $taxV;
                    
                    /* ---------- APPROVED ---------- */
                    $approvedQty   = (float)$it['approved_quantity'];
                    $approvedPrice = (float)$it['approved_price'];
                    
                    $approvedBase  = $approvedQty * $approvedPrice;
                    $approvedTax   = ($approvedBase * $taxP) / 100;
                    $approvedTotal = $approvedBase + $approvedTax;
                    
                    $approvedBaseSum  += $approvedBase;
                    $approvedTaxSum   += $approvedTax;
                    $approvedTotalSum += $approvedTotal;
                    
                    
                    /* ---------- PROCESSED ---------- */
                    $processedQty = (float)$it['processed_quantity'];
                    
                    $processedBase  = $processedQty * $approvedPrice;
                    $processedTax   = ($processedBase * $taxP) / 100;
                    $processedTotal = $processedBase + $processedTax;
                    
                    $processedBaseSum  += $processedBase;
                    $processedTaxSum   += $processedTax;
                    $processedTotalSum += $processedTotal;

                    $baseSum  += $base;
                    $taxSum   += $taxV;
                    $totalSum += $total;
                ?>

                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= h($it['sku_name']) ?></td>
                        <td><?= h($it['sku_unit_name']) ?></td>

                        <td>₹<?= number_format($price,2) ?></td>
                        <td><?= number_format($taxP,2) ?>%</td>
                        <td><?= $qty ?></td>

                        <td>₹<?= number_format($base,2) ?></td>
                        <td>₹<?= number_format($taxV,2) ?></td>
                        <td class="font-weight-bold">₹<?= number_format($total,2) ?></td>

                        <?php if($indent['status'] >= 2): ?>
                            <td><?= $approvedQty ?></td>
                            <td>₹<?= number_format($approvedBase,2) ?></td>
                            <td>₹<?= number_format($approvedTax,2) ?></td>
                            <td>₹<?= number_format($approvedTotal,2) ?></td>
                        <?php endif; ?>
                        
                        <?php if($indent['status'] == 3): ?>
                            <td><?= $processedQty ?></td>
                            <td>₹<?= number_format($processedBase,2) ?></td>
                            <td>₹<?= number_format($processedTax,2) ?></td>
                            <td>₹<?= number_format($processedTotal,2) ?></td>
                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>

                </tbody>


                <!-- ================= TOTALS ================= -->

                <tfoot class="bg-light font-weight-bold text-center">
                    <tr>
                        <td colspan="6">Totals</td>
                    
                        <td>₹<?= number_format($baseSum,2) ?></td>
                        <td>₹<?= number_format($taxSum,2) ?></td>
                        <td>₹<?= number_format($totalSum,2) ?></td>
                    
                        <?php if($indent['status'] >= 2): ?>
                            <td></td>
                            <td>₹<?= number_format($approvedBaseSum,2) ?></td>
                            <td>₹<?= number_format($approvedTaxSum,2) ?></td>
                            <td>₹<?= number_format($approvedTotalSum,2) ?></td>
                        <?php endif; ?>
                    
                        <?php if($indent['status'] == 3): ?>
                            <td></td>
                            <td>₹<?= number_format($processedBaseSum,2) ?></td>
                            <td>₹<?= number_format($processedTaxSum,2) ?></td>
                            <td>₹<?= number_format($processedTotalSum,2) ?></td>
                        <?php endif; ?>
                    </tr>

                </tfoot>

            </table>

        </div>
    </div>
</div>
