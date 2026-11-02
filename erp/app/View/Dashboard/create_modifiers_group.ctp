<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Create Modifier Groups</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="modifiers_group"><button class='btn btn-danger' type="button">Back</button></a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('User', ['type' => 'file']); ?>

                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php 
                            echo $this->Form->input('name', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Title', 
                                'class' => 'form-control',
                                'required' => 'required'
                            ]);

                            echo $this->Form->input('group_type', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Group type', 
                                'class' => 'form-control',
                                'between' => '<span class="desc"></span><div class="controls">', 
                                'after' => '</div>',
                                'options' => ['0' => 'Add-On', '1' => 'Variant']
                            ]);

                            echo $this->Form->input('sort_order', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Sort Order',
                                'value' => '0', 
                                'class' => 'form-control', 
                                'type' => 'number',
                                'between' => '<span class="desc"></span><div class="controls">', 
                                'after' => '</div>'
                            ]);
                        ?>

                        <!-- Min Max container -->
                        <div id="minMaxContainer">
                            <?php
                                echo $this->Form->input('min', [
                                    'div' => ['class' => 'form-group'], 
                                    'label' => 'Min Range',
                                    'value' => '0', 
                                    'class' => 'form-control', 
                                    'type' => 'number',
                                    'between' => '<span class="desc"></span><div class="controls">', 
                                    'after' => '</div>'
                                ]);

                                echo $this->Form->input('max', [
                                    'div' => ['class' => 'form-group'], 
                                    'label' => 'Max Range',
                                    'value' => '12', 
                                    'class' => 'form-control', 
                                    'type' => 'number',
                                    'between' => '<span class="desc"></span><div class="controls">', 
                                    'after' => '</div>'
                                ]);
                            ?>
                        </div>

                        <?php
                            // echo $this->Form->input('is_default', [
                            //     'div' => ['class' => 'form-group'], 
                            //     'label' => 'Is Default', 
                            //     'class' => 'form-control',
                            //     'options' => ['0' => 'No', '1' => 'Yes']
                            // ]);

                            echo $this->Form->input('description', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Description', 
                                'class' => 'form-control',
                                'rows' => 3
                            ]);
                        ?>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-8"><br>
                        <button id="addsku" class='btn btn-info' type="button" style="float:right;">Add Modifier </button>
                    </div>
                    <div class="col-md-8">
                        <div id="skuInputs">
                            <!-- Dynamically added SKU inputs will appear here -->
                        </div>
                    </div>
                </div>

                <a href="modifiers_group"><button class='btn btn-danger' type="button">Cancel</button></a>
                <?php echo $this->Form->button('Save', ['class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn']); ?>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Handle group_type change
    function toggleMinMaxFields() {
        var selectedValue = $('#UserGroupType').val();
        if (selectedValue == '1') { // Variant
            $('#minMaxContainer').hide();
        } else {
            $('#minMaxContainer').show();
        }
    }

    // Initial toggle
    toggleMinMaxFields();

    // On change
    $('#UserGroupType').change(function () {
        toggleMinMaxFields();
    });

    // Add Modifier button
    $('#addsku').click(function () {
        var newInput = $('<div class="form-group"><div class="row">' +
            '<div class="col-md-7"><label>Modifier Name</label>' +
            '<select class="form-control tax modifier-select" required="required" name="modifier_id[]">' +
            '<option value="">Select Modifier</option></select></div>' +
            '<div class="col-md-3"><label>Price</label>' +
            '<input type="number" class="form-control price" name="modifier_price[]" readonly="readonly"></div>' +
            '<div class="col-md-2"><br><button class="btn btn-danger removetax" type="button" style="float:right;">Remove</button></div>' +
            '</div></div>');

        $('#skuInputs').append(newInput);

        // Fetch modifiers
        $.ajax({
            url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_modifiers_options']); ?>',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.response_code === 1) {
                    var select = newInput.find('.modifier-select');
                    $.each(response.data, function (index, mod) {
                        select.append('<option data-price="' + mod.price + '" value="' + mod.id + '">' + mod.name + '</option>');
                    });
                } else {
                    alert('Failed to fetch Modifiers.');
                }
            },
            error: function (xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    // Set price on modifier change
    $(document).on('change', '.modifier-select', function () {
        var selectedPrice = $(this).find('option:selected').data('price');
        $(this).closest('.row').find('.price').val(selectedPrice || 0);
    });

    // Remove modifier
    $(document).on('click', '.removetax', function () {
        $(this).closest('.form-group').remove();
    });
});
</script>
