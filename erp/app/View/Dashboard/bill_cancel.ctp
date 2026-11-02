<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">UnBill Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                    <div class="form-group">
                        <?php echo $this->Form->create('Search', array('url'=>'bill_cancel')); 
                            // echo $this->Form->input('user_id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select User','required'=>'required')); 
                            $options = [];
                            if ($this->Session->read('Auth.User.role') == 'admin') {
                                $options[''] = 'All Stores';
                            }
                            foreach($users as $user){
                                $options[$user['Location']['id']] = $user['Location']['name'];
                            }
                            echo $this->Form->input('cashier', array('div'=>array('class'=>'form-group'), 'label'=>'Select Store', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'category','id'=>'cashier'));
                            echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                            echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                            echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                            // echo"&nbsp;". $this->Form->button('Print', array('class'=>'btn btn-info', 'type'=>'button','id'=>'btn-pdf'));
                            echo $this->Form->end(); 
                        ?>
                    </div>
                    <div class="table-responsive mt-4">
                    <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bill No</th>
                                <th>Store</th>
                                <th>Payment Type</th>
                                <th>Remarks</th>
                                <th>Bill Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $x = 1; foreach ($transactions as $tr):?>
                                <tr>
                                    <td><?= $x++ ?></td>
                                    <td><?= h($tr['t']['orderno']) ?></td>
                                    <td><?= h($tr[0]['cashier']) ?></td>
                                    <td><?= h($tr[0]['type']) ?></td>
                                    <td><?= h($tr[0]['remarks']) ?></td>
                                    <td><?= number_format($tr[0]['amount'], 2) ?></td>
                                    <td><?= date('d-m-Y h:i A', strtotime($tr['t']['created'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    $("#exportdata").click(function () {
                        var sdate = $("#startdate").val();
                        var cashier = $("#cashier").val();
                        window.open('<?php echo $this->base; ?>/dashboard/bill_cancel_export?sdate=' + sdate + '&cashier=' + cashier, '_blank');
                    });
                </script>
            </div>
        </div>
    </div>
</div>
