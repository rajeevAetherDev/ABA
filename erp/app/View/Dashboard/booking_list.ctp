                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Online Bookings</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_booking"><button  class='btn btn-success' type="button" style="float:right">Add New Booking</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'booking_list')); 
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
            <th><?php echo 'Table Number'; ?></th>
            <th><?php echo 'Reference ID'; ?></th>
            <th><?php echo 'Cover Charge'; ?></th>
            <th><?php echo 'PAX'; ?></th>
            <th><?php echo 'Customer Name'; ?></th>
            <th><?php echo 'Customer Phone'; ?></th>
            <th><?php echo 'Booking Time'; ?></th>
            <th><?php echo 'Payment Type'; ?></th>
            <th><?php echo 'Payment Mode'; ?></th>
            <th><?php echo 'Booking Time'; ?></th>
            <th><?php echo 'Action'; ?></th>
            </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user):
                                      $id=$user['online_bookings']['id'];
                                        $payment_type='Postpaid';
                                        if($user['online_bookings']['payment_type']==2){
                                            $payment_type='Prepaid';
                                        }
                                        
                                  ?>
    <tr>
        <td><?php print_r($x);?></td>
        <td><?php print_r($user['online_bookings']['table_number']);?></td>
        <td><?php print_r($user['online_bookings']['reference_id']);?></td>
        <td><?php print_r($user['online_bookings']['cover_charge']);?></td>
        <td><?php print_r($user['online_bookings']['pax']);?></td>
        <td><?php print_r($user['online_bookings']['name']);?></td>
        <td><?php print_r($user['online_bookings']['phone']);?></td>
        <td><?php print_r($user['online_bookings']['booking_time']);?></td>
        <td><?php print_r($payment_type);?></td>
        <td><?php print_r($user['online_bookings']['pay_option_value']);?></td>
        <td><?php print_r($user['online_bookings']['booking_time']);?></td>
        <td>
            <?php
            echo '<a href="edit_booking?id=' . $user['online_bookings']['id'] . '">
                <button class="btn btn-info" type="button">Switch Booking</button>
              </a>';
            echo "<button class='btn btn-danger mark-status' data-id='$id'>Cancel Booking</button>";
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
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'cancel_booking']); ?>',
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