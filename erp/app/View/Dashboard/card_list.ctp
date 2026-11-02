                     <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                    <h2 class="page-title">Card List</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'card_list')); 
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
            <th><?php echo 'ID'; ?></th>
            <th><?php echo 'Card Number'; ?></th>
            <th><?php echo 'Card Balance'; ?></th>
            <th><?php echo 'Name'; ?></th>
            <th><?php echo 'Mobile'; ?></th>
            <th><?php echo 'Status'; ?></th>
            <th><?php echo 'Created'; ?></th>
            <th><?php echo 'Action'; ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user): 
                                      $id=$user['Card']['id'];
                                     
                                      if($user['Card']['status']==0){
                                          $status='InActive';
                                      }else{
                                          $status='active';
                                      }
                                      
                                      
                                        if($user['Card']['status']==0){
                                          
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
        <td><?php print_r($user['Card']['cardnumber']);?></td>
        <td><?php print_r($user['Card']['cardbalance']);?></td>
        <td><?php print_r($user['Card']['name']);?></td>
        <td><?php print_r($user['Card']['mobile']);?></td>
        <td><?php print_r($status);?></td>
        <td><?php print_r($user['Card']['created']);?></td>
        <td><?php 
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
        window.open('<?php echo $this->base; ?>/dashboard/card_listexport?sdate='+sdate, '_blank');
    
    });


$(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'card_change_status']); ?>',
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
                 
                