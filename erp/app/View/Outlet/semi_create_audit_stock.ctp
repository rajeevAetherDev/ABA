<div class="row mb-3">
    <div class="col-sm-11">
        <h2 class="page-title">Create Semifinished Audit</h2>
    </div>
    <div class="col-sm-1 text-end">
        <a href="<?= $this->Html->url(['action' => 'semi_auditstock']) ?>">
            <button class="btn btn-danger" type="button">Back</button>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?= $this->Form->create(false, ['id' => 'auditStockForm']) ?>
        <?php $today = date('Y-m-d'); ?>

        <div class="form-group row">
            <div class="col-sm-6">
                <label>Audit Date</label>
                <input type="date" id="audit_date" class="form-control" value="<?= h($today) ?>" min="<?= h($today) ?>" required>
            </div>
            <div class="col-sm-6">
                <label>Audit Time</label>
                <input type="time" id="audit_time" class="form-control" value="<?= h($currentTime) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Remarks</label>
            <input type="text" id="remarks" class="form-control" placeholder="Please type a Remark...">
        </div>

        <div class="form-group">
            <label>Select Semifinished Items</label>
            <?php if (!empty($skus)): ?>
                <table class="table table-bordered table-striped" id="example">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Semifinished Name</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($skus as $row):
                            $sku = $row['inventory_semifinished']; ?>
                            <tr>
                                <td><?= $x++ ?></td>
                                <td><?= h($sku['name']) ?></td>
                                <td><?= h($sku['unit_name']) ?></td>
                                <td><?= h($sku['price']) ?></td>
                                <td>
                                    <input type="number" class="form-control qty-input" 
                                        id="quantity_<?= $sku['id'] ?>" min="0" value="0" style="width:100px">
                                </td>

                                <!-- Hidden Fields for JS -->
                                <input type="hidden" id="sku_name_<?= $sku['id'] ?>" value="<?= h($sku['name']) ?>">
                                <input type="hidden" id="unit_name_<?= $sku['id'] ?>" value="<?= h($sku['unit_name']) ?>">
                                <input type="hidden" id="unit_id_<?= $sku['id'] ?>" value="<?= h($sku['unit']) ?>">
                                <input type="hidden" id="price_<?= $sku['id'] ?>" value="<?= h($sku['price']) ?>">
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No Semifinished Items Found.</p>
            <?php endif; ?>
        </div>

        <div class="form-group text-end">
            <button class="btn btn-danger" type="button" onclick="window.location.href='<?= $this->Html->url(['action' => 'semi_auditstock']) ?>'">Cancel</button>
            <button class="btn btn-success" type="button" onclick="saveSemiAudit()">Save Audit</button>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<script>
    let selectedItems = {};
let table;

$(document).ready(function() {
    table = $('#example').DataTable({
        destroy: true,
        paging: true,
        searching: true,
        info: true
    });
});

// Store selected quantities
$(document).on('change', ".qty-input", function () {
    const qty = $(this).val().trim();
    const id = this.id.replace('quantity_', '');

    if (qty !== "" && qty !== "0") {
        selectedItems[id] = {
            id: id,
            sku_name: $('#sku_name_' + id).val(),
            unit_name: $('#unit_name_' + id).val(),
            unit_id: $('#unit_id_' + id).val(),
            quantity: qty,
            price: $('#price_' + id).val()
        };
    } else {
        delete selectedItems[id];
    }
});

// Save audit data via AJAX
function saveSemiAudit() {
    const audit_date = $('#audit_date').val();
    const audit_time = $('#audit_time').val();
    const remarks = $('#remarks').val();
    const dataList = Object.values(selectedItems);

    if (!audit_date || !audit_time) {
        Swal.fire({ icon: 'warning', text: 'Audit Date & Time are required.' });
        return;
    }

    if (dataList.length === 0) {
        Swal.fire({ icon: 'warning', text: 'Please enter quantity for at least one item.' });
        return;
    }

    $.ajax({
        url: '<?= $this->Html->url(['controller' => 'Outlet', 'action' => 'add_semi_auditstock']); ?>',
        type: 'POST',
        data: {
            audit_date: audit_date,
            audit_time: audit_time,
            remarks: remarks,
            checkedData: JSON.stringify(dataList)
        },
        success: function (response) {
            Swal.fire({ icon:'success', title:'Saved!', timer:1200, showConfirmButton:false })
            .then(() => { window.location.href = '<?= $this->Html->url(['action' => 'semi_auditstock']) ?>'; });
        }
    });
}

</script>