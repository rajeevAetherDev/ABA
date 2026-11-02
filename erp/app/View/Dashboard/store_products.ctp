<?php echo nl2br($this->Flash->render('flash')); ?>


<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Store Product Management</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('Search', ['url' => 'store_products']); 
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
                    echo"&nbsp;". $this->Form->button('Copy From', array('class'=>'btn btn-success', 'type'=>'button','id'=>'copy_stores','style'=>'display:none;'));
                    echo"&nbsp;". $this->Form->button('Publish to all', array('class'=>'btn btn-success', 'type'=>'button','id'=>'publish_all_data'));
                    echo"&nbsp;". $this->Form->button('Publish', array('class'=>'btn btn-success', 'type'=>'button','id'=>'publish_data','style'=>'display:none;'));
                    echo "&nbsp;" . $this->Form->button('Sync to UrbanPiper', array('class' => 'btn btn-warning', 'type' => 'button', 'id' => 'btn-sync-urbanpiper', 'style' => 'display:none;'));
                    echo "&nbsp;" . $this->Form->button('Sync to Reelo', array('class' => 'btn btn-warning', 'type' => 'button', 'id' => 'btn-sync-reelo', 'style' => 'display:none;'));

                    echo $this->Form->end(); 
                ?>
                <div style="height:30px; width: 100%" class="clearfix"></div>

                <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Category Name</th>
                            <th>Sub Category Name</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1; foreach($assignedCategories as $category): ?>
                        <tr id="row-<?php echo $category['Catalog_product_branches']['product_id']; ?>">
                            <td><?php echo $x; ?></td>
                            <td><?php echo htmlspecialchars($category['Catalog_product_branches']['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($category['Catalog_product_branches']['cat_name']); ?></td>
                            <td><?php echo htmlspecialchars($category['Catalog_product_branches']['subcat_name']); ?></td>
                            <td>&#8377; <?php echo htmlspecialchars($category['Catalog_product_branches']['price']); ?></td>
                            <td><?php echo htmlspecialchars($category['Catalog_product_branches']['unit_name']); ?></td>
                            <td>
                                <button class="btn btn-danger toggle-status" data-id="<?php echo $category['Catalog_product_branches']['product_id']; ?>"  data-loc_row_id="<?=$selectedStore?>">Remove</button>
                            </td>
                        </tr>
                        <?php $x++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Copy Categories Modal -->
<div class="modal fade" id="copy_store_products" tabindex="-1" role="dialog" aria-labelledby="copyStoreProductsLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="copyProductsForm" method="post" action="<?= Router::url(['controller' => 'Dashboard', 'action' => 'copy_store_products']) ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="copyStoreProductsLabel">Copy Products From Store</h5>
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

                    

<!-- Import Products Modal -->
<div class="modal fade" id="import_store_products" tabindex="-1" role="dialog" aria-labelledby="importStoreProductsLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="importProductsForm" method="post" action="<?= Router::url(['controller' => 'Dashboard', 'action' => 'bulk_import_store_products']) ?>" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importStoreProductsLabel">Import Store Products</h5>
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
        $("#copy_stores").show();
        $("#publish_data").show();
        $("#publish_all_data").hide();
        $("#btn-sync-urbanpiper").show();
        $("#btn-sync-reelo").show();
    }
    $("#cashier").change(function(){
    var loc_id = $(this).val();
    if(loc_id){
        $("#manager_catgory").show();
        $("#copy_stores").show();      
        $("#publish_data").show();
        $("#publish_all_data").hide();
      //  $("#exportdata").show();
      //  $("#importdata").show();
        $('#store-ids').val(loc_id);
        $("#btn-sync-urbanpiper").show();
        $("#btn-sync-reelo").show();
    } else {
        $("#manager_catgory").hide();
        $("#copy_stores").hide();
        $("#publish_data").hide();
        $("#publish_all_data").show();
        $("#btn-sync-urbanpiper").hide();
        $("#btn-sync-reelo").hide();
      // $("#exportdata").hide();
      // $("#importdata").hide();
    }
    });
    $("#manager_catgory").click(function(){
        var loc_id=$("#cashier").val();
        window.location.href = '<?php echo $this->base; ?>/dashboard/manage_store_products?loc_id=' + loc_id;
    });

    $("#exportdata").click(function(){
        var loc_id=$("#cashier").val();
        window.open('<?php echo $this->base; ?>/dashboard/store_product_sample?loc_id=' + loc_id +'', '_blank');
    
    });
    
    $("#copy_stores").click(function(){
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
        $("#copy_store_products").modal('show');
    });
    
     // PUBLISH
    $("#publish_all_data").click(function(){
        update_location_publish();
    });

    $("#publish_data").click(function(){
        update_location_publish($("#cashier").val());
    });

    function update_location_publish(store_id = '') {
        $("#publish_data, #publish_all_data").prop('disabled', true);

        $.ajax({
            url: '<?= Router::url(["controller"=>"Dashboard","action"=>"update_location_publish"]) ?>',
            type: "POST",
            dataType: "json",
            data: { store_id },
            success: function (res) {
                if(res.success){
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Data published successfully',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert("Publish failed");
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: res.message ? res.message : 'Publish failed',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function () {
                 Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonText: 'OK'
                });
            },
            complete: function(){
                $("#publish_data, #publish_all_data").prop('disabled', false);
            }
        });
    }

    
    
    $('#importdata').click(function(){
        $("#import_store_products").modal('show');
    });
      
    
    $('#btn-sync-urbanpiper').click(function() {
        var storeId = $('#cashier').val();
        if(!storeId) {
            Swal.fire({
                icon: 'warning',
                title: 'No Store Selected',
                text: 'Please select a store to sync.',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (!confirm("Are you sure you want to sync this store's catalog to UrbanPiper?")) {
            return;
        }
        
        $('#btn-sync-urbanpiper').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');
        
        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'sync_urbanpiper']); ?>',
            type: 'POST',
            data: { store_id: storeId },
            dataType: 'text',
            success: function(response) {
                $('#btn-sync-urbanpiper').prop('disabled', false).html('Sync to UrbanPiper');
                
                if (response.indexOf('Status: success') !== -1) {
                    Swal.fire({
                        title: 'Success!',
                        text: response,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else if (response.indexOf('response_code') !== -1) {
                    try {
                        var jsonResponse = JSON.parse(response);
                        if (jsonResponse.response_code == 0) {
                            Swal.fire({
                                title: 'Error',
                                text: jsonResponse.response_message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                title: 'Success',
                                text: jsonResponse.response_message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                        }
                    } catch(e) {
                        Swal.fire({
                            title: 'Response',
                            text: response,
                            icon: 'info',
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'API Response',
                        text: response,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                $('#btn-sync-urbanpiper').prop('disabled', false).html('Sync to UrbanPiper');
                
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while syncing. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                
                console.log('Error:', xhr.responseText);
            }
        });
    });      

    $('#btn-sync-reelo').click(function() {
        var storeId = $('#cashier').val();
        if(!storeId) {
            Swal.fire({
                icon: 'warning',
                title: 'No Store Selected',
                text: 'Please select a store to sync.',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (!confirm("Are you sure you want to sync this store's catalog to Reelo?")) {
            return;
        }
        
        $('#btn-sync-reelo').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');
        
        $.ajax({
            url: '<?php echo Router::url(['controller' => 'ReeloApis', 'action' => 'sync_reelo']); ?>',
            type: 'POST',
            data: { store_id: storeId },
            dataType: 'json',
            success: function(response) {
                $('#btn-sync-reelo').prop('disabled', false).html('Sync to Reelo');
                
                if (response.response_code == 1) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.response_message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.response_message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                $('#btn-sync-reelo').prop('disabled', false).html('Sync to Reelo');
                
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while syncing. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                
                console.log('Error:', xhr.responseText);
            }
        });
    });

$(document).ready(function() {
    $(document).on('click', '.toggle-status', function() {
        const id = $(this).data('id');
        const row = $('#row-' + id); 
        const loc_row_id = $(this).data('loc_row_id');
        $.ajax({
            url: '<?= Router::url(["controller" => "Dashboard", "action" => "change_store_product_status"]) ?>',
            type: 'POST',
            data: { product_id: id, loc_row_id: loc_row_id },
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
