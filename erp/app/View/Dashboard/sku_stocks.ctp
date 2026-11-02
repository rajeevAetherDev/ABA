                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">SKU Stocks</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th><?php echo '#'; ?></th>
                                                        <th><?php echo 'Name'; ?></th>
                                                        <th><?php echo 'Unit'; ?></th>
                                                        <th><?php echo 'Quantity'; ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $x=1;
                                                    foreach ($users as $user):
                                                        $quantity=0;
                                                        if($user[0]['quantity']){
                                                            $quantity=$user[0]['quantity'];
                                                        }
                                                    ?>
                                                        <tr>
                                                            <td><?php print_r($x);?></td>
                                                            <td><?php print_r($user['inventory_skus']['name']);?></td>
                                                            <td><?php print_r($user['inventory_skus']['unit_name']);?></td>
                                                            <td><?php print_r($quantity);?></td>
                                                        </tr>
                                                        <?php $x++;
                                                    endforeach; ?>            
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
        
        window.open('<?php echo $this->base; ?>/task/employeelist_export?sdate='+sdate, '_blank');
    
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
                