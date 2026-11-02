<div class="row mb-3">
<div class="col-sm-11">
<h2 class="page-title">Create Product</h2>
</div>
<div class="col-sm-1 text-end">
<a href="product_list" class="btn btn-danger">Back</a>
</div>
</div>

<div class="card">
<div class="card-body col-md-8">
<?= $this->Form->create('Product', ['type' => 'file']); ?>
<div class="form-group mb-3">

<?= $this->Form->input('name', ['label' => 'Product Name', 'placeholder' => 'Enter product name', 'class' => 'form-control', 'id' => 'name', 'required' => true]); ?>

<div class="row">
<div class="col-md-12">
<?php
echo '<br>' . $this->Form->input('category', [
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


<?= $this->Form->input('description', ['label' => 'Description', 'placeholder' => 'Enter description', 'class' => 'form-control', 'rows' => 3]); ?>

<?= '<br>'.$this->Form->input('dish_type', [
'label' => 'Dish Type',
'id' => 'dish_type',
'class' => 'form-control',
'options' => ['0' => 'Food', '1' => 'Beverage', '2' => 'Liquor', '3' => 'Others']
]); ?>

<div class="form-group mb-3" id="food_type_wrapper">
<br><label for="food_type">Food Type</label>
<select name="Product[food_type]" id="food_type" class="form-control"></select>
</div>

<?= $this->Form->input('package', ['label' => 'Package', 'class' => 'form-control', 'options' => ['0' => 'No', '1' => 'Yes']]); ?>

<div class="row">
<div class="col-md-12">
<?php
$unitoptions = [];
foreach ($units as $unit) {
$unitoptions[$unit['Catalog_unit']['id']] = $unit['Catalog_unit']['name'];
}

echo '<br>' . $this->Form->input('unit', [
'label' => 'Select Unit',
'placeholder' => 'Choose unit',
'class' => 'form-control',
'options' => $unitoptions,
'empty' => '- Select Unit -',
'id' => 'select_unit'
]);
?>
<div id="customAddedUnits" class="mt-2"></div>
</div>
</div>
<div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title" id="addUnitModalLabel">Add New Unit</h5>
</div>

<div class="modal-body">
<input type="text" id="newUnitName" class="form-control" placeholder="Enter unit name">
</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" id="cancelUnit">Cancel</button>
<button type="button" class="btn btn-primary" id="saveUnit">Save</button>
</div>

</div>
</div>
</div>


<div class="row">
<div class="col-md-12">
<?php
$kdsoptions = [];
foreach ($kdss as $kds) {
    if (empty($kds['Pos_printer']['kds'])) {
        continue;
    }
    $groupKey = $kds['Pos_printer']['kds'];
    if (!isset($kdsoptions[$groupKey])) {
        $kdsoptions[$groupKey] = $kds['Pos_printer']['name'];
    }
}

echo '<br>' . $this->Form->input('kds', [
'label' => 'Select KDS',
'placeholder' => 'Choose KDS',
'class' => 'form-control',
'options' => $kdsoptions,
'empty' => '- Select KDS -',
'id' => 'select_kds'
]);
?>
<div id="customAddedKDS" class="mt-2"></div>
</div>
</div><div class="modal fade top-aligned" id="addKDSModal" tabindex="-1" aria-labelledby="addKDSModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title" id="addKDSModalLabel">Add New KDS</h5>
</div>

<div class="modal-body">
<div class="mb-3">
<label for="newKDSName" class="form-label"><strong>KDS Name</strong></label>
<input type="text" id="newKDSName" class="form-control" placeholder="Enter KDS name">
</div>
<div class="mb-2">
<label class="form-label"><strong>Stores</strong> <small class="text-muted">(at least 1 store + IP required)</small></label>
<div id="modalKdsStoreRows"></div>
<button type="button" id="modalAddKdsStoreBtn" class="btn btn-sm btn-secondary mt-2">+ Add Store</button>
</div>
</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" id="cancelKds" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-primary" id="saveKDS">Save</button>
</div>

</div>
</div>
</div>


<?= $this->Form->input('price', ['label' => 'Price', 'placeholder' => 'Enter price', 'id' => 'price', 'type' => 'number', 'step' => '0.00000001', 'required' => true, 'class' => 'form-control']); ?>

<div class="form-group mb-3">
<br><label for="is_discountable">
<?= $this->Form->checkbox('is_discountable', ['id' => 'is_discountable', 'checked' => true]); ?>
Allow Discounts
</label>
</div>

<?= '<br>'.$this->Form->input('preparation', ['label' => 'Preparation Time(In Minutes)', 'placeholder' => 'Select Preparation Time', 'value' => '0', 'id' => 'preparation', 'type' => 'number', 'required' => true, 'class' => 'form-control']); ?>
<?= '<br>'.$this->Form->input('serve_time', ['label' => 'Serve Time(In Minutes)', 'placeholder' => 'Enter Serve Time', 'id' => 'serve_time', 'value' => '0', 'type' => 'number',  'required' => true, 'class' => 'form-control']); ?>
<?= '<br>'.$this->Form->input('moq', ['label' => 'MOQ', 'placeholder' => 'Enter MOQ', 'id' => 'moq', 'type' => 'number', 'value' => '0', 'required' => true, 'class' => 'form-control']); ?>


<div class="form-group mb-3">
<div class="row">
<div class="col-md-12">
<br><label><strong>Taxes</strong></label>
<div id="customAddedTaxes" class="row mb-2">
<?php foreach ($taxes as $taxess) {
$taxID = $taxess['Catalog_tax']['id'];
$taxName = $taxess['Catalog_tax']['name'] . ' (' . $taxess['Catalog_tax']['percentage'] . '%)';
?>
<div class="col-md-3">
<?= $this->Form->checkbox('Product.tax_ids.' . $taxID, [
'value' => $taxID,
'id' => 'tax_' . $taxID,
'required' => true
]); ?><br>
<label for="tax_<?= $taxID ?>"><?= $taxName ?></label>
</div>
<?php } ?>
</div>
</div>
</div>
<div class="row">
<div class="col-md-12 ">
<button class="btn btn-info" type="button" id="openTaxModal" style='float:right;'>+ Tax</button>
</div>
</div>
</div>

<!-- TAX MODAL -->
<div class="modal fade" id="addTaxModal" tabindex="-1" aria-labelledby="addTaxModalLabel" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="addTaxModalLabel">Add New Tax</h5>
</div>
<div class="modal-body">
<input type="text" id="newTaxName" class="form-control mb-2" placeholder="Tax name">
<input type="number" id="newTaxValue" class="form-control" step="0.00000001" placeholder="Tax percentage">
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" id="cancelTax">Cancel</button>
<button type="button" class="btn btn-primary" id="saveTax">Save</button>
</div>
</div>
</div>
</div>

<div class="row">
  <div class="col-md-12">
    <br><label><strong>Modifier Groups</strong></label>
    <div class="row">
      <?php foreach ($modifiers as $modifier):
        
        if($modifier['Catalog_modifier_group']['group_type']==1){
            $group_type='Add-On';    
        }else{
            $group_type='Variant';
        }
        $taxID = $modifier['Catalog_modifier_group']['id'];
        $taxName = $modifier['Catalog_modifier_group']['name'] . ' (' . $group_type . ')';
      ?>
        <div class="col-md-3 mb-2">
          <?= $this->Form->checkbox('Product.modifiers.' . $taxID, [
            'value' => $taxID,
            'id' => 'modifier_' . $taxID
          ]); ?><br>
          <label for="modifier_<?= $taxID ?>"><?= h($taxName) ?></label>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<br>
<?php
// Define all fields, including Sort Order, with type and label
$fields = [
'sort_order'    => ['label' => 'Sort Order', 'type' => 'number'],
'recommended'   => ['label' => 'Recommended', 'type' => 'text'],
'variation'     => ['label' => 'Variation', 'type' => 'text'],
'allergy_tags'  => ['label' => 'Allergy Tags', 'type' => 'text'],
'weight'        => ['label' => 'Weight', 'type' => 'number'],
'serves_people' => ['label' => 'Serves People', 'type' => 'number'],
'image'         => ['label' => 'Upload Image', 'type' => 'file'],
];




foreach ($fields as $field => $config): ?>
<?php
$label = $config['label'];
$type = $config['type'];
$id = 'field_' . $field;
$btnId = 'btn_' . $field;
$wrapperId = 'wrapper_' . $field;
?>
<div class="mb-3">
<button type="button" id="<?= h($btnId) ?>" class="btn btn-sm btn-outline-primary">
+ Add <?= h($label) ?>
</button>
<div id="<?= h($wrapperId) ?>" style="display: none; margin-top: 10px;">
<?= $this->Form->control($field, [
'label' => $label,
'placeholder' => $label,
'type' => $type,
'class' => 'form-control',
'id' => $id,
'min' => ($type === 'number') ? 0 : null
]) ?>
</div>
</div>
<?php endforeach; ?>

<!-- Custom Attributes Section -->
<div class="mb-3">
<button type="button" id="addAttributeBtn" class="btn btn-sm btn btn-secondary">
+ Add Attribute
</button>
<div id="attributeContainer" class="mt-3"></div>
</div>



<br>
<a href="product_list" class="btn btn-danger me-2">Cancel</a>
<?= $this->Form->button('Save', ['class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn']); ?>
</div>
<?= $this->Form->end(); ?>
</div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
const fields = [
'sort_order',
'recommended',
'variation',
'allergy_tags',
'weight',
'serves_people',
'image'
];

fields.forEach(function (field) {
const input = document.getElementById('field_' + field);
const wrapper = document.getElementById('wrapper_' + field);
const button = document.getElementById('btn_' + field);

button.addEventListener('click', function () {
wrapper.style.display = 'block';
button.style.display = 'none';
input?.focus();
});

input?.addEventListener('blur', function () {
const isEmpty = (input.type === 'file') ? !input.value : !input.value.trim();
if (isEmpty) {
wrapper.style.display = 'none';
button.style.display = 'inline-block';
}
});
});

const addBtn = document.getElementById('addAttributeBtn');
const container = document.getElementById('attributeContainer');
let attributeIndex = 0;

addBtn.addEventListener('click', function () {
const lastWrapper = container.lastElementChild;
if (lastWrapper) {
const lastNameInput = lastWrapper.querySelector('.attr-name');
const lastValueInput = lastWrapper.querySelector('.attr-value');

const nameFilled = lastNameInput?.value.trim() !== '';
const valueFilled = lastValueInput?.value.trim() !== '';

if (!nameFilled || !valueFilled) {
Swal.fire({
icon: 'warning',
title: 'Complete the Current Attribute',
text: 'Please fill both name and value before adding another attribute.',
});

// Auto-focus the first empty field
if (!nameFilled) {
lastNameInput.focus();
} else if (!valueFilled) {
lastValueInput.focus();
}
return;
}
}

const wrapper = document.createElement('div');
wrapper.className = 'row g-2 align-items-end mb-2';
wrapper.setAttribute('data-index', attributeIndex);

const nameFieldName = `custom_attributes[${attributeIndex}][name]`;
const valueFieldName = `custom_attributes[${attributeIndex}][value]`;

wrapper.innerHTML = `
<div class="col-md-5">
<input type="text" name="${nameFieldName}" class="form-control attr-name" placeholder="Attribute Name (e.g. Color)" required>
</div>
<div class="col-md-5">
<input type="text" name="${valueFieldName}" class="form-control attr-value" placeholder="Value (e.g. Red)" required>
</div>
<div class="col-md-2">
<button type="button" class="btn btn-sm btn-danger removeAttributeBtn">X</button>
</div>
`;

container.appendChild(wrapper);
attributeIndex++;
});

// Handle removal
container.addEventListener('click', function (e) {
if (e.target.classList.contains('removeAttributeBtn')) {
e.target.closest('.row')?.remove();
}
});

});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
// === Utilities ===
function showModal(modalInstance) {
modalInstance.show();
}

