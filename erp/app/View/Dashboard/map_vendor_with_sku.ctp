<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Map SKUs <?php echo !empty($vendor_info) ? '- ' . htmlspecialchars($vendor_info['name']) . ($vendor_info['company'] ? ' (' . htmlspecialchars($vendor_info['company']) . ')' : '') : ''; ?></h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title"><a href="vendor_list"><button class='btn btn-danger' type="button" style="float:right;">Back</button></a></h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <!-- View/Assign Toggle Buttons & Actions -->
                <div class="form-group row align-items-center mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-info mode-tab-btn" id="btn_view_mode" onclick="switchMode('view')">
                            View
                        </button>
                        &nbsp;
                        <button type="button" class="btn btn-success mode-tab-btn" id="btn_assign_mode" onclick="switchMode('assign')">
                            Assign
                        </button>
                        &nbsp;
                        <button type="button" onclick="resetAllMappings()" class="btn btn-warning" id="resetbtn" style="float:right;">Reset All</button>
                    </div>
                </div>

                <div style="height:15px; width: 100%" class="clearfix"></div>

                <!-- VIEW MODE: Mapped SKUs Table -->
                <div id="view_mode_container">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6 col-sm-8">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold mb-1"><i class="fa fa-filter"></i> Filter Mapped SKUs by Category:</label>
                                <select id="view_category_filter" class="form-control">
                                    <option value="">-- All Categories --</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['inventory_category']['name']); ?>"><?php echo htmlspecialchars($cat['inventory_category']['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h4 class="header-title mt-0 mb-3">Mapped SKUs</h4>
                    <table id="example_view" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Brand</th>
                                <th>Unit Name</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $x = 1; foreach ($mapped_skus as $sku): 
                                $catName = !empty($sku['s']['cat_name']) ? $sku['s']['cat_name'] : (!empty($sku[0]['cat_name']) ? $sku[0]['cat_name'] : 'Uncategorized');
                                $subcatName = !empty($sku['s']['subcat_name']) ? $sku['s']['subcat_name'] : (!empty($sku[0]['subcat_name']) ? $sku[0]['subcat_name'] : '-');
                            ?>
                            <tr id="row-mapped-<?php echo $sku['s']['sku_id']; ?>">
                                <td><?php echo $x; ?></td>
                                <td><strong><?php echo htmlspecialchars($sku['s']['sku_name']); ?></strong></td>
                                <td><span class="badge badge-info" style="font-size:12px;"><?php echo htmlspecialchars($catName); ?></span></td>
                                <td><?php echo htmlspecialchars($subcatName); ?></td>
                                <td><?php echo htmlspecialchars($sku['s']['sku_brand']); ?></td>
                                <td><?php echo htmlspecialchars($sku['s']['sku_unit_name']); ?></td>
                                <td>&#8377; <?php echo htmlspecialchars($sku['v']['vendor_price']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-sku-btn" data-sku-id="<?php echo $sku['s']['sku_id']; ?>" data-sku-name="<?php echo htmlspecialchars($sku['s']['sku_name']); ?>">Remove</button>
                                </td>
                            </tr>
                            <?php $x++; endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ASSIGN MODE: Unmapped SKUs Table -->
                <div id="assign_mode_container" style="display: none;">
                    <!-- Category Selection & Quick Actions Bar -->
                    <div class="row align-items-center mb-3 p-3 bg-light rounded border">
                        <div class="col-md-5 col-sm-12 mb-2 mb-md-0">
                            <label class="font-weight-bold mb-1 text-primary"><i class="fa fa-tags"></i> Assign SKUs by Category:</label>
                            <select id="assign_category_filter" class="form-control">
                                <option value="">-- All Categories --</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['inventory_category']['name']); ?>"><?php echo htmlspecialchars($cat['inventory_category']['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-7 col-sm-12 text-md-right">
                            <label class="d-none d-md-block mb-1">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btn_select_all_category" onclick="selectAllFiltered(true)">
                                    <i class="fa fa-check-square"></i> Select All Filtered / In Category
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_deselect_all_category" onclick="selectAllFiltered(false)">
                                    <i class="fa fa-square"></i> Deselect All
                                </button>
                            </div>
                        </div>
                    </div>

                    <h4 class="header-title mt-0 mb-3">Assign SKUs</h4>
                    <table id="example_assign" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Brand</th>
                                <th>Unit Name</th>
                                <th>Price</th>
                                <th>Map &nbsp;<input type="checkbox" id="select_all" title="Select / Deselect all in current view"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $x = 1; foreach ($unmapped_skus as $sku): 
                                $catName = !empty($sku['s']['cat_name']) ? $sku['s']['cat_name'] : (!empty($sku[0]['cat_name']) ? $sku[0]['cat_name'] : 'Uncategorized');
                                $subcatName = !empty($sku['s']['subcat_name']) ? $sku['s']['subcat_name'] : (!empty($sku[0]['subcat_name']) ? $sku[0]['subcat_name'] : '-');
                            ?>
                            <tr class="sku-assign-row" data-category="<?php echo htmlspecialchars($catName); ?>">
                                <td><?php echo $x; ?></td>
                                <td><strong><?php echo htmlspecialchars($sku['s']['sku_name']); ?></strong></td>
                                <td><span class="badge badge-info" style="font-size:12px;"><?php echo htmlspecialchars($catName); ?></span></td>
                                <td><?php echo htmlspecialchars($subcatName); ?></td>
                                <td><?php echo htmlspecialchars($sku['s']['sku_brand']); ?></td>
                                <td><?php echo htmlspecialchars($sku['s']['sku_unit_name']); ?></td>
                                <td>
                                    <input type="number" 
                                           step="any" 
                                           class="form-control price-input" 
                                           style="width:120px" 
                                           id="price_<?php echo $sku['s']['sku_id']; ?>" 
                                           value="<?php echo htmlspecialchars($sku['s']['base_price'] ?? 0); ?>" 
                                           data-sku-id="<?php echo $sku['s']['sku_id']; ?>">
                                </td>
                                <td>
                                    <input type="checkbox" 
                                           class="checkbox sku-select-checkbox" 
                                           id="seltask_<?php echo htmlspecialchars($sku['s']['sku_id']); ?>"
                                           data-sku-id="<?php echo htmlspecialchars($sku['s']['sku_id']); ?>"
                                           data-sku-name="<?php echo htmlspecialchars($sku['s']['sku_name']); ?>"
                                           data-sku-brand="<?php echo htmlspecialchars($sku['s']['sku_brand']); ?>"
                                           data-unit-name="<?php echo htmlspecialchars($sku['s']['sku_unit_name']); ?>"
                                           data-unit-id="<?php echo htmlspecialchars($sku['s']['sku_unit'] ?? ''); ?>"
                                           data-vendor-id="<?php echo (int)$id; ?>">
                                </td>
                            </tr>
                            <?php $x++; endforeach; ?>
                        </tbody>
                    </table>
                    <br>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span id="selected_count_badge" class="badge badge-pill badge-primary p-2" style="font-size: 13px;">0 SKUs selected</span>
                        </div>
                        <div>
                            <button onclick="switchMode('view')" class="btn btn-secondary mr-2">Cancel</button>
                            <button onclick="getCheckedCheckboxes()" class="btn btn-success" id="dltbtn">
                                <i class="fa fa-link"></i> Map Selected SKUs
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
var viewTable, assignTable;

