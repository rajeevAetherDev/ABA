<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit Offer</h2>
        </div>
    </div>

    <div class="col-sm-1">
        <div class="page-title-box">
            <a href="offers" class="btn btn-danger">Back</a>
        </div>
    </div>
</div>

<div class="card">
<div class="card-body">

<!-- ================= BASIC DETAILS ================= -->
<div class="row">
    <div class="col-md-6">
        <?= $this->Form->input('offer_name', [
            'label' => 'Offer Name',
            'class' => 'form-control',
            'id' => 'offer_name',
            'value' => $offer['name']
        ]) ?>
    </div>

    <div class="col-md-6">
        <?= $this->Form->input('offer_type', [
            'label' => 'Offer Type',
            'class' => 'form-control',
            'options' => ['bogo' => 'Bogo', 'b2g1' => 'Buy 2 Get 1'],
            'selected' => $offer['offer_type'],
            'id' => 'offer_type'
        ]) ?>
    </div>
</div>

<br>

<!-- ================= DATE TIME ================= -->
<div class="row">
    <div class="col-md-6">
        <label>Start Date</label>
        <input type="date" id="start_date" class="form-control" value="<?= $startDate ?>">
    </div>

    <div class="col-md-6">
        <label>Start Time</label>
        <input type="time" id="start_time" class="form-control" value="<?= $startTime ?>">
    </div>
</div>

<br>

<div class="row">
    <div class="col-md-6">
        <label>End Date</label>
        <input type="date" id="end_date" class="form-control" value="<?= $endDate ?>">
    </div>

    <div class="col-md-6">
        <label>End Time</label>
        <input type="time" id="end_time" class="form-control" value="<?= $endTime ?>">
    </div>
</div>

<br>

<!-- ================= APPLICABLE DAYS ================= -->
<?php
    $savedDays = isset($offer['days']) && !empty($offer['days']) ? $offer['days'] : 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday';
    $daysArray = ($savedDays === 'full_week') ? ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] : explode(',', $savedDays);
    $shortToFull = ['Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'];
    $daysArray = array_map(function($d) use ($shortToFull) { return isset($shortToFull[trim($d)]) ? $shortToFull[trim($d)] : trim($d); }, $daysArray);
    $isFullWeek = (count($daysArray) >= 7 || $savedDays === 'full_week');
    $dayList = [
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
        'Saturday' => 'Sat',
        'Sunday' => 'Sun'
    ];
?>
<div class="row">
    <div class="col-md-12">
        <label style="font-weight: bold;">Select Applicable Days</label>
        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; background: #f8f9fa; padding: 10px 15px; border-radius: 5px; border: 1px solid #dee2e6;">
            <label style="margin-bottom:0; cursor:pointer; font-weight: 600; color: #007bff;">
                <input type="checkbox" id="select_full_week" <?= $isFullWeek ? 'checked' : '' ?>> <strong>Full Week</strong>
            </label>
            <span style="color:#ccc;">|</span>
            <?php foreach ($dayList as $fullDay => $label): ?>
                <label style="margin-bottom:0; cursor:pointer;">
                    <input type="checkbox" class="day-checkbox" value="<?= $fullDay ?>" <?= in_array($fullDay, $daysArray) ? 'checked' : '' ?>> <?= $label ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<br>

<!-- ================= LOCATION ================= -->
<div class="row">
    <div class="col-md-6">
        <?= $this->Form->input('location', [
            'label'    => 'Select Location',
            'class'    => 'form-control select2',
            'multiple' => 'multiple',
            'options'  => $locations,
            'id'       => 'location',
            'value'    => $selectedLocationIds
        ]) ?>

    </div>

    <!-- ================= APPLY TYPE ================= -->

    <div class="col-md-6">
        <?= $this->Form->input('type', [
            'label' => 'Applied To',
            'class' => 'form-control',
            'options' => ['item' => 'Items', 'category' => 'Category'],
            'selected' => $offer['item_type'],
            'id' => 'type'
        ]) ?>
    </div>
</div>

<br>
<!-- ================= CATEGORY ================= -->
<div class="row" id="category_section" style="display:none;">
    <div class="col-md-6">
        <?= $this->Form->input('category', [
            'label' => 'Select Category',
            'class' => 'form-control',
            'options' => $options,
            'empty' => '- Select Category -',
            'id' => 'select_cat',
            'value' => $selectedCategory
        ]) ?>

    </div>
</div>


<!-- ================= ITEMS ================= -->
<div id="item_section">
<table id="example" class="table table-bordered table-hover">
<thead>
<tr>
    <th>#</th>
    <th>Item Name</th>
    <th>Unit</th>
    <th>
        Map
        <input type="checkbox" id="select_all">
    </th>