function hideModal(modalInstance) {
modalInstance.hide();
}

function resetInputFields(...fields) {
fields.forEach(field => field.value = '');
}

function createOption(text, value, selected = false) {
return new Option(text, value, selected, selected);
}

function optionExists(select, valueOrText, by = 'value') {
return [...select.options].some(opt => opt[by] === valueOrText);
}

function removeOption(select, value) {
const index = [...select.options].findIndex(opt => opt.value === value);
if (index !== -1) select.remove(index);
}

function addBadge(container, value, className, dataAttr, labelText) {
const badge = document.createElement('span');
badge.className = `badge d-inline-flex align-items-center ${className} text-white rounded-pill px-3 py-2 me-2 mb-2`;
badge.innerHTML = `
<span style="font-size: 0.85rem;">${labelText}</span>
<button type="button" class="btn btn-sm text-white ms-2 removeBtn" data-val="${value}" style="background:transparent;border:none;">❌</button>
`;
container.appendChild(badge);
}

// === Tax Modal ===
const openTaxModalBtn = document.getElementById('openTaxModal');
const addTaxModal = new bootstrap.Modal(document.getElementById('addTaxModal'));
const saveTaxBtn = document.getElementById('saveTax');
const newTaxName = document.getElementById('newTaxName');
const newTaxValue = document.getElementById('newTaxValue');
const customAddedTaxes = document.getElementById('customAddedTaxes');

