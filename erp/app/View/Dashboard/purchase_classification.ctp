<div class="row"><div class="col-sm-12"><div class="page-title-box">
    <h2 class="page-title">Purchase Classification (CAPEX / OPEX)</h2>
    <p class="text-muted">
        Classify each SKU as <strong>CAPEX</strong> (capital expenditure) or
        <strong>OPEX</strong> (operating expenditure).
        <span class="text-info ml-2">
            <i class="fa fa-info-circle"></i>
            Auto suggestions are based on SKU name/category keywords and can always be edited manually.
        </span>
    </p>
</div></div></div>

<div class="row"><div class="col-sm-12"><div class="card"><div class="card-body">

    <?php if($this->Session->check('Message.flash')): ?>
        <?php echo $this->Session->flash(); ?>
    <?php endif; ?>

    <!-- ══ AUTO-CLASSIFY PANEL ══════════════════════════════════════════════ -->
    <div class="card border mb-4" id="autoClassifyPanel">
        <div class="card-header bg-light">
            <strong><i class="fa fa-magic"></i> Smart Auto-Classification</strong>
            <span class="text-muted ml-2 small">
                — classifies only <em>UNCLASSIFIED</em> rows using restaurant-domain keyword matching.
                Never overrides CAPEX/OPEX values you have already set.
            </span>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button id="btnPreview" class="btn btn-outline-info btn-sm">
                    <i class="fa fa-search"></i> Preview Auto-Classify
                </button>
                <button id="btnApply" class="btn btn-warning btn-sm">
                    <i class="fa fa-bolt"></i> Apply Auto-Classify
                </button>
                <button id="btnReset" class="btn btn-outline-secondary btn-sm d-none">
                    <i class="fa fa-times"></i> Clear Preview
                </button>
            </div>

            <!-- Preview results area -->
            <div id="autoClassifyResult" class="d-none">
                <div id="acCounts" class="mb-3"></div>
                <ul class="nav nav-tabs" id="acTabs">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#acCapex">CAPEX <span class="badge badge-primary" id="tabCapexCount">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#acOpex">OPEX <span class="badge badge-warning" id="tabOpexCount">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#acUncl">Still Unclassified <span class="badge badge-secondary" id="tabUnclCount">0</span></a></li>
                </ul>
                <div class="tab-content border border-top-0 p-2" id="acTabContent">
                    <div class="tab-pane fade show active" id="acCapex">
                        <p class="text-muted small mt-1 mb-1">Up to 25 samples shown.</p>
                        <div id="acCapexList"></div>
                    </div>
                    <div class="tab-pane fade" id="acOpex">
                        <p class="text-muted small mt-1 mb-1">Up to 25 samples shown.</p>
                        <div id="acOpexList"></div>
                    </div>
                    <div class="tab-pane fade" id="acUncl">
                        <p class="text-muted small mt-1 mb-1">Up to 25 samples shown. These will not be updated.</p>
                        <div id="acUnclList"></div>
                    </div>
                </div>
            </div>

            <!-- Apply result area -->
            <div id="autoApplyResult" class="d-none alert alert-success mt-2"></div>
        </div>
    </div>
    <!-- ══ END AUTO-CLASSIFY PANEL ══════════════════════════════════════════ -->

    <h5 class="mt-0">Bulk Classify by Category</h5>
    <?php echo $this->Form->create(false, ['url'=>'purchase_classification','class'=>'form-inline mb-3']); ?>
        <?php echo $this->Form->input('bulk_cat_id', ['div'=>false,'label'=>false,'class'=>'form-control mr-2','empty'=>'Select Category','options'=>$categories,'id'=>'bulk_cat']); ?>
		<?php echo $this->Form->input('bulk_expense_type', ['div'=>false,'label'=>false,'class'=>'form-control mr-2','empty'=>'Select a type','options'=>['CAPEX'=>'CAPEX','OPEX'=>'OPEX','UNCLASSIFIED'=>'Unclassified'],'id'=>'bulk_type','required'=>true]); ?>
        <?php echo $this->Form->button('Apply Bulk', ['class'=>'btn btn-warning','type'=>'submit']); ?>
    <?php echo $this->Form->end(); ?>

    <hr>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">SKU Classification</h5>
        <div class="d-flex gap-2">
            <input type="text" id="skuSearch" class="form-control form-control-sm" placeholder="Search SKU..." style="width:200px">
            <select id="filterType" class="form-control form-control-sm" style="width:160px">
                <option value="">All types</option>
                <option value="UNCLASSIFIED">Show Unclassified only</option>
                <option value="CAPEX">Show CAPEX only</option>
                <option value="OPEX">Show OPEX only</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-condensed" id="skuTable">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Current Type</th>
                    <th>Set Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($skus as $sku): ?>
                <?php $et = $sku['Inventory_sku']['expense_type'] ?: 'UNCLASSIFIED'; ?>
                <tr data-expense-type="<?= h($et) ?>">
                    <td><?= h($sku['Inventory_sku']['name']) ?></td>
                    <td><?= h($sku['Inventory_sku']['cat_name']) ?></td>
                    <td class="type-badge-cell">
                        <span class="badge badge-<?= $et==='CAPEX'?'primary':($et==='OPEX'?'warning':'secondary') ?>">
                            <?= h($et) ?>
                        </span>
                    </td>
                    <td>
                        <?php echo $this->Form->create(false, ['url'=>'purchase_classification','style'=>'display:inline','class'=>'sku-save-form']); ?>
                        <?php echo $this->Form->hidden('sku_id', ['value'=>$sku['Inventory_sku']['id']]); ?>
                       
						<?php echo $this->Form->select('expense_type',
							['CAPEX'=>'CAPEX','OPEX'=>'OPEX','UNCLASSIFIED'=>'Unclassified'],
							[
								'class' => 'form-control form-control-sm d-inline w-auto',
								'value' => (in_array($et, ['CAPEX','OPEX','UNCLASSIFIED']) ? $et : 'UNCLASSIFIED'),
								'empty' => false
							]
						); ?>
                        <?php echo $this->Form->button('Save', ['class'=>'btn btn-sm btn-primary','type'=>'submit']); ?>
                        <?php echo $this->Form->end(); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small">
        <i class="fa fa-lock"></i>
        Values saved via this table are <strong>user-set</strong> and will <strong>not</strong> be overridden by auto-classify.
    </p>

