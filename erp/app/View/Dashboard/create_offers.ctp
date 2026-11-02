<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Create Offer</h2>
        </div>
    </div>

    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="offers"><button class='btn btn-danger' type="button">Back</button></a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="col-lg-12">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <?= $this->Form->input('offer_name', [
                                    'label' => 'Name',
                                    'class' => 'form-control',
                                    'id' => 'offer_name'
                                ]); ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo $this->Form->input('offer_type', [
                                    'label' => 'Offer Type',
                                    'class' => 'form-control',
                                    'options' => ['bogo' => 'Bogo','b2g1' => 'Buy 2 get 1'],
                                    'id' => 'offer_type'
                                ]);
                                ?>
                            </div>
                        </div>
                        <!-- Start/End Dates -->
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <br><label>Start Date</label>
                                <input type="date" id="start_date" class="form-control"
                                       value="<?= $startDate ?>"
                                       min="<?= $startDate ?>" required>
                            </div>
                        
                            <div class="col-sm-6">
                                <br><label>Start Time</label>
                                <input type="time" id="start_time" class="form-control"
                                       value="<?= $startTime ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <label>End Date</label>
                                <input type="date" id="end_date" class="form-control"
                                       value="<?= $endDate ?>"
                                       min="<?= $startDate ?>" required>
                            </div>
                        
                            <div class="col-sm-6">
                                <label>End Time</label>
                                <input type="time" id="end_time" class="form-control"
                                       value="<?= $endTime ?>" required>
                            </div>
                        </div>
                        
                        <!-- Applicable Days -->
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <label style="font-weight: bold;">Select Applicable Days</label>
                                <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; background: #f8f9fa; padding: 10px 15px; border-radius: 5px; border: 1px solid #dee2e6;">
                                    <label style="margin-bottom:0; cursor:pointer; font-weight: 600; color: #007bff;">
                                        <input type="checkbox" id="select_full_week" checked> <strong>Full Week</strong>
                                    </label>
                                    <span style="color:#ccc;">|</span>
                                    <label style="margin-bottom:0; cursor:pointer;"><input type="checkbox" class="day-checkbox" value="Monday" checked> Mon</label>
                                    <label style="margin-bottom:0; cursor:pointer;"><input type="checkbox" class="day-checkbox" value="Tuesday" checked> Tue</label>
                                    <label style="margin-bottom:0; cursor:pointer;"><input type="checkbox" class="day-checkbox" value="Wednesday" checked> Wed</label>
                                    <label style="margin-bottom:0; cursor:pointer;"><input type="checkbox" class="day-checkbox" value="Thursday" checked> Thu</label>
                                    <label style="margin-bottom:0; cursor:pointer;"><input type="checkbox" class="day-checkbox" value="Friday" checked> Fri</label>
                                    <label style="margin-bottom:0; cursor:pointer;"><input type="checkbox" class="day-checkbox" value="Saturday" checked> Sat</label>
                                    <label style="margin-bottom:0; cursor:pointer;"><input type="checkbox" class="day-checkbox" value="Sunday" checked> Sun</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Offer Type & Applied To -->
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                    $locoptions=[''=>'Select location'];
                                    foreach($locations as $location){
                                    $locoptions[$location['Location']['id']] = $location['Location']['name'];
                                    }
                                    echo $this->Form->input('location', array('div'=>array('class'=>'form-group'), 'label'=>'Select Location','multiple' => 'multiple', 'class'=>'form-control select2', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$locoptions,'id'=>'location','required'=>true));
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?= $this->Form->input('type', [
                                    'label' => 'Applied To',
                                    'class' => 'form-control',
                                    'options' => ['item' => 'Items', 'category' => 'Category'],
                                    'id' => 'type'
                                ]); ?>
                            </div>
                        </div>
                        
                        <!-- CATEGORY DROPDOWN (HIDDEN BY DEFAULT) -->
                        <div class="row" id="category_section" style="display:none;">
                            <div class="col-md-12">
                                <?= '<br>'.$this->Form->input('category', [
                                    'label' => 'Select Category',
                                    'placeholder' => 'Choose category',
                                    'class' => 'form-control',
                                    'options' => $options,
                                    'empty' => '- Select Category -',
                                    'id' => 'select_cat'
                                ]); ?>
                            </div>
                        </div>
                        
                        <br>
                        
                        <!-- ITEM TABLE (VISIBLE BY DEFAULT) -->
                        <div id="item_section">
                            <br>
                            <table id="example" class="display table table-hover table-condensed" width="100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Unit Name</th>
                                        <th>Map <input type="checkbox" id="select_all"></th>
                                    </tr>
                                </thead>
                        
                                <tbody>
                                    <?php $x=1; foreach($users as $u): $p=$u['catalog_product']; ?>
                                    <tr>
                                        <td><?= $x ?></td>
                                        <td><?= h($p['name']) ?></td>
                                        <td><?= h($p['unit_name']) ?></td>
                        
                                        <input type="hidden" class="sku_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" class="sku_name" value="<?= h($p['name']) ?>">
                                        <input type="hidden" class="cat_id" value="<?= h($p['cat_id']) ?>">
                                        <input type="hidden" class="cat_name" value="<?= h($p['cat_name']) ?>">
                                        <input type="hidden" class="subcat_id" value="<?= h($p['subcat_id']) ?>">
                                        <input type="hidden" class="subcat_name" value="<?= h($p['subcat_name']) ?>">
                        
                                        <td><input type="checkbox" class="checkbox"></td>
                                    </tr>
                                    <?php $x++; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button id="saveBtn" onclick="getCheckedCheckboxes()" class="btn btn-success" style="float:right;">Save</button>
                
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    // Toggle Category / Items sections
    $("#type").on("change", function () {
        let selected = $(this).val();

        if (selected === "category") {
            $("#category_section").show();
            $("#item_section").hide();
        } else {
            $("#category_section").hide();
            $("#item_section").show();
        }
    });

    // Full week toggle
    $("#select_full_week").on("change", function () {
        $(".day-checkbox").prop("checked", $(this).prop("checked"));
    });

    $(".day-checkbox").on("change", function () {
        let totalDays = $(".day-checkbox").length;
        let checkedDays = $(".day-checkbox:checked").length;
        $("#select_full_week").prop("checked", totalDays === checkedDays);
    });

});