openTaxModalBtn.addEventListener('click', () => showModal(addTaxModal));

saveTaxBtn.addEventListener('click', () => {
const name = newTaxName.value.trim();
const percentage = parseFloat(newTaxValue.value.trim());

if (!name || isNaN(percentage)) return alert('Please enter a valid tax name and percentage.');

const exists = [...document.querySelectorAll('input[name^="Product[custom_taxes]"]')]
.some(input => input.value.toLowerCase() === name.toLowerCase());

if (exists) return alert("Tax with this name already exists.");

const key = 'custom_' + Date.now();
const checkboxId = 'tax_' + key;

const wrapper = document.createElement('div');
wrapper.className = 'col-md-3 mb-2';
wrapper.innerHTML = `
<input type="hidden" name="Product[custom_taxes][${key}][name]" value="${name}">
<input type="hidden" name="Product[custom_taxes][${key}][percentage]" value="${percentage}">
<input type="checkbox" name="Product[tax_ids][${key}]" value="1" id="${checkboxId}" checked>
<label for="${checkboxId}">${name} (${percentage}%)</label>
`;
customAddedTaxes.appendChild(wrapper);

resetInputFields(newTaxName, newTaxValue);
hideModal(addTaxModal);
});

document.getElementById('cancelTax').addEventListener('click', () => {
resetInputFields(newTaxName, newTaxValue);
hideModal(addTaxModal);
});

