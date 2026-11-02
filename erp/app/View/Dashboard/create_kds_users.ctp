<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create kds</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="kds_users"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('User',['type' => 'file']); ?>
                                   
                                    <div class="form-group row">
                                        
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('username', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','required'=>'required'));
                                                echo $this->Form->input('password', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','required'=>'required'));
                                                
                                                $options=[''=>'Select location'];
                                                foreach($locations as $location){
                                                    $options[$location['Location']['id']] = $location['Location']['name'];
                                                }
                                                echo $this->Form->input('location', array('div'=>array('class'=>'form-group'), 'label'=>'Select Location', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'location','required'=>true));
                                                $roleoptions=['Preparation'=>'Preparation','Expo'=>'Expo'];
                                                echo $this->Form->input('role', array('div'=>array('class'=>'form-group'), 'label'=>'Select Role', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$roleoptions,'id'=>'role','required'=>true));
                                                ?>
                                                <label><strong>Stations</strong></label>
                                                <div id="stationContainer" class="row mb-2">
                                                    <div class="col-12 text-muted">Please select a location to view stations.</div>
                                                </div>
                                              
                                        </div>
                                    </div>
                                    <a href="kds_users"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                    <?php echo $this->Form->end(); ?>
                                </div>
                            </div>
                        </div> 
                    </div>

<script>
$(document).ready(function() {
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
                        var ipText = station.ip ? ' (' + escapeHtml(station.ip) + ')' : '';
                        var checkboxHtml = '<div class="col-md-3">' +
                            '<input type="checkbox" name="station_ids[' + station.id + ']" value="' + station.id + '" id="station_' + station.id + '">' +
                            '<label for="station_' + station.id + '"> ' + escapeHtml(station.name) + ' <small>' + ipText + '</small></label>' +
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