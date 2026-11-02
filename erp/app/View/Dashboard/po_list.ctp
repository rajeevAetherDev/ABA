    <div class="row">
        <div class="col-sm-6">
            <div class="page-title-box">
                <h2 class="page-title">Purchase Order List</h2>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="page-title-box text-right">
                <a href="create_po" class="btn btn-success">Create Purchase Order</a>
                <!--<a href="create_po_without_indent" class="btn btn-info ml-2" style="background-color: #4d79f6; border-color: #4d79f6; color: #ffffff; font-weight: 600; box-shadow: 0 2px 4px rgba(77, 121, 246, 0.3);">Create PO Without Indent</a>-->
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Submitted Purchase Orders</p>
                            <h3 class="my-3"><?php echo $submittedpo ?? 0;?></h3>
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
                            <p class="text-dark font-weight-semibold font-14">Approved Purchase Orders</p>
                            <h3 class="my-3"><?php echo $approvedpo ?? 0;?></h3>
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
                            <p class="text-dark font-weight-semibold font-14">Processed Purchase Orders</p>
                            <h3 class="my-3"><?php echo $processedpo ?? 0;?></h3>
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
                            <p class="text-dark font-weight-semibold font-14">Stocked In Purchase Orders</p>
                            <h3 class="my-3"><?php echo $stockedinpo ?? 0;?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <?= $this->Form->create('Search', ['url' => 'po_list']);
                    
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
                    
                    ?>
                    <div class="form-group row">
                        <div class="col-sm-8">
                            <?= $this->Form->input('startdate', [
                                'label' => 'Select Date',
                                'class' => 'form-control calendarmultiple',
                                'id' => 'startdate',
                                'name' => 'daterange',
                                'required' => 'required',
                                'value' => $startdate
                            ]) ?>
                        </div>
                        <div class="col-sm-4">
                            <br>
                            <?= $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit']) ?>
                        </div>
                    </div>
                    <?= $this->Form->end() ?>
    
                    <div class="row mt-4">
                        <div class="col-12">
                            <table id="example" class="display table table-hover table-condensed" width="100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>PO Id</th>
                                        <th>Indent Id</th>
                                        <th>Auto Indent</th>
                                        <th>Location</th>
                                        <th>Location Type</th>
                                        <th>Vendor Name</th>
                                        <th>Total Amount</th>
                                        <th>PO Date</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $x = 1;
                                    foreach ($purchaseOrders as $user):
                                        $po = $user['Inventory_purchase_order'];
                                        $poid = h($po['p_o_id']);
                                        $formId = 'pdfForm_' . $poid;
                                
                                        switch ($po['status']) {
                                            case 0:
                                                $status = 'Inactive';
                                                $buttonClass = 'btn-success';
                                                $buttonText = 'Mark Active';
                                                $disabled = '';
                                                $amount=$po['total_amount'];
                                                break;
                                            case 1:
                                                $status = 'Submitted';
                                                $buttonClass = 'btn-danger';
                                                $buttonText = 'Mark Inactive';
                                                $disabled = '';
                                                $amount=$po['total_amount'];
                                                break;
                                            case 2:
                                                $status = 'Approved';
                                                $buttonClass = 'btn-danger';
                                                $buttonText = 'Approved';
                                                $disabled = 'disabled';
                                                $amount=$po['approved_amount'];
                                                break;
                                            case 3:
                                                $status = 'Processed';
                                                $buttonClass = 'btn-danger';
                                                $buttonText = 'Processed';
                                                $disabled = 'disabled';
                                                $amount=$po['processed_amount'];
                                                break;
                                            case 4:
                                                $status = 'Stocked In';
                                                $buttonClass = 'btn-danger';
                                                $buttonText = 'Stocked In';
                                                $disabled = 'disabled';
                                                $amount=$po['stockin_amount'];
                                                break;
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $x ?></td>
                                        <td><?= h($poid) ?></td>
                                        <td><?= h($po['internal_indent_id']) ?></td>
                                        <td>
                                            <?php if (!empty($po['is_autogenerated_indent'])): ?>
                                                <span class="badge badge-info" style="font-size:11px; padding: 4px 8px;">Auto</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary" style="font-size:11px; padding: 4px 8px;">Manual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= h($po['location_name']) ?></td>
                                        <td><?= h($po['location_type']) ?></td>
                                        <td><?= h($po['vendor_name']) ?> <?php if (!empty($po['direct_purchase'])): ?>
                                            <span style='background:#e67e22;color:#fff;padding:1px 7px;border-radius:8px;font-size:11px;font-weight:700;margin-left:4px;'>&#9889; Direct</span>
                                            <?php endif; ?></td>
                                        <td><?= h($amount) ?></td>
                                        <td><?= h($po['po_date']) ?></td>
                                        <td><?= $status ?></td>
                                        <td><?= h($po['created']) ?></td>
                                        <td>
                                            <a href="view_po?id=<?= $poid ?>" class="btn btn-info btn-sm">View</a>
                                
                                            <?php if($po['status']>1){ ?>
                                                <button class="btn btn-warning btn-sm" disabled>Edit</button>
                                            <?php }else{ ?>
                                                <a href="edit_po?id=<?= $poid ?>" class="btn btn-warning btn-sm">Edit</a>
                                            <?php } ?>
                                
                                            <button class="btn <?= $buttonClass ?> btn-sm mark-status" data-id="<?= $po['id'] ?>" <?= $disabled ?>>Delete</button>
                                            <!-- PDF Button & Form with unique ID -->
                                            <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">PDF</button>
                                    
                                            <?= $this->Form->create('Report', ['id' => $formId, 'url' => 'po_pdf','type'=>'post', 'target' => '_blank','value' => $poid]) ?>
                                            <?= $this->Form->input('poid', ['type' => 'hidden', 'value' => $poid]) ?>
                                            <?= $this->Form->end() ?>
                                        </td>
                                    </tr>
                                    <?php $x++; endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function () {
            $(document).on('click', '.btn-pdf', function () {
                const formId = $(this).data('form');
                    document.getElementById(formId).submit();

            });
        
            $(document).on('click', '.mark-status', function () {
                const id = $(this).data('id');
        
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to change the status of this PO?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#aaa',
                    reverseButtons: true,
                    confirmButtonText: 'Yes, update it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'po_change_status']) ?>',
                            type: 'POST',
                            data: { _id: id },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Updated!',
                                        text: 'Status updated successfully!',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    // ðŸ”¥ Use backend message if exists
                                    const msg = response.message || 'Failed to update status.';
                                    Swal.fire('Error', msg, 'error');
                                }
                            },

                        });
                    }
                });
            });
        });

    </script>
