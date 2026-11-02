                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Locations</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_location"><button  class='btn btn-success' type="button" style="float:right">Add New Location</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'location_list')); 
                                                echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                                                echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                                // echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
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
            <th><?php echo 'Name'; ?></th>
            <th><?php echo 'State'; ?></th>
            <th><?php echo 'Shift Start Time'; ?></th>
            <th><?php echo 'Shift End Time'; ?></th>
            <th><?php echo 'Reelo Merchant ID'; ?></th>
            <th><?php echo 'Reelo Customer Key'; ?></th>
            <th><?php echo 'Service Charge Rate (%)'; ?></th>
            <th><?php echo 'Status'; ?></th>
            <th><?php echo 'Created'; ?></th>
            <th><?php echo 'Action'; ?></th>
            </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user):
                                    $id=$user['locations']['id'];
                                     
                                      if($user['locations']['status']==0){
                                          
                                          $status='Inactive';
                                          $buttonClass = 'btn-success';
                                          $buttonText = 'Mark Active';

                                      }else{
                                          $status='Active';
                                          $buttonClass = 'btn-danger';
                                          $buttonText = 'Mark Inactive';

                                      }

                                  ?>
                                                    <tr>
                                                        <td><?php print_r($x);?></td>
                                                        <td><?php print_r($user['locations']['name']);?></td>
                                                        <td><?php print_r($user['locations']['state']);?></td>
                                                        <?php
                                                            $defStartFmt = date('h:i A', strtotime(date('Y-m-d') . " " . trim(shiftstart)));
                                                            $defEndFmt   = date('h:i A', strtotime(date('Y-m-d') . " " . trim(shiftend)));
                                                            $valStart    = !empty($user['locations']['shift_start_time']) ? date('h:i A', strtotime(date('Y-m-d') . " " . $user['locations']['shift_start_time'])) : $defStartFmt;
                                                            $valEnd      = !empty($user['locations']['shift_end_time']) ? date('h:i A', strtotime(date('Y-m-d') . " " . $user['locations']['shift_end_time'])) : $defEndFmt;
                                                        ?>
                                                        <td><?php echo $valStart; ?></td>
                                                        <td><?php echo $valEnd; ?></td>
                                                        <td><?php print_r(isset($user['locations']['reelo_merchant_id']) ? $user['locations']['reelo_merchant_id'] : '');?></td>
                                                        <td><?php print_r(isset($user['locations']['reelo_customer_key']) ? $user['locations']['reelo_customer_key'] : '');?></td>
                                                        <td><?php print_r(isset($user['locations']['service_charge_rate']) ? $user['locations']['service_charge_rate'] : '');?></td>
                                                        <td><?php print_r($status);?></td>
                                                        <td><?php print_r($user['locations']['created']);?></td>
                                                        <td>
                                                            <?php
                                                            echo '<a href="edit_location?id=' . $user['locations']['id'] . '">
                                                                <button class="btn btn-info" type="button">Edit</button>
                                                              </a>';
                                                            echo '<a href="view_location?id=' . $user['locations']['id'] . '">
                                                                <button class="btn btn-info" type="button">View</button>
                                                            </a>';
                                                            
                                                            echo "<button class='btn $buttonClass mark-status' data-id='$id'>$buttonText</button>";
                                                            ?>
                                                            
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
    $("#exportdata").click(function(){
        var sdate=$("#startdate").val();
        
        window.open('<?php echo $this->base; ?>/task/locationlist_export?sdate='+sdate, '_blank');
    
    });      
          
$(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'location_change_status']); ?>',
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