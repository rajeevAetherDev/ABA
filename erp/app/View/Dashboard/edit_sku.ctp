<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Update SKUs</h2>
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
                                <?php echo $this->Form->create('User',['type' => 'file']); ?>
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('id', array('value' =>$products[0]['inventory_skus']['id'],'type'=>'hidden'));
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name','id'=>'name','class'=>'form-control','value' =>$products[0]['inventory_skus']['name']));
                                                ?>
                                                
                                                
    <div class="row">
  <div class="col-md-12">
    <?php
    echo $this->Form->input('category', [
  'label' => 'Category',
  'class' => 'form-control',
  'options' => $options, 
 'id' => 'select_cat'
]);

?>
    <div id="customAddedCategories" class="mt-2"></div>
  </div>
</div>
<!-- Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    
      <div class="modal-header">
        <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5><!-- Instead of icon button, use visible close text -->

      </div>

      <div class="modal-body">
        <input type="text" id="newCategoryName" class="form-control" placeholder="Enter category name">
      </div>

      <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelCategory">Cancel</button>

        <button type="button" class="btn btn-primary" id="saveCategory">Save</button>
      </div>

    </div>
  </div>
</div>
<?php
                                                
                                            
$options = [];
$selectedunitId =$products[0]['inventory_skus']['unit']; 

$selectedunit = null;
foreach ($units as $unit) {
if ($unit['Catalog_unit']['id'] == $selectedcatId) {
$selectedunit = $unit['Catalog_unit'];
} else {
$options[$unit['Catalog_unit']['id']] = $unit['Catalog_unit']['name'];
}
}

if ($selectedunit) {
$options = array($selectedunit['id'] => $selectedunit['name']) + $options;
}

echo '<br>'.$this->Form->input('unit', [
    'div' => ['class' => 'form-group'],
    'label' => 'Select Purchase Unit',
    'class' => 'form-control',
    'options' => $options,
    'default' => $selectedunitId,
    'id' => 'select_unit',
]);


$itemType = $products[0]['inventory_skus']['item_type']; // 1 = Salable, 0 = Non-salable

echo '<br>' . $this->Form->input('item_type', [
    'div' => ['class' => 'form-group'],
    'label' => 'Item Type',
    'class' => 'form-control',
    'options' => [
        1 => 'Saleable',
        0 => 'Non-saleable'
    ],
    'value' => $itemType,   
    'id' => 'item_type',
]);

