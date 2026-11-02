<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Unit Mapping</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="unit_mapping"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('User'); ?>
                                   
                                    <div class="form-group row">
                                        
                                        <div class="col-sm-4">
                                            <?php 
                                            
                                                $unitoptions = [];
                                                foreach ($units as $unit) {
                                                    $unitoptions[$unit['Catalog_unit']['id']] = $unit['Catalog_unit']['name'];
                                                }
                                                
                                                echo '<br>' . $this->Form->input('unit', [
                                                    'label' => 'From Unit (Purchase / Production)',
                                                    'placeholder' => 'Choose unit',
                                                    'class' => 'form-control',
                                                    'options' => $unitoptions,
                                                    'empty' => '- Select Unit -',
                                                    'id' => 'select_unit',
                                                    'required' => true
                                                ]);
                                            ?>
                                        </div>
                                        <div class="col-sm-4">
                                            <?php 
                                            
                                                $unitoptions = [];
                                                foreach ($units as $unit) {
                                                    $unitoptions[$unit['Catalog_unit']['id']] = $unit['Catalog_unit']['name'];
                                                }
                                                
                                                echo '<br>' . $this->Form->input('con_unit', [
                                                    'label' => 'To Unit (Consumption)',
                                                    'placeholder' => 'Choose unit',
                                                    'class' => 'form-control',
                                                    'options' => $unitoptions,
                                                    'empty' => '- Select Unit -',
                                                    'id' => 'select_conunit',
                                                    'required' => true
                                                ]);
                                            ?>
                                        </div>
                                        <div class="col-sm-4">
                                            <?= '<br>'.$this->Form->input('factor', ['label' => 'Conversion Factor', 'placeholder' => 'Conversion Factor', 'value' => '0', 'id' => 'factor', 'type' => 'number', 'required' => true, 'class' => 'form-control']); ?>
                                        </div>
                                    </div>
                                    <a href="unit_mapping"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>