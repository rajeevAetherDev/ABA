<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit Modifier</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <a href="modifiers">
                <button class="btn btn-danger" type="button">Back</button>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?= $this->Form->create('Catalog_modifier', ['type' => 'file']) ?>

                <?= $this->Form->input('id', ['type' => 'hidden', 'value' => $modifier['Catalog_modifier']['id']]) ?>

                <div class="form-group row">
                    <div class="col-sm-8">
                        <?= $this->Form->input('name', [
                            'label' => 'Name',
                            'class' => 'form-control',
                            'required' => true,
                            'value' => $modifier['Catalog_modifier']['name']
                        ]) ?>

                        <?= '<br>'.$this->Form->input('description', [
                            'label' => 'Description',
                            'class' => 'form-control',
                            'rows' => 3,
                            'value' => $modifier['Catalog_modifier']['description']
                        ]) ?>

                        <?= '<br>'.$this->Form->input('food_type', [
                            'label' => 'Food Type',
                            'class' => 'form-control',
                            'options' => ['0' => 'Veg', '1' => 'Non-Veg'],
                            'value' => $modifier['Catalog_modifier']['food_type']
                        ]) ?>

                        <?= '<br>'.$this->Form->input('sort_order', [
                            'label' => 'Sort Order',
                            'class' => 'form-control',
                            'type' => 'number',
                            'value' => $modifier['Catalog_modifier']['sort_order'] ?? 0
                        ]) ?>

                        <?= '<br>'.$this->Form->input('price', [
                            'label' => 'Default Sales Price',
                            'class' => 'form-control',
                            'type' => 'number',
                            'step' => '0.01',
                            'required' => true,
                            'value' => $modifier['Catalog_modifier']['price']
                        ]) ?>

                        <?= '<br>'.$this->Form->input('weight', [
                            'label' => 'Weight',
                            'class' => 'form-control',
                            'value' => $modifier['Catalog_modifier']['weight']
                        ]) ?>

                        <?= '<br>'.$this->Form->input('status', [
                            'label' => 'Status',
                            'class' => 'form-control',
                            'options' => ['1' => 'Active', '0' => 'Inactive'],
                            'value' => $modifier['Catalog_modifier']['status']
                        ]) ?>
                    </div>
                </div>

                <a href="modifiers">
                    <button class="btn btn-danger" type="button">Cancel</button>
                </a>
                <?= $this->Form->button('Update', ['class' => 'btn btn-success', 'type' => 'submit']) ?>

                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
