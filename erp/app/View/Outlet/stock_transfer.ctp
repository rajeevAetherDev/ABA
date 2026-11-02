                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Outlet to Outlet Stock Transfer</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_stock_tranfer"><button  class='btn btn-success' type="button" style="float:right">Create Stock Transfer</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'stock_transfer')); 
                                                echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                                                echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                                //echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                                                echo $this->Form->end(); 
                                            ?>
                                        </div>
                                    </div>
                                    <div style="height:30px; width: 100%" class="clearfix"></div>
                                <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                     <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                                            <thead>
                                                <tr>
                                                    <th><?php echo '#'; ?></th>
                                                    <th><?php echo 'Transfer Id'; ?></th>
                                                    <th><?php echo 'Transfer From'; ?></th>
                                                    <th><?php echo 'Transfer To'; ?></th>
                                                    <th><?php echo 'Amount'; ?></th>
                                                    <th><?php echo 'Created'; ?></th>
                                                    <th><?php echo 'Action'; ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user):
                                      
                                      $id=$user['stock_transfers']['id'];
                                      $tr_id=$user['stock_transfers']['transfer_id'];
                                       
                                      if($user['stock_transfers']['status']==0){
                                          
                                          $status='Inactive';
                                          $buttonClass = 'btn-success';
                                          $buttonText = 'Mark Active';
                                          $buttonDisabled = '';

                                      }
                                      if($user['stock_transfers']['status']==1){
                                          $status='Active';
                                          $buttonClass = 'btn-danger';
                                          $buttonText = 'Delete';
                                          $buttonDisabled = '';

                                      }
                                      
                                  ?>
                                        <tr>
                                            <td><?php print_r($x);?></td>
                                            <td><?php print_r($user['stock_transfers']['transfer_id']);?></td>
                                            <td><?php print_r($user['stock_transfers']['transfer_from_name']);?></td>
                                            <td><?php print_r($user['stock_transfers']['transfer_to_name']);?></td>
                                            <td><?php print_r($user['stock_transfers']['amount']);?></td>
                                            <td><?php print_r($user['stock_transfers']['created']);?></td>
                                            <td>
                                                <a href="edit_stock_transfer?id=<?= $tr_id ?>">
                                                    <button class="btn btn-warning btn-sm">Edit</button>
                                                </a>
                                                
                                                
                                                <a href="<?=
                                                    $this->Html->url([
                                                        'action' => 'view_stock_transfer',
                                                        '?' => ['id' => $user['stock_transfers']['transfer_id']]
                                                    ]);
                                                ?>">
                                                    <button class="btn btn-info btn-sm" type="button">
                                                        View
                                                    </button>
                                                </a>
                                                <?php
                                                    $transferId = $user['stock_transfers']['transfer_id'];
                                                    $formId = 'pdfForm_' . $transferId;
                                                ?>
                                            
                                                <button class="btn btn-info btn-sm btn-pdf" data-form="<?= $formId ?>">
                                                    PDF
                                                </button>
                                            
                                                <?= $this->Form->create(false, [
                                                    'id' => $formId,
                                                    'url' => [
                                                        'action' => 'stock_transfer_pdf'
                                                    ],
                                                    'type' => 'post',
                                                    'target' => '_blank'
                                                ]) ?>
                                                <?= $this->Form->input('transfer_id', [
                                                    'type' => 'hidden',
                                                    'value' => $transferId
                                                ]) ?>
                                            
                                                <?= $this->Form->end() ?>
                                    
                                            
                                            </td>
                                           
                                        </tr>
                                    <?php $x++;endforeach; ?>            
                                            </tbody>
                                        </table>
                                        <!-- ********************************************** -->

    
                                </div>
                                </div>
                           
                                         
                            </div>
                        </section></div>




                                </div>
                            </div>
                        </div> 
                    </div>
                                     
<script>
    
            
$(document).ready(function() {
    $(document).on('click', '.btn-pdf', function () {

        var formId = $(this).data('form');
    
        document.getElementById(formId).submit();
    
    });
    
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url([ 'action' => 'delete_stock_transfer']); ?>',
            type: 'POST',
            data: { _id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // alert('Status Updated');
                    location.reload();
                } else {
                    alert('Failed to update status');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while updating the status.');
                console.log(xhr.responseText);
            }
        });
    });

});

</script> 
                