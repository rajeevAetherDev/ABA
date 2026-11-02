<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Recipe</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="semifinished_list">
                    <button class='btn btn-danger' type="button">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>

<?php echo $this->Form->create('User',['type' => 'file']); ?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <?php
                            echo $this->Form->input('id',        ['div'=>['class'=>'form-group'],'class'=>'form-control','type'=>'hidden','value'=>$products[0]['inventory_semifinished']['id']]);
                            echo $this->Form->input('unit',      ['div'=>['class'=>'form-group'],'class'=>'form-control','type'=>'hidden','value'=>$products[0]['inventory_semifinished']['unit']]);
                            echo $this->Form->input('name',      ['div'=>['class'=>'form-group'],'label'=>'Semifinished Name','class'=>'form-control','readonly'=>'readonly','value'=>$products[0]['inventory_semifinished']['name']]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Semi-Finished Mapping -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-12">
                        <h4 class="page-title">Map Semi-Finished</h4>
                        <?php foreach($recipes as $recipe):
                            $semi_id   = $recipe['inventory_linking']['component_id'];
                            $semi_name = $recipe['inventory_linking']['component_name'];
                            $semi_qty  = $recipe['inventory_linking']['conversion_quantity'];
                            $comp_unit = $recipe['inventory_linking']['component_unit_name'];
                            $comp_price= $recipe['inventory_linking']['component_price'];
                            $semi_live_price = isset($recipe[0]['live_price']) ? $recipe[0]['live_price'] : 0;
                                
                        ?>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <?php
                                    echo $this->Form->input('semi_id_'.$semi_id,   ['div'=>['class'=>'form-group'],'type'=>'hidden','class'=>'form-control','value'=>$semi_id]);
                                    echo $this->Form->input('semi_name_'.$semi_id, ['div'=>['class'=>'form-group'],'label'=>'Semifinished Name','class'=>'form-control','readonly'=>'readonly','value'=>$semi_name]);
                                ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo $this->Form->input('semi_unit_'.$semi_id,     ['div'=>['class'=>'form-group'],'label'=>'Unit','class'=>'form-control','readonly'=>true,'value'=>$comp_unit]); ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo $this->Form->input('semi_price_'.$semi_id,    ['div'=>['class'=>'form-group'],'label'=>'Unit Cost','class'=>'form-control','readonly'=>true,'value'=>number_format($semi_live_price,4)]); ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo $this->Form->input('semi_quantity_'.$semi_id, ['div'=>['class'=>'form-group'],'label'=>'Quantity','class'=>'form-control','type'=>'number','value'=>$semi_qty,'step'=>'0.0000001','required'=>true]); ?>
                            </div>
                            <div class="col-md-1"><br>
                                <button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-12"><div id="semiInputs"></div></div>
                    <div class="col-md-12">
                        <button id="addsemi" class='btn btn-info' type="button" style="float:right;">Add Semi-Finished</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SKU Mapping -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-12">
                        <h4 class="page-title">Map Sku</h4>

                        <?php
                        // =========================================================
                        // FIX: All helper functions declared ONCE here, outside any
                        //      loop, and guarded with function_exists() so they are
                        //      safe even if the view is rendered more than once.
                        // =========================================================

                        if (!function_exists('_sfk')) {
                            // function _sfk($u){
                            //     $u=strtolower(trim($u??''));
                            //     if(in_array($u,['gms','gm','g','gram','grams','kg','kgs','kilo'])) return 'weight';
                            //     if(in_array($u,['ml','millilitre','milliliter','l','ltr','litre','liter','lt'])) return 'volume';
                            //     return 'piece';
                            // }
                            function _sfk($u){
                                $u = strtolower(trim($u ?? ''));
                            
                                // Weight check
                                if (
                                    strpos($u, 'gm') !== false ||
                                    strpos($u, 'gram') !== false ||
                                    strpos($u, 'kg') !== false ||
                                    strpos($u, 'kilo') !== false
                                ) {
                                    return 'weight';
                                }
                            
                                // Volume check
                                if (
                                    strpos($u, 'ml') !== false ||
                                    strpos($u, 'litre') !== false ||
                                    strpos($u, 'liter') !== false ||
                                    strpos($u, 'ltr') !== false ||
                                    strpos($u, 'l') !== false
                                ) {
                                    return 'volume';
                                }
                            
                                return 'piece';
                            }
                        
                        }
                        // Warn about magnitude mismatches within same family (e.g. Kg in recipe but Gms as SKU unit)
                        
                        if (!function_exists('skuUnitFamily')) {
                            function skuUnitFamily($u) {
                                $u = strtolower(trim($u ?? ''));
                            
                                if (in_array($u, ['gms','gm','g','gram','grams'])) return 'grams';
                                if (in_array($u, ['kg','kgs','kilo','kilogram','kilograms'])) return 'grams';
                                if (in_array($u, ['ml','millilitre','milliliter'])) return 'volume';
                                if (in_array($u, ['l','ltr','litre','liter','lt'])) return 'volume';
                                if (in_array($u, ['nos','no','pcs','pce','ea','each','por','portion','pc'])) return 'piece';
                            
                                return 'other_'.$u;
                            }
                        }

                        if (!function_exists('skuUnitMagnitude')) {
                            function skuUnitMagnitude($u) {
                                $u = strtolower(trim($u ?? ''));
                                if (in_array($u, ['kg','kgs','kilo','kilogram'])) return 'large';
                                if (in_array($u, ['l','ltr','litre','liter'])) return 'large';
                                return 'normal';
                            }
                        }

                        // =========================================================
                        // Pre-scan: count ingredient mismatches for summary banner.
                        // FIX: _sfk() is now called AFTER it is declared above —
                        //      previously the function was being declared inside
                        //      this very loop, causing "Cannot redeclare _sfk()"
                        //      on the second iteration.
                        // =========================================================
                        $ingredientMismatchCount = 0;
                        foreach ($skurecipes as $_sr) {
                            $_cu  = $_sr['inventory_linking']['component_unit_name'] ?? '';
                            $_sku = $_sr[0]['sku_con_unit'] ?? $_sr['inventory_skus']['consumption_unit_name'] ?? '';
                            if ($_sku !== '') {
                                $_rf  = _sfk($_cu);
                                $_sf2 = _sfk($_sku);
                                // Handle descriptive unit names like "Packet Of 400 Gms"
                                if (stripos($_sku, 'gm') !== false || stripos($_sku, 'gram') !== false) $_sf2 = 'weight';
                                elseif (stripos($_sku, 'ml') !== false || stripos($_sku, 'litre') !== false) $_sf2 = 'volume';
                                if ($_rf !== $_sf2) $ingredientMismatchCount++;
                            }
                        }
                        ?>

                        <?php if ($ingredientMismatchCount > 0): ?>
                        <div style="background:#fff3cd; border:1px solid #ffc107; border-radius:4px; padding:8px 12px; margin-bottom:10px; font-size:13px;">
                            <strong>⚠ <?php echo $ingredientMismatchCount; ?> ingredient<?php echo $ingredientMismatchCount > 1 ? 's have' : ' has'; ?> unit mismatches</strong>
                            — recipe unit vs SKU consumption unit differ in type (weight/volume/piece). These are <span style="color:#dc3545; font-weight:600;">highlighted in red</span> below. Food cost for those lines will be incorrect until fixed.
                        </div>
                        <?php endif; ?>

                        <?php
                        // =========================================================
                        // Main SKU loop — skuUnitFamily() and skuUnitMagnitude()
                        // are already declared above; no function declarations here.
                        // =========================================================
                        foreach($skurecipes as $skurecipe):
                            $sku_id      = $skurecipe['inventory_linking']['component_id'];
                            $sku_name    = $skurecipe['inventory_linking']['component_name'];
                            $sku_qty     = $skurecipe['inventory_linking']['conversion_quantity'];
                            $comp_unit   = $skurecipe['inventory_linking']['component_unit_name'];
                            $comp_price  = $skurecipe['inventory_linking']['component_price'];
                            $sku_live_price = isset($skurecipe['inventory_skus']['live_price']) ? $skurecipe['inventory_skus']['live_price'] : 0;
                            
                            // CakePHP places joined columns in either [0] or ['inventory_skus'] depending on model loading
                            $sku_con_unit = $skurecipe[0]['sku_con_unit']
                                         ?? $skurecipe['inventory_skus']['consumption_unit_name']
                                         ?? '';

                            // Check for unit mismatch
                            $recipeFamily      = skuUnitFamily($comp_unit);
                            $skuFamily         = skuUnitFamily($sku_con_unit);
                            $recipeMag         = skuUnitMagnitude($comp_unit);
                            $skuMag            = skuUnitMagnitude($sku_con_unit);
                            $familyMismatch    = ($sku_con_unit !== '' && $recipeFamily !== $skuFamily);
                            $magnitudeMismatch = (!$familyMismatch && $sku_con_unit !== '' && $recipeMag !== $skuMag);
                        ?>
                        <div class="form-group row align-items-end" style="<?php echo $familyMismatch ? 'border-left:3px solid #dc3545; padding-left:6px;' : ($magnitudeMismatch ? 'border-left:3px solid #fd7e14; padding-left:6px;' : ''); ?>">
                            <div class="col-md-4">
                                <?php
                                    echo $this->Form->input('sku_id_'.$sku_id,   ['div'=>['class'=>'form-group'],'type'=>'hidden','class'=>'form-control','value'=>$sku_id]);
                                    echo $this->Form->input('sku_name_'.$sku_id, ['div'=>['class'=>'form-group'],'label'=>'Sku Name','class'=>'form-control','readonly'=>'readonly','value'=>$sku_name]);
                                ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo $this->Form->input('sku_unit_'.$sku_id, ['div'=>['class'=>'form-group'],'label'=>'Unit (recipe)','class'=>'form-control','readonly'=>true,'value'=>$comp_unit]); ?>
                                <?php if ($familyMismatch): ?>
                                <span style="color:#dc3545; font-size:11px; font-weight:600;" title="Recipe uses '<?php echo htmlspecialchars($comp_unit); ?>' but SKU is priced per '<?php echo htmlspecialchars($sku_con_unit); ?>' — different unit types. Cost will be wrong.">
                                    ⚠ Unit mismatch: priced per <?php echo htmlspecialchars($sku_con_unit); ?>
                                </span>
                                <?php elseif ($magnitudeMismatch): ?>
                                <span style="color:#fd7e14; font-size:11px; font-weight:600;" title="Recipe uses '<?php echo htmlspecialchars($comp_unit); ?>' but SKU is priced per '<?php echo htmlspecialchars($sku_con_unit); ?>' — same family but 1000x different. Cost may be off.">
                                    ⚠ Magnitude gap: priced per <?php echo htmlspecialchars($sku_con_unit); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo $this->Form->input('sku_price_'.$sku_id, ['div'=>['class'=>'form-group'],'label'=>'Unit Cost (₹/unit)','class'=>'form-control','readonly'=>true,'value'=>number_format($sku_live_price,4)]); ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo $this->Form->input('sku_quantity_'.$sku_id, ['div'=>['class'=>'form-group'],'label'=>'Quantity','class'=>'form-control','type'=>'number','value'=>$sku_qty,'step'=>'0.0000001','required'=>true]); ?>
                            </div>
                            <div class="col-md-1"><br>
                                <button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-12"><div id="skuInputs"></div></div>
                    <div class="col-md-12">
                        <button id="addsku" class='btn btn-info' type="button" style="float:right;">Add Sku</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yield, Unit Name + Unit Conversion -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-3">
                        <?php
                            echo $this->Form->input('yield', ['div'=>['class'=>'form-group'],'label'=>'Yield (Portions / Units)','class'=>'form-control','value'=>$products[0]['inventory_semifinished']['yield'],'type'=>'number','step'=>'0.000000001','required'=>true]);
                        ?>
                    </div>
                    <div class="col-md-3">
                        <?php
                            echo $this->Form->input('unit_name', ['div'=>['class'=>'form-group'],'label'=>'Yield Unit','class'=>'form-control','type'=>'text','readonly'=>'readonly','value'=>$products[0]['inventory_semifinished']['unit_name']]);
                        ?>
                    </div>
                </div>

                <?php
                    $sfUnitName      = $products[0]['inventory_semifinished']['consumption_unit_name'] ?? '';
                    $sfPortionWt     = floatval($products[0]['inventory_semifinished']['portion_weight_grams'] ?? 0);
                    $yieldUnitDisplay= $products[0]['inventory_semifinished']['unit_name'] ?? '';
                    $hasMappings     = !empty($sfUnitMappings);
                    // $sfNeedsConversion and $sfPortionWtSet come from controller
                ?>

                <!-- Unit Conversion -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5 style="font-size:14px; font-weight:600; color:#495057;">
                            Unit Conversion
                            <small class="text-muted fw-normal">(maps 1 production unit → consumption unit for cross-unit recipe cost calculation)</small>
                        </h5>
                    </div>
                </div>

                <?php if (!$sfNeedsConversion): ?>
                <!-- No cross-unit mismatch — show green status always -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert py-2 mb-2" style="background:#e8f5e9; border:1px solid #a5d6a7; color:#2e7d32;">
                            <small>
                                <strong>✓ No conversion needed.</strong>
                                All product recipes using this item reference the same unit as its production unit
                                (<strong><?php echo htmlspecialchars($yieldUnitDisplay); ?></strong>).
                                <?php if ($sfPortionWt > 0): ?>
                                Gram weight is set to <strong><?php echo $sfPortionWt; ?>g</strong> per <?php echo htmlspecialchars($yieldUnitDisplay); ?> (optional metadata).
                                <?php else: ?>
                                You can optionally record the gram weight per <?php echo htmlspecialchars($yieldUnitDisplay); ?> below.
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
                <!-- Optional gram-weight per unit — purely informational, not a conversion factor -->
                <input type="hidden" name="data[User][consumption_unit_conv]" value="<?php echo htmlspecialchars($sfUnitName); ?>">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:12px;">
                            Gram weight per <?php echo htmlspecialchars($yieldUnitDisplay); ?> (optional)
                            <span class="text-muted" style="font-size:10px;">— only needed if products ever use this item in Gms</span>
                        </label>
                        <input type="number" class="form-control" name="data[User][portion_weight_grams]"
                            step="0.01" min="0"
                            value="<?php echo $sfPortionWt > 0 ? $sfPortionWt : ''; ?>"
                            placeholder="e.g. 223.75 (optional)">
                    </div>
                    <div class="col-md-8">
                        <small class="text-muted" style="font-size:11px;">
                            Leave blank unless some product recipes use this item measured in grams/ml instead of <?php echo htmlspecialchars($yieldUnitDisplay); ?>.
                        </small>
                    </div>
                </div>

                <?php else: ?>
                <!-- Cross-unit mismatch detected: need conversion factor -->
                <?php if (!$hasMappings): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-warning py-2 mb-2">
                            <small>
                                <strong>⚠ Unit mismatch detected</strong> — this item is used in product recipes with a different unit than its production unit
                                ("<strong><?php echo htmlspecialchars($yieldUnitDisplay); ?></strong>").
                                To fix costs, first
                                <a href="<?php echo $this->Html->url(['controller'=>'dashboard','action'=>'create_unit_mapping']); ?>" target="_blank">
                                    create a Unit Mapping
                                </a>
                                (e.g. <?php echo htmlspecialchars($yieldUnitDisplay); ?> → Gms = X).
                                Then come back to select it. Or enter a factor manually below.
                            </small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-muted" style="font-size:12px;">From (Production Unit)</label>
                        <input type="text" class="form-control bg-light" readonly
                            value="<?php echo htmlspecialchars($yieldUnitDisplay); ?>">
                    </div>
                    <div class="col-md-1 text-center" style="font-size:22px; color:#adb5bd; padding-bottom:6px;">→</div>
                    <div class="col-md-3">
                        <label class="form-label text-muted" style="font-size:12px;">To (Consumption Unit)</label>
                        <?php if ($hasMappings): ?>
                        <select class="form-control" name="data[User][consumption_unit_conv]" id="conv_to_unit">
                            <option value="">-- Select Unit --</option>
                            <?php foreach ($sfUnitMappings as $toUnit => $factor): ?>
                            <option value="<?php echo htmlspecialchars($toUnit); ?>"
                                data-factor="<?php echo floatval($factor); ?>"
                                <?php echo ($sfUnitName === $toUnit) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($toUnit); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" name="data[User][consumption_unit_conv]"
                            id="conv_to_unit_manual"
                            value="<?php echo htmlspecialchars($sfUnitName); ?>"
                            placeholder="e.g. Gms">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted" style="font-size:12px;">
                            Conversion Factor
                            <span class="text-info" id="factor_hint" style="font-size:11px;">
                                <?php if ($sfPortionWt > 0 && $sfUnitName && $sfUnitName !== $yieldUnitDisplay): ?>
                                    (saved: 1 <?php echo htmlspecialchars($yieldUnitDisplay); ?> = <?php echo $sfPortionWt; ?> <?php echo htmlspecialchars($sfUnitName); ?>)
                                <?php endif; ?>
                            </span>
                        </label>
                        <input type="number" class="form-control" name="data[User][portion_weight_grams]"
                            id="conv_factor"
                            step="0.01" min="0"
                            value="<?php echo $sfPortionWt > 0 ? $sfPortionWt : ''; ?>"
                            placeholder="e.g. 120">
                    </div>
                    <div class="col-md-2" style="padding-bottom:4px;">
                        <small class="text-muted d-block" style="font-size:11px;">
                            1 <?php echo htmlspecialchars($yieldUnitDisplay); ?> = [factor] <?php echo htmlspecialchars($sfUnitName ?: '?'); ?>
                        </small>
                        <?php if ($hasMappings && $sfPortionWt <= 0): ?>
                        <small class="text-info">Select unit to auto-fill factor</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; // sfNeedsConversion ?>
            </div>
        </div>
    </div>

    <!-- Food Cost Summary -->
    <div class="col-sm-12">
        <div class="card" style="background:#f8f9fa; border-left:4px solid #28a745;">
            <div class="card-body py-3">
                <?php
                    $batchCost = isset($sf_cost['batch_cost']) ? floatval($sf_cost['batch_cost']) : 0;
                    $unitCost  = isset($sf_cost['unit_cost'])  ? floatval($sf_cost['unit_cost'])  : 0;
                    $yieldQty  = floatval($products[0]['inventory_semifinished']['yield'] ?? 1);
                    $yieldUnit = $products[0]['inventory_semifinished']['unit_name'] ?? '';
                    $portionWt = floatval($products[0]['inventory_semifinished']['portion_weight_grams'] ?? 0);
                ?>
                <div class="row align-items-center">
                    <div class="col-md-3 text-center border-end">
                        <div class="text-muted small">Batch Cost</div>
                        <div style="font-size:22px; font-weight:700; color:#28a745;">
                            ₹<?php echo number_format($batchCost, 2); ?>
                        </div>
                    </div>
                    <div class="col-md-3 text-center border-end">
                        <div class="text-muted small">Cost Per <?php echo htmlspecialchars($yieldUnit ?: 'Portion'); ?></div>
                        <div style="font-size:22px; font-weight:700; color:#007bff;">
                            ₹<?php echo number_format($unitCost, 2); ?>
                        </div>
                    </div>
                    <div class="col-md-3 text-center border-end">
                        <div class="text-muted small">Yield</div>
                        <div style="font-size:18px; font-weight:600;">
                            <?php echo $yieldQty; ?> <?php echo htmlspecialchars($yieldUnit); ?>
                        </div>
                    </div>
                    <?php if ($portionWt > 0): ?>
                    <div class="col-md-3 text-center">
                        <div class="text-muted small">Per Gram (derived)</div>
                        <div style="font-size:18px; font-weight:600; color:#6c757d;">
                            ₹<?php echo number_format($unitCost / $portionWt, 4); ?>/g
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($batchCost <= 0): ?>
                <div class="alert alert-warning mt-2 mb-0 py-2">
                    <small><strong>Note:</strong> Batch cost is ₹0 — some ingredients may have no price in the system yet. Set unit prices in SKUs or create a stock-in record.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <?php
            echo '&nbsp;' . $this->Form->button('Save', ['class'=>'btn btn-success','type'=>'submit','id'=>'submitBtn','style'=>'float:right']);
        ?>
        <a href="semifinished_list"><button class='btn btn-danger' type="button" style="float:right;">Cancel</button></a>&nbsp;
    </div>
</div><br>

<script>
$(document).ready(function(){
    var semifinishedData = {};
    var skuData = {};

    $('#addsemi').click(function(){
        var newInput = $('<div class="form-group"><div class="row">' +
            '<div class="col-md-4"><label>Semifinished Name</label>' +
            '<select class="form-control semi-select" required name="select_semi_name[]">' +
            '<option value="">--Select--</option></select></div>' +
            '<div class="col-md-2"><label>Unit</label>' +
            '<input type="text" class="form-control semi-unit" readonly></div>' +
            '<div class="col-md-2"><label>Unit Cost</label>' +
            '<input type="text" class="form-control semi-price" readonly></div>' +
            '<div class="col-md-2"><label>Quantity</label>' +
            '<input type="number" class="form-control semi-quantity" required name="semi_quantity[]" step="0.0000001"></div>' +
            '<div class="col-md-1"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
            '</div></div>');
        $('#semiInputs').append(newInput);

        if($.isEmptyObject(semifinishedData)){
            $.ajax({
                url: '<?php echo $this->Html->url(['controller'=>'dashboard','action'=>'fetch_semifinished_options']); ?>',
                type: 'GET', dataType: 'json',
                success: function(response){
                    if(response.response_code === 1){
                        semifinishedData = {};
                        $.each(response.data, function(i, item){ semifinishedData[item.id] = item; });
                        var select = newInput.find('select');
                        $.each(semifinishedData, function(id, item){ select.append('<option value="'+id+'">'+item.name+'</option>'); });
                    }
                }
            });
        } else {
            var select = newInput.find('select');
            $.each(semifinishedData, function(id, item){ select.append('<option value="'+id+'">'+item.name+'</option>'); });
        }
    });

    $(document).on('change', '.semi-select', function(){
        var id = $(this).val();
        var c = $(this).closest('.form-group');
        if(semifinishedData[id]){
            c.find('.semi-unit').val(semifinishedData[id].consumption_unit_name);
            c.find('.semi-price').val(semifinishedData[id].consumption_unit_price);
        } else { c.find('.semi-unit').val(''); c.find('.semi-price').val(''); }
    });

    $('#addsku').click(function(){
        var newInput = $('<div class="form-group"><div class="row">' +
            '<div class="col-md-4"><label>Sku Name</label>' +
            '<select class="form-control sku-select" required name="select_sku_name[]">' +
            '<option value="">--Select--</option></select></div>' +
            '<div class="col-md-2"><label>Unit</label>' +
            '<input type="text" class="form-control sku-unit" readonly></div>' +
            '<div class="col-md-2"><label>Unit Cost</label>' +
            '<input type="text" class="form-control sku-price" readonly></div>' +
            '<div class="col-md-2"><label>Quantity</label>' +
            '<input type="number" class="form-control sku-quantity" required name="sku_quantity[]" step="0.0000001"></div>' +
            '<div class="col-md-1"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
            '</div></div>');
        $('#skuInputs').append(newInput);

        if($.isEmptyObject(skuData)){
            $.ajax({
                url: '<?php echo $this->Html->url(['controller'=>'dashboard','action'=>'fetch_sku_options']); ?>',
                type: 'GET', dataType: 'json',
                success: function(response){
                    if(response.response_code === 1){
                        skuData = {};
                        $.each(response.data, function(i, item){ skuData[item.id] = item; });
                        var select = newInput.find('select');
                        $.each(skuData, function(id, item){ select.append('<option value="'+id+'">'+item.name+'</option>'); });
                    }
                }
            });
        } else {
            var select = newInput.find('select');
            $.each(skuData, function(id, item){ select.append('<option value="'+id+'">'+item.name+'</option>'); });
        }
    });

    $(document).on('change', '.sku-select', function(){
        var id = $(this).val();
        var c = $(this).closest('.form-group');
        if(skuData[id]){
            c.find('.sku-unit').val(skuData[id].consumption_unit_name);
            c.find('.sku-price').val(skuData[id].consumption_unit_price);
        } else { c.find('.sku-unit').val(''); c.find('.sku-price').val(''); }
    });

    $(document).on('click', '.removetax', function(){ $(this).closest('.form-group').remove(); });

    // Auto-fill conversion factor when a mapped unit is selected
    $(document).on('change', '#conv_to_unit', function(){
        var factor = $(this).find(':selected').data('factor');
        if (factor > 0) {
            $('#conv_factor').val(factor);
            $('#factor_hint').text('(from Unit Mapping — you can override for this SF)').css('color','#17a2b8');
        } else {
            $('#conv_factor').val('');
            $('#factor_hint').text('');
        }
    });
});
</script>
