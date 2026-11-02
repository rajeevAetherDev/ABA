<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Modifier Groups</h2>
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
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php 
                            $group_type_val = $products[0]['catalog_modifier_group']['group_type'];
                            $group_type = ($group_type_val == 0) ? 'Add-On' : 'Variant';
                            $default = ($products[0]['catalog_modifier_group']['is_default'] == 0) ? 'No' : 'Yes';

                            echo $this->Form->input('name', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Name',
                                'class' => 'form-control',
                                'value' => $products[0]['catalog_modifier_group']['name'],
                                'readonly' => 'readonly'
                            ]);

                            echo $this->Form->input('group_type', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Group Type',
                                'class' => 'form-control',
                                'value' => $group_type,
                                'readonly' => 'readonly'
                            ]);

                            echo $this->Form->input('description', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Description',
                                'class' => 'form-control',
                                'value' => $products[0]['catalog_modifier_group']['description'],
                                'rows' => 3,
                                'readonly' => 'readonly'
                            ]);

                            echo $this->Form->input('sort_order', [
                                'div' => ['class' => 'form-group'], 
                                'label' => 'Sort Order',
                                'class' => 'form-control',
                                'value' => $products[0]['catalog_modifier_group']['sort_order'],
                                'readonly' => 'readonly'
                            ]);

                            // Conditionally show Min/Max Range only for Add-On
                            if ($group_type_val == 0) {
                                echo $this->Form->input('min', [
                                    'div' => ['class' => 'form-group'], 
                                    'label' => 'Min Range',
                                    'value' => $products[0]['catalog_modifier_group']['min'],
                                    'class' => 'form-control', 
                                    'type' => 'number',
                                    'readonly' => 'readonly'
                                ]);

                                echo $this->Form->input('max', [
                                    'div' => ['class' => 'form-group'], 
                                    'label' => 'Max Range',
                                    'value' => $products[0]['catalog_modifier_group']['max'],
                                    'class' => 'form-control', 
                                    'type' => 'number',
                                    'readonly' => 'readonly'
                                ]);
                            }

                            // echo $this->Form->input('is_default', [
                            //     'div' => ['class' => 'form-group'], 
                            //     'label' => 'Is Default',
                            //     'class' => 'form-control',
                            //     'value' => $default,
                            //     'readonly' => 'readonly'
                            // ]);
                        ?>

                        <?php foreach($modifier_linking as $modifier): ?>
                            <div class="form-group">
                                <label>Modifier</label>
                                <input type="text" class="form-control" value="<?= h($modifier['inventory_linking']['component_name']) ?>" readonly>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
