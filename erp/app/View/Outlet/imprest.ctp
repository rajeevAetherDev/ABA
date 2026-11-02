<!-- Page Header -->
<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Imprest</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box text-right">
            <!-- Button to trigger modal -->
            <button 
                class="btn btn-success" 
                type="button" 
                data-bs-toggle="modal" 
                data-bs-target="#addimprestModal">
                Create Imprest
            </button>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo $this->Form->create('Search', ['url' => 'imprest']);
                
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

                <!-- Table -->
                <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $indent = $user['Account_imprest'];
                            
                            $id = $indent['id'];
                            $status = $indent['status'] == 0 ? 'Inactive' : 'Active';
                            $buttonClass = $indent['status'] == 0 ? 'btn-success' : 'btn-danger';
                            $buttonText = $indent['status'] == 0 ? 'Mark Active' : 'Mark Inactive';
                        ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($indent['amount']); ?></td>
                                <td><?= h($indent['description']); ?></td>
                                <td><?= h($indent['location_name']); ?></td>
                                <td><?= h($status); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <button 
                                        class="btn btn-sm btn-info edit-imprest" 
                                        data-id="<?= h($indent['id']); ?>">Edit</button>
                                
                                    <button 
                                        class="btn btn-sm btn-secondary view-imprest" 
                                        data-id="<?= h($indent['id']); ?>">View</button>
                                    <button class="btn <?= $buttonClass; ?> btn-sm mark-status" data-id="<?= $id; ?>"><?= $buttonText; ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" 
     id="addimprestModal" 
     tabindex="-1" 
     aria-labelledby="addimprestModalLabel" 
     aria-hidden="true"
     data-bs-backdrop="static" 
     data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="addimprestModalLabel">Add New Imprest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <form id="createImprestForm">
                    <div class="mb-3">
                        <label for="imprestAmount" class="form-label">Amount</label>
                        <input type="text" id="imprestAmount" name="amount" class="form-control" placeholder="Enter Amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="imprestDescription" class="form-label">Description</label>
                        <textarea id="imprestDescription" name="description" class="form-control" placeholder="Enter Description"></textarea>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCategory">Save</button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" 
     id="editImprestModal" 
     tabindex="-1" 
     aria-labelledby="editImprestModalLabel" 
     aria-hidden="true"
     data-bs-backdrop="static" 
     data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="editImprestModalLabel">Edit Imprest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
            </div>

            <div class="modal-body">
                <form id="editImprestForm">
                    <input type="hidden" name="id" id="editImprestId">

                    <div class="mb-3">
                        <label for="editImprestAmount" class="form-label">Amount</label>
                        <input type="text" id="editImprestAmount" name="amount" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="editImprestDescription" class="form-label">Description</label>
                        <textarea id="editImprestDescription" name="description" class="form-control"></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="updateImprestBtn">Update</button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="viewImprestModal" tabindex="-1" aria-labelledby="viewImprestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="viewImprestModalLabel">View Imprest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
            </div>

            <div class="modal-body">
                <p><strong>Amount:</strong> <span id="viewImprestAmount"></span></p>
                <p><strong>Description:</strong> <span id="viewImprestDescription"></span></p>
                <p><strong>Location:</strong> <span id="viewImprestLocation"></span></p>
                <p><strong>Status:</strong> <span id="viewImprestStatus"></span></p>
                <p><strong>Created:</strong> <span id="viewImprestCreated"></span></p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<!-- Include Bootstrap JS (v5) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTable Init + Status Toggle -->
<script>
$(document).ready(function () {
    

    // Toggle Status with confirmation
    $(document).on('click', '.mark-status', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Change Status?',
            text: "Do you want to toggle the status of this Imprest?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Change it',
            reverseButtons: true // ✅ puts "Yes, Change it" on the RIGHT
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= Router::url(['controller' => 'Outlet', 'action' => 'imprest_change_status']); ?>',
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

    // Save Button (Modal)
    $('#saveCategory').on('click', function () {
        const amount = $('#imprestAmount').val().trim();
        const description = $('#imprestDescription').val().trim();
    
        // Simple validation
        if (amount === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Amount is required!',
                text: 'Please enter an amount before saving.'
            });
            return; // stop execution
        }
    
        if (isNaN(amount) || parseFloat(amount) <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Amount!',
                text: 'Please enter a valid positive number.'
            });
            return;
        }
    
        const formData = $('#createImprestForm').serialize();
    
        $.ajax({
            url: '<?= Router::url(['controller' => 'Outlet', 'action' => 'add_imprest']); ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.response_code) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Imprest Created Successfully',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        $('#addimprestModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', 'Failed to create imprest.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
            }
        });
    });

});

$(document).ready(function () {

    // 🔹 Fetch and open Edit modal
    $(document).on('click', '.edit-imprest', function () {
        const id = $(this).data('id');

        $.ajax({
            url: '<?= Router::url(['controller' => 'Outlet', 'action' => 'get_imprest']); ?>',
            type: 'GET',
            data: { id },
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    const i = res.data;
                    $('#editImprestId').val(i.id);
                    $('#editImprestAmount').val(i.amount);
                    $('#editImprestDescription').val(i.description);
                    $('#editImprestModal').modal('show');
                } else {
                    Swal.fire('Error', 'Imprest not found', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Could not fetch imprest data', 'error');
            }
        });
    });

    // 🔹 Update Imprest
    $('#updateImprestBtn').on('click', function () {
        const data = $('#editImprestForm').serialize();

        $.ajax({
            url: '<?= Router::url(['controller' => 'Outlet', 'action' => 'update_imprest']); ?>',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Imprest updated successfully',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        $('#editImprestModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', 'Failed to update imprest.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Unexpected error occurred', 'error');
            }
        });
    });

    // 🔹 View Imprest
    $(document).on('click', '.view-imprest', function () {
        const id = $(this).data('id');

        $.ajax({
            url: '<?= Router::url(['controller' => 'Outlet', 'action' => 'view_imprest']); ?>',
            type: 'GET',
            data: { id },
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    const i = res.data;
                    $('#viewImprestAmount').text(i.amount);
                    $('#viewImprestDescription').text(i.description);
                    $('#viewImprestLocation').text(i.location_name);
                    $('#viewImprestStatus').text(i.status == 1 ? 'Active' : 'Inactive');
                    $('#viewImprestCreated').text(i.created);
                    $('#viewImprestModal').modal('show');
                } else {
                    Swal.fire('Error', 'Failed to fetch imprest details', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Could not load imprest details', 'error');
            }
        });
    });

});

</script>


