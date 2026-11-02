<style>
    .badge-pending {
        background-color: #ffeeba;
        color: #856404;
        padding: 5px 12px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-block;
        font-size: 12px;
        border: 1px solid #ffe8a1;
    }
    .badge-approved {
        background-color: #d4edda;
        color: #155724;
        padding: 5px 12px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-block;
        font-size: 12px;
        border: 1px solid #c3e6cb;
    }
    .badge-rejected {
        background-color: #f8d7da;
        color: #721c24;
        padding: 5px 12px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-block;
        font-size: 12px;
        border: 1px solid #f5c6cb;
    }
    .filter-card {
        background: #fdfdfd;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 18px 20px;
        margin-bottom: 20px;
    }
    .action-btn-group {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }
    .btn-approve-action {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: #fff !important;
        font-weight: 500;
        padding: 4px 10px;
        font-size: 12px;
        border-radius: 4px;
    }
    .btn-approve-action:hover {
        background-color: #218838 !important;
    }
    .btn-reject-action {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #fff !important;
        font-weight: 500;
        padding: 4px 10px;
        font-size: 12px;
        border-radius: 4px;
    }
    .btn-reject-action:hover {
        background-color: #c82333 !important;
    }
    .btn-reopen {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 3px;
        margin-left: 4px;
    }
    .table td {
        vertical-align: middle !important;
    }
</style>

