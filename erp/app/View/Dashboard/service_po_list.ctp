<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Service Purchase Orders</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box text-right">
            <a href="create_service_po" class="btn btn-success">Create Service PO</a>
        </div>
    </div>
</div>

<!-- FILTER + TABLE -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?= $this->Form->create(false, ['url'=>'service_po_list']) ?>

                <div class="row">
                    <div class="col-md-3">
                        <label>Date Range</label>
                        <input type="text" name="daterange" id="daterange"
                               class="form-control"
                               value="<?= date('m/d/Y',strtotime($startDate)).' - '.date('m/d/Y',strtotime($endDate)) ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Store</label>
                        <select name="data[Search][store]" class="form-control">
                            <option value="">All Stores</option>
                            <?php foreach($stores as $s): ?>
                                <option value="<?= $s['Location']['id'] ?>">
                                    <?= h($s['Location']['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Vendor</label>
                        <select name="data[Search][vendor]" class="form-control">
                            <option value="">All Vendors</option>
                            <?php foreach($vendors as $vid=>$vname): ?>
                                <option value="<?= $vid ?>"><?= h($vname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Status</label>
                        <select name="data[Search][status]" class="form-control">
                            <option value="">All</option>
                            <option value="1">Submitted</option>
                            <option value="2">Approved</option>
                            <option value="3">Processed</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <br>
                        <button class="btn btn-info btn-block">View</button>
                    </div>
                </div>

                <?= $this->Form->end() ?>

                <hr>

                <table id="example" class="table table-hover table-condensed">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SPO ID</th>
                            <th>Vendor</th>
                            <th>Store</th>
                            <th>Service Type</th>
                            <th>Period</th>
                            <th>Total (₹)</th>
                            <th>Approved</th>
                            <th>Processed</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $i=1; foreach($spos as $spo): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= h($spo['spo_id']) ?></td>
                            <td><?= h($spo['vendor']) ?></td>
                            <td><?= h($spo['store']) ?></td>
                            <td><?= h($spo['service_type']) ?></td>

                            <td>
                                <?php if($spo['period_from']): ?>
                                    <?= date('d M Y', strtotime($spo['period_from'])) ?>
                                    -
                                    <?= date('d M Y', strtotime($spo['period_to'])) ?>
                                <?php else: echo '—'; endif; ?>
                            </td>

                            <td><?= number_format($spo['total_amount'],2) ?></td>
                            <td><?= $spo['status']>=2 ? number_format($spo['approved_amount'],2) : '-' ?></td>
                            <td><?= $spo['status']>=3 ? number_format($spo['processed_amount'],2) : '-' ?></td>

                            <td>
                                <?php
                                $statusArr = [
                                    1 => 'Submitted',
                                    2 => 'Approved',
                                    3 => 'Processed'
                                ];
                                echo $statusArr[$spo['status']] ?? 'Inactive';
                                ?>
                            </td>

                            <td><?= h($spo['created_by']) ?></td>

                            <td>
                                <a href="view_service_po?id=<?= $spo['spo_id'] ?>" class="btn btn-info btn-sm">View</a>

                                <?php if($spo['status']==1): ?>
                                    <a href="edit_service_po?id=<?= $spo['spo_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <?php endif; ?>

                                <?php if($spo['status']==1): ?>
                                    <a href="view_service_po?id=<?= $spo['spo_id'] ?>#approve-section"
                                       class="btn btn-success btn-sm">Approve</a>
                                <?php endif; ?>

                                <?php if($spo['status']==2): ?>
                                    <a href="view_service_po?id=<?= $spo['spo_id'] ?>#process-section"
                                       class="btn btn-primary btn-sm">Process</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if(empty($spos)): ?>
                            <tr>
                                <td colspan="12" class="text-center">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<script>
$(function(){

    $('#example').DataTable({
        order: [],
        pageLength: 25
    });

    $('#daterange').daterangepicker({
        locale: { format: 'MM/DD/YYYY' },
        startDate: '<?= date('m/d/Y',strtotime($startDate)) ?>',
        endDate:   '<?= date('m/d/Y',strtotime($endDate)) ?>'
    });

});
</script>
