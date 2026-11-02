<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">View Sku</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                   
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="sku_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name','class'=>'form-control','value' =>$products[0]['inventory_skus']['name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('cat', array('div'=>array('class'=>'form-group'), 'label'=>'Category','class'=>'form-control','value' =>$products[0]['inventory_skus']['cat_name'],'readonly'=>'readonly'));
                                                if($products[0]['inventory_skus']['subcat_name']){
                                                    echo $this->Form->input('cat', array('div'=>array('class'=>'form-group'), 'label'=>'Sub Category','class'=>'form-control','value' =>$products[0]['inventory_skus']['subcat_name'],'readonly'=>'readonly'));
                                                }
                                                echo $this->Form->input('brand', array('div'=>array('class'=>'form-group'), 'label'=>'Brand','class'=>'form-control','value' =>$products[0]['inventory_skus']['brand'],'readonly'=>'readonly'));
                                                echo $this->Form->input('unit', array('div'=>array('class'=>'form-group'), 'label'=>'Purchase Unit Name', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['unit_name'],'readonly'=>'readonly'));
                                                $itemTypeValue = $products[0]['inventory_skus']['item_type'];

                                                $itemTypeLabel = ($itemTypeValue == 1) ? 'Saleable' : 'Non-saleable';
                                                
                                                echo $this->Form->input('item_type', [
                                                    'div' => ['class' => 'form-group'],
                                                    'label' => 'Item Type',
                                                    'class' => 'form-control',
                                                    'value' => $itemTypeLabel,
                                                    'readonly' => 'readonly'
                                                ]);
                                                echo $this->Form->input('price', array('div'=>array('class'=>'form-group'), 'label'=>'Purchase Price', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['price'],'readonly'=>'readonly'));
                                                echo $this->Form->input('conunit', array('div'=>array('class'=>'form-group'), 'label'=>'Consumption Unit Name', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['consumption_unit_name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('confacot', array('div'=>array('class'=>'form-group'), 'label'=>'Consumption Factor', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['consumption_factor'],'readonly'=>'readonly'));
                                                echo $this->Form->input('conprice', array('div'=>array('class'=>'form-group'), 'label'=>'Consumption Price', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['consumption_unit_price'],'readonly'=>'readonly'));
                                                echo $this->Form->input('moq', [
                                                    'div' => ['class'=>'form-group'],
                                                    'label' => 'MOQ',
                                                    'class' => 'form-control',
                                                    'value' => $products[0]['inventory_skus']['moq'],
                                                    'readonly' => 'readonly'
                                                ]);
                                                echo $this->Form->input('stock', [
                                                    'div' => ['class'=>'form-group'],
                                                    'label' => 'Required Stock',
                                                    'class' => 'form-control',
                                                    'value' => $products[0]['inventory_skus']['stock'],
                                                    'readonly' => 'readonly'
                                                ]);
                                                echo $this->Form->input('remarks', array('div'=>array('class'=>'form-group'), 'label'=>'Remarks', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['remarks'],'readonly'=>'readonly','rows'=>3,'readonly'=>'readonly'));
                                                
                                                $taxoptions=[];
                                                foreach($taxes as $tax){
                                                    // print_r($tax);exit;
                                                    $taxoptions[$tax['inventory_sku_tax']['id']] = $tax['0']['taxname'];
                                                }
                                                echo $this->Form->input('tax', array('label'=>'Selected Tax', 'class'=>'form-control','options'=>$taxoptions, 'multiple'=>true,'readonly'=>'readonly'));
                                            ?>
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>

                    <?php
                    // Build excise rate map: location_id => rates
                    $exciseRateMap = [];
                    foreach ($sku_excise_rates as $er) {
                        $lid = $er['inventory_sku_excise']['location_id'];
                        $exciseRateMap[$lid] = $er['inventory_sku_excise'];
                    }
                    $hasExcise = !empty($sku_excise_rates);
                    $skuExciseApplicable = !empty($products[0]['inventory_skus']['excise_applicable']);
                    $skuHasVariableTax   = !empty($products[0]['inventory_skus']['has_variable_tax']);
                    if ($hasExcise || $skuExciseApplicable || $skuHasVariableTax):
                    ?>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Tax Configuration</h5>
                                    <?php if ($skuHasVariableTax): ?>
                                        <span class="badge bg-info text-dark mb-2">Tax varies by state / location</span>
                                    <?php elseif ($skuExciseApplicable): ?>
                                        <span class="badge bg-warning text-dark mb-2">Excise Applicable (uniform)</span>
                                    <?php endif; ?>

                                    <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Location</th>
                                                <th>State</th>
                                                <th>Excise ₹/unit</th>
                                                <th>Addl. Excise ₹/unit</th>
                                                <th>VAT %</th>
                                                <th>TCS %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($all_locations as $loc):
                                            $lid   = $loc['locations']['id'];
                                            $lname = $loc['locations']['name'];
                                            $lstate= $loc['locations']['state'] ?? '';
                                            $epu   = isset($exciseRateMap[$lid]) ? floatval($exciseRateMap[$lid]['excise_per_unit'])      : 0;
                                            $aepu  = isset($exciseRateMap[$lid]) ? floatval($exciseRateMap[$lid]['addl_excise_per_unit']) : 0;
                                            $vatp  = isset($exciseRateMap[$lid]) ? floatval($exciseRateMap[$lid]['vat_percentage'])       : 0;
                                            $tcsp  = isset($exciseRateMap[$lid]) ? floatval($exciseRateMap[$lid]['tcs_percentage'])       : 0;
                                            $hasAny = ($epu > 0 || $aepu > 0 || $vatp > 0 || $tcsp > 0);
                                        ?>
                                        <tr <?= $hasAny ? 'class="table-success"' : '' ?>>
                                            <td><?= h($lname) ?></td>
                                            <td><?= h($lstate) ?></td>
                                            <td><?= $epu > 0 ? number_format($epu, 2) : '<span class="text-muted">—</span>' ?></td>
                                            <td><?= $aepu > 0 ? number_format($aepu, 2) : '<span class="text-muted">—</span>' ?></td>
                                            <td><?= $vatp > 0 ? number_format($vatp, 2) . '%' : '<span class="text-muted">—</span>' ?></td>
                                            <td><?= $tcsp > 0 ? number_format($tcsp, 2) . '%' : '<span class="text-muted">—</span>' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    </div>
                                    <?php if (!$hasExcise): ?>
                                    <p class="text-muted mt-2" style="font-size:12px;">No per-location excise rates configured yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
