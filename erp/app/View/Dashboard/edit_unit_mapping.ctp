<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Update Mapping</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="unit_mapping">
                    <button class="btn btn-danger" type="button">Back</button>
                </a>
            </h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?php
                // Opening form for Catalog_unit_mapping model
                // Pass the 'id' as query param for the action URL so the controller knows which to edit
               echo $this->Form->create('User', [
                    'url' => [
                        'controller' => 'dashboard',
                        'action' => 'edit_unit_mapping',
                        '?' => ['id' => $products[0]['Catalog_unit_mapping']['id']]
                    ]
                ]);

                ?>

                <div class="form-group row">

                    <div class="col-sm-4">
                        <?php
                        // Prepare options for unit select dropdown
                        $unitoptions = [];
                        foreach ($units as $unit) {
                            $unitoptions[$unit['Catalog_unit']['id']] = $unit['Catalog_unit']['name'];
                        }
                        echo $this->Form->input('unit', [
                            'label' => 'From Unit (Purchase / Production)',
                            'type' => 'select',
                            'options' => $unitoptions,
                            'default' => $products[0]['Catalog_unit_mapping']['unit_id'],
                            'id' => 'select_unit',
                            'class' => 'form-control',
                            'required' => true
                        ]);
                        ?>
                    </div>

                    <div class="col-sm-4">
                        <?php
                        // Prepare options for consumption unit select dropdown
                        $conunitoptions = [];
                        foreach ($units as $unit) {
                            $conunitoptions[$unit['Catalog_unit']['id']] = $unit['Catalog_unit']['name'];
                        }
                        echo $this->Form->input('con_unit', [
                            'label' => 'To Unit (Consumption)',
                            'type' => 'select',
                            'options' => $conunitoptions,
                            'default' => $products[0]['Catalog_unit_mapping']['mapped_unit_id'],
                            'id' => 'select_conunit',
                            'class' => 'form-control',
                            'required' => true
                        ]);
                        ?>
                    </div>

                    <div class="col-sm-4">
                        <?php
                        echo $this->Form->input('factor', [
                            'label' => 'Conversion Factor',
                            'type' => 'number',
                            'placeholder' => 'Conversion Factor',
                            'value' => $products[0]['Catalog_unit_mapping']['conversion_factor'] ?? '0',
                            'id' => 'factor',
                            'required' => true,
                            'class' => 'form-control',
                            'step' => 'any', // allows decimals
                            'min' => '0'
                        ]);
                        ?>
                    </div>

                </div>

                <a href="unit_mapping">
                    <button class="btn btn-danger" type="button">Cancel</button>
                </a>
                <?php echo $this->Form->button('Save', ['class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn']); ?>

                <?php echo $this->Form->end(); ?>

            </div>
        </div>
    </div>
</div>