echo $this->Form->input('price', array('div'=>array('class'=>'form-group'), 'label'=>'Purchase Price','id'=>'price', 'class'=>'form-control','required'=>'required','type'=>'number','value' =>$products[0]['inventory_skus']['price']));
$hasVariableTax  = !empty($products[0]['inventory_skus']['has_variable_tax']) ? 1 : 0;
$exciseApplicable = !empty($products[0]['inventory_skus']['excise_applicable']) ? 1 : 0;
// Build per-location rate map
$exciseRateMap = [];
foreach ($sku_excise_rates as $er) {
    $exciseRateMap[$er['inventory_sku_excise']['location_id']] = $er['inventory_sku_excise'];
}
$selectedTaxIds = [];
foreach ($product_taxes as $pt) {
    if (!empty($pt['Inventory_sku_tax']['tax_id'])) {
        $selectedTaxIds[] = $pt['Inventory_sku_tax']['tax_id'];
    }
}
?>
<!-- ===== UNIFIED TAX CONFIGURATION CARD ===== -->
<div class="card mt-2 mb-2" style="border-left:3px solid #6f42c1; background:#faf8ff;">
<div class="card-body py-2">
    <h6 class="mb-2" style="font-size:13px; font-weight:600; color:#6f42c1;">Tax Configuration</h6>

    <!-- Mode toggle -->
    <div class="mb-3">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="data[User][has_variable_tax]"
                   id="tax_mode_uniform" value="0" <?= !$hasVariableTax ? 'checked' : '' ?>
                   onchange="switchTaxMode(0)">
            <label class="form-check-label" for="tax_mode_uniform">
                <b>Same tax at all locations</b>
                <small class="text-muted d-block" style="font-weight:normal;">Fill once — applies everywhere (e.g. glassware with CGST/SGST)</small>
            </label>
        </div>
        <div class="form-check form-check-inline mt-2">
            <input class="form-check-input" type="radio" name="data[User][has_variable_tax]"
                   id="tax_mode_variable" value="1" <?= $hasVariableTax ? 'checked' : '' ?>
                   onchange="switchTaxMode(1)">
            <label class="form-check-label" for="tax_mode_variable">
                <b>Tax varies by state / location</b>
                <small class="text-muted d-block" style="font-weight:normal;">Set per-location rates (e.g. liquor with state excise + VAT)</small>
            </label>
        </div>
    </div>

    <!-- ── UNIFORM MODE ─────────────────────────────── -->
    <div id="tax_uniform_section" <?= $hasVariableTax ? 'style="display:none;"' : '' ?>>
        <div class="row">
        <?php foreach ($taxes as $taxess):
            $taxID   = $taxess['Inventory_tax']['id'];
            $taxName = $taxess['Inventory_tax']['name'] . ' (' . $taxess['Inventory_tax']['percentage'] . '%)';
            $isChecked = in_array($taxID, $selectedTaxIds);
        ?>
        <div class="col-md-3">
            <?php echo $this->Form->input('tax_'.$taxID, ['label'=>$taxName,'value'=>$taxID,'checked'=>$isChecked,'type'=>'checkbox']); ?>
        </div>
        <?php endforeach; ?>
        </div>
        <button id="addtax" class="btn btn-sm btn-outline-secondary mt-2" type="button">+ Add Custom Tax</button>
        <div id="taxInputs" class="mt-1"></div>

        <!-- Excise sub-section inside uniform mode -->
        <div class="mt-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="excise_applicable"
                       name="data[User][excise_applicable]" value="1"
                       <?= ($exciseApplicable && !$hasVariableTax) ? 'checked' : '' ?>
                       onchange="toggleExciseTable(this.checked)">
                <label class="form-check-label" for="excise_applicable"><b>Excise Applicable</b>
                    <small class="text-muted fw-normal"> — enter per-location flat duty</small>
                </label>
            </div>
            <div id="excise_location_table" style="<?= ($exciseApplicable && !$hasVariableTax) ? '' : 'display:none;' ?>">
                <table class="table table-sm table-bordered mt-2">
                    <thead class="table-light">
                        <tr>
                            <th>Location</th><th>State</th>
                            <th>Excise ₹/unit</th><th>Addl. Excise ₹/unit</th>
                            <th>VAT %</th><th>TCS %</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($all_locations as $loc):
                        $lid   = $loc['locations']['id'];
                        $lname = $loc['locations']['name'];
                        $lstate= $loc['locations']['state'] ?? '';
                        $epu   = $exciseRateMap[$lid]['excise_per_unit']      ?? 0;
                        $aepu  = $exciseRateMap[$lid]['addl_excise_per_unit'] ?? 0;
                        $vatp  = $exciseRateMap[$lid]['vat_percentage']       ?? 0;
                        $tcsp  = $exciseRateMap[$lid]['tcs_percentage']       ?? 0;
                    ?>
                    <tr>
                        <td><?= h($lname) ?></td>
                        <td><?= h($lstate) ?></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][excise_per_unit]"
                               value="<?= $epu ?>" min="0" step="any"></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][addl_excise_per_unit]"
                               value="<?= $aepu ?>" min="0" step="any"></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][vat_percentage]"
                               value="<?= $vatp ?>" min="0" step="any"></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][tcs_percentage]"
                               value="<?= $tcsp ?>" min="0" step="any"></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- /uniform -->

    <!-- ── VARIABLE MODE ────────────────────────────── -->
    <div id="tax_variable_section" <?= !$hasVariableTax ? 'style="display:none;"' : '' ?>>
        <p class="text-muted mb-2" style="font-size:12px;">
            Set all tax rates per location. Leave 0 if a tax doesn't apply.
            <strong>TCS</strong> is a recoverable advance — it is tracked separately and not added to landed cost.
            <strong>VAT</strong> and <strong>Excise</strong> are irrecoverable and are included in landed cost.
        </p>
        <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle" style="min-width:700px;">
            <thead class="table-light">
                <tr>
                    <th>Location</th>
                    <th>State</th>
                    <th>Excise<br><small class="text-muted">₹/unit</small></th>
                    <th>Addl. Excise<br><small class="text-muted">₹/unit</small></th>
                    <th>VAT<br><small class="text-muted">%</small></th>
                    <th>TCS<br><small class="text-muted">%</small></th>
                </tr>
            </thead>
            <tbody>
            <!-- Base / Default row -->
            <tr class="table-warning">
                <td colspan="2">
                    <strong>★ Base / Default</strong>
                    <small class="text-muted d-block" style="font-size:11px;">Click "Apply ↓" to fill all locations</small>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_excise_pu" class="form-control form-control-sm" value="0" min="0" step="any" style="width:80px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseCol('excise_per_unit','base_excise_pu')">↓</button>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_addl_excise_pu" class="form-control form-control-sm" value="0" min="0" step="any" style="width:80px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseCol('addl_excise_per_unit','base_addl_excise_pu')">↓</button>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_vat_pct" class="form-control form-control-sm" value="0" min="0" step="any" style="width:70px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseCol('vat_percentage','base_vat_pct')">↓</button>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_tcs_pct" class="form-control form-control-sm" value="0" min="0" step="any" style="width:70px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseCol('tcs_percentage','base_tcs_pct')">↓</button>
                    </div>
                </td>
            </tr>
            <!-- Per-location rows -->
            <?php foreach ($all_locations as $loc):
                $lid   = $loc['locations']['id'];
                $lname = $loc['locations']['name'];
                $lstate= $loc['locations']['state'] ?? '';
                $epu   = $exciseRateMap[$lid]['excise_per_unit']      ?? 0;
                $aepu  = $exciseRateMap[$lid]['addl_excise_per_unit'] ?? 0;
                $vat   = $exciseRateMap[$lid]['vat_percentage']       ?? 0;
                $tcs   = $exciseRateMap[$lid]['tcs_percentage']       ?? 0;
            ?>
            <tr>
                <td><?= h($lname) ?></td>
                <td><?= h($lstate) ?></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input"
                           name="excise_rates[<?= $lid ?>][excise_per_unit]"
                           data-col="excise_per_unit" value="<?= $epu ?>" min="0" step="any"></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input"
                           name="excise_rates[<?= $lid ?>][addl_excise_per_unit]"
                           data-col="addl_excise_per_unit" value="<?= $aepu ?>" min="0" step="any"></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input"
                           name="excise_rates[<?= $lid ?>][vat_percentage]"
                           data-col="vat_percentage" value="<?= $vat ?>" min="0" step="any"></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input"
                           name="excise_rates[<?= $lid ?>][tcs_percentage]"
                           data-col="tcs_percentage" value="<?= $tcs ?>" min="0" step="any"></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <!-- hidden excise_applicable = 1 when variable mode active so controller saves correctly -->
        <input type="hidden" name="data[User][excise_applicable]" value="1">
    </div><!-- /variable -->