$(document).ready(function() {
    viewTable = $('#example_view').DataTable({
        pageLength: 10
    });
    assignTable = $('#example_assign').DataTable({
        pageLength: 10
    });

    // Category filter for View Mode table (Column 2 is Category)
    $('#view_category_filter').on('change', function() {
        var val = $(this).val();
        if (val) {
            viewTable.column(2).search('^' + $.fn.dataTable.util.escapeRegex(val) + '$', true, false).draw();
        } else {
            viewTable.column(2).search('').draw();
        }
    });

    // Category filter for Assign Mode table (Column 2 is Category)
    $('#assign_category_filter').on('change', function() {
        var val = $(this).val();
        if (val) {
            assignTable.column(2).search('^' + $.fn.dataTable.util.escapeRegex(val) + '$', true, false).draw();
        } else {
            assignTable.column(2).search('').draw();
        }
        updateSelectedCount();
    });

    // Toggle header select_all checkbox
    $('#select_all').on('change', function() {
        selectAllFiltered(this.checked);
    });

    // Individual checkbox change
    $(document).on('change', '.sku-select-checkbox', function() {
        updateSelectedCount();
    });

    // Remove individual SKU mapping from View Mode
    $(document).on('click', '.remove-sku-btn', function() {
        var skuId = $(this).data('sku-id');
        var skuName = $(this).data('sku-name');
        var vendorId = '<?php echo (int)$id; ?>';

        Swal.fire({
            title: 'Remove Mapping?',
            text: 'Are you sure you want to remove "' + skuName + '" from this vendor?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'map_vendor_with_sku']); ?>',
                    type: 'POST',
                    data: {
                        action: 'remove_sku',
                        remove_sku_id: skuId,
                        vendor_id: vendorId
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire('Removed!', 'SKU mapping removed successfully.', 'success').then(function() {
                            window.location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to remove SKU mapping.', 'error');
                    }
                });
            }
        });
    });
});

