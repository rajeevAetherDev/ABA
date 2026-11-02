<div class="row mb-3">
    <div class="col-sm-8">
        <h2 class="page-title">Tables</h2>
    </div>
    <div class="col-sm-4 text-end">
        <a href="table_category_list" class="btn btn-primary me-2">Manage Categories</a>
        <a href="create_table" class="btn btn-success">Add New Table</a>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <!-- Only show table if $users is not empty -->
        <?php if (!empty($users)) : ?>
            <table id="example" class="display table table-hover table-condensed" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Number</th>
                        <th>Category</th>
                        <th>Reference ID</th>
                        <th>Location</th>
                        <th>Cover Charge</th>
                        <th>Weekend Charge</th>
                        <th>PAX</th>
                        <th>Waiter Required</th>
                        <th>Customer Details Required</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $x = 1;
                    echo "<pre/>";
                    // print_r($users); // Debugging line to check the structure of $users
                    // exit;
                    foreach ($users as $user):
                        $table = $user['order_tables'];
                        $id = $table['id'];
                        $categoryName = !empty($user['table_categories']['category_name']) ? $user['table_categories']['category_name'] : 'NA';

                        $statusList = [
                            1 => 'Vacant',
                            2 => 'Booked',
                            3 => 'Billed',
                            0 => 'Inactive'
                        ];
                        $status = $statusList[$table['status']] ?? 'Unknown';

                        $buttonClass = $table['status'] == 0 ? 'btn-success' : 'btn-danger';
                        $buttonText = $table['status'] == 0 ? 'Mark Active' : 'Mark Inactive';

                        $waiterStatus = $table['is_waiter_required'] ? 'Yes' : 'No';
                        $customerStatus = $table['is_customer_detail_required'] ? 'Yes' : 'No';
                    ?>
                    <tr>
                        <td><?= $x++; ?></td>
                        <td><?= h($table['number']) ?></td>
                        <td><span class="badge bg-primary text-white"><?= h($categoryName) ?></span></td>
                        <td><?= h($table['reference_id']) ?></td>
                        <td><?= h($table['location_name']) ?></td>
                        <td><?= h($table['cover_charge']) ?></td>
                        <td><?= h($table['weekend_charge']) ?></td>
                        <td><?= h($table['pax']) ?></td>
                        <td><?= $waiterStatus ?></td>
                        <td><?= $customerStatus ?></td>
                        <td><?= $status ?></td>
                        <td><?= h($table['created']) ?></td>
                        <td>
                            <a href="edit_table?id=<?= $id ?>" class="btn btn-info mb-1">Edit</a>
                            <button class="btn <?= $buttonClass ?> mark-status" data-id="<?= $id ?>"><?= $buttonText ?></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- JS for Status Toggle with SweetAlert -->
<script>
$(document).ready(function () {
    $(document).on('click', '.mark-status', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Change Status?',
            text: "Do you want to toggle this table's status?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'table_change_status']) ?>',
                    type: 'POST',
                    data: { _id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status) {
                            Swal.fire('Success', 'Status updated successfully.', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Failed', 'Failed to update status.', 'error');
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error', 'An error occurred while updating the status.', 'error');
                        console.log(xhr.responseText);
                    }
                });
            }
        });
    });
});
</script>
