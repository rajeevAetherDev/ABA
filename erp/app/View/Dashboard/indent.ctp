<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Indent List</h2>
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
        <?= $this->Form->create('Search', ['url' => ['controller' => 'Dashboard', 'action' => 'indent'], 'type' => 'post']); ?>
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
                ]); ?>
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
        <a class="nav-link" id="approved-tab" data-toggle="tab" href="#approved" role="tab">Under Processing</a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="indentTabsContent">
    <!-- Submitted -->
    <div class="tab-pane fade show active" id="submitted" role="tabpanel" style="text-decoration:none;">
        <div class="card mt-3">
            <div class="card-body">
                <table  class="display table table-hover table-condensed" cellspacing="0" width="100%" id="example">
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
                                    <a href="approve_indent?id=<?= h($indent['indent_id']); ?>" class="btn btn-info btn-sm">Approve</a>
                                    <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId,
                                        'url' => ['controller' => 'Dashboard', 'action' => 'indent_pdf'],
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
                <table  class="display table table-hover table-condensed" cellspacing="0" width="100%"  id="example1">
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
                                <td><?= h($indent['indent_id']); ?></td>
                                <td><?= h($indent['expiry']); ?></td>
                                <td><?= h($indent['location_name']); ?></td>
                                <td><?= h($indent['total_amount']); ?></td>
                                <td><?= h($indent['approved_amount']); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <a href="process_indent?id=<?= h($indent['indent_id']); ?>" class="btn btn-info btn-sm">Process</a>
                                    <!--<a href="edit_indent?id=<?= h($indent['indent_id']); ?>" class="btn btn-info btn-sm">Update</a>-->
                                    <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId,
                                        'url' => ['controller' => 'Dashboard', 'action' => 'indent_pdf'],
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
<script>
$(document).ready(function() {
    $('#example2').DataTable({
        "ordering": true, // Optional: enable/disable sorting
        "searching": true // Optional: enable/disable search
    });
    // PDF Button
    $(document).on('click', '.btn-pdf', function () {
        const formId = $(this).data('form');
        document.getElementById(formId).submit();
    });
});
</script>

<style>
    /* Inactive tabs */
    .nav-tabs .nav-link {
        background-color: #f8f9fa; /* light gray */
        color: #495057;
        border: 1px solid #dee2e6;
        margin-right: 4px;
        border-radius: 4px;
    }

    /* Active tab */
    .nav-tabs .nav-link.active {
        background-color: #28a745; /* green */
        color: white;
        border-color: #28a745;
    }

    /* Hover effect */
    .nav-tabs .nav-link:hover {
        background-color: #218838;
        color: #fff;
    }
</style>

