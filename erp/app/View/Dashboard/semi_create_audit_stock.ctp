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
        <?= $this->Form->create('AuditStock', ['id' => 'auditStockForm']) ?>

        <div class="form-group row">
            <div class="col-sm-6">
                <label>Audit Date</label>
                <input type="date" name="audit_date" class="form-control" value="<?= h($today) ?>" required>
            </div>
            <div class="col-sm-6">
                <label>Audit Time</label>
                <input type="time" name="audit_time" class="form-control" value="<?= h($currentTime) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Remarks</label>
            <input type="text" name="remarks" class="form-control" placeholder="Please type a Remark..." >
        </div>

        <!--<div class="form-group">-->
        <!--    <label>Do you want to submit All Items?</label><br>-->
        <!--    <div class="form-check form-check-inline">-->
        <!--        <input class="form-check-input" type="radio" name="submit_all_items" id="submit_no" value="no" checked>-->
        <!--        <label class="form-check-label" for="submit_no">No</label>-->
        <!--    </div>-->
        <!--    <div class="form-check form-check-inline">-->
        <!--        <input class="form-check-input" type="radio" name="submit_all_items" id="submit_yes" value="yes">-->
        <!--        <label class="form-check-label" for="submit_yes">Yes</label>-->
        <!--    </div>-->
        <!--</div>-->

        <div class="form-group">
            <label>Select Semifinished</label>
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
                                    <input type="number" name="quantity[<?= $sku['id'] ?>]" class="form-control" min="0" value="0" style="width:100px">

                                </td>
                                <!-- Hidden fields -->
                                <input type="hidden" name="sku_id[<?= $sku['id'] ?>]" value="<?= $sku['id'] ?>">
                                <input type="hidden" name="sku_name[<?= $sku['id'] ?>]" value="<?= h($sku['name']) ?>">
                                <input type="hidden" name="unit_name[<?= $sku['id'] ?>]" value="<?= h($sku['unit_name']) ?>">
                                <input type="hidden" name="unit_id[<?= $sku['id'] ?>]" value="<?= h($sku['unit']) ?>">
                                <input type="hidden" name="price[<?= $sku['id'] ?>]" value="<?= h($sku['price']) ?>">
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No SKUs found.</p>
            <?php endif; ?>
        </div>

        <div class="form-group text-end">
            <a href="<?= $this->Html->url(['action' => 'semi_auditstock']) ?>">
                <button class="btn btn-danger" type="button">Cancel</button>
            </a>
            <button class="btn btn-success" type="submit" name="prepare_submit" value="1">Prepare</button>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
