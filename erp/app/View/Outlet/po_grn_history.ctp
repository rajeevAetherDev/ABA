<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">PO GRN History</h2>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo $this->Form->create('Search', ['url' => 'po_grn_history']);
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
                            <th>PO ID</th>
                            <th>Stockin On</th>
                            <th>Stockin Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $indent = $user['inventory_stockin'];
                            $poid = h($indent['stockin_id']);
                            $formId = 'pdfForm_' . $poid;
                            
                            $id = $indent['id'];
                        ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($indent['stockin_id']); ?></td>
                                <td><?= h($indent['p_o_id']); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td><?= h($indent['stockin_amount']); ?></td>
                                <td>
								<a href="view_po_outlet?id=<?= $poid ?>" class="btn btn-info btn-sm">View</a>
								<button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    <?= $this->Form->create('Report', [
                                        'id' => $formId,
                                        'url' => ['controller' => 'Outlet', 'action' => 'po_pdf'],
                                        'type' => 'post',
                                        'target' => '_blank'
                                    ]) ?>
                                    <?= $this->Form->input('poid', ['type' => 'hidden', 'value' => $poid]) ?>
                                    <?= $this->Form->end() ?></td>
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
});
</script>
