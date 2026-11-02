<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Void Order Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <?php echo $this->Form->create('Search', array('url' => 'void_order'));
                        $options = [];
                        if ($this->Session->read('Auth.User.role') == 'admin') {
                            $options['']  = 'All Stores';
                            $storeRequired = false;
                        } else {
                            $options['']  = 'Select Store';
                            $storeRequired = true;
                        }
                        foreach ($users as $user) {
                            $options[$user['Location']['id']] = $user['Location']['name'];
                        }
                        echo $this->Form->input('storeRequired', [
                            'type'  => 'hidden',
                            'id'    => 'storeRequired',
                            'value' => $storeRequired ? 1 : 0
                        ]);
                        echo $this->Form->input('cashier', [
                            'div'     => ['class' => 'form-group'],
                            'label'   => 'Select Store',
                            'class'   => 'form-control',
                            'between' => '<span class="desc"></span><div class="controls">',
                            'after'   => '</div>',
                            'options' => $options,
                            'id'      => 'cashier',
                            'required' => $storeRequired
                        ]);
                        echo $this->Form->input('startdate', [
                            'div'     => ['class' => 'form-group'],
                            'label'   => 'Select Date',
                            'class'   => 'form-control calendarmultiple',
                            'between' => '<span class="desc"></span><div class="controls">',
                            'after'   => '</div>',
                            'id'      => 'startdate',
                            'name'    => 'daterange',
                            'required' => 'required',
                            'value'   => $startdate
                        ]);
                        echo $this->Form->button('View',   ['class' => 'btn btn-info', 'type' => 'submit']);
                        echo "&nbsp;" . $this->Form->button('Export', ['class' => 'btn btn-info', 'type' => 'button', 'id' => 'exportdata']);
                        echo $this->Form->end();
                    ?>
                </div>

                <div class="table-responsive mt-4">
                    <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Store Name</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Remarks</th>
                                <th>Manager Name</th>
                                <th>Manager Remarks</th>
                                <th>KOT Number</th>
                                <th>Punch DateTime</th>
                                <th>Void DateTime</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $x = 1; foreach ($orderhistories as $tr): ?>
								<?php
									$o = $tr['o'];
									$l = $tr['l'];
									$c = $tr[0];

									$managerRemarks = $c['manager_remarks'] ?? '';
									json_decode($managerRemarks, true);
									if (json_last_error() === JSON_ERROR_NONE) {
										$managerRemarks = '';
									}
								?>
								<tr>
									<td><?= $x++ ?></td>
									<td><?= h($l['location_name']) ?></td>
									<td><?= h($o['product_name']) ?></td>
									<td><?= h($o['quantity']) ?></td>
									<td><?= h($o['price']) ?></td>
									<td><?= h($o['remarks']) ?></td>
									<td><?= h($c['manager_name'] ?? '') ?></td>
									<td><?= h($managerRemarks) ?></td>
									<td><?= h($o['kot_number'] ?? '') ?></td>
									<td><?= date('d-m-Y h:i A', strtotime($o['created'])) ?></td>
									<td>
                                        <?= !empty($c['void_datetime']) 
                                        ? date('d-m-Y h:i A', strtotime($c['void_datetime'])) 
                                        : '' ?>
                                    </td>
								</tr>
							<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    $("#exportdata").click(function () {
                        var sdate        = $("#startdate").val();
                        var cashier      = $("#cashier").val();
                        var storeRequired = $("#storeRequired").val();
                        if (storeRequired == 1 && !cashier) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Please select store.',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                        window.open('<?php echo $this->base; ?>/report/void_order_export?sdate=' + sdate + '&cashier=' + cashier, '_blank');
                    });
                </script>
            </div>
        </div>
    </div>
</div>