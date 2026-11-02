<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">SKU Damage</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box text-right">
            <a href="create_damage">
                <button class="btn btn-success" type="button">Add SKU Damage</button>
            </a>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo $this->Form->create('Search', ['url' => 'stock_damage']);
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
                            <th>Damage ID</th>
                            <th>Location</th>
                            <th>Damage Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $indent = $user['inventory_damage'];
                            $id = $indent['id'];
                            $status = $indent['status'] == 0 ? 'Inactive' : 'Active';
                            $buttonClass = $indent['status'] == 0 ? 'btn-success' : 'btn-danger';
                            $buttonText = $indent['status'] == 0 ? 'Mark Active' : 'Mark Inactive';
                        ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($indent['damage_id']); ?></td>
                                <td><?= h($indent['location_name']); ?></td>
                                <td><?= h($indent['damage_date']); ?></td>
                                <td><?= h($indent['amount']); ?></td>
                                <td><?= h($status); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <a href="edit_damage?id=<?= h($indent['damage_id']); ?>" class="btn btn-info btn-sm">Edit</a>
                                    <a href="view_damage?id=<?= h($indent['damage_id']); ?>" class="btn btn-info btn-sm">View</a>
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
   
    // Toggle Status with confirmation
    $(document).on('click', '.mark-status', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Change Status?',
            text: "Do you want to toggle the status of this damage?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Change it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'damage_change_status']); ?>',
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