</div>
</div>

<script>
function switchTaxMode(variable) {
    document.getElementById('tax_uniform_section').style.display  = variable ? 'none' : '';
    document.getElementById('tax_variable_section').style.display = variable ? '' : 'none';
}
function toggleExciseTable(show) {
    document.getElementById('excise_location_table').style.display = show ? '' : 'none';
}
function applyBaseCol(colName, baseInputId) {
    var val = document.getElementById(baseInputId).value;
    document.querySelectorAll('input.tax-row-input[data-col="' + colName + '"]').forEach(function(inp) {
        inp.value = val;
    });
}
// Before submit: disable inputs in the hidden section so duplicate names
// don't overwrite the visible section's values in PHP $_POST.
(function() {
    var form = document.querySelector('form');
    if (!form) return;
    form.addEventListener('submit', function() {
        var uniformHidden  = document.getElementById('tax_uniform_section').style.display  === 'none';
        var variableHidden = document.getElementById('tax_variable_section').style.display === 'none';
        if (variableHidden) {
            // Uniform mode: disable variable section inputs
            document.querySelectorAll('#tax_variable_section input[name^="excise_rates"]')
                .forEach(function(el) { el.disabled = true; });
            // Also disable excise_location_table inputs if that sub-table is hidden
            var excTbl = document.getElementById('excise_location_table');
            if (excTbl && excTbl.style.display === 'none') {
                excTbl.querySelectorAll('input[name^="excise_rates"]')
                    .forEach(function(el) { el.disabled = true; });
            }
        }
        if (uniformHidden) {
            // Variable mode: disable uniform section inputs
            document.querySelectorAll('#tax_uniform_section input[name^="excise_rates"]')
                .forEach(function(el) { el.disabled = true; });
        }
    });
}());
$(document).ready(function(){
    $('#addtax').click(function(){
        var newInput = $('<div class="form-group"><div class="row">' +
                            '<div class="col-md-4"><label>Tax Name</label>' +
                            '<input type="text" class="form-control tax" required="required" name="tax_name[]"></div>' +
                            '<div class="col-md-4"><label>Tax Percentage</label>' +
                            '<input type="number" class="form-control tax" required="required" name="tax_value[]" step="0.1"></div>' +
                            '<div class="col-md-2"><br><button class="btn btn-danger removetax" type="button">Remove</button></div>' +
                        '</div>');
        $('#taxInputs').append(newInput);
    });
    $(document).on('click', '.removetax', function(){
        $(this).closest('.form-group').remove();
    });
});
</script>
<?php
// Suppress duplicate tax section below — rendering handled above
$taxoptions    = [];
$selectedTaxIds = $selectedTaxIds ?? [];  // already built above
?>
<?php
$unitOptions = [];
foreach ($conunits as $conunit) {
    $unitOptions[$conunit['Catalog_unit_mapping']['mapped_unit_id']] = $conunit['Catalog_unit_mapping']['mapped_unit_name'];
}
// Unit Conversion — editable: pick consumption unit & factor; cost auto-computed
?>
<div class="card mt-2 mb-2" style="border-left:3px solid #17a2b8; background:#f0faff;">
<div class="card-body py-2">
    <h6 class="text-info mb-2" style="font-size:13px; font-weight:600;">
        Unit Conversion
        <small class="text-muted fw-normal" style="font-size:11px;">
            &mdash; how recipes consume this SKU (e.g. buy 1 Pkt, use per Gms). Cost = Price &divide; Factor.
        </small>
    </h6>
    <div class="row align-items-end g-2">
        <div class="col-md-3">
            <label class="form-label text-muted" style="font-size:12px;">Purchase Unit (From)</label>
            <input type="text" class="form-control bg-light" readonly
                value="<?php echo htmlspecialchars($products[0]['inventory_skus']['unit_name']); ?>">
        </div>
        <div class="col-md-1 text-center" style="font-size:20px; padding-bottom:8px; color:#adb5bd;">&#8594;</div>
        <div class="col-md-3">
            <label class="form-label text-muted" style="font-size:12px;">Consumption Unit (To)</label>
            <select id="select_conunit" name="data[User][conunit]" class="form-control">
                <option value="">&#8212; Same as purchase &#8212;</option>
                <?php
                $curConunitId   = $products[0]['inventory_skus']['consumption_unit_id'] ?? '';
                $curConunitName = strtolower(trim($products[0]['inventory_skus']['consumption_unit_name'] ?? ''));
                foreach ($units as $u):
                    $uid   = $u['Catalog_unit']['id'];
                    $uname = $u['Catalog_unit']['name'];
                    // Match by ID first; fall back to name match for data-corrected SKUs
                    $sel = ($uid == $curConunitId || strtolower($uname) === $curConunitName) ? 'selected' : '';
                ?>
                <option value="<?php echo $uid; ?>" <?php echo $sel; ?>>
                    <?php echo htmlspecialchars($uname); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted" style="font-size:10px;">
                Selecting a mapped unit auto-fills Factor below.
            </small>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted" style="font-size:12px;">
                Factor <span style="font-size:10px; color:#6c757d;">(consumption units in 1 purchase unit)</span>
            </label>
            <input type="number" id="factor" name="data[User][factor]"
                class="form-control" step="0.001" min="0"
                value="<?php echo floatval($products[0]['inventory_skus']['consumption_factor'] ?? 1); ?>"
                placeholder="e.g. 1000 for Kg&#8594;Gms">
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted" style="font-size:12px;">
                Cost / Consumption Unit <span class="text-info" style="font-size:10px;">(Price &divide; Factor)</span>
            </label>
            <input type="text" id="conprice_display" class="form-control bg-light" readonly
                style="font-weight:600; color:#28a745;"
                value="&#8377;<?php echo number_format(floatval($products[0]['inventory_skus']['consumption_unit_price'] ?? 0), 4); ?>">
            <input type="hidden" id="conprice" name="data[User][conprice]"
                value="<?php echo floatval($products[0]['inventory_skus']['consumption_unit_price'] ?? 0); ?>">
        </div>
    </div>
