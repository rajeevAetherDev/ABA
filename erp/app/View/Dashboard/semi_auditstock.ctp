<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Semifinished Audit Stocks</h2>
        </div>
    </div>
    <div class="col-sm-2 text-right">
        <div class="page-title-box">
            <h2 class="page-title"><a href="semi_create_audit_stock"><button  class='btn btn-success' type="button" style="float:right;">Add Audit</button></a></h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?= $this->Form->create(false, ['url' => ['controller' => 'dashboard', 'action' => 'semi_auditstock'], 'type' => 'post']) ?>
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php
                            echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                    
                    ?>
                    </div>
                    <div class="col-sm-4">
                        <br>
                        <?= $this->Form->button('Update', ['class' => 'btn btn-info']) ?>
                    </div>
                </div>
                <?= $this->Form->end() ?>

                <div class="tab-content mt-3">
                    <div>
                        <?php if (!empty($preparedStocks)): ?>
                            <table  id='example'   class="display table table-hover table-condensed" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Audit ID</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $x=1; 
                                        foreach ($preparedStocks as $stock):
                                        ?>
                                        <tr>
                                            <td><?= h($x) ?></td>
                                            <td><?= h($stock['Audit_stock']['pos_audit_id']) ?></td>
                                            <td><?= h(date('d-m-Y', strtotime($stock['Audit_stock']['updateon']))) ?></td>
                                            <td><?= h($stock['Audit_stock']['store_name'] ?? '') ?></td>
                                            <td><a href="semi_view_audit?id=<?= h(($stock['Audit_stock']['pos_audit_id'])); ?>" class="btn btn-info btn-sm">View</a></td>
                                        </tr>
                                    <?php $x=$x+1;
                                    endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No audit records found.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

