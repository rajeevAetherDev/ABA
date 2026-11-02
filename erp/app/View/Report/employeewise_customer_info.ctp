<style>
.table thead tr th {
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    vertical-align: middle !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.4) !important;
    border-bottom: 2px solid rgba(255,255,255,0.2) !important;
    background-color: #3b5998 !important;
}
.kpi-card {
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    background: #fff;
    border-left: 4px solid #3b5998;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.kpi-title {
    font-size: 12px;
    text-transform: uppercase;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 5px;
}
.kpi-value {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
}
.text-muted-dash {
    color: #9ca3af;
    font-style: italic;
}
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-flex align-items-center justify-content-between" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="page-title" style="margin:0;">Employeewise Customer Info Report</h2>
            <span class="badge badge-primary" style="font-size:13px; padding:6px 12px; background-color:#3b5998; color:#fff;">
                <i class="fa fa-user-circle-o"></i> Customer CRM & Audit
            </span>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 15px;">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <!-- ── Filter Form ──────────────────────────────────────────────────────── -->
                <?php echo $this->Form->create('Search', array('url' => 'employeewise_customer_info', 'id' => 'searchForm')); ?>
                <div class="row" style="margin-bottom: 12px;">
                    <!-- Store Selector -->
                    <div class="col-md-3 col-sm-6" style="margin-bottom:12px;">
                        <label style="font-weight:600; font-size:13px;">Select Store:</label>
                        <?php 
                        $storeOpts = array('' => 'All Stores');
                        if (!empty($stores)) {
                            foreach($stores as $st) {
                                if (isset($st['Location']['id']) && isset($st['Location']['name'])) {
                                    $storeOpts[$st['Location']['id']] = $st['Location']['name'];
                                }
                            }
                        }
                        echo $this->Form->input('cashier', array(
                            'div' => false, 
                            'label' => false, 
                            'class' => 'form-control', 
                            'options' => $storeOpts,
                            'id' => 'cashier',
                            'value' => $selectedStoreId
                        ));
                        ?>
                    </div>

                    <!-- Cashier Selector -->
                    <div class="col-md-3 col-sm-6" style="margin-bottom:12px;">
                        <label style="font-weight:600; font-size:13px;">Select Cashier:</label>
                        <?php 
                        echo $this->Form->input('cashier_id', array(
                            'div' => false, 
                            'label' => false, 
                            'class' => 'form-control', 
                            'options' => $cashierOptions,
                            'id' => 'cashier_id',
                            'value' => $selectedCashierId
                        ));
                        ?>
                    </div>

                    <!-- Date Range Picker -->
                    <div class="col-md-3 col-sm-6" style="margin-bottom:12px;">
                        <label style="font-weight:600; font-size:13px;">Select Date Range:</label>
                        <?php 
                        echo $this->Form->input('startdate', array(
                            'div' => false, 
                            'label' => false, 
                            'class' => 'form-control calendarmultiple', 
                            'id' => 'startdate',
                            'name' => 'daterange',
                            'required' => 'required',
                            'value' => $daterangeValue
                        )); 
                        ?>
                    </div>

                    <!-- Customer Info Filter -->
                    <div class="col-md-3 col-sm-6" style="margin-bottom:12px;">
                        <label style="font-weight:600; font-size:13px;">Customer Data Filter:</label>
                        <?php 
                        $custFilterOptions = [
                            'all' => 'All Orders',
                            'with_info' => 'With Customer Info Only',
                            'without_info' => 'Without Customer Info'
                        ];
                        echo $this->Form->input('customer_filter', array(
                            'div' => false, 
                            'label' => false, 
                            'class' => 'form-control', 
                            'options' => $custFilterOptions,
                            'id' => 'customer_filter',
                            'value' => $customerFilter
                        ));
                        ?>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 15px;">
                    <!-- Action Buttons -->
                    <div class="col-sm-12" style="margin-bottom:10px;">
                        <button type="submit" class="btn btn-info" style="margin-right:8px;">
                            <i class="fa fa-search"></i> View Report
                        </button>
                        <button type="button" class="btn btn-success" id="exportBtn" style="margin-right:8px;">
                            <i class="fa fa-file-excel-o"></i> Excel Export
                        </button>
                        <button type="button" class="btn btn-primary" id="printBtn">
                            <i class="fa fa-print"></i> Print / PDF
                        </button>
                    </div>
                </div>
                <?php echo $this->Form->end(); ?>

                <!-- Hidden form for PDF printable view -->
                <?php echo $this->Form->create('Report', array('id' => 'pdfform', 'url' => 'employeewise_customer_info_pdf', 'target' => '_blank')); ?>
                <input type="hidden" name="cashier" id="pdf_cashier" value="<?php echo h($selectedStoreId); ?>">
                <input type="hidden" name="cashier_id" id="pdf_cashier_id" value="<?php echo h($selectedCashierId); ?>">
                <input type="hidden" name="daterange" id="pdf_daterange" value="<?php echo h($daterangeValue); ?>">
                <input type="hidden" name="customer_filter" id="pdf_customer_filter" value="<?php echo h($customerFilter); ?>">
                <?php echo $this->Form->end(); ?>

                <hr style="margin-top:10px; margin-bottom:20px;">

                <!-- ── KPI Summary Cards ──────────────────────────────────────────────── -->
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="kpi-card" style="border-left-color: #3b5998;">
                            <div class="kpi-title"><i class="fa fa-file-text-o"></i> Total Orders</div>
                            <div class="kpi-value"><?php echo number_format($summary['total_orders'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="kpi-card" style="border-left-color: #10b981;">
                            <div class="kpi-title"><i class="fa fa-check-circle-o"></i> With Customer Info</div>
                            <div class="kpi-value" style="color:#059669;"><?php echo number_format($summary['with_customer_info'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="kpi-card" style="border-left-color: #8b5cf6;">
                            <div class="kpi-title"><i class="fa fa-users"></i> Unique Customers</div>
                            <div class="kpi-value" style="color:#7c3aed;"><?php echo number_format($summary['unique_customers'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="kpi-card" style="border-left-color: #f59e0b;">
                            <div class="kpi-title"><i class="fa fa-id-badge"></i> Active Cashiers</div>
                            <div class="kpi-value" style="color:#d97706;"><?php echo number_format($summary['total_cashiers'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>

                <!-- ── Data Table ──────────────────────────────────────────────────────── -->
                <div class="table-responsive" style="margin-top:15px;">
                    <table class="table table-bordered table-striped table-hover" id="customerInfoTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width:40px; text-align:center;">#</th>
                                <th>Store Name</th>
                                <th>Order Number</th>
                                <th>Order Start By</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Bill Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($records)): ?>
                                <?php $sr = 1; foreach ($records as $r): ?>
                                    <tr>
                                        <td style="text-align:center;"><?php echo $sr++; ?></td>
                                        <td style="font-weight:600; color:#1e293b;">
                                            <?php echo h($r['store_name']); ?>
                                        </td><td style="font-weight:600; color:#1e293b;">
                                            <?php echo h($r['Ordernumber']); ?>
                                        </td>
                                        <td style="font-weight:600; color:#2563eb;">
                                            <i class="fa fa-user" style="font-size:11px; margin-right:4px;"></i>
                                            <?php echo h($r['Orderstartby']); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty(trim($r['name']))): ?>
                                                <strong><?php echo h($r['name']); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted-dash">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty(trim($r['phone']))): ?>
                                                <a href="tel:<?php echo h($r['phone']); ?>" style="color:#059669; font-weight:600;">
                                                    <i class="fa fa-phone" style="font-size:11px; margin-right:3px;"></i><?php echo h($r['phone']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted-dash">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width:260px; word-break:break-word;">
                                            <?php if (!empty(trim($r['address']))): ?>
                                                <?php echo h($r['address']); ?>
                                            <?php else: ?>
                                                <span class="text-muted-dash">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:12px; white-space:nowrap;" data-order="<?php echo !empty($r['Order date']) ? strtotime($r['Order date']) : 0; ?>">
                                            <?php echo !empty($r['Order date']) ? date('d M Y, h:i A', strtotime($r['Order date'])) : '-'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center" style="padding:30px; color:#6b7280;">
                                        <i class="fa fa-info-circle" style="font-size:20px; margin-bottom:8px; display:block;"></i>
                                        No customer information records found for the selected criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Role-wise cashiers list with assigned locations passed from controller
    var allCashiers = <?php echo json_encode($cashiersList ?? []); ?>;

    /**
     * Dynamically filter Cashier dropdown based on selected Store
     * - If Store is selected: only show cashiers assigned to that store
     * - If "All Stores" is selected: show all cashiers permitted for this role
     * - Preserve current selection if valid; otherwise reset to "All Cashiers"
     */
    function filterCashierDropdown(selectedStoreId, currentCashierId) {
        var $cashierSelect = $('#cashier_id');
        var prevVal = (currentCashierId !== undefined && currentCashierId !== null)
            ? String(currentCashierId)
            : String($cashierSelect.val() || '');
        var storeId = parseInt(selectedStoreId, 10);

        $cashierSelect.empty();
        $cashierSelect.append($('<option>', {
            value: '',
            text: 'All Cashiers'
        }));

        var matchFound = false;

        $.each(allCashiers, function(idx, item) {
            var isAllowed = false;
            if (!storeId || isNaN(storeId)) {
                // All Stores selected: include all role-permitted cashiers
                isAllowed = true;
            } else if (item.locations && $.inArray(storeId, item.locations) !== -1) {
                // Specific Store selected: include only cashiers assigned to this store
                isAllowed = true;
            }

            if (isAllowed) {
                var $opt = $('<option>', {
                    value: item.id,
                    text: item.name
                });
                if (String(item.id) === prevVal) {
                    $opt.prop('selected', true);
                    matchFound = true;
                }
                $cashierSelect.append($opt);
            }
        });

        if (!matchFound) {
            $cashierSelect.val('');
        }

        // Keep Select2 in sync if active
        if ($.fn.select2 && $cashierSelect.data('select2')) {
            $cashierSelect.trigger('change.select2');
        }
    }

    // Dynamic filtering when Store dropdown changes
    $('#cashier').on('change', function() {
        filterCashierDropdown($(this).val(), $('#cashier_id').val());
    });

    // Synchronize cashier dropdown on initial page load
    var initialStore = $('#cashier').val();
    if (initialStore) {
        filterCashierDropdown(initialStore, '<?php echo h($selectedCashierId); ?>');
    }

    // Excel CSV Export
    $('#exportBtn').click(function() {
        var cashier        = $('#cashier').val();
        var cashier_id     = $('#cashier_id').val();
        var daterange      = $('#startdate').val();
        var customer_filter = $('#customer_filter').val();

        var url = '<?php echo $this->base; ?>/report/employeewise_customer_info_export?' +
            'daterange=' + encodeURIComponent(daterange) +
            '&cashier=' + encodeURIComponent(cashier) +
            '&cashier_id=' + encodeURIComponent(cashier_id) +
            '&customer_filter=' + encodeURIComponent(customer_filter);

        window.open(url, '_blank');
    });

    // PDF / Print View
    $('#printBtn').click(function() {
        $('#pdf_cashier').val($('#cashier').val());
        $('#pdf_cashier_id').val($('#cashier_id').val());
        $('#pdf_daterange').val($('#startdate').val());
        $('#pdf_customer_filter').val($('#customer_filter').val());
        $('#pdfform').submit();
    });

    // DataTable initialization
    if ($.fn.DataTable) {
        if ($('#customerInfoTable').length && $('#customerInfoTable tbody tr').length > 0 && !$('#customerInfoTable tbody tr td[colspan]').length) {
            $('#customerInfoTable').DataTable({
                "paging": true,
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "searching": true,
                "ordering": true,
                "info": true,
                "order": [[0, 'asc']]
            });
        }
    }
});
</script>
