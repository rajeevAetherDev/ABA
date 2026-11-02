<div class="row mb-3">
    <div class="col-sm-8">
        <h2 class="page-title">Table Categories</h2>
    </div>
    <div class="col-sm-4 text-end">
        <a href="table_list" class="btn btn-secondary me-2">Back to Tables</a>
        <a href="create_table_category" class="btn btn-success">Add New Table Category</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($categories)) : ?>
            <table id="example" class="display table table-hover table-condensed" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $x = 1;
                    foreach ($categories as $category):
                        $cat = $category['Table_category'];
                        $id = $cat['id'];
                        $status = ($cat['status'] == 1) ? 'Active' : 'Inactive';
                        $buttonClass = ($cat['status'] == 0) ? 'btn-success' : 'btn-danger';
                        $buttonText = ($cat['status'] == 0) ? 'Mark Active' : 'Mark Inactive';
                    ?>
                    <tr>
                        <td><?= $x++; ?></td>
                        <td><?= h($cat['name']) ?></td>
                        <td>
                            <span class="badge <?= ($cat['status'] == 1) ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $status ?>
                            </span>
                        </td>
                        <td><?= h($cat['created']) ?></td>
                        <td>
                            <a href="edit_table_category?id=<?= $id ?>" class="btn btn-info mb-1">Edit</a>
                            <button class="btn <?= $buttonClass ?> mark-category-status" data-id="<?= $id ?>"><?= $buttonText ?></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No table categories found. <a href="create_table_category">Create one now</a>.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Include SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JS for Status Toggle with SweetAlert -->
<script>
$(document).ready(function () {
    $(document).on('click', '.mark-category-status', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Change Status?',
            text: "Do you want to toggle this table category status?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'table_category_change_status']) ?>',
                    type: 'POST',
                    data: { _id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status) {
                            Swal.fire('Success', 'Status updated successfully.', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', 'Failed to update status.', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Server error. Please try again.', 'error');
                    }
                });
            }
        });
    });
});
</script>
