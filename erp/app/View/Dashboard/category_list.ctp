                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Category List</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_category"><button  class='btn btn-success' type="button" style="float:right">Add Category</button></a></h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'category_list')); 
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
            <th><?php echo 'Name'; ?></th>
            <th><?php echo 'Status'; ?></th>
            <th><?php echo 'Created'; ?></th>
            <th><?php echo 'Action'; ?></th>
            </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user): 
                                      $id=$user['Catalog_category']['id'];
                                     
                                      if($user['Catalog_category']['status']==0){
                                          
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
        <td><?php print_r($user['Catalog_category']['name']);?></td>
        <td><?php print_r($status);?></td>
        <td><?php print_r($user['Catalog_category']['created']);?></td>
        <td>
            <?php
            echo '<a href="edit_category?id=' . $user['Catalog_category']['id'] . '">
                <button class="btn btn-info" type="button">Edit</button>
              </a>';
            echo '<a href="view_category?id=' . $user['Catalog_category']['id'] . '">
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
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'category_change_status']); ?>',
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

$("#exportdata").click(function(){
    var sdate = $("#startdate").val();
    if(!sdate){
        alert("Please select a date before exporting.");
        return;
    }
    window.open('<?php echo $this->base; ?>/dashboard/category_list_export?sdate=' + encodeURIComponent(sdate), '_blank');
});
       </script>         