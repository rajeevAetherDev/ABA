                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">KDS Users</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_kds_users"><button  class='btn btn-success' type="button" style="float:right">Add Kds Users</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'kds_users')); 
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
            <th><?php echo 'Username'; ?></th>
            <th><?php echo 'Location'; ?></th>
            <th><?php echo 'Role'; ?></th>
            <th><?php echo 'Stations'; ?></th>
            <th><?php echo 'Status'; ?></th>
            <th><?php echo 'Created'; ?></th>
            <th><?php echo 'Action'; ?></th>
            </tr>
                                            </thead>
<tbody>
<?php 
$x=1;
foreach ($users as $user): 
    $id = $user['ku']['id'];

    if ($user['ku']['status'] == 0) {
        $status = 'Inactive';
        $buttonClass = 'btn-success';
        $buttonText  = 'Mark Active';
    } else {
        $status = 'Active';
        $buttonClass = 'btn-danger';
        $buttonText  = 'Mark Inactive';
    }
?>
<tr>
    <td><?php echo $x; ?></td>
    <td><?php echo h($user['ku']['username']); ?></td>
    <td><?php echo h($user['ku']['location_name']); ?></td>
    <td><?php echo h($user['ku']['role']); ?></td>
    <td><?php echo h(!empty($user[0]['stations']) ? $user[0]['stations'] : '-'); ?></td>
    <td><?php echo $status; ?></td>
    <td><?php echo h($user['ku']['created']); ?></td>
    <td>
        <a href="edit_kds_users?id=<?php echo $id; ?>">
            <button class="btn btn-info" type="button">Edit</button>
        </a>
        <button class="btn <?php echo $buttonClass; ?> mark-status" data-id="<?php echo $id; ?>">
            <?php echo $buttonText; ?>
        </button>
    </td>
</tr>
<?php 
$x++;
endforeach; 
?>            
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
                
                window.open('<?php echo $this->base; ?>/task/employeelist_export?sdate='+sdate, '_blank');
            
            });
            
            
$(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'kds_users_change_status']); ?>',
            type: 'POST',
            data: { _id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // alert('Status Updated');
                    location.reload();
                } else {
                    alert(response.message || 'Failed to update status');
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