<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Replace SKU</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'dashboard']); ?>">
                    <button class='btn btn-danger' type="button">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>

<!-- Full-page loader overlay --> 
<div id="replace-loader" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.55);
    z-index: 9999;
    flex-direction: column;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #fff;
        border-radius: 10px;
        padding: 36px 48px;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        min-width: 260px;
    ">
        <div style="margin-bottom: 18px;">
            <!-- Spinning ring -->
            <div style="
                width: 56px; height: 56px;
                border: 6px solid #e0e0e0;
                border-top-color: #28a745;
                border-radius: 50%;
                animation: skuSpin 0.85s linear infinite;
                margin: 0 auto;
            "></div>
        </div>
        <div id="loader-title" style="font-size:16px; font-weight:600; color:#333; margin-bottom:6px;">
            Replacing SKU...
        </div>
        <div id="loader-sub" style="font-size:13px; color:#777;">
            Updating all recipe links. Please wait.
        </div>
    </div>
</div>

<style>
@keyframes skuSpin {
    to { transform: rotate(360deg); }
}
/* Preview AJAX mini-loader */
#preview-ajax-loader {
    display: none;
    padding: 10px 0;
    color: #555;
    font-size: 13px;
}
#preview-ajax-loader span {
    display: inline-block;
    width: 14px; height: 14px;
    border: 2px solid #ccc;
    border-top-color: #007bff;
    border-radius: 50%;
    animation: skuSpin 0.7s linear infinite;
    vertical-align: middle;
    margin-right: 7px;
}
</style>

<?php echo $this->Form->create('Replace', [
    'url' => ['controller' => 'dashboard', 'action' => 'replace_sku'],
    'id'  => 'replace-sku-form'
]); ?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">Select SKUs to Replace in All Recipes</h4>
                <div class="form-group row">
                    <div class="col-md-5">
                        <?php echo $this->Form->input('sku_a', [
                            'div'      => ['class' => 'form-group'],
                            'label'    => 'Select SKU (To Replace)',
                            'class'    => 'form-control select2',
                            'options'  => $skus,
                            'empty'    => '-- Select Sku --',
                            'required' => true,
                            'id'       => 'sku_a_select'
                        ]); ?>
                    </div>
                    <div class="col-md-2" style="text-align:center; margin-top:30px; font-weight:bold; color:#444;">
                        ---- Replace With ---->
                    </div>
                    <div class="col-md-5">
                        <?php echo $this->Form->input('sku_b', [
                            'div'      => ['class' => 'form-group'],
                            'label'    => 'Select SKU (Replacement)',
                            'class'    => 'form-control select2',
                            'options'  => $skus,
                            'empty'    => '-- Select Sku --',
                            'required' => true,
                            'id'       => 'sku_b_select'
                        ]); ?>
                    </div>
                </div>

                <!-- Preview AJAX mini-loader -->
                <div id="preview-ajax-loader" class="row">
                    <div class="col-md-12">
                        <span></span> Loading recipe links...
                    </div>
                </div>

                <!-- Preview Panel -->
                <div id="preview-panel" style="display:none;" class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-info" style="margin-bottom:10px;">
                            <strong>Preview:</strong> The following recipes currently use
                            <strong id="preview-sku-a-name"></strong>
                            and will be updated to use
                            <strong id="preview-sku-b-name"></strong>:
                        </div>
                        <div id="preview-table-wrapper"></div>
                    </div>
                </div>

                <div id="no-links-warning" style="display:none;" class="row mt-2">
                    <div class="col-md-12">
                        <div class="alert alert-warning">
                            <strong>No active recipe links found</strong> for the selected SKU A. Nothing will be updated.
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <?php echo $this->Form->button('Replace SKU', [
                            'class'  => 'btn btn-success',
                            'type'   => 'submit',
                            'id'     => 'submitBtn',
                            'style'  => 'float:right; margin-left:10px;'
                        ]); ?>
                        <a href="<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'replace_sku']); ?>">
                            <button class='btn btn-danger' type="button" style="float:right;">Reset</button>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php echo $this->Form->end(); ?>

<script>
$(document).ready(function () {

    if ($.fn.select2) {
        $('.select2').select2({ placeholder: "Select a SKU", allowClear: true });
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    function showFullLoader(skuAName, skuBName) {
        $('#loader-title').text('Replacing SKU…');
        $('#loader-sub').text(
            '"' + skuAName + '"  →  "' + skuBName + '"  |  Updating all recipe links. Please wait.'
        );
        $('#replace-loader').css('display', 'flex');
    }

    function hideFullLoader() {
        $('#replace-loader').hide();
    }

    // ── Preview AJAX ──────────────────────────────────────────────────────
    $('#sku_a_select, #sku_b_select').on('change', function () {
        loadPreview();
    });

    function loadPreview() {
        var skuAId   = $('#sku_a_select').val();
        var skuAName = $('#sku_a_select option:selected').text();
        var skuBName = $('#sku_b_select option:selected').text();

        $('#preview-panel').hide();
        $('#no-links-warning').hide();

        if (!skuAId) return;

        $('#preview-ajax-loader').show();

        $.ajax({
            url: '<?php echo $this->Html->url(['controller' => 'dashboard', 'action' => 'preview_sku_links']); ?>',
            type: 'POST',
            data: { sku_a_id: skuAId },
            dataType: 'json',
            success: function (response) {
                $('#preview-ajax-loader').hide();

                if (response.count > 0) {
                    $('#preview-sku-a-name').text(skuAName);
                    $('#preview-sku-b-name').text(skuBName || '(select SKU B)');

                    var html = '<table class="table table-bordered table-sm">'
                             + '<thead><tr>'
                             + '<th>#</th><th>Parent Type</th><th>Recipe / Parent Name</th>'
                             + '<th>Qty</th><th>Unit</th>'
                             + '</tr></thead><tbody>';

                    $.each(response.links, function (i, row) {
                        html += '<tr>'
                              + '<td>' + (i + 1) + '</td>'
                              + '<td>' + row.parent_type + '</td>'
                              + '<td>' + row.parent_name + '</td>'
                              + '<td>' + row.quantity + '</td>'
                              + '<td>' + row.component_unit_name + '</td>'
                              + '</tr>';
                    });

                    html += '</tbody></table>';
                    $('#preview-table-wrapper').html(html);
                    $('#preview-panel').show();

                } else {
                    $('#no-links-warning').show();
                }
            },
            error: function () {
                $('#preview-ajax-loader').hide();
                $('#no-links-warning').show();
            }
        });
    }

    // ── Form submit — show full-page loader ───────────────────────────────
    $('#replace-sku-form').on('submit', function (e) {
        var skuA     = $('#sku_a_select').val();
        var skuB     = $('#sku_b_select').val();
        var skuAName = $('#sku_a_select option:selected').text();
        var skuBName = $('#sku_b_select option:selected').text();

        if (skuA && skuB && skuA === skuB) {
            e.preventDefault();
            alert('SKU A and SKU B cannot be the same. Please select different SKUs.');
            return;
        }

        if (skuA && skuB) {
            showFullLoader(skuAName, skuBName);

            // Safety fallback: hide loader after 30s in case of server error
            setTimeout(hideFullLoader, 30000);
        }
    });

    // ── Update preview SKU B name on dropdown change ──────────────────────
    $('#sku_b_select').on('change', function () {
        if ($('#preview-panel').is(':visible')) {
            $('#preview-sku-b-name').text(
                $(this).find('option:selected').text() || '(select SKU B)'
            );
        }
    });
});
</script>