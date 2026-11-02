<?php
$statusLabel = [
    'NOT_IN_TABLE'  => ['label' => 'Missing',       'class' => 'danger',  'icon' => '✗'],
    'MISSING_ID'    => ['label' => 'No ID',          'class' => 'danger',  'icon' => '✗'],
    'ZERO_PRICE'    => ['label' => 'No Price Set',   'class' => 'warning', 'icon' => '⚠'],
    'NAME_MISMATCH' => ['label' => 'Name Mismatch',  'class' => 'info',    'icon' => '≠'],
];
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'ALL';
$filterType   = isset($_GET['type'])   ? $_GET['type']   : 'ALL';
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Recipe Integrity Check</h1>
    <ol class="breadcrumb">
      <li><a href="product_list">Products</a></li>
      <li class="active">Recipe Integrity</li>
    </ol>
  </section>

  <section class="content">
    <?php echo $this->Flash->render(); ?>

    <!-- Summary Cards -->
    <div class="row" style="margin-bottom:20px">
      <?php
      $cards = [
          ['key'=>'NOT_IN_TABLE', 'label'=>'Missing Items',    'color'=>'#e74c3c', 'icon'=>'fa-times-circle'],
          ['key'=>'MISSING_ID',   'label'=>'No ID Linked',     'color'=>'#c0392b', 'icon'=>'fa-unlink'],
          ['key'=>'ZERO_PRICE',   'label'=>'No Price Set',     'color'=>'#e67e22', 'icon'=>'fa-exclamation-triangle'],
          ['key'=>'NAME_MISMATCH','label'=>'Name Mismatch',    'color'=>'#3498db', 'icon'=>'fa-info-circle'],
      ];
      foreach ($cards as $c):
          $cnt = $summary[$c['key']] ?? 0;
          $active = ($filterStatus === $c['key']) ? 'border:3px solid #333;' : '';
      ?>
      <div class="col-xs-6 col-sm-3">
        <a href="?status=<?= $c['key'] ?>" style="text-decoration:none">
          <div class="small-box" style="background:<?= $c['color'] ?>;color:#fff;<?= $active ?>">
            <div class="inner">
              <h3><?= $cnt ?></h3>
              <p><?= $c['label'] ?></p>
            </div>
            <div class="icon"><i class="fa <?= $c['icon'] ?>"></i></div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Helper: what to fill in when editing a zero-price SKU -->
    <div class="box box-info collapsed-box" id="helpBox">
      <div class="box-header with-border" style="cursor:pointer" onclick=".closest('.box').toggleClass('collapsed-box')">
        <h3 class="box-title"><i class="fa fa-info-circle"></i> How to fix No-Price items</h3>
        <div class="box-tools pull-right"><button type="button" class="btn btn-box-tool"><i class="fa fa-plus"></i></button></div>
      </div>
      <div class="box-body">
        <div class="row">
          <div class="col-sm-6">
            <strong>For SKUs (Edit SKU page):</strong>
            <ol style="padding-left:18px;margin-top:6px">
              <li>Select correct <strong>Purchase Unit</strong> (Kg, Nos, Ltr etc.)</li>
              <li>Set <strong>Purchase Price</strong> (price per purchase unit)</li>
              <li>Select <strong>Consumption Unit</strong> (Gms, Ml etc.)</li>
              <li>Set <strong>Consumption Factor</strong> (e.g. 1000 for Kg→Gms)</li>
              <li><em>Per Unit Price auto-calculates as Price ÷ Factor</em></li>
            </ol>
          </div>
          <div class="col-sm-6">
            <strong>For Semi-Finished (Edit SF + Recipe):</strong>
            <ol style="padding-left:18px;margin-top:6px">
              <li>Set <strong>Consumption Unit</strong> and <strong>Consumption Factor</strong><br><em>(batch yield in grams/ml)</em></li>
              <li>Click <strong>Recipe</strong> button to add SKU ingredients</li>
              <li>Run <strong>Recalculate Food Costs</strong> after all SFs are set</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="box box-default">
      <div class="box-header with-border">
        <h3 class="box-title">
          <?php if ($filterStatus !== 'ALL'): ?>
            Showing: <strong><?= h($filterStatus) ?></strong>
            &nbsp;<a href="?" class="btn btn-xs btn-default">Show All</a>
          <?php else: ?>
            All Issues (<?= count($issues) ?> items)
          <?php endif; ?>
        </h3>
        <div class="box-tools pull-right">
          <input type="text" id="searchBox" class="form-control input-sm" placeholder="Search ingredient or recipe…" style="width:220px;display:inline-block">
          &nbsp;
          <select id="typeFilter" class="form-control input-sm" style="width:120px;display:inline-block">
            <option value="ALL">All Types</option>
            <option value="Sku">SKU</option>
            <option value="Semifinished">Semi-Finished</option>
          </select>
        </div>
      </div>

      <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-condensed" id="issueTable">
          <thead>
            <tr>
              <th width="8%">Status</th>
              <th width="10%">Type</th>
              <th width="22%">Ingredient Name</th>
              <th width="8%">ID</th>
              <th width="17%">Actual Name in DB</th>
              <th width="7%">Price</th>
              <th width="18%">Used In Recipe</th>
              <th width="10%">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $purchaseUnitMap = ['Gms'=>'Kg','Gm'=>'Kg','Kg'=>'Kg','Ml'=>'Ltr','Ltr'=>'Ltr','Nos'=>'Nos','Por'=>'Por','Pkt'=>'Pkt'];
            $convFactorMap   = ['Kg/Gms'=>1000,'Ltr/Ml'=>1000,'Nos/Nos'=>1,'Por/Por'=>1,'Pkt/Pkt'=>1,'Kg/Gm'=>1000];
            foreach ($issues as $row):
                $s = $row['status'] ?? 'OK';
                $sInfo = $statusLabel[$s] ?? ['label'=>$s,'class'=>'default','icon'=>'?'];
                if ($filterStatus !== 'ALL' && $filterStatus !== $s) continue;
                $cType = h($row['component_type'] ?? '');
                $cName = h($row['component_name'] ?? '');
                $cId   = h($row['component_id']   ?? '');
                $actualName  = h($row['actual_name']  ?? '—');
                $actualPrice = isset($row['actual_price']) ? number_format((float)$row['actual_price'], 4) : '—';
                $parentName  = h($row['parent_name']  ?? '');
                $parentType  = h($row['parent_type']  ?? '');
                $parentId    = h($row['parent_id']    ?? '');
                $unitName    = h($row['unit_name']    ?? 'Kg');
            ?>
            <tr class="issue-row" data-type="<?= $cType ?>" data-name="<?= strtolower($cName) ?>" data-parent="<?= strtolower($parentName) ?>">
              <td>
                <span class="label label-<?= $sInfo['class'] ?>">
                  <?= $sInfo['icon'] ?> <?= $sInfo['label'] ?>
                </span>
              </td>
              <td>
                <span class="label <?= $cType === 'Sku' ? 'label-primary' : 'label-success' ?>">
                  <?= $cType ?>
                </span>
              </td>
              <td><strong><?= $cName ?></strong></td>
              <td><?= $cId ?: '<em class="text-muted">none</em>' ?></td>
              <td><?php if ($s === 'NAME_MISMATCH'): ?>
                <span class="text-warning"><i class="fa fa-warning"></i> <?= $actualName ?></span>
              <?php elseif ($s === 'NOT_IN_TABLE' || $s === 'MISSING_ID'): ?>
                <em class="text-danger">Not found</em>
              <?php else: ?>
                <?= $actualName ?>
              <?php endif; ?></td>
              <td><?php if ((float)($row['actual_price'] ?? 0) == 0): ?>
                <span class="text-danger">₹0</span>
              <?php else: ?>
                ₹<?= $actualPrice ?>
              <?php endif; ?></td>
              <td>
                <small>
                  <?= $parentType === 'Product' ? '<i class="fa fa-cube"></i>' : '<i class="fa fa-cogs"></i>' ?>
                  <a href="<?= $parentType === 'Product' ? 'product_recipe?id='.$parentId : 'add_recipe?id='.$parentId ?>">
                    <?= $parentName ?>
                  </a>
                </small>
              </td>
              <td>
                <?php if ($s === 'NOT_IN_TABLE' || $s === 'MISSING_ID'): ?>
                  <!-- Create missing item -->
                  <?php
                    $uomKey  = $cType.'_'.$cId.'_'.($row['component_name'] ?? '');
                    $majUom  = !empty($majorityUom[$uomKey]) ? $majorityUom[$uomKey] : 'Gms';
                    $inferP  = $purchaseUnitMap[$majUom] ?? 'Kg';
                    $inferCf = $convFactorMap[$inferP.'/'.$majUom] ?? 1000;
                  ?>
                  <button class="btn btn-xs btn-danger btn-create"
                    data-name="<?= $cName ?>"
                    data-type="<?= $cType ?>"
                    data-unit="<?= h($inferP) ?>"
                    data-cons-unit="<?= h($majUom) ?>"
                    data-cons-factor="<?= $inferCf ?>"
                    data-toggle="modal" data-target="#createModal">
                    <i class="fa fa-plus"></i> Create (<?= h($inferP) ?>/<?= h($majUom) ?>)
                  </button>
                <?php elseif ($s === 'ZERO_PRICE'): ?>
                  <?php if ($cType === 'Sku'): ?>
                    <a href="edit_sku?id=<?= $cId ?>"
                       class="btn btn-xs btn-warning">
                      <i class="fa fa-pencil"></i> Edit SKU
                    </a>
                  <?php else: ?>
                    <div>
                      <a href="edit_semifinished?id=<?= $cId ?>"
                         class="btn btn-xs btn-warning" style="margin-bottom:3px">
                        <i class="fa fa-pencil"></i> Edit SF
                      </a>
                      <a href="add_recipe?id=<?= $cId ?>"
                         class="btn btn-xs btn-info" title="Create/view recipe for this semi-finished item">
                        <i class="fa fa-list-ul"></i> Recipe
                      </a>
                    </div>
                  <?php endif; ?>
                <?php elseif ($s === 'NAME_MISMATCH'): ?>
                  <span class="text-muted small">ID ok, name differs</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (empty($issues)): ?>
          <div class="text-center" style="padding:40px">
            <i class="fa fa-check-circle fa-3x text-success"></i>
            <p class="text-success" style="margin-top:10px;font-size:1.2em">All recipe ingredients look good!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<!-- Create Missing Item Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-plus"></i> Create Missing Item</h4>
      </div>
      <?php echo $this->Form->create(false, ['url' => ['action' => 'create_missing_ingredient'], 'method' => 'POST']); ?>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="fa fa-info-circle"></i>
          This will create a placeholder item with price ₹0. You must set the price afterwards in the SKU or Semi-Finished master.
        </div>
        <input type="hidden" name="type" id="modal_type">
        <div class="form-group">
          <label>Item Name</label>
          <input type="text" name="name" id="modal_name" class="form-control" readonly>
        </div>
        <div class="form-group">
          <label>Item Type</label>
          <input type="text" id="modal_type_display" class="form-control" readonly>
        </div>
        <div class="row">
          <div class="col-sm-4">
            <div class="form-group">
              <label>Purchase Unit</label>
              <input type="text" name="unit_name" id="modal_unit" class="form-control" value="Kg">
            </div>
          </div>
          <div class="col-sm-4">
            <div class="form-group">
              <label>Consumption Unit</label>
              <input type="text" name="cons_unit" class="form-control" value="Gms">
            </div>
          </div>
          <div class="col-sm-4">
            <div class="form-group">
              <label>Conversion Factor</label>
              <input type="number" name="cons_factor" class="form-control" value="1000" min="1">
            </div>
          </div>
        </div>
        <p class="text-muted small">
          Example: Purchase=Kg, Consumption=Gms, Factor=1000 means 1 Kg = 1000 Gms.
          After creating, set the price in the master list.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fa fa-plus"></i> Create Item</button>
      </div>
      <?php echo $this->Form->end(); ?>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
    // Populate modal from button
    $(document).on('click', '.btn-create', function(){
        $('#modal_name').val($(this).data('name'));
        $('#modal_type').val($(this).data('type'));
        $('#modal_type_display').val($(this).data('type'));
        $('#modal_unit').val($(this).data('unit') || 'Kg');
        $('[name=cons_unit]').val($(this).data('cons-unit') || 'Gms');
        $('[name=cons_factor]').val($(this).data('cons-factor') || 1000);
    });

    // Search filter
    $('#searchBox').on('keyup', function(){
        var q = $(this).val().toLowerCase();
        filterRows();
    });
    $('#typeFilter').on('change', filterRows);

    function filterRows(){
        var q    = $('#searchBox').val().toLowerCase();
        var type = $('#typeFilter').val();
        $('#issueTable tbody tr').each(function(){
            var name   = $(this).data('name')   || '';
            var parent = $(this).data('parent') || '';
            var rtype  = $(this).data('type')   || '';
            var matchQ = !q || name.indexOf(q) >= 0 || parent.indexOf(q) >= 0;
            var matchT = type === 'ALL' || rtype === type;
            $(this).toggle(matchQ && matchT);
        });
    }
});
</script>
