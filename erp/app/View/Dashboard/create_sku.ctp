<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create SKU</h2>
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
                                            
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name', 'class'=>'form-control','id'=>'name','required'=>'required'));
                                                ?>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <?php
                                                            echo $this->Form->input('category', [
                                                            'label' => 'Select Category',
                                                            'placeholder' => 'Choose category',
                                                            'class' => 'form-control',
                                                            'options' => $options, // No 'add_new' here!
                                                            'empty' => '- Select Category -',
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
                                                
                                                $unitoptions=[];
                                                foreach($units as $unit){
                                                    $unitoptions[$unit['Catalog_unit']['id']] = $unit['Catalog_unit']['name'];
                                                }
                                                    // Purchase Unit select
                                                    echo '<br>'.$this->Form->input('unit', [
                                                        'div' => ['class' => 'form-group'],
                                                        'label' => 'Select Purchase Unit',
                                                        'class' => 'form-control',
                                                        'options' => $unitoptions,
                                                        'empty' => '- Select Purchase Unit -',
                                                        'id' => 'select_unit',
                                                    ]);
                                                    echo '<br>' . $this->Form->input('item_type', [
                                                    	'div' => ['class' => 'form-group'],
                                                    	'label' => 'Item Type',
                                                    	'class' => 'form-control',
                                                    	'options' => [
                                                    		'1' => 'Saleable',
                                                    		'0' => 'Non-saleable'
                                                    	],
                                                    	'default' => 'salable',
                                                    	'id' => 'item_type',
                                                    ]);

                                                    echo $this->Form->input('price', array('div'=>array('class'=>'form-group'), 'label'=>'Purchase Price','id'=>'price', 'class'=>'form-control','required'=>'required','type'=>'number'));
                                                    
                                                    // Consumption Unit select - initially empty or with all options if you want
                                                    echo $this->Form->input('conunit', [
                                                        'div' => ['class' => 'form-group'],
                                                        'label' => 'Select Consumption Unit',
                                                        'class' => 'form-control',
                                                        'options' => [],  // empty at first
                                                        'empty' => '- Select Consumption Unit -',
                                                        'id' => 'select_conunit',
                                                    ]);
                                                    echo $this->Form->input('factor', array('div'=>array('class'=>'form-group'), 'label'=>'Consumption Factor','id'=>'factor', 'class'=>'form-control','readonly'=>true));
                                                    echo $this->Form->input('conprice', array('div'=>array('class'=>'form-group'), 'label'=>'Per Unit Price','id'=>'conprice', 'class'=>'form-control','readonly'=>true));

                                                    
                                                echo $this->Form->input('brand', array('div'=>array('class'=>'form-group'), 'label'=>'Brand', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>'));
                                                echo $this->Form->input('moq', ['label' => 'MOQ', 'placeholder' => 'Enter MOQ', 'id' => 'moq', 'type' => 'number', 'value' => '0', 'required' => true, 'class' => 'form-control']); 
                                                echo '<br>'.$this->Form->input('stock', ['label' => 'Required Stock', 'placeholder' => 'Enter Par Stock', 'id' => 'stock', 'type' => 'number', 'value' => '0', 'class' => 'form-control']); 

// ===== UNIFIED TAX CONFIGURATION CARD (create) =====
$selectedTaxIds = [];
?>
<!-- ===== UNIFIED TAX CONFIGURATION CARD ===== -->
<div class="card mt-2 mb-2" style="border-left:3px solid #6f42c1; background:#faf8ff;">
<div class="card-body py-2">
    <h6 class="mb-2" style="font-size:13px; font-weight:600; color:#6f42c1;">Tax Configuration</h6>

    <!-- Mode toggle -->
    <div class="mb-3">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="data[User][has_variable_tax]"
                   id="tax_mode_uniform" value="0" checked
                   onchange="switchTaxModeCreate(0)">
            <label class="form-check-label" for="tax_mode_uniform">
                <b>Same tax at all locations</b>
                <small class="text-muted d-block" style="font-weight:normal;">Fill once — applies everywhere</small>
            </label>
        </div>
        <div class="form-check form-check-inline mt-2">
            <input class="form-check-input" type="radio" name="data[User][has_variable_tax]"
                   id="tax_mode_variable" value="1"
                   onchange="switchTaxModeCreate(1)">
            <label class="form-check-label" for="tax_mode_variable">
                <b>Tax varies by state / location</b>
                <small class="text-muted d-block" style="font-weight:normal;">Set per-location rates (liquor, etc.)</small>
            </label>
        </div>
    </div>

    <!-- ── UNIFORM MODE ─────────────────────────────── -->
    <div id="tax_uniform_section_create">
        <div class="row">
        <?php foreach ($taxes as $taxess):
            $taxID   = $taxess['Inventory_tax']['id'];
            $taxName = $taxess['Inventory_tax']['name'] . ' (' . $taxess['Inventory_tax']['percentage'] . '%)';
        ?>
        <div class="col-md-3">
            <?php echo $this->Form->input('tax_'.$taxID, ['label'=>$taxName,'value'=>$taxID,'type'=>'checkbox']); ?>
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
                       onchange="toggleExciseTableCreate(this.checked)">
                <label class="form-check-label" for="excise_applicable"><b>Excise Applicable</b>
                    <small class="text-muted fw-normal"> — enter per-location flat duty</small>
                </label>
            </div>
            <div id="excise_location_table_create" style="display:none;">
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
                    ?>
                    <tr>
                        <td><?= h($lname) ?></td>
                        <td><?= h($lstate) ?></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][excise_per_unit]"
                               value="0" min="0" step="any"></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][addl_excise_per_unit]"
                               value="0" min="0" step="any"></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][vat_percentage]"
                               value="0" min="0" step="any"></td>
                        <td><input type="number" class="form-control form-control-sm"
                               name="excise_rates[<?= $lid ?>][tcs_percentage]"
                               value="0" min="0" step="any"></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- /uniform -->

    <!-- ── VARIABLE MODE ────────────────────────────── -->
    <div id="tax_variable_section_create" style="display:none;">
        <p class="text-muted mb-2" style="font-size:12px;">
            Set all tax rates per location. Leave 0 if a tax doesn't apply.
            <strong>TCS</strong> is recoverable (tracked separately, not in landed cost).
            <strong>VAT</strong> and <strong>Excise</strong> are irrecoverable (included in landed cost).
        </p>
        <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle" style="min-width:700px;">
            <thead class="table-light">
                <tr>
                    <th>Location</th><th>State</th>
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
                    <small class="text-muted d-block" style="font-size:11px;">Click "↓" to fill all locations</small>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_excise_pu_c" class="form-control form-control-sm" value="0" min="0" step="any" style="width:80px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseColCreate('excise_per_unit','base_excise_pu_c')">↓</button>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_addl_excise_pu_c" class="form-control form-control-sm" value="0" min="0" step="any" style="width:80px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseColCreate('addl_excise_per_unit','base_addl_excise_pu_c')">↓</button>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_vat_pct_c" class="form-control form-control-sm" value="0" min="0" step="any" style="width:70px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseColCreate('vat_percentage','base_vat_pct_c')">↓</button>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" id="base_tcs_pct_c" class="form-control form-control-sm" value="0" min="0" step="any" style="width:70px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyBaseColCreate('tcs_percentage','base_tcs_pct_c')">↓</button>
                    </div>
                </td>
            </tr>
            <?php foreach ($all_locations as $loc):
                $lid   = $loc['locations']['id'];
                $lname = $loc['locations']['name'];
                $lstate= $loc['locations']['state'] ?? '';
            ?>
            <tr>
                <td><?= h($lname) ?></td>
                <td><?= h($lstate) ?></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input-c"
                           name="excise_rates[<?= $lid ?>][excise_per_unit]"
                           data-col="excise_per_unit" value="0" min="0" step="any"></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input-c"
                           name="excise_rates[<?= $lid ?>][addl_excise_per_unit]"
                           data-col="addl_excise_per_unit" value="0" min="0" step="any"></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input-c"
                           name="excise_rates[<?= $lid ?>][vat_percentage]"
                           data-col="vat_percentage" value="0" min="0" step="any"></td>
                <td><input type="number" class="form-control form-control-sm tax-row-input-c"
                           name="excise_rates[<?= $lid ?>][tcs_percentage]"
                           data-col="tcs_percentage" value="0" min="0" step="any"></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <input type="hidden" name="data[User][excise_applicable]" value="1">
    </div><!-- /variable -->

</div>
</div>
<script>
function switchTaxModeCreate(variable) {
    document.getElementById('tax_uniform_section_create').style.display  = variable ? 'none' : '';
    document.getElementById('tax_variable_section_create').style.display = variable ? '' : 'none';
}
function toggleExciseTableCreate(show) {
    document.getElementById('excise_location_table_create').style.display = show ? '' : 'none';
}
function applyBaseColCreate(colName, baseInputId) {
    var val = document.getElementById(baseInputId).value;
    document.querySelectorAll('input.tax-row-input-c[data-col="' + colName + '"]').forEach(function(inp) {
        inp.value = val;
    });
}
// Before submit: disable inputs in the hidden section so duplicate names
// don't overwrite the visible section's values in PHP $_POST.
(function() {
    var form = document.querySelector('form');
    if (!form) return;
    form.addEventListener('submit', function() {
        var uniformHidden  = document.getElementById('tax_uniform_section_create').style.display  === 'none';
        var variableHidden = document.getElementById('tax_variable_section_create').style.display === 'none';
        if (variableHidden) {
            document.querySelectorAll('#tax_variable_section_create input[name^="excise_rates"]')
                .forEach(function(el) { el.disabled = true; });
            var excTbl = document.getElementById('excise_location_table_create');
            if (excTbl && excTbl.style.display === 'none') {
                excTbl.querySelectorAll('input[name^="excise_rates"]')
                    .forEach(function(el) { el.disabled = true; });
            }
        }
        if (uniformHidden) {
            document.querySelectorAll('#tax_uniform_section_create input[name^="excise_rates"]')
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
                                                echo $this->Form->input('remarks', array('div'=>array('class'=>'form-group'), 'label'=>'Remarks', 'class'=>'form-control','rows'=>3));
?>
                                        </div>
                                    </div>
                                    <a href="sku_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectCat = document.getElementById('select_cat');
    const addCatModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    const saveCategoryBtn = document.getElementById('saveCategory');
    const newCategoryNameInput = document.getElementById('newCategoryName');
    const customCatContainer = document.getElementById('customAddedCategories');
    const ADD_CAT_VALUE = 'add_new';

    function optionExists(select, value) {
        return Array.from(select.options).some(option => option.value === value);
    }

    function createOption(label, value, selected = false) {
        const opt = document.createElement('option');
        opt.text = label;
        opt.value = value;
        if (selected) opt.selected = true;
        return opt;
    }

    function toggleAddCatOption(add = true) {
        if (add && !optionExists(selectCat, ADD_CAT_VALUE)) {
            selectCat.add(createOption('+ Add New Category', ADD_CAT_VALUE));
        } else if (!add) {
            Array.from(selectCat.options).forEach(opt => {
                if (opt.value === ADD_CAT_VALUE) opt.remove();
            });
        }
    }

    function addBadge(container, label, className, btnClass, value) {
        const badge = document.createElement('span');
        badge.className = `badge ${className} me-2 p-2`;
        badge.innerHTML = `${label} <span class="removeBtn text-danger ms-2" style="cursor:pointer;" data-val="${value}">x</span>`;
        container.appendChild(badge);
    }

    function resetInputFields(input) {
        input.value = '';
    }

    function hideModal(modal) {
        modal.hide();
    }

    function showModal(modal) {
        modal.show();
    }

    toggleAddCatOption(true);

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
            Array.from(selectCat.options).forEach(opt => {
                if (opt.value === val) opt.remove();
            });
            if (selectCat.value === val) selectCat.value = '';
            e.target.closest('.badge')?.remove();
            toggleAddCatOption(true);
        }
    });

    document.getElementById('cancelCategory').addEventListener('click', () => {
        hideModal(addCatModal);
    });

    // Add New Tax Inputs
        $('#addtax').click(function () {
        const newInput = $(`
            <div class="form-group mt-2 custom-tax-group">
                <div class="row">
                    <div class="col-md-4">
                        <label>Tax Name</label>
                        <input type="text" class="form-control" name="custom_tax_name[]" required>
                    </div>
                    <div class="col-md-4">
                        <label>Tax Percentage</label>
                        <input type="number" class="form-control" name="custom_tax_value[]" required step="0.00000001">
                    </div>
                    <div class="col-md-2">
                        <br>
                        <button class="btn btn-danger removetax" type="button">Remove</button>
                    </div>
                </div>
            </div>
        `);
        $('#taxInputs').append(newInput);
    });

    $(document).on('click', '.removetax', function () {
        $(this).closest('.form-group').remove();
    });
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
var _uniformHidden  = document.getElementById('tax_uniform_section_create').style.display  === 'none';
var _variableHidden = document.getElementById('tax_variable_section_create').style.display === 'none';
if (_variableHidden) {
    document.querySelectorAll('#tax_variable_section_create input[name^="excise_rates"]')
        .forEach(function(el) { el.disabled = true; });
    var _excTbl = document.getElementById('excise_location_table_create');
    if (_excTbl && _excTbl.style.display === 'none') {
        _excTbl.querySelectorAll('input[name^="excise_rates"]')
            .forEach(function(el) { el.disabled = true; });
    }
}
if (_uniformHidden) {
    document.querySelectorAll('#tax_uniform_section_create input[name^="excise_rates"]')
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

        if (!isNaN(factor) && factor !== 0 && !isNaN(purchasePrice)) {
            const conPrice = purchasePrice / factor;
            $('#conprice').val(conPrice.toFixed(2));
        } else {
            $('#conprice').val('');
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
    
    $('#price').on('input', function() {
        updateConsumptionPrice();
    });
});
</script>


                 