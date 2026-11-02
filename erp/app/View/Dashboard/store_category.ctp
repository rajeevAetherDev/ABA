<?php echo nl2br($this->Flash->render('flash')); ?>
             
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                    <h2 class="page-title">Store Category Management</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php echo $this->Form->create('Search', array('url'=>'store_category')); 
                                                $options[''] = 'Select Store';
                                                // echo "<pre/>";
                                                // print_r($cashier_users);die;
                                                foreach($cashier_users as $user){
                                                    $options[$user['Location']['id']] = $user['Location']['name'];
                                                }
                                                  

                                                $selectedStore = isset($this->request->data['Search']['cashier']) 
                                                ? $this->request->data['Search']['cashier'] 
                                                : '';  
                                                
                                                echo $this->Form->input('cashier', array('div'=>array('class'=>'form-group'), 'label'=>'Select Store', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'cashier','required'=>true  ,  'value' => $selectedStore));
                                                echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                                echo"&nbsp;". $this->Form->button('Import', array('class'=>'btn btn-info', 'type'=>'button','id'=>'importdata','style'=>'display:none;'));
                                                echo"&nbsp;". $this->Form->button('Export', array('class'=>'btn btn-info', 'type'=>'button','id'=>'exportdata','style'=>'display:none;'));
                                                echo"&nbsp;". $this->Form->button('Assign', array('class'=>'btn btn-success', 'type'=>'button','id'=>'manager_catgory','style'=>'display:none;'));
                                                 echo"&nbsp;". $this->Form->button('Copy From', array('class'=>'btn btn-success', 'type'=>'button','id'=>'copy_categories','style'=>'display:none;'));
                                                 
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
                                                    <th><?php echo 'Category ID'; ?></th>
                                                    <th><?php echo 'Category Name'; ?></th>
                                                    <th><?php echo 'Action'; ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                  <?php $x=1;
                                  foreach ($users as $user): 
                                      $id=$user['Catalog_category_branches']['id'];
                                  ?>
                                        <tr id="row-<?php echo $user['Catalog_category_branches']['category_id']; ?>">
                                            <td><?php print_r($x);?></td>
                                            <td><?php print_r($user['Catalog_category_branches']['category_id']);?></td>
                                            <td><?php print_r($user['Catalog_category_branches']['category_name']);?></td>
                                            <td> <button class="btn btn-danger toggle-status" data-id="<?php echo $user['Catalog_category_branches']['category_id']; ?>" data-loc_row_id ="<?=$selectedStore;?>" data-status="active">Remove
                                            </button></td>
                                        
                                        </tr>
                                    <?php $x++;endforeach; ?>            
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

<!-- Copy Categories Modal -->
<div class="modal fade" id="copy_store_categories" tabindex="-1" role="dialog" aria-labelledby="copyStoreCategoriesLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="copyCategoriesForm" method="post" action="<?= Router::url(['controller' => 'Dashboard', 'action' => 'copy_store_categories']) ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="copyStoreCategoriesLabel">Copy Categories From Store</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <input type="hidden" id="modal_store_id" name="store_id" value="">
            <label for="copy_from_store">Select Store to Copy From</label>
            <select name="copy_from_store" id="copy_from_store" class="form-control" required>
              <option value="">Select Store</option>
              <?php foreach($cashier_users as $user): ?>
                <option value="<?php echo htmlspecialchars($user['Location']['id']); ?>">
                  <?php echo htmlspecialchars($user['Location']['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <input type="hidden" name="current_store_id" value="<?php echo htmlspecialchars($this->request->data['Search']['cashier'] ?? ''); ?>">
        </div>
        
        <div class="modal-footer custom-theme">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Copy</button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- Import Category Modal -->
<div class="modal fade" id="import_store_categories" tabindex="-1" role="dialog" aria-labelledby="importStoreCategoriesLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="importCategoriesForm" method="post" action="<?= Router::url(['controller' => 'Dashboard', 'action' => 'bulk_import_store_categories']) ?>" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importStoreCategoriesLabel">Import Store Categories</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="import_file">Select File to Upload</label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                        <small class="form-text text-muted">Accepted formats: .xls</small>
                    </div>
                    <input type="hidden" id="store-ids" name="store_idss" value="<?php echo htmlspecialchars($user['Location']['id']); ?>">
                </div>
                
                <div class="modal-footer custom-theme">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

                    
       <script>
    if($("#cashier").val()){
        $("#manager_catgory").show();
        $("#copy_categories").show();
        //$("#exportdata").show();
        //$("#importdata").show();
    }

    $("#cashier").change(function(){
        var loc_id = $(this).val();
        if(loc_id){
            $("#manager_catgory").show();
            $("#copy_categories").show();
           // $("#exportdata").show();
           // $("#importdata").show();
            $('#store-ids').val(loc_id);
        } else {
            $("#manager_catgory").hide();
            $("#copy_categories").hide();
          //  $("#exportdata").hide();
           // $("#importdata").hide();
        }
    });

    $("#manager_catgory").click(function(){
        var loc_id=$("#cashier").val();
        window.location.href = '<?php echo $this->base; ?>/dashboard/manage_store_category?loc_id=' + loc_id;
    });
        
    $("#exportdata").click(function(){
        window.open('<?php echo $this->base; ?>/dashboard/store_category_sample', '_blank');
    
    });

    $("#copy_categories").click(function(){
        var loc_id=$("#cashier").val();
        $("#modal_store_id").val(loc_id);
        $("#copy_from_store option").each(function(){
            if ($(this).val() === loc_id) {
                $(this).hide(); 
            } else {
                $(this).show(); 
            }
        }); 
        $("#copy_from_store").val('');
        $("#copy_store_categories").modal('show');
    });

    $('#importdata').click(function(){
        $("#import_store_categories").modal('show');
    });
            
$(document).ready(function() {
    // Handle status toggle button click
    $(document).on('click', '.toggle-status', function() {
        const id = $(this).data('id');
        const loc_row_id = $(this).data('loc_row_id');
        const row = $('#row-' + id); 
        $.ajax({
            url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'change_store_category_status']) ?>',
            type: 'POST',
            data: { _id: id,
                    loc_row_id: loc_row_id
             },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Failed to update status');
                }
            },
            error: function(xhr) {
                alert('Error updating status');
                console.error(xhr.responseText);
            }
        });
    });

});
</script>         