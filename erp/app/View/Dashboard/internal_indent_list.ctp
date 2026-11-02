<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Internal Indent List</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box text-right">
            <a href="create_internal_indent">
                <button class="btn btn-success" type="button">Create Internal Indent</button>
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
                        <p class="text-dark font-weight-semibold font-14">Open Internal Indent</p>
                        <h3 class="my-3"><?php echo $open_indents ?? 0;?></h3>
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
                        <p class="text-dark font-weight-semibold font-14">Closed Internal Indent</p>
                        <h3 class="my-3"><?php echo $closed_indents ?? 0;?></h3>
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
                echo $this->Form->create('Search', ['url' => 'internal_indent_list']);
                $options = [];
                            
                if ($this->Session->read('Auth.User.role') == 'admin') {
                    $options[''] = 'All Stores';
                }else{
                    $options[''] = 'Select Store';
                }
                
                foreach($stores as $store){
                    $options[$store['Location']['id']] = $store['Location']['name'];
                }
                
                $isAdmin = $this->Session->read('Auth.User.role') == 'admin';

                echo $this->Form->input('cashier', array('div'=>array('class'=>'form-group'), 'label'=>'Select Store', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'cashier','required' => !$isAdmin));

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
                            <th>Indent ID</th>
                            <th>Location</th>
                            <th>Created By</th>
                            <th>Total Amount</th>
                            <th>Completed</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $indent = $user['Inventory_internal_indent'];
                            $id = $indent['id'];
                            $status = $indent['status'] == 0 ? 'Inactive' : 'Active';
                            $buttonClass = $indent['status'] == 0 ? 'btn-success' : 'btn-danger';
                            $buttonText = $indent['status'] == 0 ? 'Mark Active' : 'Mark Inactive';
                    
                            $formId = 'pdfForm_' . $id;
                    
                            $disabled = '';
                            $disabledClass = '';
                            
                            if ($indent['is_completed'] == 1) {
                                $disabled = 'disabled';
                                $disabledClass = 'disabled';
                            }
                        ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($indent['indent_id']); ?></td>
                                <td><?= h($indent['location_name']); ?></td>
                                <td><?= h($indent['created_by_name']); ?></td>
                                <td><?= h($indent['total_amount']); ?></td>
                                <td><?= h($indent['is_completed'] ? 'Yes':'No'); ?></td>
                                <td><?= h($status); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <a href="<?= ($indent['is_completed'] == 1) ? 'javascript:void(0);' : 'edit_internal_indent?id=' . h($indent['indent_id']); ?>"
                                       class="btn btn-info btn-sm <?= $disabledClass; ?>"
                                       <?= $disabled; ?>>
                                       Edit
                                    </a>
                                    <a href="view_internal_indent?id=<?= h($indent['indent_id']); ?>" class="btn btn-info btn-sm">View</a>
                                    <button
                                        class="btn <?= $buttonClass; ?> btn-sm mark-status"
                                        data-id="<?= $id; ?>"
                                        <?= $disabled; ?>
                                    >
                                        <?= $buttonText; ?>
                                    </button>
                                    
                                    <a href="<?php echo $this->Html->url(['action' => 'internal_indent_pdf', '?' => ['id' => $indent['indent_id']]]); ?>" target="_blank">
                                        <button class="btn btn-info">Print PDF</button>
                                    </a>
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
            confirmButtonText: 'Yes, Change it',
            reverseButtons : true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'indent_change_status']); ?>',
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
