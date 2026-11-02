<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Indent List</h2>
        </div>
    </div>
    <div class="col-sm-2 text-right">
        <div class="page-title-box">
            <a href="create_indent">
                <button class="btn btn-success">Create Indent</button>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Submitted Indent</p>
                        <h3 class="my-3"><?php echo $submitted_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col-->
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Approved Indent</p>
                        <h3 class="my-3"><?php echo $approved_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col-->
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Processed Indent</p>
                        <h3 class="my-3"><?php echo $processed_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col--> 
    
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Stocked IN</p>
                        <h3 class="my-3"><?php echo $stockedin_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col--> 
</div>

<!-- Date Filter -->
<div class="row mt-3">
    <div class="col-sm-12">
        <?= $this->Form->create('Search', ['url' => ['controller' => 'Outlet', 'action' => 'indent'], 'type' => 'post']); ?>
        <div class="form-group">
            <?= $this->Form->input('startdate', [
                    'div' => ['class' => 'form-group'],
                    'label' => ['class' => 'form-label'],
                    'class' => 'form-control calendarmultiple',
                    'between' => '<span class="desc"></span><div class="controls">',
                    'after' => '</div>',
                    'label' => 'Select Date',
                    'id' => 'startdate',
                    'name' => 'daterange',
                    'required' => 'required',
                    'value' => $startdate
                ]);?>
        </div>
        <?= $this->Form->button('View', ['class' => 'btn btn-info']); ?>
        <?= $this->Form->end(); ?>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mt-3" id="indentTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="submitted-tab" data-toggle="tab" href="#submitted" role="tab">Submitted</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="approved-tab" data-toggle="tab" href="#approved" role="tab">Approved</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="processed-tab" data-toggle="tab" href="#processed" role="tab">Processed</a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="indentTabsContent">

    <!-- Submitted -->
    <div class="tab-pane fade show active" id="submitted" role="tabpanel" style="text-decoration:none;">
        <div class="card mt-3">
            <div class="card-body">
                <table class="display table table-hover table-condensed" cellspacing="0" width="100%" id="example">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Indent ID</th>
                            <th>Expiry</th>
                            <th>Location</th>
                            <th>Total Amount</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($submittedIndents as $item):
                            $indent = $item['inventory_indent'];
                            $poid = h($indent['indent_id']);
                            $formId = 'pdfForm_' . $poid;
                            ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($indent['indent_id']); ?></td>
                                <td><?= h($indent['expiry']); ?></td>
                                <td><?= h($indent['location_name']); ?></td>
                                <td><?= h($indent['total_amount']); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <a href="edit_indent?id=<?= $poid ?>" class="btn btn-info btn-sm">Edit</a>
                                    <a href="view_indent?id=<?= $poid ?>" class="btn btn-info btn-sm">View</a>
                                    <button class="btn <?= $indent['status'] == 0 ? 'btn-success' : 'btn-danger'; ?> btn-sm mark-status" data-id="<?=$poid?>">
                                        <?= $indent['status'] == 0 ? 'Mark Active' : 'Mark Inactive'; ?>
                                    </button>
                                    <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId,
                                        'url' => ['controller' => 'Outlet', 'action' => 'indent_pdf'],
                                        'type' => 'post',
                                        'target' => '_blank'
                                    ]) ?>
                                    <?= $this->Form->input('poid', ['type' => 'hidden', 'value' => $poid]) ?>
                                    <?= $this->Form->end() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Approved -->
    <div class="tab-pane fade" id="approved" role="tabpanel" style="text-decoration:none;">
        <div class="card mt-3">
            <div class="card-body">
                <table class="display table table-hover table-condensed" cellspacing="0" width="100%" id="example1">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Indent ID</th>
                            <th>Expiry</th>
                            <th>Location</th>
                            <th>Total Amount</th>
                            <th>Approved Amount</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($approvedIndents as $item):
                            $indent = $item['inventory_indent'];
                            $poid = h($indent['indent_id']);
                            $formId = 'pdfForm_' . $poid;
                            ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= $poid ?></td>
                                <td><?= h($indent['expiry']); ?></td>
                                <td><?= h($indent['location_name']); ?></td>
                                <td><?= h($indent['total_amount']); ?></td>
                                <td><?= h($indent['approved_amount']); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <a href="view_indent?id=<?= $poid ?>" class="btn btn-info btn-sm">View</a>
                                    <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId,
                                        'url' => ['controller' => 'Outlet', 'action' => 'indent_pdf'],
                                        'type' => 'post',
                                        'target' => '_blank'
                                    ]) ?>
                                    <?= $this->Form->input('poid', ['type' => 'hidden', 'value' => $poid]) ?>
                                    <?= $this->Form->end() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Processed -->
    <div class="tab-pane fade" id="processed" role="tabpanel" style="text-decoration:none;">
        <div class="card mt-3">
            <div class="card-body">
                <table class="display table table-hover table-condensed" cellspacing="0" width="100%" id="example2">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Indent ID</th>
                            <th>Consignment ID</th>
                            <th>Serial No.</th>
                            <th>Total Amount</th>
                            <th>Approved Amount</th>
                            <th>Processed Amount</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($processedIndents as $item):
                            $indent = $item['inventory_indent'];
                            $poid = h($indent['indent_id']);
                            $formId = 'pdfForm_' . $poid;
                            ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= $poid ?></td>
                                <td><?= h($indent['consignment_id']); ?></td>
                                <td><?= h($indent['serial_number']); ?></td>
                                <td><?= h($indent['total_amount']); ?></td>
                                <td><?= h($indent['approved_amount']); ?></td>
                                <td><?= h($indent['processed_amount']); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <a href="view_indent?id=<?= $poid ?>" class="btn btn-info btn-sm">View</a>
                                    <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId,
                                        'url' => ['controller' => 'Outlet', 'action' => 'indent_pdf'],
                                        'type' => 'post',
                                        'target' => '_blank'
                                    ]) ?>
                                    <?= $this->Form->input('poid', ['type' => 'hidden', 'value' => $poid]) ?>
                                    <?= $this->Form->end() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
