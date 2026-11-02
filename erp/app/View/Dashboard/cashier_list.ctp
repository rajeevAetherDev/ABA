                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Cashier/Manager List</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_cashier"><button  class='btn btn-success' type="button" style="float:right;">Add Cashier/Manager</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'cashier_list')); 
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
            <th><?php echo 'ID'; ?></th>
            <th><?php echo 'Name'; ?></th>
            <th><?php echo 'Username'; ?></th>
            <th><?php echo 'Mobile'; ?></th>
            <th><?php echo 'Location'; ?></th>
            <th><?php echo 'Modules'; ?></th>
            <th><?php echo 'Role'; ?></th>
            <th><?php echo 'Pin'; ?></th>
            <th><?php echo 'Can Settlement'; ?></th>
            <th><?php echo 'Status'; ?></th>
            <th><?php echo 'Created'; ?></th>
            <th><?php echo 'Action'; ?></th>
            </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user): 
                                     $id=$user['users']['id'];
                                      if($user['users']['status']==1){
                                          $status='Active';
                                      }else{
                                          $status='Inactive';
                                      }
                                      
                                        $modules = explode(',', $user[0]['module']);
                                        // Map the module names to their respective labels
                                        $moduleLabelsArray = array_map(function($module) use ($moduleLabels) {
                                            return isset($moduleLabels[$module]) ? $moduleLabels[$module] : $module;
                                        }, $modules);
                                        // print_r($moduleLabelsArray);exit;
                                        
                                        // Join the module labels into a string
                                        $moduleLabelsString = implode(', ', $moduleLabelsArray);
                                        
                                        $locationss = explode(',', $user[0]['locations']);
                                        // Map the module names to their respective labels
                                        $locationLabelsArray = array_map(function($location) use ($locationLabels) {
                                            return isset($locationLabels[$location]) ? $locationLabels[$location] : $location;
                                        }, $locationss);
                                        
                                        // Join the module labels into a string
                                        $locationLabelsString = implode(', ', $locationLabelsArray);
                                        
                                        if($user['users']['status']==1){
                                          $status='Active';
                                          $buttonClass = 'btn-danger';
                                          $buttonText = 'Mark Inactive';

                                      }else{
                                          $status='Inactive';
                                          $buttonClass = 'btn-success';
                                          $buttonText = 'Mark Active';

                                      }
                                      if($user['users']['can_settlement']==1){
                                          $can_settlement='Yes';
                                      }else{
                                          $can_settlement='No';
                                      }
                                  ?>
    <tr>
        <td><?php print_r($x);?></td>
        <td><?php print_r($user['users']['name']);?></td>
        <td><?php print_r($user['users']['username']);?></td>
        <td><?php print_r($user['users']['mobile']);?></td>
        <td><?php print_r($locationLabelsString);?></td>
        <td><?php print_r($moduleLabelsString);?></td>
        <td><?php print_r($user['users']['role']);?></td>
        <td><?php print_r($user['users']['pin']);?></td>
        <td><?php print_r($can_settlement);?></td>
        <td><?php print_r($status);?></td>
        <td><?php print_r($user['users']['created']);?></td>
        <td><?php 
              echo '<a href="edit_cashier?id=' . $user['users']['id'] . '">
                <button class="btn btn-info" type="button">Edit</button>
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
        
        window.open('<?php echo $this->base; ?>/dashboard/cashierlist_export?sdate='+sdate, '_blank');
    
    });        
    

</script>
<script>
$(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'cashier_change_status']); ?>',
            type: 'POST',
            data: { user_id: id },
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

                