// === KDS Modal ===
const kdsSelect = document.getElementById('select_kds');
const addKDSModal = new bootstrap.Modal(document.getElementById('addKDSModal'));
const saveKDSBtn = document.getElementById('saveKDS');
const customKDSContainer = document.getElementById('customAddedKDS');
const ADD_KDS_VALUE = 'add_new_kds';

const kdsStoreOptionsHtml = <?= json_encode((function() use ($locations) {
    $html = '<option value="">Select store</option>';
    if (!empty($locations)) {
        foreach ($locations as $loc) {
            $html .= '<option value="' . h($loc['Location']['id']) . '">' . h($loc['Location']['name']) . '</option>';
        }
    }
    return $html;
})()) ?>;

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function getSelectedModalStores(exceptRow) {
    const vals = [];
    document.querySelectorAll('#modalKdsStoreRows .modal-store-row').forEach(row => {
        if (row === exceptRow) return;
        const sel = row.querySelector('.modal-kds-store-select');
        if (sel && sel.value) vals.push(String(sel.value));
    });
    return vals;
}

function refreshModalStoreOptions() {
    const used = getSelectedModalStores(null);
    document.querySelectorAll('#modalKdsStoreRows .modal-store-row').forEach(row => {
        const sel = row.querySelector('.modal-kds-store-select');
        if (!sel) return;
        const cur = String(sel.value || '');
        Array.from(sel.options).forEach(opt => {
            const v = String(opt.value || '');
            if (v === '' || v === cur) {
                opt.disabled = false;
            } else {
                opt.disabled = used.includes(v);
            }
        });
    });
}

function addModalStoreRow() {
    const container = document.getElementById('modalKdsStoreRows');
    const row = document.createElement('div');
    row.className = 'modal-store-row form-group mb-2';
    row.innerHTML = `
        <div class="row align-items-center">
            <div class="col-md-5">
                <label class="small text-muted mb-1">Store</label>
                <select class="form-control modal-kds-store-select">
                    ${kdsStoreOptionsHtml}
                </select>
            </div>
            <div class="col-md-5 modal-kds-ip-wrap" style="display:none;">
                <label class="small text-muted mb-1">IP</label>
                <input type="text" class="form-control modal-kds-ip-input" placeholder="Enter IP">
            </div>
            <div class="col-md-2 text-end">
                <label class="small d-block mb-1">&nbsp;</label>
                <button type="button" class="btn btn-sm btn-danger modal-kds-remove-store">Remove</button>
            </div>
        </div>
    `;
    container.appendChild(row);
    refreshModalStoreOptions();
}

