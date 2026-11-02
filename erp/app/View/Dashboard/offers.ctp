                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Offers</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_offers"><button  class='btn btn-success' type="button" style="float:right">Add New Offer</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'offers')); 
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
                                                    <th><?php echo 'Offer ID'; ?></th>
                                                    <th><?php echo 'Name'; ?></th>
                                                    <th><?php echo 'Type'; ?></th>
                                                    <th><?php echo 'Start Date'; ?></th>
                                                    <th><?php echo 'End Date'; ?></th>
                                                    <th><?php echo 'Start Time'; ?></th>
                                                    <th><?php echo 'End Time'; ?></th>
                                                    <th><?php echo 'Days'; ?></th>
                                                    <th><?php echo 'Status'; ?></th>
                                                    <th><?php echo 'Created'; ?></th>
                                                    <th><?php echo 'Action'; ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php $x=1;
                                                    foreach ($users as $user):
                                                        $id=$user['pos_offers']['offer_id'];
                                                        if ($user[0]['offer_status'] == 0) {
                                                            $status = 'Inactive';
                                                            $buttonClass = 'btn-success';
                                                            $buttonText = 'Mark Active';
                                                        } else {
                                                            $status = 'Active';
                                                            $buttonClass = 'btn-danger';
                                                            $buttonText = 'Mark Inactive';
                                                        }

                                                ?>
                                                    <tr>
                                                        <td><?php print_r($x);?></td>
                                                        <td><?php print_r($user['pos_offers']['offer_id']);?></td>
                                                        <td><?php print_r($user['pos_offers']['name']);?></td>
                                                        <td><?php print_r($user['pos_offers']['offer_type']);?></td>
                                                        <td><?php print_r($user['pos_offers']['start_date']);?></td>
                                                        <td><?php print_r($user['pos_offers']['end_date']);?></td>
                                                        <td><?php print_r($user['pos_offers']['start_time']);?></td>
                                                        <td><?php print_r($user['pos_offers']['end_time']);?></td>
                                                         <?php 
                                                             $rawDays = isset($user[0]['days']) && !empty($user[0]['days']) ? $user[0]['days'] : (isset($user['pos_offers']['days']) && !empty($user['pos_offers']['days']) ? $user['pos_offers']['days'] : 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday');
                                                             $displayDays = ($rawDays === 'full_week' || count(explode(',', $rawDays)) >= 7) ? 'Full Week' : $rawDays;
                                                         ?>
                                                         <td><?php echo h($displayDays); ?></td>
                                                         <td><?php print_r($status);?></td>
                                                        <td><?php print_r($user['pos_offers']['created']);?></td>
                                                        <td>
                                                            <?php
                                                            echo '<a href="view_offer?id=' . $user['pos_offers']['offer_id'] . '">
                                                                <button class="btn btn-info" type="button">View</button>
                                                            </a> ';
                                                            echo '<a href="export_offer_details?id=' . $user['pos_offers']['offer_id'] . '">
                                                                <button class="btn btn-success" type="button">Export</button>
                                                            </a> ';
                                                            echo '<a href="edit_offer?id=' . $user['pos_offers']['offer_id'] . '">
                                                                <button class="btn btn-info" type="button">Edit</button>
                                                            </a> ';
                                                            
                                                            // echo "<button class='btn $buttonClass mark-status' data-id='$id'>$buttonText</button>";
                                                            echo "<button class='btn $buttonClass mark-status' data-id='$id'>Remove</button>";
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
            
$(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'offer_change_status']); ?>',
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