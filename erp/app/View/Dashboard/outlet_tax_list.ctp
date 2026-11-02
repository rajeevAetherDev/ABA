<?php
/**
 * outlet_tax_list.ctp
 * Lists all outlet-wise tax overrides with outlet filter, bulk delete, and check all.
 */
?>

<!-- PAGE TITLE -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Outlet-wise Tax Overrides
                <a href="add_outlet_tax" class="btn btn-success btn-sm" style="float:right">
                    + Add Override
                </a>
            </h2>
        </div>
    </div>
</div>

<!-- Info banner -->
<div class="alert alert-info">
    <strong>How this works:</strong> When a POS terminal syncs products, it will use the outlet-specific tax rate if one is configured here. If no override exists, the global product tax applies. Leave <em>Effective From</em> blank to activate immediately.
</div>

<!-- Outlet Filter + Bulk Delete -->
<div class="row mb-3">
    <div class="col-md-4">
        <select id="sel_outlet_filter" class="form-control">
            <option value="">Filter by Outlet</option>
            <?php foreach ($outlets as $out): ?>
            <option value="<?= h($out['Location']['name']) ?>">
                <?= h($out['Location']['name']) ?>
                <?= $out['Location']['state'] ? ' (' . h($out['Location']['state']) . ')' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-8 text-right">
        <button class="btn btn-danger btn-sm" id="btnDeleteSelected" disabled>
            <i class="fa fa-trash"></i> Delete Selected
        </button>
    </div>
</div>

<!-- Overrides table -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-hover table-sm" id="overridesTable">
                    <thead class="thead-dark">
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>#</th>
                            <th>Product</th>
                            <th>Outlet</th>
                            <th>Tax Name</th>
                            <th>Override %</th>
                            <th>Effective From</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($overrides)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No overrides configured yet.</td></tr>
                        <?php else: ?>
                        <?php $i=1; foreach ($overrides as $ov): $o = $ov['Catalog_product_tax_outlet']; ?>
                        <tr>
                            <td><input type="checkbox" class="row-chk" value="<?= $o['id'] ?>"></td>
                            <td><?= $i++; ?></td>
                            <td><?= h($o['product_name']) ?></td>
                            <td><?= h($o['outlet_name']) ?></td>
                            <td><?= h($o['tax_name']) ?></td>
                            <td><strong><?= $o['tax_percentage'] ?>%</strong></td>
                            <td><?= $o['effective_from'] ?? '—' ?></td>
                            <td>
                                <button class="btn btn-danger btn-xs btn-delete-override"
                                        data-id="<?= $o['id'] ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var overridesTable;
var selectedOverrideIds = {}; 


$(document).ready(function () {

    overridesTable = $('#overridesTable').DataTable({
        paging      : true,
        searching   : true,
        ordering    : true,
        info        : true,
        lengthChange: true,
        pageLength  : 10,
        columnDefs  : [
            { orderable: false, targets: [0, 7] }
        ]
    });

    // ── Outlet filter ──────────────────────────────────────
    $('#sel_outlet_filter').on('change', function () {
        overridesTable.column(3).search($(this).val()).draw();
    });

    // ── Check All ──────────────────────────────────────────
	$('#checkAll').on('change', function () {
		var checked = this.checked;
		overridesTable.rows({ search: 'applied' }).every(function () {
			var id = $(this.node()).find('.row-chk').val();
			$(this.node()).find('.row-chk').prop('checked', checked);
			if (checked) selectedOverrideIds[id] = true;
			else delete selectedOverrideIds[id];
		});
		updateDeleteBtn();
	});

    // ── Individual checkbox ────────────────────────────────
    $('#overridesTable tbody').on('change', '.row-chk', function () {
		var id = $(this).val();
		if (this.checked) selectedOverrideIds[id] = true;
		else delete selectedOverrideIds[id];

		var allChecked = overridesTable.rows({ search: 'applied' }).nodes()
			.toArray()
			.every(function (row) {
				return $('input.row-chk', row).prop('checked');
			});
		$('#checkAll').prop('checked', allChecked);
		updateDeleteBtn();
	});

    // ── Reset checkboxes on pagination/search ──────────────
    overridesTable.on('draw', function () {
        $('#checkAll').prop('checked', false);
        updateDeleteBtn();
    });

});

function updateDeleteBtn() {
    var count = Object.keys(selectedOverrideIds).length;
    $('#btnDeleteSelected')
        .prop('disabled', count === 0)
        .text(count > 0 ? 'Delete Selected (' + count + ')' : 'Delete Selected');
}

// ── Bulk Delete ────────────────────────────────────────────
$('#btnDeleteSelected').on('click', function () {
    var ids = [];
	var ids = Object.keys(selectedOverrideIds);
    if (!ids.length) return;
	

    Swal.fire({
        title             : 'Delete ' + ids.length + ' override(s)?',
        icon              : 'warning',
        showCancelButton  : true,
        confirmButtonText : 'Yes, delete all'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url      : '<?= Router::url(['controller' => 'Dashboard', 'action' => 'delete_outlet_tax']) ?>',
                type     : 'POST',
                dataType : 'json',
                data     : { ids: ids },
                success  : function (res) {
                    if (res.status) {
                        Swal.fire('Deleted!', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                }
            });
        }
    });
});

// ── Single Delete ──────────────────────────────────────────
$(document).on('click', '.btn-delete-override', function () {
    var id = $(this).data('id');
    Swal.fire({ 
        title             : 'Delete override?',
        icon              : 'warning',
        showCancelButton  : true,
        confirmButtonText : 'Yes, delete'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url      : '<?= Router::url(['controller' => 'Dashboard', 'action' => 'delete_outlet_tax']) ?>',
                type     : 'POST',
                dataType : 'json',
                data     : { ids: [id] },
                success  : function (res) {
                    if (res.status) {
                        location.reload();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                }
            });
        }
    });
});
</script>