document.getElementById('modalAddKdsStoreBtn').addEventListener('click', () => {
    const lastRow = document.querySelector('#modalKdsStoreRows .modal-store-row:last-child');
    if (lastRow) {
        const sel = lastRow.querySelector('.modal-kds-store-select');
        const ip = lastRow.querySelector('.modal-kds-ip-input');
        if (!sel.value || !ip.value.trim()) {
            Swal.fire({ icon: 'warning', title: 'Complete current store', text: 'Please select a store and enter its IP before adding another.' });
            if (!sel.value) sel.focus(); else ip.focus();
            return;
        }
    }
    addModalStoreRow();
});

document.getElementById('modalKdsStoreRows').addEventListener('change', (e) => {
    if (e.target.classList.contains('modal-kds-store-select')) {
        const row = e.target.closest('.modal-store-row');
        const ipWrap = row.querySelector('.modal-kds-ip-wrap');
        const ipInput = row.querySelector('.modal-kds-ip-input');
        if (e.target.value) {
            ipWrap.style.display = '';
            ipInput.focus();
        } else {
            ipWrap.style.display = 'none';
            ipInput.value = '';
        }
        refreshModalStoreOptions();
    }
});

document.getElementById('modalKdsStoreRows').addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-kds-remove-store')) {
        const rows = document.querySelectorAll('#modalKdsStoreRows .modal-store-row');
        if (rows.length > 1) {
            e.target.closest('.modal-store-row').remove();
            refreshModalStoreOptions();
        } else {
            const row = e.target.closest('.modal-store-row');
            row.querySelector('.modal-kds-store-select').value = '';
            row.querySelector('.modal-kds-ip-wrap').style.display = 'none';
            row.querySelector('.modal-kds-ip-input').value = '';
            refreshModalStoreOptions();
        }
    }
});

function toggleAddKDSOption(add = true) {
    if (add && !optionExists(kdsSelect, ADD_KDS_VALUE)) {
        kdsSelect.add(createOption('+ Add New KDS', ADD_KDS_VALUE));
    } else if (!add) {
        removeOption(kdsSelect, ADD_KDS_VALUE);
    }
}

kdsSelect.addEventListener('change', function () {
    if (this.value === ADD_KDS_VALUE) {
        document.getElementById('newKDSName').value = '';
        const rowsContainer = document.getElementById('modalKdsStoreRows');
        rowsContainer.innerHTML = '';
        addModalStoreRow();
        showModal(addKDSModal);
        this.value = '';
    }
});

saveKDSBtn.addEventListener('click', () => {
    const name = document.getElementById('newKDSName').value.trim();
    const form = kdsSelect.closest('form');
    const customKey = 'custom_' + Date.now();

    const errors = [];
    if (!name) errors.push('KDS Name is required');

    const stores = [];
    const seenStoreIds = new Set();
    document.querySelectorAll('#modalKdsStoreRows .modal-store-row').forEach(row => {
        const storeId = row.querySelector('.modal-kds-store-select').value.trim();
        const ip = row.querySelector('.modal-kds-ip-input').value.trim();
        if (storeId && ip) {
            if (seenStoreIds.has(storeId)) {
                errors.push('Duplicate store selected');
            }
            seenStoreIds.add(storeId);
            stores.push({ store_id: storeId, ip: ip });
        } else if (storeId && !ip) {
            errors.push('IP is required for the selected store');
        }
    });

    if (stores.length === 0) {
        errors.push('Please select at least 1 store with IP');
    }

    if (errors.length) {
        Swal.fire({ icon: 'error', title: 'Validation Error', html: `<ul style="text-align:left;">${errors.map(e => `<li>${e}</li>`).join('')}</ul>` });
        return;
    }

    if (optionExists(kdsSelect, name, 'text')) {
        Swal.fire({ icon: 'warning', title: 'Duplicate Name', text: 'KDS with this name already exists' });
        return;
    }

    toggleAddKDSOption(false);
    kdsSelect.add(createOption(name, customKey, true));
    kdsSelect.value = customKey;

    addBadge(customKDSContainer, customKey, 'bg-secondary', 'removeKDSBtn', name);

    let hiddenInputs = `<div id="custom_kds_holder_${customKey}">
        <input type="hidden" name="custom_kds[${customKey}][name]" value="${escapeHtml(name)}">`;
    stores.forEach((st, idx) => {
        hiddenInputs += `
        <input type="hidden" name="custom_kds[${customKey}][stores][${idx}][store_id]" value="${escapeHtml(st.store_id)}">
        <input type="hidden" name="custom_kds[${customKey}][stores][${idx}][ip]" value="${escapeHtml(st.ip)}">`;
    });
    hiddenInputs += `</div>`;
    form.insertAdjacentHTML('beforeend', hiddenInputs);

    document.getElementById('newKDSName').value = '';
    document.getElementById('modalKdsStoreRows').innerHTML = '';
    hideModal(addKDSModal);
});

