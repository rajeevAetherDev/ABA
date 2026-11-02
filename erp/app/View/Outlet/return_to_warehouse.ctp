<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Return To Warehouse</h2>
        </div>
    </div>

    <div class="col-sm-2">
        <div class="page-title-box">
            <a href="create_return_to_warehouse">
                <button class="btn btn-success" type="button" style="float:right">
                    Create Return
                </button>
            </a>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <!-- DATE FILTER -->
                <div class="form-group row">
                    <div class="col-sm-8">

                        <?php
                        echo $this->Form->create('Search', [
                            'url' => 'return_to_warehouse'
                        ]);

                        echo $this->Form->input('startdate', [
                            'div' => ['class' => 'form-group'],
                            'label' => ['class' => 'form-label'],
                            'class' => 'form-control calendarmultiple',
                            'label' => 'Select Date',
                            'id' => 'startdate',
                            'name' => 'daterange',
                            'required' => true,
                            'value' => $startdate
                        ]);

                        echo $this->Form->button('View', [
                            'class' => 'btn btn-info',
                            'type' => 'submit'
                        ]);

                        echo $this->Form->end();
                        ?>

                    </div>
                </div>


                <div style="height:30px;width:100%" class="clearfix"></div>


                <!-- TABLE -->
                <div class="row">
                    <div class="col-lg-12">

                        <table id="example"
                               class="display table table-hover table-condensed"
                               cellspacing="0"
                               width="100%">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Return ID</th>
                                    <th>Warehouse</th>
                                    <th>Remarks</th>
                                    <th>Amount</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>


                            <tbody>

                                <?php $x = 1; ?>

                                <?php foreach ($users as $user): ?>

                                    <?php
                                        $returnId = $user['inventory_return']['return_id'];
                                        $formId = 'pdfForm_' . $returnId;
                                    ?>

                                    <tr>

                                        <td><?= $user['inventory_return']['id']; ?></td>

                                        <td>
                                            <?= $user['inventory_return']['return_id']; ?>
                                        </td>

                                        <td>
                                            <?= $user['inventory_return']['warehouse_name']; ?>
                                        </td>

                                        <td>
                                            <?= $user['inventory_return']['remarks']; ?>
                                        </td>

                                        <td>
                                            ₹ <?= number_format($user['inventory_return']['total_amount'], 2); ?>
                                        </td>

                                        <td>
                                            <?= date(
                                                'd-m-Y h:i A',
                                                strtotime($user['inventory_return']['created'])
                                            ); ?>
                                        </td>
                                        
                                        <td>
                                            <?php
                                                $statusArr = [
                                                    0 => ['Pending', 'warning', '⏳'],
                                                    1 => ['Approved', 'success', '✔'],
                                                    2 => ['Rejected', 'danger', '✖']
                                                ];
                                                $statusVal = $user['inventory_return']['admin_status'] ?? 0;

                                                $s = $statusArr[$statusVal] ?? ['Unknown', 'secondary', '?'];
                                            ?>
                                            
                                            <span class="status-badge status-<?= $s[1]; ?>">
                                                <?= $s[2]; ?> <?= $s[0]; ?>
                                            </span>
                                        </td>
                                        
                                        <td>
                                            <?php if ($statusVal == 0): ?>
    
                                                <!-- EDIT -->
                                                <a href="edit_return_to_warehouse?id=<?= $returnId ?>">
                                                    <button class="btn btn-warning btn-sm">
                                                        Edit
                                                    </button>
                                                </a>
                                            <?php endif; ?>

                                            <!-- VIEW -->
                                            <a href="<?= $this->Html->url([
                                                'action' => 'view_return_to_warehouse',
                                                '?' => ['id' => $returnId]
                                            ]); ?>">
                                                <button class="btn btn-info btn-sm">
                                                    View
                                                </button>
                                            </a>


                                            <!-- PDF BUTTON -->
                                            <button class="btn btn-primary btn-sm btn-pdf"
                                                    data-form="<?= $formId ?>">
                                                PDF
                                            </button>


                                            <!-- PDF FORM -->
                                            <?= $this->Form->create(false, [
                                                'id' => $formId,
                                                'url' => [
                                                    'action' => 'return_to_warehouse_pdf'
                                                ],
                                                'type' => 'post',
                                                'target' => '_blank'
                                            ]) ?>

                                                <?= $this->Form->hidden('return_id', [
                                                    'value' => $returnId
                                                ]) ?>

                                            <?= $this->Form->end() ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

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

    $('#example').DataTable({
        destroy: true,
        order: [[0, 'desc']]
    });


    $(document).on('click', '.btn-pdf', function () {

        var formId = $(this).data('form');

        document.getElementById(formId).submit();

    });

});

</script>