<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">GRN History</h2>
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


<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo $this->Form->create('Search', ['url' => 'grn_history']);
                echo $this->Form->input('startdate', [
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
                ]);
                echo $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit']);
                echo $this->Form->end();
                ?>
                <div style="height:30px;" class="clearfix"></div>

                <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Stockin ID</th>
                            <th>Consignment ID</th>
                            <th>Indent ID</th>
                            <th>Stockin On</th>
                            <th>Stockin Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $indent = $user['inventory_indent'];
                            $poid = h($indent['indent_id']);
                            $formId = 'pdfForm_' . $poid;
                            
                            $id = $indent['id'];
                        ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($indent['stockin_id']); ?>
                                    <?php if (!empty($indent['is_direct_stockin'])): ?>
                                    <span style='background:#e67e22;color:#fff;padding:1px 7px;border-radius:8px;font-size:11px;font-weight:700;margin-left:4px;'> Direct</span>
                                    <?php endif; ?>
                                    </td>
                                <td><?= h($indent['consignment_id']); ?></td>
                                <td><?= h($indent['indent_id']); ?></td>
                                <td><?= h(date('Y-m-d H:i:s', strtotime($indent['processed_date']))); ?></td>
                                <td><?= h($indent['stockin_amount']); ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    <button class="btn btn-success btn-sm btn-excel" data-form="<?= $formId ?>_excel">Excel</button>
                                    
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId,
                                        'url' => ['controller' => 'Outlet', 'action' => 'indent_pdf'],
                                        'type' => 'post',
                                        'target' => '_blank'
                                    ]) ?>
                                    <?= $this->Form->input('poid', ['type' => 'hidden', 'value' => $poid]) ?>
                                    <?= $this->Form->end() ?>
                                    
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId . '_excel',
                                        'url' => ['controller' => 'Outlet', 'action' => 'indent_excel'],
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

<!-- DataTable Init + Status Toggle -->
<script>
$(document).ready(function () {
    // Initialize DataTable
    $('#example').DataTable({
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 10
    });

    // Toggle Status with confirmation
    $(document).on('click', '.mark-status', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Change Status?',
            text: "Do you want to toggle the status of this indent?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Change it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= Router::url(['controller' => 'Outlet', 'action' => 'indent_change_status']); ?>',
                    type: 'POST',
                    data: { _id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Status Updated',
                                timer: 1000,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', 'Failed to update status.', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'An unexpected error occurred.', 'error');
                    }
                });
            }
        });
    });
});
</script>

<script>
$(document).ready(function() {
    // PDF Button
    $(document).on('click', '.btn-pdf', function () {
        const formId = $(this).data('form');
        document.getElementById(formId).submit();
    });

    // Excel Button
    $(document).on('click', '.btn-excel', function () {
        const formId = $(this).data('form');
        document.getElementById(formId).submit();
    });
});
</script>
