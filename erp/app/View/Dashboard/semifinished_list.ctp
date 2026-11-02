                     <div class="row">
                        <div class="col-sm-10">
                            <div class="page-title-box">
                                    <h2 class="page-title">Semi-Finished List</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-2">
                            <div class="page-title-box">
                                    <h2 class="page-title"><a href="create_semifinished"><button  class='btn btn-success' type="button" style="float:right">Add Semi-Finished</button></a></h2>
                                    <br><button type="button" id="exportSemiBtn" class="btn btn-primary" style="float:right;">
                                            ⇩ Export Recipe Costing
                                        </button>
                                        
                                        </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            
                                            <?php echo $this->Form->create('Search', array('url'=>'semifinished_list')); 
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
                                                    <th><input type="checkbox" id="selectAll"></th>
            <th><?php echo '#'; ?></th>
            <th><?php echo 'Name'; ?></th>
            <th><?php echo 'Category'; ?></th>
            <th><?php echo 'Sub Category'; ?></th>
            <th><?php echo 'Unit Name'; ?></th>
            <th><?php echo 'Price'; ?></th>
            <th><?php echo 'Recipe Batch Cost'; ?></th>
            <th><?php echo 'Recipe Unit Cost'; ?></th>
            <th><?php echo 'Required Stock'; ?></th>
            <th><?php echo 'Cost'; ?></th>
            <th><?php echo 'Status'; ?></th>
            <th><?php echo 'Created'; ?></th>
            <th><?php echo 'Action'; ?></th>
            </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user):
                                      
                                       $id=$user['inventory_semifinished']['id'];
                                       $user['inventory_semifinished']['recipe_batch_cost'] = isset($user[0]['recipe_batch_cost']) ? $user[0]['recipe_batch_cost'] : 0;
                                       $user['inventory_semifinished']['recipe_unit_cost'] = isset($user[0]['recipe_unit_cost']) ? $user[0]['recipe_unit_cost'] : 0;
                                     
                                      if($user['inventory_semifinished']['status']==0){
                                          
                                          $status='Inactive';
                                          $buttonClass = 'btn-success';
                                          $buttonText = 'Mark Active';

                                      }else{
                                          $status='Active';
                                          $buttonClass = 'btn-danger';
                                          $buttonText = 'Mark Inactive';

                                      }

                                  ?>
    <?php
        // Status badges: flags come per-row as correlated subquery columns from controller
        $sfConvMismatch  = !empty($user[0]['has_conv_mismatch']);
        $sfRecipeUnitBad = !empty($user[0]['has_recipe_unit_mismatch']);
        $sfUnitCost        = floatval($user['inventory_semifinished']['recipe_unit_cost'] ?? 0);
        $sfNoCost          = ($sfUnitCost <= 0);
    ?>
    <tr>
        <td><input type="checkbox" class="semi-check" value="<?= $id ?>"></td>
        <td><?php print_r($id);?></td>
        <td><?php print_r($user['inventory_semifinished']['name']);?></td>
        <td><?php print_r($user['inventory_semifinished']['cat_name']);?></td>
        <td><?php print_r($user['inventory_semifinished']['subcat_name']);?></td>
        <td><?php print_r($user['inventory_semifinished']['unit_name']);?></td>
        <td><?php print_r($user['inventory_semifinished']['price']);?></td>
        <td><?php echo number_format($user['inventory_semifinished']['recipe_batch_cost'], 2); ?></td>
        <td><?php echo number_format($user['inventory_semifinished']['recipe_unit_cost'], 4); ?></td>
        <td><?php print_r($user['inventory_semifinished']['stock']);?></td>
        <td>
            <?php if ($sfConvMismatch): ?>
            <span class="badge" style="background:#fd7e14; color:#fff; font-size:11px; padding:3px 7px; border-radius:4px; display:inline-block; margin:1px;"
                title="Priced per portion but no gram-weight set. Open Recipe to add conversion factor.">
                ⚠ Missing Conversion
            </span>
            <?php endif; ?>
            <?php if ($sfRecipeUnitBad): ?>
            <span class="badge" style="background:#dc3545; color:#fff; font-size:11px; padding:3px 7px; border-radius:4px; display:inline-block; margin:1px;"
                title="Recipe has ingredient(s) with wrong unit type (e.g. Ml used but SKU priced per Gm). Open Recipe to fix.">
                ⚠ Unit Mismatch
            </span>
            <?php endif; ?>
            <?php if (!$sfConvMismatch && !$sfRecipeUnitBad && $sfNoCost): ?>
            <span class="badge" style="background:#6c757d; color:#fff; font-size:11px; padding:3px 7px; border-radius:4px; display:inline-block; margin:1px;"
                title="Recipe cost is 0 — ingredients may have no price or no recipe defined.">
                ◌ No Cost
            </span>
            <?php endif; ?>
            <?php if (!$sfConvMismatch && !$sfRecipeUnitBad && !$sfNoCost): ?>
            <span class="badge" style="background:#28a745; color:#fff; font-size:11px; padding:3px 7px; border-radius:4px; display:inline-block; margin:1px;">
                ✓ OK
            </span>
            <?php endif; ?>
        </td>
        <td><?php print_r($status);?></td>
        <td><?php print_r($user['inventory_semifinished']['created']);?></td>
        <td>
            <?php
            echo '<a href="add_recipe?id=' . $user['inventory_semifinished']['id'] . '">
                <button class="btn btn-info" type="button">Recipes</button>
            </a>';
            echo '<a href="edit_semifinished?id=' . $user['inventory_semifinished']['id'] . '">
                <button class="btn btn-info" type="button">Edit</button>
              </a>';
            echo '<a href="view_semifinished?id=' . $user['inventory_semifinished']['id'] . '">
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
            <form id="semiExportForm" method="POST"
      action="<?= Router::url(['controller'=>'Dashboard','action'=>'export_semifinished_recipe_costing']) ?>"
      target="_blank">

    <input type="hidden" name="semifinished_ids" id="exportSemiIds">
    <input type="hidden" name="export_all" id="exportSemiAll" value="0">
</form>                         
<script>

$(document).ready(function() {

    // ✅ Initialize DataTable ONLY ONCE
    var table = $('#example').DataTable({
        order: [[1, 'desc']]
    });

    // ✅ Select All (ALL pages, not just current)
    $('#selectAll').on('change', function() {
        var isChecked = this.checked;

        table.rows().every(function () {
            $(this.node()).find('.semi-check').prop('checked', isChecked);
        });
    });

    // ✅ Export (ALL pages)
    $('#exportSemiBtn').on('click', function() {

        var checked = [];

        table.rows().every(function () {
            $(this.node()).find('.semi-check:checked').each(function () {
                checked.push(this.value);
            });
        });

        if (checked.length === 0) {
            $('#exportSemiAll').val('1');
            $('#exportSemiIds').val('');
        } else {
            $('#exportSemiAll').val('0');
            $('#exportSemiIds').val(checked.join(','));
        }

        $('#semiExportForm').submit();
    });
    
    // Handle status toggle button click
    $(document).on('click', '.mark-status', function() {
        var id = $(this).data('id');
        var button = $(this);

        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'semifinished_change_status']); ?>',
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
                