function switchMode(mode) {
    if (mode === 'view') {
        $('#view_mode_container').show();
        $('#assign_mode_container').hide();
        $('#btn_view_mode').removeClass('btn-outline-info').addClass('btn-info');
        $('#btn_assign_mode').removeClass('btn-success').addClass('btn-outline-success');
    } else if (mode === 'assign') {
        $('#view_mode_container').hide();
        $('#assign_mode_container').show();
        $('#btn_assign_mode').removeClass('btn-outline-success').addClass('btn-success');
        $('#btn_view_mode').removeClass('btn-info').addClass('btn-outline-info');
    }
}

function selectAllFiltered(status) {
    var rows = assignTable.rows({ search: 'applied' }).nodes();
    $(rows).find('.sku-select-checkbox').prop('checked', status);
    $('#select_all').prop('checked', status);
    updateSelectedCount();
}

function updateSelectedCount() {
    var allNodes = assignTable.rows().nodes();
    var count = $(allNodes).find('.sku-select-checkbox:checked').length;
    $('#selected_count_badge').text(count + ' SKU' + (count === 1 ? '' : 's') + ' selected');
}

function getCheckedCheckboxes() {
    var allNodes = assignTable.rows().nodes();
    var checkedData = [];
    var missingPrice = false;

    $(allNodes).each(function() {
        var $row = $(this);
        var $chk = $row.find('.sku-select-checkbox');
        if ($chk.is(':checked')) {
            var $priceInput = $row.find('.price-input');
            var priceVal = $priceInput.val() ? $priceInput.val().trim() : '';

            if (!priceVal || parseFloat(priceVal) <= 0) {
                missingPrice = true;
            }

            checkedData.push({
                id: $chk.data('sku-id'),
                sku_name: $chk.data('sku-name'),
                sku_brand: $chk.data('sku-brand'),
                unit_name: $chk.data('unit-name'),
                unit_id: $chk.data('unit-id'),
                vendor_id: $chk.data('vendor-id'),
                price: priceVal
            });
        }
    });

    if (checkedData.length === 0) {
        Swal.fire('Warning', 'Please select at least one SKU to map.', 'warning');
        return;
    }

    if (missingPrice) {
        Swal.fire('Warning', 'Please ensure all selected SKUs have a valid price greater than 0.', 'warning');
        return;
    }
    
    var $btn = $('#dltbtn');
    if ($btn.data('submitting')) return;
    $btn.data('submitting', true).prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm" role="status"></span> Mapping...');

    $.ajax({
        url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'map_vendor_with_sku']); ?>',
        type: 'POST',
        data: { checkedData: JSON.stringify(checkedData), vendor_id: '<?php echo (int)$id; ?>' },
        dataType: 'json',
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'SKUs Mapped Successfully',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then(function() {
                window.location.href = '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'map_vendor_with_sku']); ?>?id=<?php echo (int)$id; ?>';
            });
        },
        error: function(xhr, status, error) {
            $btn.data('submitting', false).prop('disabled', false).html('<i class="fa fa-link"></i> Map Selected SKUs');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred: ' + error,
                confirmButtonColor: '#d33'
            });
        }
    });
}

function resetAllMappings() {
    Swal.fire({
        title: 'Reset all mapped SKUs?',
        text: 'This will remove all current SKU mappings for this vendor.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, reset',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'map_vendor_with_sku']); ?>',
                type: 'POST',
                data: { action: 'reset_all', vendor_id: '<?php echo (int)$id; ?>' },
                dataType: 'json',
                success: function() {
                    Swal.fire('Reset!', 'All SKU mappings cleared.', 'success').then(function() {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Reset failed. Please try again.', 'error');
                }
            });
        }
    });
}
</script>