<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Editing Management</h2>
        </div>
    </div>
    <div class="col-sm-2 text-right">
        <div class="page-title-box">
            <button class="btn btn-info" type="button" id="exportdata" style="float:right;">
                <i class="fa fa-download"></i> Export CSV
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <!-- Filter Box -->
                <div class="filter-card">
                    <?php echo $this->Form->create('Filter', array('url' => 'editing_management', 'type' => 'post', 'class' => 'form-inline')); ?>
                    <div class="row w-100 align-items-end">
                        
                        <!-- Date Range -->
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="form-label font-weight-bold" style="display:block; margin-bottom:5px;">Select Date</label>
                            <input type="text" class="form-control calendarmultiple w-100" id="startdate" name="daterange" required value="<?php echo h($startdate); ?>">
                        </div>

                        <!-- Store Dropdown -->
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="form-label font-weight-bold" style="display:block; margin-bottom:5px;">Select Store</label>
                            <select class="form-control select2 w-100" name="store_id" id="store_id">
                                <option value="">All Stores</option>
                                <?php if (!empty($locations)): ?>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?php echo $loc['Location']['id']; ?>" <?php echo ($selectedStore == $loc['Location']['id']) ? 'selected' : ''; ?>>
                                            <?php echo h($loc['Location']['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Status Dropdown -->
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="form-label font-weight-bold" style="display:block; margin-bottom:5px;">Status</label>
                            <select class="form-control w-100" name="status" id="status_filter">
                                <option value="">All Statuses</option>
                                <option value="1" <?php echo ($selectedStatus === '1' || $selectedStatus === 1) ? 'selected' : ''; ?>>Pending</option>
                                <option value="2" <?php echo ($selectedStatus === '2' || $selectedStatus === 2) ? 'selected' : ''; ?>>Approved</option>
                                <option value="3" <?php echo ($selectedStatus === '3' || $selectedStatus === 3) ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-md-3 col-sm-6 mb-2">
                            <button class="btn btn-info mr-2" type="submit">
                                <i class="fa fa-filter"></i> Apply Filter
                            </button>
                            <a href="editing_management" class="btn btn-secondary">Reset</a>
                        </div>

                    </div>
                    <?php echo $this->Form->end(); ?>
                </div>

                <!-- Table Listing -->
                <div class="row">
                    <div class="col-12">
                        <table id="example" class="display table table-striped table-hover table-condensed" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="14%">Reference ID</th>
                                    <th width="14%">Store</th>
                                    <th width="12%">Cashier</th>
                                    <th width="18%">Type</th>
                                    <th width="18%">Remarks</th>
                                    <th width="10%">Status</th>
                                    <th width="13%">Action By</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($requests)): ?>
                                    <?php $i = 1; foreach ($requests as $row): ?>
                                        <?php 
                                            $item = $row['Editing_management'];
                                            $id = $item['id'];
                                            $refId = $item['reference_id'];
                                            $status = (int)$item['status'];
                                        ?>
                                        <tr id="row-<?php echo $id; ?>">
                                            <td><?php echo $i++; ?></td>
                                            <td>
                                                <strong class="text-primary"><?php echo h($refId); ?></strong>
                                                <br>
                                                <small class="text-muted"><i class="fa fa-clock-o"></i> <?php echo date('d-m-Y H:i', strtotime($item['created'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo h($item['store_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">Store ID: <?php echo h($item['store_id']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo h($item['cashier_name'] ? $item['cashier_name'] : 'Cashier #' . $item['cashier_id']); ?>
                                                <br>
                                                <small class="text-muted">ID: <?php echo h($item['cashier_id']); ?></small>
                                            </td>
                                            <td>
                                                <span>
                                                    <?php echo $item['type'] ?? ''; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; color: #495057;">
                                                    <?php echo nl2br(h($item['remarks'] ?? '-')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($status === 1): ?>
                                                    <span class="badge-pending"><i class="fa fa-clock-o"></i> Pending</span>
                                                <?php elseif ($status === 2): ?>
                                                    <span class="badge-approved"><i class="fa fa-check-circle"></i> Approved</span>
                                                <?php elseif ($status === 3): ?>
                                                    <span class="badge-rejected"><i class="fa fa-times-circle"></i> Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['action_by_name'])): ?>
                                                    <strong><?php echo h($item['action_by_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo !empty($item['action_date']) ? date('d-m-Y H:i', strtotime($item['action_date'])) : ''; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-btn-group">
                                                    <?php if ($status === 1): ?>
                                                        <button type="button" class="btn btn-approve-action btn-action-approve" data-id="<?php echo $id; ?>" data-ref="<?php echo h($refId); ?>">
                                                            <i class="fa fa-check"></i> Approve
                                                        </button>
                                                        <button type="button" class="btn btn-reject-action btn-action-reject" data-id="<?php echo $id; ?>" data-ref="<?php echo h($refId); ?>">
                                                            <i class="fa fa-times"></i> Reject
                                                        </button>
                                                    <?php elseif ($status === 2): ?>
                                                        <span class="text-success font-weight-bold" style="font-size:12px;"><i class="fa fa-check"></i> Approved</span>
                                                    <?php elseif ($status === 3): ?>
                                                        <span class="text-danger font-weight-bold" style="font-size:12px;"><i class="fa fa-times"></i> Rejected</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </div>
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
    </div>
</div>

<script>
$(document).ready(function() {
    // Approve Action
    $(document).on('click', '.btn-action-approve', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var ref = $(this).data('ref');

        Swal.fire({
            title: 'Approve Request?',
            text: 'Are you sure you want to APPROVE editing for Reference ID: ' + ref + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-check"></i> Yes, Approve!',
            cancelButtonText: 'Cancel',
            reverseButtons:true
        }).then(function(result) {
            if (result.isConfirmed) {
                updateRequestStatus(id, 2, '');
            }
        });
    });

    // Reject Action with Remarks
    $(document).on('click', '.btn-action-reject', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var ref = $(this).data('ref');

        Swal.fire({
            title: 'Reject Request?',
            text: 'Please provide optional remarks for rejecting Reference ID: ' + ref,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-times"></i> Yes, Reject!',
            cancelButtonText: 'Cancel',
            reverseButtons:true
        }).then(function(result) {
            if (result.isConfirmed) {
                var reason = result.value || '';
                updateRequestStatus(id, 3, reason);
            }
        });
    });

    function updateRequestStatus(id, status, remarks) {
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while status is updated.',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'editing_management_update_status']); ?>',
            type: 'POST',
            data: {
                id: id,
                status: status,
                remarks: remarks
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: response.message || 'Could not update status'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred during status update. Please try again.'
                });
                console.error(xhr.responseText);
            }
        });
    }

    // Export CSV
    $("#exportdata").click(function() {
        var sdate = $("#startdate").val() || '';
        var storeId = $("#store_id").val() || '';
        var status = $("#status_filter").val() || '';

        var url = '<?php echo $this->base; ?>/dashboard/editing_management_export?sdate=' + encodeURIComponent(sdate) +
                  '&store_id=' + encodeURIComponent(storeId) +
                  '&status=' + encodeURIComponent(status);
        window.open(url, '_blank');
    });
});
</script>
