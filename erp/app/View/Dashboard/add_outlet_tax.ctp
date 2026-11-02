<?php
/**
 * add_outlet_tax.ctp
 *
 * Separate page for adding outlet-wise tax overrides.
 * Styled after the "Assign Products" page pattern.
 * 
 * Controller: DashboardController
 * Actions used:
 *   - add_outlet_tax()              → this view
 *   - get_categories_by_outlet()    → AJAX: returns categories for a location_id
 *   - get_products_by_categories()  → AJAX: returns products for location_id + cat_ids[]
 *   - save_outlet_tax()             → AJAX: upsert Catalog_product_tax_outlet row
 *
 * NOTE: category selection is a UI-only filter — cat_id / cat_name are NOT saved.
 */
?>

<!-- PAGE TITLE -->
<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Add Tax Override</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="<?= Router::url(['controller' => 'Dashboard', 'action' => 'outlet_tax_list']) ?>">
                    <button class="btn btn-danger" type="button">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>

<!-- ── STEP 1: Outlet & Tax Config ─────────────────────────────────────────── -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <div class="form-group row">

                    <!-- Outlet -->
                    <div class="col-md-4">
                        <label class="font-weight-bold">
                            Outlet <span class="text-danger">*</span>
                        </label>
                        <select id="sel_outlet" class="form-control" onchange="onOutletChange(this)">
                            <option value="">-- Select Outlet --</option>
                            <?php foreach ($outlets as $out): ?>
                            <option value="<?= $out['Location']['id'] ?>">
                                <?= h($out['Location']['name']) ?>
                                <?= $out['Location']['state'] ? ' (' . h($out['Location']['state']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
					
					<?php $today = date('Y-m-d'); ?>
                    <!-- Effective From -->
                    <div class="col-md-4">
                        <label class="font-weight-bold">
                            Effective From
                        </label>
                        <input type="date" id="inp_effdate" class="form-control" value="<?= h($today) ?>">
                    </div>

                    <!-- Tax -->
                <!--    <div class="col-md-3">
                        <label class="font-weight-bold">
                            Tax <span class="text-danger">*</span>
                        </label>
                        <select id="sel_tax" class="form-control" onchange="autoFillPct(this)">
                            <option value="">-- Select Tax --</option>
                            <?php foreach ($taxes as $t): ?>
                            <option value="<?= $t['Catalog_tax']['id'] ?>"
                                    data-pct="<?= $t['Catalog_tax']['percentage'] ?>">
                                <?= h($t['Catalog_tax']['name']) ?>
                                (<?= $t['Catalog_tax']['percentage'] ?>%)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="font-weight-bold">
                            Override Tax % <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="inp_pct" class="form-control"
                               step="0.01" min="0" max="100" placeholder="e.g. 9">
                    </div> -->
					<!-- Taxes Multi-Checkbox -->
					<div class="col-md-12 mt-4">
						<label class="font-weight-bold">
							Taxes <span class="text-danger">*</span>
						</label>
						<div id="tax_checkbox_group" class="border rounded p-3"
							 style="display:flex; flex-wrap:wrap; gap:10px 30px;">
							<?php foreach ($taxes as $t): ?>
							<div class="form-check" style="min-width:160px;">
								<input class="form-check-input tax-chk" type="checkbox"
									   id="tax_<?= $t['Catalog_tax']['id'] ?>"
									   value="<?= $t['Catalog_tax']['id'] ?>"
									   data-name="<?= h($t['Catalog_tax']['name']) ?>"
									   data-pct="<?= $t['Catalog_tax']['percentage'] ?>">
								<label class="form-check-label"
									   for="tax_<?= $t['Catalog_tax']['id'] ?>">
									<?= h($t['Catalog_tax']['name']) ?>
									(<?= $t['Catalog_tax']['percentage'] ?>%)
								</label>
							</div>
							<?php endforeach; ?>
						</div>
						<small class="text-muted">Select one or more taxes to apply as override.</small>
					</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── STEP 2: Category Filter ─────────────────────────────────────────────── -->
<div class="row" id="cat_section" style="display:none;">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row mb-0">
                    <div class="col-md-12">
                        <label class="font-weight-bold">
                            Filter by Category
                            <small class="text-muted font-weight-normal">
                                — select one or more to load products
                            </small>
                        </label>
                        <select id="sel_category" class="form-control select2" multiple
                                style="width:100%" disabled>
								
                        </select>
                        <small id="cat_loading" class="text-muted" style="display:none;">
                            <i class="fa fa-spinner fa-spin"></i> Loading categories…
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── STEP 3: Products Table ──────────────────────────────────────────────── -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <div class="row mb-2">
                    <div class="col-md-6">
                        <input type="text" id="prod_search" class="form-control"
                               placeholder="Search products…"
                               oninput="filterProdTable(this.value)"
                               style="display:none;">
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-info" id="prod_count_label"></small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">

                        <table id="prod_table"
                               class="display table table-hover table-condensed"
                               cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll"
                                               onchange="toggleProdCheckAll(this)"></th>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Taxes</th>
                                    <th>Base Price</th>
                                </tr>
                            </thead>
                            <tbody id="prod_tbody">
                                <tr id="prod_placeholder_row">
                                    <td colspan="4" class="text-center text-muted">
                                        Select an outlet and category to load products.
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div id="prod_loading" class="text-center py-3" style="display:none;">
                            <i class="fa fa-spinner fa-spin fa-lg"></i>
                            <span class="ml-2">Loading products…</span>
                        </div>

                    </div>
                </div>
				
				<!-- ── Save Loader Overlay ─────────────────────────────────────────────────── -->
				<div id="save_overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
					 background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
					<div style="background:#fff; border-radius:10px; padding:30px 40px; text-align:center; min-width:200px;">
						<i class="fa fa-spinner fa-spin fa-3x text-success"></i>
						<p class="mt-3 mb-0 font-weight-bold" style="font-size:16px;">Saving overrides…</p>
						<small class="text-muted" id="save_progress_label"></small>
					</div>
				</div>

                <!-- Save / Cancel -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button class="btn btn-success" type="button"
                                id="submitBtn" style="float:right;">
                            Save Override(s)
                        </button>
                        <a href="<?= Router::url(['controller' => 'Dashboard', 'action' => 'outlet_tax_list']) ?>">
                            <button class="btn btn-danger" type="button"
                                    style="float:right; margin-right:10px;">
                                Cancel
                            </button>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
#prod_tbody tr.chk-selected { background: #e8f4fd; }
#prod_tbody tr td { vertical-align: middle; font-size: 13px; }
#prod_tbody tr:hover { cursor: pointer; }
</style>


<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
var URL_GET_CATEGORIES = '<?= Router::url(['controller' => 'Dashboard', 'action' => 'get_categories_by_outlet']) ?>';
var URL_GET_PRODUCTS   = '<?= Router::url(['controller' => 'Dashboard', 'action' => 'get_products_by_categories']) ?>';
var URL_SAVE_TAX       = '<?= Router::url(['controller' => 'Dashboard', 'action' => 'save_outlet_tax']) ?>';
var URL_LIST           = '<?= Router::url(['controller' => 'Dashboard', 'action' => 'outlet_tax_list']) ?>';

var selectedIds = {};
var prodTable = null;


// ── Outlet change ─────────────────────────────────────────
function onOutletChange(sel) {
    var outletId = $(sel).val();
	
	if ($.fn.DataTable.isDataTable('#prod_table')) {
        $('#prod_table').DataTable().clear().destroy();
        prodTable = null;
    }
	
    $('#sel_category').empty().prop('disabled', true).val(null).trigger('change');
    $('#cat_section').hide();
    resetProductTable();
    selectedIds = {};

    if (!outletId) return;

    $('#cat_loading').show();
    $('#cat_section').show();

    $.post(URL_GET_CATEGORIES, { location_id: outletId }, function (res) {
        $('#cat_loading').hide();

        if (!res.status || !res.data.length) {
            Swal.fire('No Categories', 'No categories found.', 'warning');
            return;
        }

        $.each(res.data, function (i, cat) {
            $('#sel_category').append(new Option(cat.category_name, cat.category_id));
        });

        $('#sel_category').prop('disabled', false).trigger('change');

    }, 'json');
}

// ── Load products ─────────────────────────────────────────
function loadProductsByCategories() {
    var outletId = $('#sel_outlet').val();
    var catIds   = $('#sel_category').val();

    resetProductTable();
    selectedIds = {};

    if (!outletId || !catIds || !catIds.length) {
        showPlaceholder('Select outlet & category.');
        return;
    }

    $('#prod_loading').show();

    $.post(URL_GET_PRODUCTS, { location_id: outletId, cat_ids: catIds }, function (res) {
        $('#prod_loading').hide();

        if (!res.status || !res.data.length) {
            showPlaceholder('No products found.');
            return;
        }

        buildProductTable(res.data);

    }, 'json');
}

// ── Build table ─────────────────────────────────────────
function buildProductTable(products) {

    var tbody = $('#prod_tbody').empty();

    $.each(products, function (i, p) {

        var pid = p.product_id;

        var row = `
            <tr id="row-${pid}" data-id="${pid}">
                <td><input type="checkbox" class="row-checkbox" data-id="${pid}"></td>
                <td>${p.product_id}</td>
                <td>${p.product_name}</td>
                <td>${p.cat_name}</td>
				<td>${p.taxes ? p.taxes : '<span class="text-muted">No tax</span>'}</td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="price-input form-control" data-id="${pid}" value="${p.price}" readonly>
                    </div>
                </td>
            </tr>
        `;

        tbody.append(row);
    });

	
	if ($.fn.DataTable.isDataTable('#prod_table')) {
		$('#prod_table').DataTable().clear().destroy();
		prodTable = null;
	}

    // Init DataTable
    prodTable = $('#prod_table').DataTable({
        pageLength: 10,
        ordering: false,
        searching: true
    });

    // Checkbox change
    $('#prod_tbody').off('change').on('change', '.row-checkbox', function () {
        var id = $(this).data('id');

        if (this.checked) selectedIds[id] = true;
        else delete selectedIds[id];

        updateCount();
    });

    // Select All
    $('#checkAll').off('change').on('change', function () {
        var rows = prodTable.rows({ search: 'applied' }).nodes();

        $('input.row-checkbox', rows).each(function () {
            var id = $(this).data('id');
            $(this).prop('checked', $('#checkAll').prop('checked'));

            if ($(this).prop('checked')) selectedIds[id] = true;
            else delete selectedIds[id];
        });

        updateCount();
    });

    // Maintain state on pagination
    prodTable.on('draw', function () {
        $('#prod_tbody input.row-checkbox').each(function () {
            var id = $(this).data('id');
            $(this).prop('checked', selectedIds[id] === true);
        });
    });

    // Auto select on price focus
    $('#prod_tbody').on('focus', '.price-input', function () {
        var id = $(this).data('id');
        var cb = $('#row-' + id).find('.row-checkbox');

        if (!cb.prop('checked')) {
            cb.prop('checked', true).trigger('change');
        }
    });

    updateCount();
}

// ── Helpers ─────────────────────────────────────────
function updateCount() {
    $('#prod_count_label').text(Object.keys(selectedIds).length + ' selected');
}

// function resetProductTable() {
    // $('#prod_tbody').empty();
    // if (prodTable) prodTable.destroy();
// }

function resetProductTable() {
    if ($.fn.DataTable.isDataTable('#prod_table')) {
        $('#prod_table').DataTable().clear().destroy();
        prodTable = null;
    }
    $('#prod_tbody').empty();
    $('#prod_search').hide();
    $('#prod_count_label').text('');
    selectedIds = {};
}

function showPlaceholder(msg) {
    $('#prod_tbody').html(`<tr><td colspan="4">${msg}</td></tr>`);
}
// ── Get selected taxes from checkboxes ─────────────────────────────────────
function getSelectedTaxes() {
    var taxes = [];
    $('.tax-chk:checked').each(function () {
        taxes.push({
            tax_id         : $(this).val(),
            tax_name       : $(this).data('name'),
            tax_percentage : $(this).data('pct')
        });
    });
    return taxes;
}

// ── Save ───────────────────────────────────────────────────────────────────
$('#submitBtn').on('click', function () {

    var outlet_id     = $('#sel_outlet').val();
    var selectedTaxes = getSelectedTaxes();
    var ids           = Object.keys(selectedIds);
    var eff_from      = $('#inp_effdate').val();

    if (!outlet_id) {
        Swal.fire('Warning', 'Please select an outlet.', 'warning');
        return;
    }

    if (!selectedTaxes.length) {
        Swal.fire('Warning', 'Please select at least one tax.', 'warning');
        return;
    }

    if (!ids.length) {
        Swal.fire('Warning', 'Please select at least one product.', 'warning');
        return;
    }

    var total = ids.length;
    var done  = 0;

    // Show loader
    $('#save_overlay').css('display', 'flex');
    $('#submitBtn').prop('disabled', true);
    $('#save_progress_label').text('0 of ' + total + ' products saved…');

    // Build one request per product (all taxes sent together)
    var requests = [];
    ids.forEach(function (pid) {

        // Build taxes array for this product
        var taxesPayload = [];
        selectedTaxes.forEach(function (tax) {
            taxesPayload.push({
                tax_id         : tax.tax_id,
                tax_percentage : tax.tax_percentage
            });
        });

        var postData = {
            product_id     : pid,
            outlet_id      : outlet_id,
            effective_from : eff_from,
            taxes          : taxesPayload   // array of {tax_id, tax_percentage}
        };

        var req = $.ajax({
            url        : URL_SAVE_TAX,
            method     : 'POST',
            data       : JSON.stringify(postData),
            contentType: 'application/json',
            dataType   : 'json'
        }).done(function () {
            done++;
            $('#save_progress_label').text(done + ' of ' + total + ' products saved…');
        });

        requests.push(req);
    });

    $.when.apply($, requests).then(function () {
        $('#save_overlay').hide();
        $('#submitBtn').prop('disabled', false);
        Swal.fire('Saved!', 'Tax overrides saved successfully.', 'success')
            .then(() => window.location.href = URL_LIST);
    }).fail(function () {
        $('#save_overlay').hide();
        $('#submitBtn').prop('disabled', false);
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
    });
});

jQuery(document).ready(function($) {
	if ($.fn.select2) {
		$('.select2').select2({
			placeholder: "Select cateogry",
			allowClear: true,
			width: '100%'
		});
	} else {
		console.error("Select2 library not loaded!");
	}
	
    $('#sel_category').on('change', function () {
        loadProductsByCategories();
    });
});
</script>