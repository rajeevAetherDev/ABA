<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Assign Categories</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"> 
                <a href="store_category?location_id=<?php echo $locationId; ?>">
                    <button class="btn btn-danger" type="button">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <!-- Store Details -->
                <div class="form-group row">
                    <div class="col-sm-12">
                        <?php 
                            echo $this->Form->input('store_id', [
                                'type'=>'hidden', 
                                'value'=> $locationId
                            ]);
                            echo $this->Form->input('store_name', [
                                'div'=>['class'=>'form-group'], 
                                'label'=>'Store Name', 
                                'class'=>'form-control',
                                'readonly'=>'readonly',
                                'value'=> $locationname
                            ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Save & Cancel -->
 
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">                
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <table id="example1" class="display table table-hover table-condensed"  cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="checkAll"></th>
                                        <th><?php echo 'Category ID'; ?></th>
                                        <th><?php echo 'Category Name'; ?></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php 
                                    foreach($allCategories as $aCategories): 
                                        $category_id_ = $aCategories['Catalog_category']['id'];
                                        $category_name_ = $aCategories['Catalog_category']['name'];
                                        
                                    ?>
                                    <tr id="row-<?php echo $category_id_; ?>">
                                        <td><input type="checkbox" class="row-checkbox" data-id="<?php echo $category_id_; ?>"></td>
                                        <td><?php echo $category_id_; ?></td>
                                        <td><?php echo htmlspecialchars($category_name_); ?></td>
                                    </tr>
                                    <?php endforeach; ?>            
                                </tbody>
                            </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <?php echo $this->Form->button('Save', ['class'=>'btn btn-success', 'type'=>'button','style'=>'float:right', 'id'=>'submitBtn']); ?>

                        <a href="store_category">
                            <button class='btn btn-danger' type="button" style="float:right; margin-right:10px;">Cancel</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<br>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

$(document).ready(function(){

    var table = $('#example1').DataTable({
        destroy: true,
        pageLength: 10,
        order: [],
        initComplete: function () {
            $('#example1 thead').on('change', '#checkAll', function () {
                var isChecked = this.checked;
                var rows = table.rows({ 'search': 'applied' }).nodes();
                $('input[type="checkbox"].row-checkbox', rows).each(function() {
                    var id = $(this).data('id');
                    $(this).prop('checked', isChecked);
                    if(isChecked){
                        selectedIds[id] = true;
                    } else {
                        delete selectedIds[id];
                    }
                });
            });
        }
    });

    var selectedIds = {};

    $('#example1 tbody').on('change', 'input.row-checkbox', function(){
        var id = $(this).data('id');
        if(this.checked){
            selectedIds[id] = true;
        } else {
            delete selectedIds[id];
            var el = $('#checkAll').get(0);
            if(el && el.checked){
                el.indeterminate = true;
            }
        }
    });

    table.on('draw', function() {
        $('#example1 tbody input.row-checkbox').each(function() {
            var id = $(this).data('id');
            $(this).prop('checked', selectedIds[id] === true);
        });
    });

    $('#submitBtn').on('click', function() {
        var ids = Object.keys(selectedIds);
        if(ids.length === 0){
            alert("No rows selected.");
            return;
        }
        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'assign_categories_to_store']) ?>',
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({ 
                category_ids: ids, 
                store_id: <?php echo $locationId; ?>,
                store_name: "<?php echo addslashes($locationname); ?>"
            }),
            success: function(response) {
                if (response && response.status) {
                    window.location.href = '<?php echo $this->base; ?>/dashboard/store_category?location_id=' + encodeURIComponent(<?php echo $locationId; ?>);
                } else {
                    alert("An error occurred while saving categories.");
                }
            },
            error: function() {
                alert("An error occurred while saving categories.");
            }
        });
    });
});
</script>