// ✅ Select All
$(document).ready(function() {

    $('#select_all').on('click', function() {
        let checked = $(this).prop('checked');
        let rows = $("#example").dataTable().fnGetNodes();

        for (let i = 0; i < rows.length; i++) {
            $(rows[i]).find('.checkbox').prop("checked", checked);
        }
    });

});

// ✅ Save Selected
function getCheckedCheckboxes() {
    
    // Swal.fire({
    //     icon: 'warning',
    //     title: 'Not Available',
    //     text: 'This function is Currently not available'
    // });
    // return false;
    
    let offerName = $("#offer_name").val().trim();
    if (offerName === "") {
        Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please enter Offer Name'
        });
        $("#offer_name").focus();
        return false;
    }
    
    let location = $("#location").val();
    
    if (!location || location.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please Select Location'
        });
        return false;
    }

    let selectedDays = [];
    $(".day-checkbox:checked").each(function () {
        selectedDays.push($(this).val());
    });

    if (selectedDays.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please select at least one day for the offer'
        });
        return false;
    }

    let daysStr = selectedDays.join(',');
    
    let type = $("#type").val();

    // ✅ If category selected → category required
    if (type === "category") {
        let category = $("#select_cat").val();

        if (!category || category.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Required',
                text: 'Please select at least one category'
            });
            return false;
        }
    }

    // ✅ If item selected → at least one item must be checked
    if (type === "item") {
        let rows = $("#example").dataTable().fnGetNodes();
        let itemSelected = false;

        for (let i = 0; i < rows.length; i++) {
            if ($(rows[i]).find('.checkbox').prop("checked")) {
                itemSelected = true;
                break;
            }
        }

        if (!itemSelected) {
            Swal.fire({
                icon: 'warning',
                title: 'Required',
                text: 'Please select at least one item from the table'
            });
            return false;
        }
    }
    
    // Disable button & change text
    const btn = document.getElementById("saveBtn");
    btn.disabled = true;
    btn.innerText = "Please wait...";

    let rows = $("#example").dataTable().fnGetNodes();
    let checkedData = [];

    for (let i = 0; i < rows.length; i++) {
        let row = $(rows[i]);

        if (row.find('.checkbox').prop("checked")) {

            checkedData.push({
                id: row.find('.sku_id').val(),
                sku_name: row.find('.sku_name').val(),
                cat_id: row.find('.cat_id').val(),
                cat_name: row.find('.cat_name').val(),
                subcat_id: row.find('.subcat_id').val(),
                subcat_name: row.find('.subcat_name').val(),
            });
        }
    }

    $.ajax({
        url: '<?= $this->Html->url(['controller'=>'dashboard','action'=>'create_offers']); ?>',
        type: 'POST',
        data: {
            offer_name: $("#offer_name").val(),
            offer_type: $("#offer_type").val(),
            type: $("#type").val(),
            start_date: $("#start_date").val(),
            start_time: $("#start_time").val(),
            end_date: $("#end_date").val(),
            end_time: $("#end_time").val(),
            days: daysStr,
            location: location,                 // ✅ ADD THIS
            category: $("#select_cat").val(),
            checkedData: JSON.stringify(checkedData)
        },
        dataType: 'json',   // 👈 IMPORTANT

        success: function(res) {
            btn.disabled = false;
            btn.innerText = "Save";
            
            if (res.status === true) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.msg
                }).then(() => {
                    window.location.href = 'offers';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.msg   // 👈 FALSE MESSAGE SHOWN HERE
                });
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerText = "Save";
    
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please try again.'
            });
        }
    });

}
</script>


<script>
    
    jQuery(document).ready(function($) {
                        if ($.fn.select2) {
                            $('.select2').select2({
                                placeholder: "Select locations",
                                allowClear: true,
                                width: '100%'
                            });
                        } else {
                            console.error("Select2 library not loaded!");
                        }
                    });
    
</script>           