</tr>
</thead>
<tbody>

<?php $i = 1; foreach ($users as $u):
    $p = $u['Catalog_product'];
    $checked = in_array($p['id'], $selectedSkuIds);
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= h($p['name']) ?></td>
    <td><?= h($p['unit_name']) ?></td>

    <!-- hidden data -->
    <input type="hidden" class="sku_id" value="<?= $p['id'] ?>">
    <input type="hidden" class="sku_name" value="<?= h($p['name']) ?>">
    <input type="hidden" class="cat_id" value="<?= $p['cat_id'] ?>">
    <input type="hidden" class="cat_name" value="<?= h($p['cat_name']) ?>">
    <input type="hidden" class="subcat_id" value="<?= $p['subcat_id'] ?>">
    <input type="hidden" class="subcat_name" value="<?= h($p['subcat_name']) ?>">

    <td>
        <input type="checkbox" class="checkbox" <?= $checked ? 'checked' : '' ?>>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

<br>

<button id="saveBtn" class="btn btn-success" onclick="updateOffer()" style="float:right;">
    Update Offer
</button>

</div>
</div>

<!-- ================= SCRIPTS ================= -->
<script>
$(document).ready(function () {

    let table = $('#example').DataTable({
        pageLength: 10
    });

    // toggle category / items
    $('#type').change(function () {
        if ($(this).val() === 'category') {
            $('#category_section').show();
            $('#item_section').hide();
        } else {
            $('#category_section').hide();
            $('#item_section').show();
        }
    }).trigger('change');

    // SELECT ALL (ALL PAGES)
    $('#select_all').on('click', function () {
        let rows = table.rows().nodes();
        $('input.checkbox', rows).prop('checked', this.checked);
    });

    // Sync select all
    $('#example').on('change', '.checkbox', function () {
        let rows = table.rows().nodes();
        let total = $('input.checkbox', rows).length;
        let checked = $('input.checkbox:checked', rows).length;
        $('#select_all').prop('checked', total === checked);
    });

    // Full week toggle
    $('#select_full_week').on('change', function () {
        $('.day-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('.day-checkbox').on('change', function () {
        let totalDays = $('.day-checkbox').length;
        let checkedDays = $('.day-checkbox:checked').length;
        $('#select_full_week').prop('checked', totalDays === checkedDays);
    });
});

// ================= SAVE =================
function updateOffer() {

    if ($('#offer_name').val().trim() === '') {
        Swal.fire('Required', 'Offer name is required', 'warning');
        return;
    }
    
    let location = $('#location').val();
    
    if (!location || location.length === 0) {
        Swal.fire('Required', 'Please select at least one location', 'warning');
        return;
    }

    let selectedDays = [];
    $('.day-checkbox:checked').each(function () {
        selectedDays.push($(this).val());
    });

    if (selectedDays.length === 0) {
        Swal.fire('Required', 'Please select at least one day for the offer', 'warning');
        return;
    }

    let daysStr = selectedDays.join(',');

    let table = $('#example').DataTable();
    let rows = table.rows().nodes();
    let checkedData = [];

    if ($('#type').val() === 'item') {

        $(rows).each(function () {
            let row = $(this);
            if (row.find('.checkbox').prop('checked')) {
                checkedData.push({
                    id: row.find('.sku_id').val(),
                    sku_name: row.find('.sku_name').val(),
                    cat_id: row.find('.cat_id').val(),
                    cat_name: row.find('.cat_name').val(),
                    subcat_id: row.find('.subcat_id').val(),
                    subcat_name: row.find('.subcat_name').val()
                });
            }
        });

        if (checkedData.length === 0) {
            Swal.fire('Required', 'Select at least one item', 'warning');
            return;
        }
    }
    
    let type = $('#type').val();
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
    $('#saveBtn').prop('disabled', true).text('Please wait...');

    $.ajax({
        url: "<?= $this->Html->url(['action' => 'edit_offer', '?' => ['id' => $offer['offer_id']]]) ?>",
        type: "POST",
        data: {
            offer_name: $('#offer_name').val(),
            offer_type: $('#offer_type').val(),
            type: $('#type').val(),
            location: location,                    // ✅ ADD THIS
            start_date: $('#start_date').val(),
            start_time: $('#start_time').val(),
            end_date: $('#end_date').val(),
            end_time: $('#end_time').val(),
            days: daysStr,
            category: $('#select_cat').val(),
            checkedData: JSON.stringify(checkedData)
        },
        dataType: 'json',   // 👈 IMPORTANT

        success: function(res) {
            $('#saveBtn').prop('disabled', false).text('Update');

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
            $('#saveBtn').prop('disabled', false).text('Update');

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