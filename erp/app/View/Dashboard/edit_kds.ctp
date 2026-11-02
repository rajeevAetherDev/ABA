<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit kds</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="<?php echo $this->Html->url(array('action' => 'kds')); ?>"><button class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('User', array('type' => 'file', 'id' => 'editKdsForm')); ?>

                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php
                            echo $this->Form->input('name', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Name',
                                'class' => 'form-control',
                                'id' => 'kdsName',
                                'required' => 'required',
                                'value' => $kdsDetail['Pos_printer']['name']
                            ));
                            $storeOptions = array();
                            foreach ($locations as $location) {
                                $storeOptions[$location['Location']['id']] = $location['Location']['name'];
                            }
                        ?>
                        <label><strong>Stores</strong> <small class="text-muted">(at least 1 store + IP required)</small></label>
                        <div id="storeRows" class="mt-2"></div>
                        <button type="button" id="addStoreBtn" class="btn btn-sm btn-secondary mt-2">+ Add Store</button>
                    </div>
                </div>
                <a href="<?php echo $this->Html->url(array('action' => 'kds')); ?>"><button class='btn btn-danger' type="button">Cancel</button></a>
                <?php echo $this->Form->button('Update', array('class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn')); ?>
                <?php echo $this->Form->end(); ?>

            </div>
        </div>
    </div>
</div>
<style>
    .hidden {
        display: none;
    }
</style>
<script>
$(document).ready(function(){
    var storeIndex = 0;

    var storeOptionsHtml = <?php
        $optionsHtml = '<option value="">Select store</option>';

        foreach ($locations as $location) {
            $id = h($location['Location']['id']);
            $name = h($location['Location']['name']);

            $optionsHtml .= '<option value="' . $id . '">' . $name . '</option>';
        }

        echo json_encode($optionsHtml);
    ?>;

    var initialRows = <?php
        $initList = array();
        if (!empty($kdsRows)) {
            foreach ($kdsRows as $kr) {
                $initList[] = array(
                    'id' => (int)$kr['Pos_printer']['id'],
                    'store_id' => (string)$kr['Pos_printer']['store_id'],
                    'ip' => (string)$kr['Pos_printer']['ip']
                );
            }
        }
        echo json_encode($initList);
    ?>;

    function buildOptions(selectedVal) {
        var html = storeOptionsHtml;

        if (selectedVal !== undefined && selectedVal !== null && selectedVal !== '') {
            html = html.replace(
                'value="' + selectedVal + '"',
                'value="' + selectedVal + '" selected="selected"'
            );
        }

        return html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function selectedStores(exceptRow) {
        var vals = [];
        $('#storeRows .store-row').each(function(){
            if (this === exceptRow) return;
            var v = $(this).find('.store-select').val();
            if (v) vals.push(String(v));
        });
        return vals;
    }

    function refreshStoreOptions() {
        var used = selectedStores(null);
        $('#storeRows .store-row').each(function(){
            var $sel = $(this).find('.store-select');
            var cur = String($sel.val() || '');
            $sel.find('option').each(function(){
                var v = String($(this).attr('value') || '');
                if (v === '' || v === cur) {
                    $(this).prop('disabled', false);
                } else {
                    $(this).prop('disabled', $.inArray(v, used) !== -1);
                }
            });
        });
    }

    function addStoreRow(storeId, ipVal, rowId) {
        storeId = storeId || '';
        ipVal = ipVal || '';
        rowId = rowId || '';

        // If user manually adds a row, check that the last row is filled
        if (!storeId && !ipVal) {
            var lastRow = $('#storeRows .store-row').last();
            if (lastRow.length) {
                var lastStore = lastRow.find('.store-select').val();
                var lastIp = $.trim(lastRow.find('.ip-input').val());
                if (!lastStore || !lastIp) {
                    Swal.fire({ icon: 'warning', title: 'Complete current store', text: 'Please select a store and enter its IP before adding another.' });
                    if (!lastStore) lastRow.find('.store-select').focus();
                    else lastRow.find('.ip-input').focus();
                    return;
                }
            }
        }

        var idx = storeIndex++;
        var ipStyle = storeId ? '' : 'display:none;';
        var ipRequired = storeId ? 'required="required"' : '';
        var hiddenIdHtml = rowId ? '<input type="hidden" name="kds_rows[' + idx + '][id]" value="' + escapeHtml(rowId) + '" />' : '';

        var row = $('<div class="store-row form-group"><div class="row">' +
            '<div class="col-md-5"><label>Store</label>' +
            '<select name="kds_rows[' + idx + '][store_id]" class="form-control store-select" required="required">' + buildOptions(storeId) + '</select></div>' +
            '<div class="col-md-5 ip-wrap" style="' + ipStyle + '"><label>IP</label>' +
            '<input type="text" name="kds_rows[' + idx + '][ip]" class="form-control ip-input" placeholder="Enter IP" value="' + escapeHtml(ipVal) + '" ' + ipRequired + ' />' +
            hiddenIdHtml +
            '</div>' +
            '<div class="col-md-2"><br><button class="btn btn-danger remove-store" type="button" style="float:right;">Remove</button></div>' +
            '</div></div>');

        $('#storeRows').append(row);
        refreshStoreOptions();
    }

    $('#addStoreBtn').click(function(){ addStoreRow(); });

    $(document).on('change', '.store-select', function(){
        var row = $(this).closest('.store-row');
        var val = $(this).val();
        if (val) {
            row.find('.ip-wrap').show();
            row.find('.ip-input').attr('required', 'required');
        } else {
            row.find('.ip-wrap').hide();
            row.find('.ip-input').removeAttr('required').val('');
        }
        refreshStoreOptions();
    });

    $(document).on('click', '.remove-store', function(){
        if ($('#storeRows .store-row').length <= 1) {
            Swal.fire({ icon: 'warning', title: 'Cannot Remove', text: 'At least 1 store is required.' });
            return;
        }
        $(this).closest('.store-row').remove();
        refreshStoreOptions();
    });

    $('#editKdsForm').on('submit', function(e){
        var name = $.trim($('#kdsName').val());
        if (!name) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Name is required.' });
            return false;
        }
        var rows = $('#storeRows .store-row');
        if (!rows.length) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please add at least 1 store with IP.' });
            return false;
        }
        var seen = {};
        var ok = true;
        rows.each(function(){
            var s = $(this).find('.store-select').val();
            var ip = $.trim($(this).find('.ip-input').val());
            if (!s || !ip) { ok = false; return false; }
            if (seen[String(s)]) { ok = false; return false; }
            seen[String(s)] = true;
        });
        if (!ok) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Each row needs a unique store and a non-empty IP.' });
            return false;
        }
    });

    // Populate existing stores or start with one empty row
    if (initialRows && initialRows.length > 0) {
        $.each(initialRows, function(i, rowData) {
            addStoreRow(rowData.store_id, rowData.ip, rowData.id);
        });
    } else {
        addStoreRow();
    }
});
</script>