<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Discount Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                    <div class="form-group">
                        <?php echo $this->Form->create('Search', array('url'=>'discount_report')); 
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
                                <th>Payment Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Total Discount</th>
                                <th>Discount Type</th>
                                <th>Coupon</th>
                                <th>Amount</th>
                                <th>Remarks</th>
                                <th>Customer Name</th>
                                <th>Manager Name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Store Name</th>
                            </tr>
                        </thead> 
                        <tbody>
                            <?php 
                            $x = 1; foreach ($transactions as $tr): 
                            ?>
                                <tr>
                                    <td><?= $x++ ?></td>
                                    <td><?= h($tr['t']['orderno']) ?></td>
                                    <td><?= h($tr[0]['type']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($tr['p']['created'])) ?></td>
                                    <td><?= date('h:i A', strtotime($tr['p']['created'])) ?></td>
                                    <td><?= number_format($tr['t']['discount'], 2) ?></td>
                                    <td>
                                        <?php
                                        
                                        $dtype    = $tr['t']['discount_type'] ?? '';
                                        $price    = $tr['t']['price'] ?? 0;
                                        $discount = $tr['t']['discount'] ?? 0;
                                        
                                        if ($dtype === '0') {
                                        
                                            echo 'Coupon';
                                        
                                        } elseif ($dtype === '1') {
                                        
                                            // percentage discount
                                            $rate = $price > 0 ? round(($discount / $price) * 100, 2) : 0;
                                        
                                            echo 'Percentage (' . $rate . '%)';
                                        
                                        } elseif ($dtype === '2') {
                                        
                                            // flat discount
                                            echo 'Flat (₹' . number_format($discount, 2) . ')';
                                        
                                        } else {
                                        
                                            echo '-';
                                        
                                        }
                                        ?>
                                    </td>
                                    <td><?= '-' ?></td>
                                    <td><?= number_format($tr['t']['amount'], 2) ?></td>
                                    <td><?= h($tr[0]['remarks']) ?></td>
                                    <td><?= h($tr['t']['name']) ?></td>
                                    <td><?= h($tr[0]['managername']) ?></td>
                                    <td><?= h($tr['t']['phone']) ?></td>
                                    <td><?= h($tr['t']['address']) ?></td>
                                    <td><?= h($tr[0]['cashier']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    $("#exportdata").click(function () {
                        var sdate = $("#startdate").val();
                        var cashier = $("#cashier").val();
                        window.open('<?php echo $this->base; ?>/report/discount_reportexport?sdate=' + sdate + '&cashier=' + cashier, '_blank');
                    });
                </script>
            </div>
        </div>
    </div>
</div>
