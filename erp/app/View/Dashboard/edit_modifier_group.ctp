<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit Modifier Group</h2>
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
                <?= $this->Form->create('Catalog_modifier_group') ?>

                <div class="form-group row">
                    <div class="col-sm-8">
                        <?= $this->Form->input('id', ['type' => 'hidden', 'value' => $products[0]['Catalog_modifier_group']['id']]) ?>

                        <?= $this->Form->input('name', [
                            'label' => 'Group Name',
                            'class' => 'form-control',
                            'value' => $products[0]['Catalog_modifier_group']['name'],
                            'required' => true
                        ]) ?>

                        <?= '<br>'.$this->Form->input('group_type', [
                            'label' => 'Group Type',
                            'class' => 'form-control',
                            'options' => ['0' => 'Add-On', '1' => 'Variant'],
                            'default' => $products[0]['Catalog_modifier_group']['group_type'],
                            'id' => 'group_type_selector' // fixed ID
                        ]) ?>

                        <?= '<br>'.$this->Form->input('description', [
                            'label' => 'Description',
                            'class' => 'form-control',
                            'value' => $products[0]['Catalog_modifier_group']['description'],
                            'type' => 'textarea',
                            'rows' => 3
                        ]) ?>

                        <?= '<br>'.$this->Form->input('sort_order', [
                            'label' => 'Sort Order',
                            'class' => 'form-control',
                            'type' => 'number',
                            'value' => $products[0]['Catalog_modifier_group']['sort_order']
                        ]) ?>

                        <!-- Min/Max shown only for Add-On -->
                        <div id="minMaxContainer">
                            <?= '<br>'.$this->Form->input('min', [
                                'label' => 'Min Range',
                                'class' => 'form-control',
                                'type' => 'number',
                                'value' => $products[0]['Catalog_modifier_group']['min']
                            ]) ?>

                            <?= '<br>'.$this->Form->input('max', [
                                'label' => 'Max Range',
                                'class' => 'form-control',
                                'type' => 'number',
                                'value' => $products[0]['Catalog_modifier_group']['max']
                            ]) ?>
                        </div>

                        
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-8"><br>
                        <!-- Existing Modifiers -->
                        <label class="mt-3">Modifiers</label>
                        <div id="modifierList">
                            <?php foreach ($modifier_linking as $modifier): ?>
                                <div class="form-group row existing-mod">
                                    <div class="col-md-8">
                                        <input type="hidden" name="modifier_id[]" value="<?= h($modifier['Inventory_linking']['component_id']) ?>">
                                        <input type="text" class="form-control" value="<?= h($modifier['Inventory_linking']['component_name']) ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-existing">Remove</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Add Modifier Button -->
                        <button type="button" class="btn btn-info mt-3" id="addModifierBtn" style="float:right;">+ Add Modifier</button>
                    </div>

                    <div class="col-md-8">
                        <div id="modifierInputs"></div>
                        <div class="mt-4">
                            <a href="modifiers_group"><button class="btn btn-danger ml-2" type="button">Cancel</button></a>
                            <?= $this->Form->button('Save', ['class' => 'btn btn-success', 'type' => 'submit']) ?>
                        </div>
                    </div>
                </div>

                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
$(document).ready(function () {
    // Show/hide min-max based on group_type value
    function toggleMinMaxFields() {
        const groupType = $('#group_type_selector').val();
        if (groupType === '1') {
            $('#minMaxContainer').hide(); // Variant: hide
        } else {
            $('#minMaxContainer').show(); // Add-On: show
        }
    }

    toggleMinMaxFields(); // On page load

    $('#group_type_selector').on('change', function () {
        toggleMinMaxFields(); // On select change
    });

    // Add modifier dynamically
    $('#addModifierBtn').click(function () {
        var newRow = $('<div class="form-group row">' +
            '<div class="col-md-8">' +
            '<select class="form-control" name="modifier_id[]">' +
            '<option value="">Select Modifier</option>' +
            '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
            '<button class="btn btn-danger remove-dynamic" type="button">Remove</button>' +
            '</div>' +
            '</div>');

        $('#modifierInputs').append(newRow);

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'dashboard', 'action' => 'fetch_modifiers_options']) ?>',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.response_code === 1) {
                    var select = newRow.find('select');
                    $.each(response.data, function (index, mod) {
                        select.append('<option value="' + mod.id + '">' + mod.name + '</option>');
                    });
                }
            }
        });
    });

    // Remove modifiers
    $(document).on('click', '.remove-existing', function () {
        $(this).closest('.existing-mod').remove();
    });

    $(document).on('click', '.remove-dynamic', function () {
        $(this).closest('.form-group').remove();
    });
});
</script>
