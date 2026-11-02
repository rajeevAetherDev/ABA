<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Subadmin List</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="create_subadmin">
                    <button class='btn btn-success' type="button" style="float:right">Add Subadmin</button>
                </a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php
                        echo $this->Form->create('Search', array('url' => 'subadmin_list'));
                        echo $this->Form->input('startdate', array(
                            'div'      => array('class' => 'form-group'),
                            'class'    => 'form-control calendarmultiple',
                            'between'  => '<span class="desc"></span><div class="controls">',
                            'after'    => '</div>',
                            'label'    => 'Select Date',
                            'id'       => 'startdate',
                            'name'     => 'daterange',
                            'required' => 'required',
                            'value'    => $startdate
                        ));
                        echo $this->Form->button('View', array('class' => 'btn btn-info', 'type' => 'submit'));
                        echo $this->Form->end();
                        ?>
                    </div>
                </div>

                <div style="height:30px; width:100%" class="clearfix"></div>

                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Mobile</th>
                                    <th>Location</th>
                                    <th>Role</th>
                                    <th>Modules</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $x = 1;
                            foreach ($users as $user):
                                $id = $user['users']['id'];

                                // ── Status ──────────────────────────────────────────
                                if ($user['users']['status'] == 1) {
                                    $status      = 'Active';
                                    $buttonClass = 'btn-danger';
                                    $buttonText  = 'Mark&nbsp;Inactive';
                                } else {
                                    $status      = 'Inactive';
                                    $buttonClass = 'btn-success';
                                    $buttonText  = 'Mark Active';
                                }

                                // ── CHANGED: Modules ─────────────────────────────────
                                // SQL already excludes submenu rows via NOT LIKE '%:%'
                                // But as a safety net in the view, we also filter here
                                $moduleNames = array_filter(
                                    explode(',', $user[0]['module'] ?? ''),
                                    function($m) {
                                        $m = trim($m);
                                        // Skip empty and skip any submenu rows (contain ':')
                                        return $m !== '' && strpos($m, ':') === false;
                                    }
                                );
                                // Map module keys to human-readable labels
                                $moduleLabelsArray = array_map(function($module) use ($moduleLabels) {
                                    $module = trim($module);
                                    return isset($moduleLabels[$module]) ? $moduleLabels[$module] : $module;
                                }, $moduleNames);
                                $moduleLabelsString = implode(', ', $moduleLabelsArray);

                                // ── Locations (unchanged) ────────────────────────────
                                $locationss = explode(',', $user[0]['locations'] ?? '');
                                $locationLabelsArray = array_map(function($location) use ($locationLabels) {
                                    return isset($locationLabels[$location]) ? $locationLabels[$location] : $location;
                                }, $locationss);
                                $locationLabelsString = implode(', ', $locationLabelsArray);
                            ?>
                            <tr>
                                <td><?php echo $x; ?></td>
                                <td><?php echo $user['users']['name']; ?></td>
                                <td><?php echo $user['users']['username']; ?></td>
                                <td><?php echo $user['users']['mobile']; ?></td>
                                <td><?php echo $locationLabelsString; ?></td>
                                <td><?php echo $user['users']['role']; ?></td>
                                <td><?php echo $moduleLabelsString; ?></td>
                                <td><?php echo $status; ?></td>
                                <td><?php echo $user['users']['created']; ?></td>
                                <td>
                                    <?php
                                    echo '<a href="edit_subadmin?id=' . $user['users']['id'] . '">
                                            <button class="btn btn-info" type="button">Edit</button>
                                          </a> ';
                                    echo "<button class='btn $buttonClass mark-status' data-id='$id'>$buttonText</button>";
                                    ?>
                                </td>
                            </tr>
                            <?php $x++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$("#exportdata").click(function(){
    var sdate = $("#startdate").val();
    window.open('<?php echo $this->base; ?>/dashboard/cashierlist_export?sdate=' + sdate, '_blank');
});

$(document).ready(function() {
    $(document).on('click', '.mark-status', function() {
        var id     = $(this).data('id');
        var button = $(this);
        $.ajax({
            url: '<?php echo Router::url(['controller' => 'Dashboard', 'action' => 'subadmin_change_status']); ?>',
            type: 'POST',
            data: { user_id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    location.reload();
                } else {
                    alert('Failed to update status');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while updating the status.');
                console.log(xhr.responseText);
            }
        });
    });
});
</script>
