                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">SKU List</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_sku"><button  class='btn btn-success' type="button" style="float:right">Add SKU</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'sku_list')); 
                                                echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                                                // echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                                //echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata'));
                                                echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                                echo "&nbsp;";
                                                echo $this->Form->button('Export', array('class'=>'btn btn-warning', 'type'=>'button', 'id'=>'exportdata'));
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
                                                    <th><?php echo 'Category'; ?></th>
                                                    <th><?php echo 'Sub Category'; ?></th>
                                                    <th><?php echo 'Unit Name'; ?></th>
                                                    <th><?php echo 'Price'; ?></th>
                                                    <th><?php echo 'Total Tax Percentage'; ?></th>
                                                    <th><?php echo 'Last stockin Price'; ?></th>
                                                    <th><?php echo 'AVG Price'; ?></th>
                                                    <th><?php echo 'Required Stock'; ?></th>
                                                    <th><?php echo 'Status'; ?></th>
                                                    <th><?php echo 'Created'; ?></th>
                                                    <th><?php echo 'Action'; ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user):
                                      
                                       $id=$user['inv']['id'];
                                     
                                      if($user['inv']['status']==0){
                                          
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
                                            <td><?php print_r($id);?></td>
                                            <td><?php print_r($user['inv']['name']);?></td>
                                            <td><?php print_r($user['inv']['cat_name']);?></td>
                                            <td><?php print_r($user['inv']['subcat_name']);?></td>
                                            <td><?php print_r($user['inv']['unit_name']);?></td>
                                            <td><?php print_r($user['inv']['price']);?></td>
                                            <td><?php print_r($user['0']['total_tax'] ?? 0);?></td>
                                            <td><?php print_r($user['inv']['last_stockin_price']);?></td>
                                            <td><?php print_r($user['inv']['avg_stockin_price']);?></td>
                                            
                                            <td><?php print_r($user['inv']['stock']);?></td>
                                            <td><?php print_r($status);?></td>
                                            <td><?php print_r($user['inv']['created']);?></td>
                                            <td>
                                                <?php
                                                echo '<a href="edit_sku?id=' . $user['inv']['id'] . '">
                                                    <button class="btn btn-info" type="button">Edit</button>
                                                  </a>';
                                                echo '<a href="view_sku?id=' . $user['inv']['id'] . '">
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
    var sdate = $("#startdate").val();
    window.open('<?php echo $this->base; ?>/dashboard/sku_list_export?sdate=' + encodeURIComponent(sdate), '_blank');
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
                