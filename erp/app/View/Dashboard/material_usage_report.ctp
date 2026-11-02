<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Material Usage &amp; Cost Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <!-- Filters -->
                <div class="form-group row">
                    <div class="col-sm-10">
                        <?php echo $this->Form->create('Search', ['url' => 'material_usage_report']); ?>
                        <div class="row">
                            <div class="col-sm-4">
                                <label class="form-label">Select Date Range</label>
                                <input name="daterange" class="form-control calendarmultiple"
                                       id="startdate" required="required"
                                       value="<?php echo h($startdate); ?>" type="text"/>
                            </div>
                            
                            <!-- Filters -->
                            <div class="col-sm-3">
                                <label class="form-label">Select Store</label>
                                <select name="data[location_id]" class="form-control">
                                    <?php if ($this->Session->read('Auth.User.role') === 'admin'): ?>
                                        <option value="0">All Stores</option>
                                    <?php endif; ?>
                                    <?php foreach ($locations as $loc):
                                        $lid   = $loc['locations']['id']   ?? $loc[0]['id'];
                                        $lname = $loc['locations']['name'] ?? $loc[0]['name'];
                                    ?>
                                    <option value="<?php echo intval($lid); ?>"
                                        <?php echo ($location_id == $lid) ? 'selected' : ''; ?>>
                                        <?php echo h($lname); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-sm-3" style="padding-top:26px;">
                                <?php echo $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit']); ?>
                                &nbsp;
                                <button type="button" class="btn btn-success" id="exportBtn">
                                    Export CSV
                                </button>
                            </div>
                        </div>
                        <?php echo $this->Form->end(); ?>
                    </div>
                </div>

                <div style="height:20px;" class="clearfix"></div>

                <?php if (!empty($reportRows)): ?>
                <!-- Summary cards -->
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-sm-3">
                        <div class="card" style="background:#f7f9fc;border:1px solid #dee2e6;">
                            <div class="card-body text-center" style="padding:12px;">
                                <h5 style="margin:0;color:#555;">Total SKUs</h5>
                                <h3 style="margin:4px 0 0;color:#333;"><?php echo count($reportRows); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="card" style="background:#f7f9fc;border:1px solid #dee2e6;">
                            <div class="card-body text-center" style="padding:12px;">
                                <h5 style="margin:0;color:#555;">Total Ideal Cost</h5>
                                <h3 style="margin:4px 0 0;color:#28a745;">
                                    &#x20B9;<?php echo number_format($totalCost, 2); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="card" style="background:#f7f9fc;border:1px solid #dee2e6;">
                            <div class="card-body text-center" style="padding:12px;">
                                <h5 style="margin:0;color:#555;">SKUs with Damage</h5>
                                <h3 style="margin:4px 0 0;color:#dc3545;">
                                    <?php //echo count(array_filter($reportRows, fn($r) => $r['damage'] > 0)); 
                                        echo count(array_filter($reportRows, function($r) { return $r['damage'] > 0; })); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="card" style="background:#f7f9fc;border:1px solid #dee2e6;">
                            <div class="card-body text-center" style="padding:12px;">
                                <h5 style="margin:0;color:#555;">SKUs with NC</h5>
                                <h3 style="margin:4px 0 0;color:#fd7e14;">
                                    <?php 
                                    // echo count(array_filter($reportRows, fn($r) => $r['nc_consumption'] > 0)); 
                                    echo count(array_filter($reportRows, function($r) { return $r['nc_consumption'] > 0; }));
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Report Table -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <!-- Report Table -->
                            <table id="materialUsageTable"
                                   class="display table table-hover table-condensed table-bordered"
                                   cellspacing="0" width="100%" style="font-size:13px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>SKU Name</th>
                                        <th>Category</th>
                                        <th>Sub Category</th>
                                        <th>Tax Slabs (Total Tax %)</th>
                                        <th>Tax Value</th>
                                        <th>UOM</th>
                                        <th style="text-align:right;">Avg Purchase Price (&#x20B9;)</th>
                                        <th style="text-align:right;">Consumption via Billed Items</th>
                                        <th style="text-align:right;">Consumption via NC</th>
                                        <th style="text-align:right;">Damage</th>
                                        <th style="text-align:right;">Total Ideal Consumption</th>
                                        <th style="text-align:right;">Total Ideal Cost (&#x20B9;)</th>
                                    </tr>
                                </thead>
                            
                                <tbody>
                                <?php
                                if (empty($reportRows)) {
                                    echo '<tr><td colspan="13" class="text-center" style="padding:30px;color:#888;">
                                            No data found for the selected date range.
                                          </td></tr>';
                                } else {
                                    $sr = 1;
                                    $totalBilled = 0; $totalNC = 0; $totalDamage = 0;
                                    $totalConsumption = 0; $grandCost = 0;
                                    foreach ($reportRows as $row):
                                        $totalBilled      += $row['billed_consumption'];
                                        $totalNC          += $row['nc_consumption'];
                                        $totalDamage      += $row['damage'];
                                        $totalConsumption += $row['total_consumption'];
                                        $grandCost        += $row['total_cost'];
                                ?>
                                <tr>
                                    <td><?php echo $sr++; ?></td>
                                    <td><?php echo h($row['sku_name']); ?></td>
                                    <td><?php echo h($row['cat_name']); ?></td>
                                    <td><?php echo h($row['subcat_name']); ?></td>
                                    <!-- Tax Slabs -->
                                    <td><?php
                                        if (!empty($row['tax_slabs'])) {
                                            echo h($row['tax_slabs'] . ' (' . $row['total_tax'] . '%)');
                                        } else {
                                            echo '';  // no tax rows at all
                                        }
                                    ?></td>
                                    
                                    <!-- Tax Value -->
                                    <td style="text-align:right;">
                                        <?php
                                        // null  = no tax slabs at all → blank
                                        // 0     = tax slabs exist but 0% → show 0
                                        // 6.50  = normal tax → show value
                                        echo ($row['tax_value'] !== null) ? number_format($row['tax_value'], 2) : '';
                                        ?>
                                    </td>
                                    <td><?php echo h($row['unit_name']); ?></td>
                                    <td style="text-align:right;"><?php echo number_format($row['avg_price'], 2); ?></td>
                                    <td style="text-align:right;"><?php echo number_format($row['billed_consumption'], 4); ?></td>
                                    <td style="text-align:right;<?php echo $row['nc_consumption'] > 0 ? 'color:#fd7e14;font-weight:600;' : ''; ?>">
                                        <?php echo number_format($row['nc_consumption'], 4); ?>
                                    </td>
                                    <td style="text-align:right;<?php echo $row['damage'] > 0 ? 'color:#dc3545;font-weight:600;' : ''; ?>">
                                        <?php echo number_format($row['damage'], 4); ?>
                                    </td>
                                    <td style="text-align:right;font-weight:600;"><?php echo number_format($row['total_consumption'], 4); ?></td>
                                    <td style="text-align:right;font-weight:600;"><?php echo number_format($row['total_cost'], 2); ?></td>
                                </tr>
                                <?php endforeach; } ?>
                                </tbody>
                            
                                <!-- ✅ Totals in tfoot — completely separate from tbody so DataTables ignores it -->
                                <tfoot>
                                    <tr style="background:#f0f4f8;font-weight:700;border-top:2px solid #333;">
                                        <td colspan="8" style="text-align:right;">TOTAL</td>
                                        <td style="text-align:right;"><?php echo !empty($reportRows) ? number_format($totalBilled, 4) : ''; ?></td>
                                        <td style="text-align:right;"><?php echo !empty($reportRows) ? number_format($totalNC, 4) : ''; ?></td>
                                        <td style="text-align:right;"><?php echo !empty($reportRows) ? number_format($totalDamage, 4) : ''; ?></td>
                                        <td style="text-align:right;"><?php echo !empty($reportRows) ? number_format($totalConsumption, 4) : ''; ?></td>
                                        <td style="text-align:right;">&#x20B9;<?php echo !empty($reportRows) ? number_format($grandCost, 2) : ''; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- /card-body -->
        </div><!-- /card -->
    </div>
</div>

<script>
// Export: pure vanilla JS - no jQuery dependency
document.getElementById('exportBtn').addEventListener('click', function() {
    var dateVal = document.getElementById('startdate').value;
    var locSel  = document.querySelector('select[name="data[location_id]"]');
    var locVal  = locSel ? locSel.value : 0;
    if (!dateVal) { alert('Please select a date range first.'); return; }
    window.location = 'material_usage_report_export?daterange=' +
        encodeURIComponent(dateVal) + '&location_id=' + encodeURIComponent(locVal);
});
window.addEventListener('load', function() {
    if (typeof $.fn.dataTable === 'undefined') {
        setTimeout(arguments.callee, 500);
        return;
    }

    if (!document.getElementById('materialUsageTable')) return;

    if ($.fn.DataTable.isDataTable('#materialUsageTable')) {
        $('#materialUsageTable').DataTable().destroy();
    }

    $('#materialUsageTable').DataTable({
        paging:     true,
        ordering:   true,
        info:       true,
        searching:  true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order:      [[1, 'asc']],
        columnDefs: [
            { targets: 0,  orderable: false },
            { targets: [5,7,8,9,10,11,12], className: 'dt-right', type: 'num' }
        ],
        dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-2"<"col-sm-5"i><"col-sm-7"p>>',
        language: {
            search:           '',
            searchPlaceholder:'Search SKU, Category...',
            lengthMenu:       'Show _MENU_ entries',
            info:             'Showing _START_ to _END_ of _TOTAL_ SKUs',
            infoFiltered:     '(filtered from _MAX_ total)',
            zeroRecords:      'No matching SKUs found',
            paginate: { first:'«', last:'»', next:'›', previous:'‹' }
        }
    });

    $('#materialUsageTable_filter input')
        .addClass('form-control form-control-sm')
        .css({ width: '250px', display: 'inline-block' });

    $('#materialUsageTable_length select')
        .addClass('form-control form-control-sm')
        .css('width', 'auto');
});
</script>