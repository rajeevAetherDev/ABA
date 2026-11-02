<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Payment Partners</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title"><a href="create_payment_partner"><button  class='btn btn-success' type="button" style="float:right">Add Payment Partner</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
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
                                if (!empty($partners)):
                                    foreach ($partners as $partner):
                                        $id = $partner['PaymentPartner']['id'];
                                        $name = $partner['PaymentPartner']['name'];
                                        $statusVal = isset($partner['PaymentPartner']['status']) ? $partner['PaymentPartner']['status'] : 1;
                                        $created = isset($partner['PaymentPartner']['created']) ? $partner['PaymentPartner']['created'] : (isset($partner['PaymentPartner']['created_at']) ? $partner['PaymentPartner']['created_at'] : '');

                                        if ($statusVal == 0) {
                                            $status = 'Inactive';
                                            $buttonClass = 'btn-success';
                                            $buttonText = 'Mark Active';
                                        } else {
                                            $status = 'Active';
                                            $buttonClass = 'btn-danger';
                                            $buttonText = 'Mark Inactive';
                                        }
                                ?>
                                        <tr>
                                            <td><?php echo $x; ?></td>
                                            <td><?php echo h($name); ?></td>
                                            <td><?php echo h($status); ?></td>
                                            <td><?php echo h($created); ?></td>
                                            <td>
                                                <button class='btn <?php echo $buttonClass; ?> mark-status' data-id='<?php echo $id; ?>'><?php echo $buttonText; ?></button>
                                            </td>
                                        </tr>
                                <?php $x++;
                                    endforeach;
                                endif; ?>
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
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'payment_partner_change_status']); ?>',
            type: 'POST',
            data: { _id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Status Updated',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Failed to update status',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the status.',
                    confirmButtonColor: '#d33'
                });
                console.log(xhr.responseText);
            }
        });
    });
});
</script>