</div>
</div>
<?php
echo $this->Form->input('moq', ['label' => 'MOQ', 'placeholder' => 'Enter MOQ', 'id' => 'moq', 'type' => 'number', 'required' => true,'value' =>$products[0]['inventory_skus']['moq'], 'class' => 'form-control']);
echo '<br>' . $this->Form->input('stock', ['label' => 'Required Stock', 'placeholder' => 'Enter Par Stock', 'id' => 'stock', 'type' => 'number','value' =>$products[0]['inventory_skus']['stock'], 'class' => 'form-control']);
echo '<br>' . $this->Form->input('brand', array('div'=>array('class'=>'form-group'), 'label'=>'Brand', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['brand']));

// Taxes rendered in unified Tax Configuration card above
echo $this->Form->input('remarks', array('div'=>array('class'=>'form-group'), 'label'=>'Remarks', 'class'=>'form-control','value' =>$products[0]['inventory_skus']['remarks'],'rows'=>3));
?>
                                        </div>
                                    </div>
                                    <a href="sku_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Update', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
// === Category Modal ===
  const selectCat = document.getElementById('select_cat');
  const addCatModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
  const saveCategoryBtn = document.getElementById('saveCategory');
  const newCategoryNameInput = document.getElementById('newCategoryName');
  const customCatContainer = document.getElementById('customAddedCategories');
  const ADD_CAT_VALUE = 'add_new';

  function toggleAddCatOption(add = true) {
    if (add && !optionExists(selectCat, ADD_CAT_VALUE)) {
      selectCat.add(createOption('+ Add New Category', ADD_CAT_VALUE));
    } else if (!add) {
      removeOption(selectCat, ADD_CAT_VALUE);
    }
  }

  selectCat.addEventListener('change', function () {
    if (this.value === ADD_CAT_VALUE) {
      showModal(addCatModal);
      this.value = '';
    }
  });

  saveCategoryBtn.addEventListener('click', function () {
    const newCat = newCategoryNameInput.value.trim();
    if (!newCat) return alert('Please enter a category name');
    if (optionExists(selectCat, newCat)) return alert("Category already exists");

    toggleAddCatOption(false);
    selectCat.add(createOption(newCat, newCat, true));
    selectCat.value = newCat;
    addBadge(customCatContainer, newCat, 'bg-info', 'removeCatBtn', newCat);

    resetInputFields(newCategoryNameInput);
    hideModal(addCatModal);
  });

  customCatContainer.addEventListener('click', function (e) {
    if (e.target.classList.contains('removeBtn')) {
      const val = e.target.dataset.val;
      removeOption(selectCat, val);
      if (selectCat.value === val) selectCat.value = '';
      e.target.closest('.badge')?.remove();
      toggleAddCatOption(true);
    }
  });

  document.getElementById('cancelCategory').addEventListener('click', () => {
    document.activeElement.blur();
    hideModal(addCatModal);
  });

  toggleAddCatOption(true);
});

