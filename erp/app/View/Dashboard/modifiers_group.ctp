                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Modifier Groups List</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_modifiers_group"><button  class='btn btn-success' type="button" style="float:right;">Add Modifier Groups</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'modifiers_group')); 
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
            <th><?php echo 'Title'; ?></th>
            <th><?php echo 'Group Type'; ?></th>
            <th><?php echo 'Default'; ?></th>
            <th><?php echo 'Status'; ?></th>
            <th><?php echo 'Created'; ?></th>
            <th><?php echo 'Action'; ?></th>
            </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user):
                                      
                                       $id=$user['catalog_modifier_group']['id'];
                                     
                                      if($user['catalog_modifier_group']['status']==0){
                                          
                                          $status='Inactive';
                                          $buttonClass = 'btn-success';
                                          $buttonText = 'Mark Active';

                                      }else{
                                          $status='Active';
                                          $buttonClass = 'btn-danger';
                                          $buttonText = 'Mark Inactive';

                                      }


                                      if($user['catalog_modifier_group']['group_type']==1){
                                          $group_type='Variant';
                                      }else{
                                          $group_type='Add-On';
                                      }
                                      if($user['catalog_modifier_group']['is_default']==1){
                                          $is_default='Yes';
                                      }else{
                                          $is_default='No';
                                      }
                                      
                                     
                                  ?>
    <tr>
        <td><?php print_r($x);?></td>
        <td><?php print_r($user['catalog_modifier_group']['name']);?></td>
        <td><?php print_r($group_type);?></td>
        <td><?php print_r($is_default);?></td>
        <td><?php print_r($status);?></td>
        <td><?php print_r($user['catalog_modifier_group']['created']);?></td>
        <td>
            <?php
            echo '<a href="edit_modifier_group?id=' . $user['catalog_modifier_group']['id'] . '">
                <button class="btn btn-info" type="button">Edit</button>
              </a>';
            echo '<a href="view_modifiers_group?id=' . $user['catalog_modifier_group']['id'] . '">
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
    $(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'modifiers_group_change_status']); ?>',
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
                