<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Direct Purchase</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box text-right">
            <a href="create_direct_purchase">
                <button class="btn btn-success" type="button">Create Direct Purchase</button>
            </a>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo $this->Form->create('Search', ['url' => 'direct_purchase']);
                
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
                            <th>PO ID</th>
                            <th>Location</th>
                            <th>Vendor Name</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1;
                        foreach ($users as $user):
                            $indent = $user['Inventory_purchase_order'];
                            $id = $indent['id'];
                            $status = $indent['status'] == 0 ? 'Inactive' : 'Active';
                            $buttonClass = $indent['status'] == 0 ? 'btn-success' : 'btn-danger';
                            $buttonText = $indent['status'] == 0 ? 'Mark Active' : 'Mark Inactive';
                        ?>
                            <tr>
                                <td><?= $x++; ?></td>
                                <td><?= h($indent['p_o_id']); ?></td>
                                <td><?= h($indent['location_name']); ?></td>
                                <td><?= h($indent['vendor_name']); ?></td>
                                <td><?= h($indent['total_amount']); ?></td>
                                <td><?= h('Stocked IN'); ?></td>
                                <td><?= h($indent['created']); ?></td>
                                <td>
                                    <a href="view_direct_purchase?id=<?= h($indent['p_o_id']); ?>" class="btn btn-info btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>