</div></div></div></div>

<script>
(function() {
    // ── Search + filter ────────────────────────────────────────────────────────
    function applyFilters() {
        var search  = $("#skuSearch").val().toLowerCase();
        var typeVal = $("#filterType").val();
        $("#skuTable tbody tr").each(function() {
            var textMatch = !search || $(this).text().toLowerCase().indexOf(search) > -1;
            var typeMatch = !typeVal || $(this).data("expense-type") === typeVal;
            $(this).toggle(textMatch && typeMatch);
        });
    }
    $("#skuSearch").on("keyup", applyFilters);
    $("#filterType").on("change", applyFilters);

    // ── Auto-classify helpers ──────────────────────────────────────────────────
    var autoClassifyBaseUrl = "<?php echo $this->Html->url(['controller'=>'Dashboard','action'=>'auto_classify_skus']); ?>";

    function renderSampleList(samples, containerId) {
        if (!samples || samples.length === 0) {
            $("#" + containerId).html('<em class="text-muted">None in this bucket.</em>');
            return;
        }
        var rows = samples.map(function(s) {
            return '<span class="badge badge-light border mr-1 mb-1" title="ID: ' + s.id + '">'
                 + $('<div/>').text(s.name).html()
                 + ' <small class="text-muted">(' + $('<div/>').text(s.cat || '').html() + ')</small>'
                 + '</span>';
        });
        $("#" + containerId).html(rows.join(''));
    }

    // ── Preview ────────────────────────────────────────────────────────────────
    $("#btnPreview").on("click", function() {
        var $btn = $(this).prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Previewing…');
        $("#autoClassifyResult").addClass("d-none");
        $("#autoApplyResult").addClass("d-none");
        $.getJSON(autoClassifyBaseUrl + "?dry_run=1", function(res) {
            if (!res.status) { alert(res.message || "Error"); return; }
            var c = res.counts;
            $("#acCounts").html(
                '<div class="alert alert-info mb-2 py-2">'
                + '<strong>Total UNCLASSIFIED:</strong> ' + c.total_unclassified
                + ' &nbsp;|&nbsp; <strong class="text-primary">→ CAPEX:</strong> ' + c.would_set_capex
                + ' &nbsp;|&nbsp; <strong class="text-warning">→ OPEX:</strong> ' + c.would_set_opex
                + ' &nbsp;|&nbsp; <strong class="text-secondary">Still unclear:</strong> ' + c.still_unclassified
                + '</div>'
            );
            $("#tabCapexCount").text(c.would_set_capex);
            $("#tabOpexCount").text(c.would_set_opex);
            $("#tabUnclCount").text(c.still_unclassified);
            renderSampleList(res.samples.CAPEX,        "acCapexList");
            renderSampleList(res.samples.OPEX,         "acOpexList");
            renderSampleList(res.samples.UNCLASSIFIED, "acUnclList");
            $("#autoClassifyResult").removeClass("d-none");
            $("#btnReset").removeClass("d-none");
        }).fail(function() {
            alert("Failed to fetch preview. Check server logs.");
        }).always(function() {
            $btn.prop("disabled", false).html('<i class="fa fa-search"></i> Preview Auto-Classify');
        });
    });

    // ── Apply ──────────────────────────────────────────────────────────────────
    $("#btnApply").on("click", function() {
        if (!confirm(
            "This will auto-classify all UNCLASSIFIED SKUs.\n" +
            "CAPEX and OPEX already set manually will NOT be changed.\n\n" +
            "Proceed?"
        )) return;

        var $btn = $(this).prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Applying…');
        $("#autoApplyResult").addClass("d-none");

        $.getJSON(autoClassifyBaseUrl + "?dry_run=0", function(res) {
            if (!res.status) { alert(res.message || "Error"); return; }
            var html = '<strong><i class="fa fa-check-circle"></i> Done!</strong> '
                     + res.changed + ' SKU(s) classified. '
                     + res.still_unclassified + ' still unclassified after auto-run.<br>'
                     + '<em class="small">' + (res.note || '') + '</em>';
            if (res.examples && res.examples.length) {
                html += '<br><small>Examples: ' + res.examples.map(function(e) {
                    return '<strong>' + $('<div/>').text(e.name).html() + '</strong> → ' + e.set_to;
                }).join(', ') + '</small>';
            }
            $("#autoApplyResult").html(html).removeClass("d-none");
            // Reload page after short delay so badges refresh
            setTimeout(function() { location.reload(); }, 2500);
        }).fail(function() {
            alert("Failed to apply. Check server logs.");
        }).always(function() {
            $btn.prop("disabled", false).html('<i class="fa fa-bolt"></i> Apply Auto-Classify');
        });
    });

    // ── Reset preview ──────────────────────────────────────────────────────────
    $("#btnReset").on("click", function() {
        $("#autoClassifyResult").addClass("d-none");
        $(this).addClass("d-none");
    });
})();
</script>