</script>                   


<script>
$(function () {
    
$('#submitBtn').click(function (e) {
e.preventDefault();
let errors = [];

const requiredFields = [
{ id: 'name', label: 'Product Name' },
{ id: 'select_unit', label: 'Unit' },
{ id: 'price', label: 'Price' }
];

for (let field of requiredFields) {
let val = $('#' + field.id).val();
if (!val || val.trim() === "") {
errors.push(field.label + ' is required');
}
}

// ✅ Validate Category (custom or selected)
const isCategorySelected = $('#select_cat').val();
const isCustomCategoryAdded = $('#customAddedCategories .badge').length > 0;
if (!isCustomCategoryAdded && (!isCategorySelected || isCategorySelected.trim() === '')) {
errors.push('Please select or add a category');
}

// ✅ Validation for taxes
// const isTaxChecked = $('input[type="checkbox"][name^="data\\[User\\]\\[tax_"]').filter(':checked').length > 0;
// const isCustomTaxAdded = $('input[name="custom_tax_name[]"]').filter(function() {
//     return $(this).val().trim() !== '';
// }).length > 0;

// if (!isTaxChecked && !isCustomTaxAdded) {
//     errors.push('Please select a tax or add custom tax.');
// }



if (errors.length > 0) {
Swal.fire({
icon: 'error',
title: 'Validation Error',
html: '<ul style="text-align:left;">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>'
});
return false;
}

// Submit
$(this).prop('disabled', true).text('Saving...');
// Disable inputs in hidden section before submit to prevent duplicate-key overwrite in PHP
var _uniformHidden  = document.getElementById('tax_uniform_section').style.display  === 'none';
var _variableHidden = document.getElementById('tax_variable_section').style.display === 'none';
if (_variableHidden) {
    document.querySelectorAll('#tax_variable_section input[name^="excise_rates"]')
        .forEach(function(el) { el.disabled = true; });
    var _excTbl = document.getElementById('excise_location_table');
    if (_excTbl && _excTbl.style.display === 'none') {
        _excTbl.querySelectorAll('input[name^="excise_rates"]')
            .forEach(function(el) { el.disabled = true; });
    }
}
if (_uniformHidden) {
    document.querySelectorAll('#tax_uniform_section input[name^="excise_rates"]')
        .forEach(function(el) { el.disabled = true; });
}
$(this).closest('form').submit();
});

});
</script>