customKDSContainer.addEventListener('click', function (e) {
    if (e.target.classList.contains('removeBtn')) {
        const val = e.target.dataset.val;
        removeOption(kdsSelect, val);
        e.target.closest('.badge').remove();
        const holder = document.getElementById('custom_kds_holder_' + val);
        if (holder) holder.remove();
        if (kdsSelect.value === val) kdsSelect.value = '';
        toggleAddKDSOption(true);
    }
});

document.getElementById('cancelKds').addEventListener('click', () => {
    document.activeElement.blur();
    hideModal(addKDSModal);
});

toggleAddKDSOption(true);

// === Unit Modal ===
const unitSelect = document.getElementById('select_unit');
const addUnitModal = new bootstrap.Modal(document.getElementById('addUnitModal'));
const saveUnitBtn = document.getElementById('saveUnit');
const newUnitNameInput = document.getElementById('newUnitName');
const customUnitContainer = document.getElementById('customAddedUnits');
const ADD_UNIT_VALUE = 'add_new_unit';

function toggleAddUnitOption(add = true) {
if (add && !optionExists(unitSelect, ADD_UNIT_VALUE)) {
unitSelect.add(createOption('+ Add New Unit', ADD_UNIT_VALUE));
} else if (!add) {
removeOption(unitSelect, ADD_UNIT_VALUE);
}
}

unitSelect.addEventListener('change', function () {
if (this.value === ADD_UNIT_VALUE) {
showModal(addUnitModal);
this.value = '';
}
});

saveUnitBtn.addEventListener('click', function () {
const newUnit = newUnitNameInput.value.trim();
if (!newUnit) return alert('Please enter a unit name');
if (optionExists(unitSelect, newUnit)) return alert("Unit already exists");

toggleAddUnitOption(false);
unitSelect.add(createOption(newUnit, newUnit, true));
unitSelect.value = newUnit;
addBadge(customUnitContainer, newUnit, 'bg-success', 'removeUnitBtn', newUnit);

resetInputFields(newUnitNameInput);
hideModal(addUnitModal);
});

customUnitContainer.addEventListener('click', function (e) {
if (e.target.classList.contains('removeBtn')) {
const val = e.target.dataset.val;
removeOption(unitSelect, val);
if (unitSelect.value === val) unitSelect.value = '';
e.target.closest('.badge')?.remove();
toggleAddUnitOption(true);
}
});

document.getElementById('cancelUnit').addEventListener('click', () => {
document.activeElement.blur();
hideModal(addUnitModal);
});

toggleAddUnitOption(true);

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
function updateFoodTypeOptions() {
const dishType = $('#dish_type').val();
const $foodType = $('#food_type');
let options = [];

if (dishType === '0') {
options = [
{ value: 0, text: 'Veg' },
{ value: 1, text: 'Non-Veg' }
];
} else if (dishType === '1' || dishType === '2') {
options = [
{ value: 2, text: 'Non-Alcoholic' },
{ value: 3, text: 'Alcoholic' }
];
} else {
options = [
{ value: 0, text: 'Veg' },
{ value: 1, text: 'Non-Veg' },
{ value: 2, text: 'Non-Alcoholic' },
{ value: 3, text: 'Alcoholic' }
];
}

$foodType.empty();
options.forEach(opt => {
$foodType.append(`<option value="${opt.value}">${opt.text}</option>`);
});
}

$('#dish_type').on('change', updateFoodTypeOptions);
updateFoodTypeOptions();

$('#submitBtn').click(function (e) {
e.preventDefault();
let errors = [];

const requiredFields = [
{ id: 'name', label: 'Product Name' },
{ id: 'select_unit', label: 'Unit' },
{ id: 'select_kds', label: 'KDS' },
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

// ✅ Validate Tax (custom or checked)
const isCustomTaxAdded = $('#customAddedTaxes input[name^="Product[custom_taxes]"]').length > 0;

// const isTaxChecked = $('input[type="checkbox"][name^="Product[tax_ids]"]:checked').length > 0;
const isTaxChecked = $('input[type="checkbox"][name*="tax_ids"]:checked').length > 0;

if (!isCustomTaxAdded && !isTaxChecked) {
errors.push('Please select or add a tax');
}


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
$(this).closest('form').submit();
});

});
</script>


