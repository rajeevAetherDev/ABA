<div class="row">
    <div class="col-sm-8">
        <div class="page-title-box">
            <h2 class="page-title">View Offer</h2>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="page-title-box text-right">
            <?php if (!empty($indents[0]['pos_offers']['offer_id'])): ?>
                <a href="<?php echo $this->base; ?>/dashboard/export_offer_details?id=<?php echo $indents[0]['pos_offers']['offer_id']; ?>">
                    <button class="btn btn-success" type="button" style="margin-right: 5px;">Export</button>
                </a>
            <?php endif; ?>
            <a href="offers">
                <button class="btn btn-danger" type="button">Back</button>
            </a>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-4">
                        <?= $this->Form->input('indent_ids', [
                            'label' => 'Offer ID',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['pos_offers']['offer_id']
                        ]) ?>
                      
                    </div><div class="col-sm-4">
                        <?= $this->Form->input('indent_ids', [
                            'label' => 'Name',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['name']
                        ]) ?>
                      
                    </div>
                    <div class="col-sm-4">
                        <?= $this->Form->input('location_name', [
                            'label' => 'Offer Type',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['offer_type']
                        ]) ?>
                    </div>
                    <div class="col-sm-4">
                        <?= '<br>'.$this->Form->input('location_name', [
                            'label' => 'Location Name',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['locations']
                        ]) ?>
                    </div>
                    <div class="col-sm-4">
                        <?= '<br>'.$this->Form->input('total_amount', [
                            'label' => 'Type',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['item_type']
                        ]) ?>
                    </div>
                    
                    <div class="col-sm-4">
                        <?= '<br>'.$this->Form->input('total_amount', [
                            'label' => 'Start Date',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['start_date']
                        ]) ?>
                    </div>
                    
                    <div class="col-sm-4">
                        <?= '<br>'.$this->Form->input('total_amount', [
                            'label' => 'End Date',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['end_date']
                        ]) ?>
                    </div>
                    
                    <div class="col-sm-4">
                        <?= '<br>'.$this->Form->input('total_amount', [
                            'label' => 'Start Time',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['start_time']
                        ]) ?>
                    </div>
                    
                    <div class="col-sm-4">
                        <?= '<br>'.$this->Form->input('total_amount', [
                            'label' => 'End Time',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0][0]['end_time']
                        ]) ?>
                    </div>

                    <?php 
                        $viewDaysRaw = isset($indents[0][0]['days']) && !empty($indents[0][0]['days']) ? $indents[0][0]['days'] : 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday';
                        $viewDaysDisplay = ($viewDaysRaw === 'full_week' || count(explode(',', $viewDaysRaw)) >= 7) ? 'Full Week' : $viewDaysRaw;
                    ?>
                    <div class="col-sm-4">
                        <?= '<br>'.$this->Form->input('applicable_days', [
                            'label' => 'Applicable Days',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $viewDaysDisplay
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title mb-3">Offer Items</h4>
                <?php foreach ($items as $item): ?>
    <div class="form-group row">
        <div class="col-md-4">
            <?= $this->Form->input('sku_name', [
                'label' => 'Item Name',
                'class' => 'form-control',
                'readonly' => true,
                'value' => $item['pos_offers']['sku_name']
            ]) ?>
        </div>

        <div class="col-md-4">
            <?= $this->Form->input('cat_name', [
                'label' => 'Category',
                'class' => 'form-control',
                'readonly' => true,
                'value' => $item['pos_offers']['cat_name']
            ]) ?>
        </div>

        <div class="col-md-4">
            <?= $this->Form->input('subcat_name', [
                'label' => 'Sub Category',
                'class' => 'form-control',
                'readonly' => true,
                'value' => $item['pos_offers']['subcat_name']
            ]) ?>
        </div>
    </div>
<?php endforeach; ?>

            </div>
        </div>
    </div>
</div>