<script>
$(document).ready(function() {
    $('#select_unit').on('change', function() {
        var unitId = $(this).val();

        if (unitId) {
            $.ajax({
                url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'get_mapped_units']); ?>',
                type: 'GET',
                data: { unit_id: unitId },
                dataType: 'json',
                success: function(response) {
                    var $conunit = $('#select_conunit');
                    $conunit.empty(); // clear old options

                    if (response.length > 0) {
                        $conunit.append('<option value="">- Select Unit -</option>');
                        $.each(response, function(index, item) {
                            $conunit.append('<option value="' + item.mapped_unit_id + '">' + item.mapped_unit_name + '</option>');
                        });
                    } else {
                        $conunit.append('<option value="">No mapped units found</option>');
                        $('#factor').val('');
                        $('#conprice').val('');
                    }
                },
                error: function() {
                    alert('Error fetching mapped units');
                }
            });
        } else {
            $('#select_conunit').empty().append('<option value="">- Select Unit -</option>');
            $('#conprice').val('');
            $('#factor').val('');
        }
    });
});


</script>

<script>
$(document).ready(function() {
    // When purchase unit changes, we already update consumption units (your previous code)
    // Now, when consumption unit changes, fetch factor

     function updateConsumptionPrice() {
        const factor = parseFloat($('#factor').val());
        const purchasePrice = parseFloat($('#price').val());

        if (!isNaN(factor) && factor > 0 && !isNaN(purchasePrice) && purchasePrice > 0) {
            const conPrice = purchasePrice / factor;
            $('#conprice').val(conPrice.toFixed(6));
            $('#conprice_display').val('\u20b9' + conPrice.toFixed(4));
        } else {
            $('#conprice').val('');
            $('#conprice_display').val('');
        }
    }
    
    $('#select_conunit').on('change', function() {
        var purchaseUnitId = $('#select_unit').val();
        var consumptionUnitId = $(this).val();

        if (purchaseUnitId && consumptionUnitId) {
            $.ajax({
                url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'get_conversion_factor']); ?>',
                type: 'GET',
                data: {
                    unit_id: purchaseUnitId,
                    mapped_unit_id: consumptionUnitId
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.factor !== undefined) {
                        $('#factor').val(response.factor);
                        updateConsumptionPrice();

                        // const factor = parseFloat(response.factor);
                        // const purchaseprice = parseFloat($('#price').val()); // assuming you have an input with id purchaseprice
                        
                        // if (!isNaN(purchaseprice) && factor !== 0) {
                        //     const conprice = purchaseprice / factor;
                        //     $('#conprice').val(conprice.toFixed(2)); // rounding to 2 decimals
                        // } else {
                        //     $('#conprice').val('');
                        // }
                    } else {
                        $('#factor').val('');
                        $('#conprice').val('');
                    }
                },
                error: function() {
                    alert('Error fetching conversion factor');
                }
            });
        } else {
            $('#factor').val('');
            $('#conprice').val('');
        }
    });
    
    $('#factor').on('input change', updateConsumptionPrice);
    $('#price').on('input', function() {
        updateConsumptionPrice();
    });
});
</script>
