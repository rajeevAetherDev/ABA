<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Audit</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box text-right">
            <a href="semi_auditstock">
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
                    <div class="col-sm-3">
                        <?= $this->Form->input('indent_ids', [
                            'label' => 'Audit ID',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['pos_auditstock']['pos_audit_id']
                        ]) ?>
                      
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Damage', [
                            'label' => 'Location Name',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['pos_auditstock']['store_name']
                        ]) ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('total_amount', [
                            'label' => 'Remarks',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['pos_auditstock']['remarks']
                        ]) ?>
                    </div>
                    <div class="col-sm-3">
                        <?= $this->Form->input('Damage', [
                            'label' => 'Audit Created Date',
                            'class' => 'form-control',
                            'readonly' => 'readonly',
                            'value' => $indents[0]['pos_auditstock']['updateon']
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
                <h4 class="page-title mb-3">Audit Items</h4>
                <div class="">
                    <table  id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Unit</th>
                                <th>Opening Stock</th>
                                <th>Physical Stock</th>
                                <th>Variance</th>
                                <th>Audit From</th>
                                <th>Audit To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?= $item['pos_auditstock_items']['sku_name']; ?>
                                    </td>
                                    <td>
                                        <?= $item['pos_auditstock_items']['sku_unit_name']; ?>
                                    </td>
                                    <td>
                                        <?= $item['pos_auditstock_items']['openingstock']; ?>
                                    </td>
                                    <td>
                                        <?= $item['pos_auditstock_items']['physicalstock']; ?>
                                    </td>
                                    <td>
                                        <?= $item['pos_auditstock_items']['variance']; ?>
                                    </td>
                                    <td>
                                        <?= $item['pos_auditstock_items']['auditfrom']; ?>
                                    </td>
                                    <td>
                                        <?= $item['pos_auditstock_items']['auditto']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