$(document).ready(function () {
    // Retain tab on reload
    const activeTab = localStorage.getItem('activeTab');
    if (activeTab) {
        $('#indentTabs a[href="' + activeTab + '"]').tab('show');
    }

    $('#indentTabs a').on('shown.bs.tab', function (e) {
        const id = $(e.target).attr('href');
        localStorage.setItem('activeTab', id);
    });

    // Initialize tables
    $(' #example2').DataTable({
        ordering: true,
        searching: true
    });

    // PDF Button
    $(document).on('click', '.btn-pdf', function () {
        const formId = $(this).data('form');
        document.getElementById(formId).submit();
    });
    
    $(document).on('click', '.mark-status', function () {
    
        var id = $(this).data('id');
    
        Swal.fire({
            title: 'Change Status?',
            text: 'Are you sure you want to update this indent status?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Update',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            reverseButtons: true
        }).then((result) => {
    
            if (!result.isConfirmed) return;
    
            $.ajax({
                url: '<?php echo Router::url(['controller' => 'Outlet', 'action' => 'indent_change_status']); ?>',
                type: 'POST',
                data: { _id: id },
                dataType: 'json',
    
                beforeSend: function () {
                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
    
                success: function (response) {
    
                    if (response.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Status update failed'
                        });
                    }
                },
    
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Something went wrong. Please try again.'
                    });
                    console.log(xhr.responseText);
                }
            });
    
        });
    });

});
</script>



<!-- Style -->
<style>
.nav-tabs .nav-link {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    margin-right: 4px;
    border-radius: 4px;
}
.nav-tabs .nav-link.active {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}
.nav-tabs .nav-link:hover {
    background-color: #218838;
    color: #fff;
}
</style>



