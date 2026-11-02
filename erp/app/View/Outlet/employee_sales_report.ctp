<style>
.table thead tr th {
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    vertical-align: middle !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.4) !important;
    border-bottom: 2px solid rgba(255,255,255,0.2) !important;
}
.table thead tr th.th-left { text-align: left !important; }
.table thead tr th.th-center { text-align: center !important; }
.table thead tr th.th-right { text-align: right !important; }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h2 class="page-title" style="margin:0;">Employee / Cashier Report</h2>
            <span class="badge badge-info" style="font-size:13px; padding:6px 12px; background-color:#17a2b8; color:#fff;">
                <i class="fa fa-users"></i> Cashier DSR
            </span>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 15px;">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <!-- ── Filter Form ──────────────────────────────────────────────────────── -->
                <div class="form-group row">
                    <div class="col-sm-12">
                        <?php 
                        echo $this->Form->create('Search', array('url' => array('controller' => 'outlet', 'action' => 'employee_sales_report'), 'class' => 'form-inline')); 
                        
                        $options = array();
                        if (!empty($users)) {
                            foreach($users as $user) {
                                if (isset($user['Location']['id']) && isset($user['Location']['name'])) {
                                    $options[$user['Location']['id']] = $user['Location']['name'];
                                }
                            }
                        }
                        if (empty($options)) {
                            $options[$selectedStoreId] = $storeName;
                        }
                        
                        echo '<div class="form-group" style="margin-right:15px; margin-bottom:10px;">';
                        echo '<label style="margin-right:8px; font-weight:600;">Store: </label>';
                        echo $this->Form->input('cashier', array(
                            'div' => false, 
                            'label' => false, 
                            'class' => 'form-control', 
                            'options' => $options,
                            'id' => 'cashier',
                            'value' => $selectedStoreId,
                            'style' => 'min-width:200px;'
                        ));
                        echo '</div>';

                        echo '<div class="form-group" style="margin-right:15px; margin-bottom:10px;">';
                        echo '<label style="margin-right:8px; font-weight:600;">Select Date: </label>';
                        echo $this->Form->input('startdate', array(
                            'div' => false, 
                            'label' => false, 
                            'class' => 'form-control calendarmultiple', 
                            'id' => 'startdate',
                            'name' => 'daterange',
                            'required' => 'required',
                            'value' => $daterangeValue,
                            'style' => 'min-width:220px;'
                        )); 
                        echo '</div>';

                        echo '<div class="form-group" style="margin-bottom:10px;">';
                        echo $this->Form->button('<i class="fa fa-search"></i> View Report', array('class' => 'btn btn-info', 'type' => 'submit', 'escape' => false, 'style' => 'margin-right:6px;'));
                        echo $this->Form->button('<i class="fa fa-file-excel-o"></i> Excel', array('class' => 'btn btn-success', 'type' => 'button', 'id' => 'exportdata', 'escape' => false, 'style' => 'margin-right:6px;'));
                        echo $this->Form->button('<i class="fa fa-print"></i> Print PDF', array('class' => 'btn btn-primary', 'type' => 'button', 'id' => 'btn-pdf', 'escape' => false));
                        echo '</div>';

                        echo $this->Form->end(); 
                        ?>
                    </div>
                </div>

                <?php echo $this->Form->create('Report', array('id' => 'pdfform', 'url' => array('controller' => 'outlet', 'action' => 'employee_sales_report_pdf'), 'target' => '_blank')); ?>
                <input type="hidden" name="cashier" id="pdf_cashier" value="<?php echo h($selectedStoreId); ?>">
                <input type="hidden" name="daterange" id="pdf_daterange" value="<?php echo h($daterangeValue); ?>">
                <?php echo $this->Form->end(); ?>

                <hr style="margin-top:10px; margin-bottom:20px;">

                <?php if (empty($isSearched)): ?>
                    <div class="alert alert-info text-center" style="margin-top:20px; padding:20px; font-size:15px;">
                        <i class="fa fa-info-circle" style="font-size:20px; margin-right:8px;"></i> 
                        Please select date and click <strong>View Report</strong> to load the report data.
                    </div>
                <?php else: ?>
                    <!-- ── KPI Summary Cards ──────────────────────────────────────────────── -->
                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-md-3 col-sm-6">
                            <div style="background:#eef2ff; border-left:4px solid #4f46e5; border-radius:6px; padding:15px;">
                                <span style="font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase;">Total Collection</span>
                                <h3 style="margin:5px 0 0 0; color:#1e1b4b; font-weight:700;">₹<?php echo number_format($grandTotals['net_payable'] ?? 0, 2); ?></h3>
                                <small style="color:#4f46e5;"><i class="fa fa-building"></i> Store: <?php echo h($storeName); ?></small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div style="background:#f0fdf4; border-left:4px solid #16a34a; border-radius:6px; padding:15px;">
                                <span style="font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase;">Total Bills</span>
                                <h3 style="margin:5px 0 0 0; color:#064e3b; font-weight:700;"><?php echo number_format($grandTotals['bills_count'] ?? 0); ?></h3>
                                <small style="color:#16a34a;"><i class="fa fa-shopping-cart"></i> Items Sold: <?php echo number_format($grandTotals['qty_sold'] ?? 0, 2); ?></small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div style="background:#fff7ed; border-left:4px solid #ea580c; border-radius:6px; padding:15px;">
                                <span style="font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase;">Total Discounts</span>
                                <h3 style="margin:5px 0 0 0; color:#7c2d12; font-weight:700;">₹<?php echo number_format($grandTotals['discount'] ?? 0, 2); ?></h3>
                                <small style="color:#ea580c;"><i class="fa fa-percent"></i> Net Sales: ₹<?php echo number_format($grandTotals['net_sales'] ?? 0, 2); ?></small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div style="background:#faf5ff; border-left:4px solid #9333ea; border-radius:6px; padding:15px;">
                                <span style="font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase;">Active Employees</span>
                                <h3 style="margin:5px 0 0 0; color:#581c87; font-weight:700;"><?php echo count($cashierSalesList); ?></h3>
                                <small style="color:#9333ea;"><i class="fa fa-calendar"></i> Period: <?php echo h($daterangeValue); ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- ── Table 1: Cashier-wise DSR & Financial Aggregation ────────────── -->
                    <h4 style="margin-top:20px; margin-bottom:15px; font-weight:700; color:#333;">
                        <i class="fa fa-list-alt" style="color:#17a2b8;"></i> 1. Cashier Sales & Financial Summary
                    </h4>
                    <div class="table-responsive">
                        <table id="cashierTable" class="table table-bordered table-striped table-hover" style="font-size:13px;">
                            <thead style="background:#2c3e50;">
                                <tr>
                                    <th class="th-center" style="color:#ffffff !important;">#</th>
                                    <th class="th-left" style="color:#ffffff !important;">Employee / Cashier</th>
                                    <th class="th-center" style="color:#ffffff !important;">Bills</th>
                                    <th class="th-center" style="color:#ffffff !important;">Qty Sold</th>
                                    <th class="th-right" style="color:#ffffff !important;">Subtotal (₹)</th>
                                    <th class="th-right" style="color:#ffffff !important;">Discount (₹)</th>
                                    <th class="th-right" style="color:#ffffff !important;">Net Sales (₹)</th>
                                    <th class="th-right" style="color:#ffffff !important;">Tax (₹)</th>
                                    <th class="th-right" style="color:#ffffff !important; background:#1a252f;">Total Collection (₹)</th>
                                    <th class="th-right" style="color:#ffc107 !important;">NC Sales (₹)</th>
                                    <th class="th-left" style="color:#ffffff !important;">Payment Breakdown</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($cashierSalesList)): ?>
                                    <?php $idx = 1; foreach ($cashierSalesList as $cs): ?>
                                        <tr>
                                            <td style="text-align:center; font-weight:600;"><?php echo $idx++; ?></td>
                                            <td style="font-weight:600; color:#2c3e50;">
                                                <i class="fa fa-user-circle-o" style="color:#6c757d;"></i> <?php echo h($cs['cashier_name']); ?>
                                                <br><small style="color:#888; font-weight:normal;">(ID: <?php echo intval($cs['cashier_id']); ?>)</small>
                                            </td>
                                            <td style="text-align:center; font-weight:600;"><?php echo number_format($cs['bills_count']); ?></td>
                                            <td style="text-align:center;"><?php echo number_format($cs['qty_sold'], 2); ?></td>
                                            <td style="text-align:right;"><?php echo number_format($cs['gross_sales'], 2); ?></td>
                                            <td style="text-align:right; color:#dc3545;"><?php echo number_format($cs['discount'], 2); ?></td>
                                            <td style="text-align:right; font-weight:600;"><?php echo number_format($cs['net_sales'], 2); ?></td>
                                            <td style="text-align:right;"><?php echo number_format($cs['tax'], 2); ?></td>
                                            <td style="text-align:right; font-weight:700; color:#28a745; background:#f8fff9;"><?php echo number_format($cs['net_payable'], 2); ?></td>
                                            <td style="text-align:right; color:#d97706;"><?php echo number_format($cs['nc_sales'], 2); ?></td>
                                            <td style="font-size:12px;">
                                                <?php 
                                                $pmStr = [];
                                                if ($cs['payments']['cash'] > 0)    $pmStr[] = '<b>Cash:</b> ₹' . number_format($cs['payments']['cash'], 2);
                                                if ($cs['payments']['card'] > 0)    $pmStr[] = '<b>Card:</b> ₹' . number_format($cs['payments']['card'], 2);
                                                if ($cs['payments']['upi'] > 0)     $pmStr[] = '<b>UPI:</b> ₹' . number_format($cs['payments']['upi'], 2);
                                                if ($cs['payments']['online'] > 0)  $pmStr[] = '<b>Online:</b> ₹' . number_format($cs['payments']['online'], 2);
                                                if ($cs['payments']['storecard'] > 0) $pmStr[] = '<b>Storecard:</b> ₹' . number_format($cs['payments']['storecard'], 2);
                                                if ($cs['payments']['nc'] > 0)      $pmStr[] = '<b>NC:</b> ₹' . number_format($cs['payments']['nc'], 2);
                                                
                                                if (!empty($cs['payments']['custom'])) {
                                                    foreach ($cs['payments']['custom'] as $cName => $cAmt) {
                                                        if ($cAmt > 0) {
                                                            $pmStr[] = '<b>' . h($cName) . ':</b> ₹' . number_format($cAmt, 2);
                                                        }
                                                    }
                                                }
                                                echo !empty($pmStr) ? implode(' | ', $pmStr) : '<span style="color:#aaa;">No payments recorded</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" style="text-align:center; color:#999; padding:20px;">
                                            <i class="fa fa-info-circle" style="font-size:18px;"></i> No sales data found for the selected store and date range.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot style="background:#e9ecef; font-weight:700; font-size:13px;">
                                <tr>
                                    <td colspan="2" style="text-align:right;">GRAND TOTAL:</td>
                                    <td style="text-align:center;"><?php echo number_format($grandTotals['bills_count'] ?? 0); ?></td>
                                    <td style="text-align:center;"><?php echo number_format($grandTotals['qty_sold'] ?? 0, 2); ?></td>
                                    <td style="text-align:right;">₹<?php echo number_format($grandTotals['gross_sales'] ?? 0, 2); ?></td>
                                    <td style="text-align:right; color:#dc3545;">₹<?php echo number_format($grandTotals['discount'] ?? 0, 2); ?></td>
                                    <td style="text-align:right;">₹<?php echo number_format($grandTotals['net_sales'] ?? 0, 2); ?></td>
                                    <td style="text-align:right;">₹<?php echo number_format($grandTotals['tax'] ?? 0, 2); ?></td>
                                    <td style="text-align:right; color:#28a745; background:#e2fbe8; font-size:14px;">₹<?php echo number_format($grandTotals['net_payable'] ?? 0, 2); ?></td>
                                    <td style="text-align:right; color:#d97706;">₹<?php echo number_format($grandTotals['nc_sales'] ?? 0, 2); ?></td>
                                    <td>-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- ── Table 2: Dish Type Sales WITHOUT Service Charge ────────────── -->
                    <h4 style="margin-top:35px; margin-bottom:15px; font-weight:700; color:#333;">
                        <i class="fa fa-cutlery" style="color:#8e44ad;"></i> 2. Dish Type Sales Without Service Charge
                    </h4>
                    <div class="table-responsive">
                        <table id="dishTypeTable" class="table table-bordered table-striped table-hover" style="font-size:13px;">
                            <thead style="background:#8e44ad;">
                                <tr>
                                    <th class="th-center" style="color:#ffffff !important;">#</th>
                                    <th class="th-left" style="color:#ffffff !important;">Employee / Cashier</th>
                                    <?php if (!empty($allDishTypesNoSc)): ?>
                                        <?php foreach ($allDishTypesNoSc as $dtKey): ?>
                                            <th class="th-right" style="color:#ffffff !important; min-width:120px;"><?php echo h($dtKey); ?> (₹)</th>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <th class="th-left" style="color:#ffffff !important;">Dish Type</th>
                                    <?php endif; ?>
                                    <th class="th-right" style="color:#ffffff !important; background:#5b2c6f;">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($cashierSalesList)): ?>
                                    <?php $nIdx = 1; foreach ($cashierSalesList as $cs): ?>
                                        <tr>
                                            <td style="text-align:center; font-weight:600;"><?php echo $nIdx++; ?></td>
                                            <td style="font-weight:600; color:#2c3e50;"><?php echo h($cs['cashier_name']); ?></td>
                                            <?php 
                                            $rowNoScSum = 0;
                                            if (!empty($allDishTypesNoSc)):
                                                foreach ($allDishTypesNoSc as $dtKey): 
                                                    $v = floatval($cs['dish_type_no_sc'][$dtKey] ?? 0);
                                                    $rowNoScSum += $v;
                                                    ?>
                                                    <td style="text-align:right;"><?php echo $v > 0 ? number_format($v, 2) : '<span style="color:#ccc;">0.00</span>'; ?></td>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <td>-</td>
                                            <?php endif; ?>
                                            <td style="text-align:right; font-weight:700; color:#8e44ad; background:#f5eeef;">₹<?php echo number_format($rowNoScSum, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo !empty($allDishTypesNoSc) ? (count($allDishTypesNoSc) + 3) : 3; ?>" style="text-align:center; color:#999; padding:20px;">No data.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot style="background:#e9ecef; font-weight:700; font-size:13px;">
                                <tr>
                                    <td colspan="2" style="text-align:right;">TOTAL WITHOUT SERVICE CHARGE:</td>
                                    <?php 
                                    $overallNoScSum = 0;
                                    if (!empty($allDishTypesNoSc)):
                                        foreach ($allDishTypesNoSc as $dtKey): 
                                            $tot = floatval($grandTotalsDtNoSc[$dtKey] ?? 0);
                                            $overallNoScSum += $tot;
                                            ?>
                                            <td style="text-align:right; color:#8e44ad;">₹<?php echo number_format($tot, 2); ?></td>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <td>-</td>
                                    <?php endif; ?>
                                    <td style="text-align:right; color:#8e44ad; background:#f5eeef; font-size:14px;">₹<?php echo number_format($overallNoScSum, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#exportdata').click(function() {
        var cashier   = $('#cashier').val();
        var daterange = $('#startdate').val();
        var url = '<?php echo $this->base; ?>/outlet/employee_sales_report_export?daterange=' + encodeURIComponent(daterange) + '&cashier=' + encodeURIComponent(cashier);
        window.open(url, '_blank');
    });

    $('#btn-pdf').click(function() {
        $('#pdf_cashier').val($('#cashier').val());
        $('#pdf_daterange').val($('#startdate').val());
        $('#pdfform').submit();
    });

    if ($.fn.DataTable) {
        if ($('#cashierTable').length && $('#cashierTable tbody tr').length > 0 && !$('#cashierTable tbody tr td[colspan]').length) {
            $('#cashierTable').DataTable({
                "paging": true,
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "searching": true,
                "ordering": true,
                "info": true
            });
        }
        if ($('#dishTypeTable').length && $('#dishTypeTable tbody tr').length > 0 && !$('#dishTypeTable tbody tr td[colspan]').length) {
            $('#dishTypeTable').DataTable({
                "paging": true,
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "searching": true,
                "ordering": true,
                "info": true
            });
        }
    }
});
</script>
