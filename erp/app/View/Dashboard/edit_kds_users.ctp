<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit KDS User</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="<?= $this->Html->url(['action' => 'kds_users']) ?>"><button class='btn btn-danger' type="button">Back</button></a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?= $this->Form->create('Kds_user', ['type' => 'file', 'url' => ['controller' => 'Dashboard', 'action' => 'edit_kds_users']]) ?>


                <?= $this->Form->input('id', [
                    'type' => 'hidden',
                    'value' => $userDetail['Kds_user']['id']
                ]) ?>

                <div class="form-group row">
                    <div class="col-sm-8">
                        <?= $this->Form->input('username', [
                            'div'=>['class'=>'form-group'],
                            'label'=>['class'=>'form-label'],
                            'class'=>'form-control',
                            'required'=>true,
                            'value' => $userDetail['Kds_user']['username']
                        ]) ?>

                        <?= $this->Form->input('password', [
                            'div'=>['class'=>'form-group'],
                            'label'=>['class'=>'form-label'],
                            'class'=>'form-control',
                            'required'=>false, // optional password update
                            'placeholder' => '******',
                        ]) ?>

                        <?php
                            foreach ($locations as $location) {
                                $options[$location['Location']['id']] = $location['Location']['name'];
                            }

                            echo $this->Form->input('location', [
                                'div'=>['class'=>'form-group'],
                                'label'=>'Select Location',
                                'class'=>'form-control',
                                'options'=>$options,
                                'id'=>'location',
                                'required'=>true,
                                'selected' => $userDetail['Kds_user']['location_id']
                            ]);

                            $roleOptions = ['Preparation' => 'Preparation', 'Expo' => 'Expo'];
                            echo $this->Form->input('role', [
                                'div'=>['class'=>'form-group'],
                                'label'=>'Select Role',
                                'class'=>'form-control',
                                'options'=>$roleOptions,
                                'id'=>'role',
                                'required'=>true,
                                'selected' => $userDetail['Kds_user']['role']
                            ]);
                        ?>

                        <label><strong>Stations</strong></label>
                        <div id="stationContainer" class="row mb-2">
                            <?php
                                if (!empty($stations)) {
                                    $seenGroups = array();
                                    foreach($stations as $station){
                                        $groupKey = (int)$station['Pos_printer']['kds'];
                                        if (isset($seenGroups[$groupKey])) { continue; }
                                        $seenGroups[$groupKey] = true;
                                        $name = $station['Pos_printer']['name'];
                                        $ipLbl = !empty($station['Pos_printer']['ip']) ? ' ('.$station['Pos_printer']['ip'].')' : '';
                                    ?>
                                    <div class="col-md-3">
                                        <input type="checkbox" name="station_ids[<?=$groupKey?>]" value="<?=$groupKey?>" id="station_<?=$groupKey?>" <?= in_array($groupKey, (array)($selectedStations ?? [])) ? 'checked' : '' ?>>
                                        <label for="station_<?=$groupKey?>"><?=h($name)?><small><?=h($ipLbl)?></small></label>
                                    </div>
                                <?php }
                                } else { ?>
                                    <div class="col-12 text-muted">Please select a location to view stations.</div>
                                <?php } ?>
                        </div>
                    </div>
                </div>

                <a href="<?= $this->Html->url(['action' => 'kds_users']) ?>"><button class='btn btn-danger' type="button">Cancel</button></a>
                <?= $this->Form->button('Update', ['class'=>'btn btn-success', 'type'=>'submit', 'id'=>'submitBtn']); ?>
                <?= $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var selectedStations = <?= json_encode(array_values(array_map('intval', (array)($selectedStations ?? [])))) ?>;

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function loadStations(locationId) {
        var $container = $('#stationContainer');
        if (!locationId) {
            $container.html('<div class="col-12 text-muted">Please select a location to view stations.</div>');
            return;
        }

        $container.html('<div class="col-12 text-muted">Loading stations...</div>');

        $.ajax({
            url: '<?= $this->Html->url(['controller' => 'Dashboard', 'action' => 'get_stations_by_location']) ?>',
            type: 'GET',
            data: { location_id: locationId },
            dataType: 'json',
            success: function(response) {
                $container.empty();
                if (response.status && response.stations && response.stations.length > 0) {
                    $.each(response.stations, function(index, station) {
                        var stId = parseInt(station.id, 10);
                        var isChecked = selectedStations.indexOf(stId) !== -1 ? 'checked' : '';
                        var ipText = station.ip ? ' (' + escapeHtml(station.ip) + ')' : '';
                        var checkboxHtml = '<div class="col-md-3">' +
                            '<input type="checkbox" name="station_ids[' + stId + ']" value="' + stId + '" id="station_' + stId + '" ' + isChecked + '>' +
                            '<label for="station_' + stId + '"> ' + escapeHtml(station.name) + ' <small>' + ipText + '</small></label>' +
                            '</div>';
                        $container.append(checkboxHtml);
                    });
                } else {
                    $container.html('<div class="col-12 text-muted">No stations found for this location.</div>');
                }
            },
            error: function() {
                $container.html('<div class="col-12 text-danger">Failed to load stations. Please try again.</div>');
            }
        });
    }

    $('#location').on('change', function() {
        loadStations($(this).val());
    });

    var initialLoc = $('#location').val();
    if (initialLoc) {
        loadStations(initialLoc);
    } else {
        $('#stationContainer').html('<div class="col-12 text-muted">Please select a location to view stations.</div>');
    }
});
</script>

