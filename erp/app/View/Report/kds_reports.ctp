                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">KDS Report</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'kds_reports')); 
                                                $options = [];
                                                if ($this->Session->read('Auth.User.role') == 'admin') {
                                                    $options[''] = 'All Stores';
                                                }
                                                foreach($cashier_users as $user){
                                                    $options[$user['Location']['id']] = $user['Location']['name'];
                                                }
                                                echo $this->Form->input('cashier', array('div'=>array('class'=>'form-group'), 'label'=>'Select Store', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'cashier'));
                                                echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                                                echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                                echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
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
                                                    <th><?php echo 'Reference ID'; ?></th>
                                                    <th><?php echo 'Group ID'; ?></th>
                                                    <th><?php echo 'KDS Station'; ?></th>
                                                    <th><?php echo 'Product Name'; ?></th>
                                                    <th><?php echo 'Role'; ?></th>
                                                    <th><?php echo 'Location Name'; ?></th>
                                                    <th><?php echo 'Status'; ?></th>
                                                    <th><?php echo 'Created'; ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php 
                                                    $x=1;
                                                    foreach ($users as $user):
                                                    $id=$user['k']['id'];
                                                    $statusLabel = '';
                                                    // print_r($user);exit;
                                                    switch ($user['k']['status']) {
                                                        case 0:
                                                            $statusLabel = 'Unfire';
                                                            break;
                                                        case 1:
                                                            $statusLabel = 'Fire';
                                                            break;
                                                        case 2:
                                                            $statusLabel = 'Hold';
                                                            break;
                                                        case 3:
                                                            $statusLabel = 'Start';
                                                            break;
                                                        case 4:
                                                            $statusLabel = 'Bump';
                                                            break;
                                                        case 5:
                                                            $statusLabel = 'Flag';
                                                            break;
                                                        case 6:
                                                            $statusLabel = 'Send to Table';
                                                            break;
                                                        case 7:
                                                            $statusLabel = 'Recall';
                                                            break;
                                                        default:
                                                            $statusLabel = 'Unknown';
                                                            break;
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?php print_r($x);?></td>
                                                    <td><?php print_r($user['k']['order_reference_id']);?></td>
                                                    <td><?php print_r($user['k']['kds_group_id']);?></td>
                                                    <td><?php print_r($user[0]['station_name']);?></td>
                                                    <td><?php print_r($user['k']['product_name']);?></td>
                                                    <td><?php print_r($user['k']['role']);?></td>
                                                    <td><?php print_r($user['k']['location_name']);?></td>
                                                    <td><?php print_r($statusLabel);?></td>
                                                    <td><?php print_r($user['k']['created']);?></td>
                                                    
                                                   
                                                </tr>
                                                <?php $x++;endforeach; ?>            
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                           
                                         
                            </div>
                        </section></div>




                                </div>
                            </div>
                        </div> 
                    </div>
                                     
<script>
    $("#exportdata").click(function(){
        var sdate=$("#startdate").val();
        var cashier=$("#cashier").val();
        
        window.open('<?php echo $this->base; ?>/report/kds_reportexport?sdate='+sdate+'&cashier='+cashier, '_blank');
    
    });
    
            
$(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'sku_change_status']); ?>',
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
                