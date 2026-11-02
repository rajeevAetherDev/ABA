
<!-- ── Page header ─────────────────────────────────────────────────────── -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h2 class="page-title" style="margin:0;">Direct Stockin</h2>
            <span style="background:#e67e22;color:#fff;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600;letter-spacing:.5px;">
                &#9889; DIRECT STOCKIN
            </span>
        </div>
    </div>
</div>

<!-- ── Location badge ──────────────────────────────────────────────────── -->
<div class="row" style="margin-bottom:12px;">
    <div class="col-sm-12">
        <div style="background:#f0f4ff;border:1px solid #c8d6ff;border-radius:8px;padding:10px 16px;display:inline-flex;align-items:center;gap:10px;">
            <i class="fe-map-pin" style="color:#3b5bdb;font-size:16px;"></i>
            <span style="font-weight:600;color:#333;">Receiving Location:</span>
            <span style="color:#3b5bdb;font-weight:700;"><?php echo h($locationName); ?></span>
        </div>
    </div>
</div>

<!-- ── Phase 1: Vendor Selection ──────────────────────────────────────── -->
<div class="row" id="phase-vendor">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title" style="margin-bottom:20px;">Step 1 — Select Vendor</h4>
                <div class="row align-items-end">
                    <input type="hidden" id="locationPicker" value="<?php echo intval($primaryLocationId); ?>">
                    <div class="col-sm-5">
                        <label class="form-label" style="font-weight:600;">Vendor <span style="color:red;">*</span></label>
                        <select id="vendorSelect" class="form-control select2" style="width:100%;">
                            <option value="">— Search or select a vendor —</option>
                            <?php foreach ($vendorRows as $vr):
                                $v = $vr['inventory_vendors'] ?? $vr[0] ?? [];
                                $vid   = $v['id'] ?? '';
                                $vname = $v['name'] ?? '';
                                $vco   = $v['company'] ?? '';
                                $display = $vco ? $vco . ' (' . $vname . ')' : $vname;
                            ?>
                            <option value="<?php echo intval($vid); ?>"><?php echo h($display); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="vendorError" style="color:#c0392b;font-size:12px;margin-top:4px;display:none;">
                            No items mapped for this vendor. Ask admin to add SKU mapping first.
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <button id="loadItemsBtn" class="btn btn-info" style="height:42px;width:100%;">
                            Load Items &rarr;
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Phase 2: Items Form (injected via AJAX) ────────────────────────── -->
<div id="phase-items" style="display:none;">

    <!-- Invoice + date + charges + order discount row -->
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-sm-2">
                            <label class="form-label" style="font-weight:600;">
                                Invoice No <span style="color:red;">*</span>
                            </label>
                            <input type="text" id="invoiceNo" class="form-control" placeholder="e.g. INV-20260510" maxlength="100">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label" style="font-weight:600;">
                                Stockin Date <span style="color:red;">*</span>
                            </label>
                            <input type="date" id="stockinDate" class="form-control"
                                   value="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label" style="font-weight:600;">Charges (₹)</label>
                            <input type="number" id="chargesInput" class="form-control" value="0" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label" style="font-weight:600;">Order-level Discount</label>
                            <div class="input-group">
                                <input type="number" id="orderDiscInput" class="form-control" value="0" min="0" step="0.01" placeholder="0.00">
                                <select id="orderDiscType" class="form-control" style="max-width:110px;">
                                    <option value="flat">Flat ₹</option>
                                    <option value="percentage">Percent %</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <button id="changeVendorBtn" class="btn btn-outline-secondary" style="height:42px;">
                                &larr; Change Vendor
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
    #itemsTable {
        table-layout: fixed !important;
        width: 100% !important;
        font-size: 11px;
    }
    #itemsTable th {
        padding: 5px 3px !important;
        font-size: 11px !important;
        font-weight: 600;
        vertical-align: middle !important;
        line-height: 1.15;
        word-wrap: break-word;
        white-space: normal !important;
    }
    #itemsTable td {
        padding: 3px 2px !important;
        vertical-align: middle !important;
        font-size: 11px !important;
        word-wrap: break-word;
    }
    #itemsTable .form-control {
        height: 28px !important;
        padding: 2px 3px !important;
        font-size: 11px !important;
        border-radius: 3px !important;
        line-height: 1.2 !important;
    }
    #itemsTable select.form-control {
        padding: 2px 1px !important;
    }
</style>

    <!-- Items table -->
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title" style="margin-bottom:16px;">Step 2 — Enter Quantities</h4>
                    <div class="table-responsive" style="overflow-x: hidden;">
                        <table class="table table-bordered table-hover" id="itemsTable" style="width:100% !important; table-layout:fixed !important;">
                            <thead style="background:#f7f9fc;">
                                <tr>
                                    <th style="width:2%;">#</th>
                                    <th style="width:16%;">SKU Name</th>
                                    <th style="width:4%;">UOM</th>
                                    <th style="width:6.5%;text-align:right;">Vendor Price (₹)</th>
                                    <th style="width:6.5%;text-align:right;">Excise ₹/u</th>
                                    <th style="width:6.5%;text-align:right;">Addl.Ex ₹/u</th>
                                    <th style="width:6.5%;text-align:right;">VAT%</th>
                                    <th style="width:6.5%;text-align:right;">TCS%</th>
                                    <th style="width:4%;text-align:right;">Tax%</th>
                                    <th style="width:7%;text-align:right;">Tax Value (₹)</th>
                                    <th style="width:7%;text-align:right;">Landed ₹/u</th>
                                    <th style="width:6.5%;text-align:center;">Qty</th>
                                    <th style="width:6.5%;text-align:right;">Item Disc</th>
                                    <th style="width:6.5%;text-align:center;">Disc Type</th>
                                    <th style="width:8%;text-align:right;">Line Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div class="row justify-content-end" style="margin-top:20px;">
                        <div class="col-sm-4">
                            <table class="table table-sm" style="background:#f7f9fc;border-radius:8px;">
                                <tr>
                                    <td style="color:#555;">Subtotal</td>
                                    <td style="text-align:right;font-weight:600;" id="sumSubtotal">₹0.00</td>
                                </tr>
                                <tr>
                                    <td style="color:#555;">Discount(order level/item level)</td>
                                    <td style="text-align:right;color:#c0392b;" id="sumItemDisc"> ₹0.00</td>
                                </tr>
                                <tr>
                                    <td style="color:#555;">Total(after discount)</td>
                                    <td style="text-align:right;font-weight:600;" id="sumTotal">₹0.00</td>
                                </tr>
                                <tr>
                                    <td style="color:#555;">Tax Total</td>
                                    <td style="text-align:right;color:#856404;" id="sumTaxTotal"> ₹0.00</td>
                                </tr>
                                <tr style="font-size:12px;">
                                    <td style="color:#777;padding-left:18px;"> GST Total</td>
                                    <td style="text-align:right;color:#777;" id="sumGstTotal">₹0.00</td>
                                </tr>
                                <tr style="font-size:12px;">
                                    <td style="color:#777;padding-left:18px;"> Excise Total</td>
                                    <td style="text-align:right;color:#777;" id="sumExciseTotal">₹0.00</td>
                                </tr>
                                <tr style="font-size:12px;">
                                    <td style="color:#777;padding-left:18px;"> VAT Total</td>
                                    <td style="text-align:right;color:#777;" id="sumVatTotal">₹0.00</td>
                                </tr>
                                <tr style="font-size:12px;">
                                    <td style="color:#777;padding-left:18px;"> TCS Total</td>
                                    <td style="text-align:right;color:#777;" id="sumTcsTotal">₹0.00</td>
                                </tr>
                                <tr>
                                    <td style="color:#555;">Charges</td>
                                    <td style="text-align:right;color:#2980b9;" id="sumCharges">+ ₹0.00</td>
                                </tr>
                                <tr style="border-top:2px solid #dee2e6;">
                                    <td style="font-weight:700;font-size:15px;">Grand Total</td>
                                    <td style="text-align:right;font-weight:700;font-size:15px;color:#27ae60;" id="sumGrand">₹0.00</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row" style="margin-top:8px;">
                        <div class="col-sm-12" style="text-align:right;">
                            <button id="cancelBtn" class="btn btn-secondary" style="margin-right:10px;">Cancel</button>
                            <button id="submitBtn" class="btn btn-success btn-lg" disabled>
                                Submit &amp; Receive Stock
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Phase 3: Progress Overlay ──────────────────────────────────────── -->
<div id="phase-progress" style="display:none;position:fixed;inset:0;background:rgba(15,20,50,.75);z-index:9999;
     display:none;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:48px 56px;min-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center;">
        <h3 style="margin:0 0 8px;color:#2c3e50;">Processing Direct Stockin</h3>
        <p style="color:#7f8c8d;margin:0 0 32px;font-size:14px;">Please wait while we set everything up...</p>

        <div id="stepList" style="text-align:left;">
            <?php
            $steps = [
                'step-indent' => 'Creating Indent',
                'step-po'     => 'Creating Purchase Order',
                'step-stockin'=> 'Processing Stock In',
                'step-ledger' => 'Updating Stock Ledger &amp; Prices',
            ];
            foreach ($steps as $sid => $label): ?>
            <div id="<?php echo $sid; ?>" style="display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid #f0f0f0;">
                <div class="step-icon" style="width:28px;height:28px;border-radius:50%;background:#e9ecef;
                     display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">
                    <span class="icon-inner">&#9675;</span>
                </div>
                <span class="step-label" style="color:#7f8c8d;font-size:15px;"><?php echo $label; ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Success state (hidden initially) -->
        <div id="successBox" style="display:none;margin-top:24px;">
            <div style="font-size:56px;">&#10003;</div>
            <h3 style="color:#27ae60;margin:8px 0;">Stock Received Successfully!</h3>
            <table style="margin:16px auto;text-align:left;font-size:14px;border-collapse:collapse;">
                <tr><td style="padding:3px 12px 3px 0;color:#7f8c8d;">Indent:</td>   <td style="font-weight:600;" id="resIndentId"></td></tr>
                <tr><td style="padding:3px 12px 3px 0;color:#7f8c8d;">Serial No:</td><td style="font-weight:600;" id="resSerial"></td></tr>
                <tr><td style="padding:3px 12px 3px 0;color:#7f8c8d;">PO:</td>        <td style="font-weight:600;" id="resPoId"></td></tr>
                <tr><td style="padding:3px 12px 3px 0;color:#7f8c8d;">Stockin ID:</td><td style="font-weight:600;" id="resStockinId"></td></tr>
            </table>
            <div style="margin-top:16px;">
                <a href="direct_stockin" class="btn btn-success" style="margin-right:8px;">New Direct Stockin</a>
                <a href="grn_history" class="btn btn-outline-secondary">View GRN History</a>
            </div>
        </div>

        <!-- Error state (hidden initially) -->
        <div id="errorBox" style="display:none;margin-top:16px;">
            <div style="color:#e74c3c;font-size:40px;">&#10007;</div>
            <p style="color:#e74c3c;font-weight:600;" id="errorMsg">An error occurred.</p>
            <button onclick="resetProgress()" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {

var skuData = [];
var dtInstance = null;

// ── Select2 init ──────────────────────────────────────────────────────────
if (typeof $ !== 'undefined' && $.fn.select2) {
    $(function() { $('#vendorSelect').select2({ placeholder: '— Search or select a vendor —', width: '100%' }); });
}

// ── Load Items ────────────────────────────────────────────────────────────
document.getElementById('loadItemsBtn').addEventListener('click', function() {
    var vendorId = (typeof $ !== 'undefined' ? $('#vendorSelect').val() : null)
                   || document.getElementById('vendorSelect').value;
    var locId    = document.getElementById('locationPicker').value;

    if (!vendorId) { alert('Please select a vendor first.'); return; }

    document.getElementById('vendorError').style.display = 'none';
    document.getElementById('loadItemsBtn').textContent   = 'Loading…';
    document.getElementById('loadItemsBtn').disabled      = true;

    var fd = new FormData();
    fd.append('vendor_id',   vendorId);
    fd.append('location_id', locId);

    fetch('direct_stockin', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('loadItemsBtn').textContent = 'Load Items →';
            document.getElementById('loadItemsBtn').disabled    = false;
            if (!d.success || !d.skus || d.skus.length === 0) {
                document.getElementById('vendorError').style.display = 'block';
                return;
            }
            skuData = d.skus;
            buildTable(skuData);
            document.getElementById('phase-vendor').style.display = 'none';
            document.getElementById('phase-items').style.display  = 'block';
            recalcTotals();
        })
        .catch(function() {
            document.getElementById('loadItemsBtn').textContent = 'Load Items →';
            document.getElementById('loadItemsBtn').disabled    = false;
            alert('Network error loading items. Please try again.');
        });
});

function buildTable(skus) {
    if (dtInstance) {
        dtInstance.destroy();
    }
    var tbody = document.getElementById('itemsBody');
    tbody.innerHTML = '';

    // Deduplicate SKUs by sku_id
    var seen = {};
    var uniqueSkus = (skus || []).filter(function(item) {
        if (!item || !item.sku_id) return true;
        if (seen[item.sku_id]) return false;
        seen[item.sku_id] = true;
        return true;
    });

    uniqueSkus.forEach(function(sku, i) {
        var epu  = parseFloat(sku.excise_per_unit)      || 0;
        var aepu = parseFloat(sku.addl_excise_per_unit) || 0;
        var vatp = parseFloat(sku.vat_percentage)       || 0;
        var tcsp = parseFloat(sku.tcs_percentage)       || 0;
        var _vatBase = sku.sku_price + epu + aepu;
        var _vatAmt  = _vatBase * vatp / 100;
        var lnd  = parseFloat(sku.landed_price) || (sku.sku_price + epu + aepu + _vatAmt);
        var tr = document.createElement('tr');
        tr.setAttribute('data-index',  i);
        tr.setAttribute('data-tax-pct', sku.tax_pct || 0);
        tr.innerHTML =
            '<td style="vertical-align:middle;">' + (i + 1) + '</td>' +
            '<td style="vertical-align:middle;">' + escHtml(sku.sku_name) + '</td>' +
            '<td style="vertical-align:middle;">' + escHtml(sku.sku_unit_name) + '</td>' +
            '<td style="vertical-align:middle;text-align:right;">' +
                '<input type="number" class="form-control price-input" value="' + sku.sku_price.toFixed(2) + '" min="0" step="0.01" placeholder="0.00" style="text-align:right;"></td>' +
            '<td style="vertical-align:middle;text-align:right;">' +
                '<input type="number" class="form-control excise-input" value="' + epu.toFixed(2) + '" min="0" step="0.01" placeholder="0.00" style="text-align:right;"></td>' +
            '<td style="vertical-align:middle;text-align:right;">' +
                '<input type="number" class="form-control addlex-input" value="' + aepu.toFixed(2) + '" min="0" step="0.01" placeholder="0.00" style="text-align:right;"></td>' +
            '<td style="vertical-align:middle;text-align:right;">' +
                '<input type="number" class="form-control vat-input" value="' + vatp.toFixed(2) + '" min="0" max="100" step="0.01" placeholder="0.00" style="text-align:right;"></td>' +
            '<td style="vertical-align:middle;text-align:right;">' +
                '<input type="number" class="form-control tcs-input" value="' + tcsp.toFixed(2) + '" min="0" max="100" step="0.01" placeholder="0.00" style="text-align:right;"></td>' +
            '<td style="vertical-align:middle;text-align:right;color:#1a5276;">' + (sku.tax_pct > 0 ? sku.tax_pct + '%' : '—') + '</td>' +
            '<td style="vertical-align:middle;text-align:right;color:#1a5276;" class="tax-value">0.00</td>' +
            '<td style="vertical-align:middle;text-align:right;color:#1a7a3c;font-weight:600;" class="landed-price">' + fmt2(lnd) + '</td>' +
            '<td style="vertical-align:middle;text-align:center;">' +
                '<input type="number" class="form-control qty-input" value="" min="0" step="0.001" placeholder="0"></td>' +
            '<td style="vertical-align:middle;text-align:right;">' +
                '<input type="number" class="form-control disc-input" value="0" min="0" step="0.01"></td>' +
            '<td style="vertical-align:middle;">' +
                '<select class="form-control disc-type">' +
                '<option value="flat">Flat</option>' +
                '<option value="percentage">% Percent</option></select></td>' +
            '<td style="vertical-align:middle;text-align:right;font-weight:600;" class="line-total">0.00</td>';
        tbody.appendChild(tr);
    });
    
    // Initialize DataTable
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        dtInstance = $('#itemsTable').DataTable({
            responsive: false,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [] // Disable initial sorting
        });
        
        // Re-bind events to all nodes in datatable
        dtInstance.$('.qty-input, .disc-input, .disc-type, .price-input, .excise-input, .addlex-input, .vat-input, .tcs-input').on('input change', recalcTotals);
    } else {
        tbody.querySelectorAll('.qty-input,.disc-input,.disc-type,.price-input,.excise-input,.addlex-input,.vat-input,.tcs-input').forEach(function(el) {
            el.addEventListener('input',  recalcTotals);
            el.addEventListener('change', recalcTotals);
        });
    }
}

function recalcTotals() {
    var ordDiscRaw  = parseFloat(document.getElementById('orderDiscInput').value) || 0;
    var ordDiscType = document.getElementById('orderDiscType').value;

    var subtotal = 0, totalItemDisc = 0;
    var rows = dtInstance ? dtInstance.$('tr').get() : Array.from(document.querySelectorAll('#itemsBody tr'));
    rows.forEach(function(tr) {
        var price = parseFloat(tr.querySelector('.price-input').value) || 0;
        var qty   = parseFloat(tr.querySelector('.qty-input').value)   || 0;
        var discRaw  = parseFloat(tr.querySelector('.disc-input').value) || 0;
        var discType = tr.querySelector('.disc-type').value;
        var base = price * qty;
        var discVal = discType === 'percentage' ? Math.min(discRaw,100)*base/100 : Math.min(discRaw,base);
        discVal = Math.round(discVal * 100) / 100;
        
        subtotal += base;
        totalItemDisc += discVal;
    });

    var subtotalExclTax = subtotal - totalItemDisc;
    var ordDiscVal = (ordDiscType === 'percentage')
        ? Math.round(subtotalExclTax * Math.min(ordDiscRaw, 100) / 100 * 100) / 100
        : Math.round(Math.min(ordDiscRaw, subtotalExclTax) * 100) / 100;

    var totalTaxVal = 0, totalExcise = 0, totalVat = 0, totalTcs = 0, hasQty = false;
    rows.forEach(function(tr) {
        var price    = parseFloat(tr.querySelector('.price-input').value)   || 0;
        var taxPct   = parseFloat(tr.getAttribute('data-tax-pct')) || 0;
        var excisePu = parseFloat(tr.querySelector('.excise-input').value)  || 0;
        var addlExPu = parseFloat(tr.querySelector('.addlex-input').value)  || 0;
        var vatPct   = parseFloat(tr.querySelector('.vat-input').value)     || 0;
        var tcsPct   = parseFloat(tr.querySelector('.tcs-input').value)     || 0;
        var qty      = parseFloat(tr.querySelector('.qty-input').value)     || 0;
        var discRaw  = parseFloat(tr.querySelector('.disc-input').value)    || 0;
        var discType = tr.querySelector('.disc-type').value;
        var base     = price * qty;
        var discVal  = discType === 'percentage' ? Math.min(discRaw,100)*base/100 : Math.min(discRaw,base);
        discVal      = Math.round(discVal * 100) / 100;
        var afterItemDisc = base - discVal;
        
        // Distribute order discount proportionally
        var ordItemDisc = 0;
        if (ordDiscVal > 0 && subtotalExclTax > 0) {
            ordItemDisc = Math.round((ordDiscVal * afterItemDisc / subtotalExclTax) * 10000) / 10000;
        }
        var afterDisc = afterItemDisc - ordItemDisc;
        var exciseAmt = Math.round(excisePu * qty * 100) / 100;
        var addlExAmt = Math.round(addlExPu * qty * 100) / 100;
        var vatBase  = afterDisc + exciseAmt + addlExAmt;
        var vatAmt   = Math.round(vatBase * vatPct / 100 * 100) / 100;
        var landedAmt= afterDisc + exciseAmt + vatAmt;
        var unitLandedPrice = qty > 0 ? (landedAmt / qty) : (price - (discVal / (qty || 1)) + excisePu + ((price - (discVal / (qty || 1)) + excisePu) * vatPct / 100));
        var tcsAmt   = Math.round(landedAmt * tcsPct / 100 * 100) / 100;
        var tax      = Math.round(afterDisc * taxPct / 100 * 100) / 100;
        var lineTotal= Math.round((landedAmt + tax + tcsAmt) * 100) / 100;

        tr.querySelector('.line-total').textContent = fmt2(lineTotal);
        tr.querySelector('.landed-price').textContent = fmt2(unitLandedPrice);
        tr.querySelector('.tax-value').textContent = fmt2(tax);

        totalTaxVal += tax;
        totalExcise += exciseAmt;
        totalVat += vatAmt;
        totalTcs += tcsAmt;
        if (qty > 0) hasQty = true;
    });

    var totalDiscount = totalItemDisc + ordDiscVal;
    var totalExclTax = subtotal - totalDiscount;
    var totalTax = totalTaxVal + totalExcise + totalVat + totalTcs;
    var charges     = parseFloat(document.getElementById('chargesInput').value) || 0;
    var grand       = Math.round((totalExclTax + totalTax + charges) * 100) / 100;

    document.getElementById('sumSubtotal').textContent  = fmt2(subtotal);
    document.getElementById('sumItemDisc').textContent  = fmt2(totalDiscount);
    document.getElementById('sumTotal').textContent     = fmt2(totalExclTax);
    document.getElementById('sumTaxTotal').textContent  = fmt2(totalTax);
    document.getElementById('sumGstTotal').textContent  = fmt2(totalTaxVal);
    document.getElementById('sumExciseTotal').textContent = fmt2(totalExcise);
    document.getElementById('sumTcsTotal').textContent  = fmt2(totalTcs);
    document.getElementById('sumVatTotal').textContent  = fmt2(totalVat);
    document.getElementById('sumCharges').textContent   = '+' + fmt2(charges);
    document.getElementById('sumGrand').textContent     = fmt2(grand);
    var inv = document.getElementById('invoiceNo').value.trim();
    document.getElementById('submitBtn').disabled = !(hasQty && inv.length > 0);
}

['invoiceNo','orderDiscInput','chargesInput'].forEach(function(id) {
    document.getElementById(id).addEventListener('input', recalcTotals);
});
document.getElementById('orderDiscType').addEventListener('change', recalcTotals);

document.getElementById('changeVendorBtn').addEventListener('click', function() {
    document.getElementById('phase-items').style.display  = 'none';
    document.getElementById('phase-vendor').style.display = 'block';
    document.getElementById('itemsBody').innerHTML = '';
    document.getElementById('invoiceNo').value = '';
    document.getElementById('chargesInput').value = '0';
    document.getElementById('orderDiscInput').value = '0';
    recalcTotals();
});

document.getElementById('cancelBtn').addEventListener('click', function() {
    if (confirm('Cancel this Direct Stockin?')) window.location.href = 'grn_history';
});

document.getElementById('submitBtn').addEventListener('click', function() {

    var items = [];

    // Read order discount
    var ordDiscRaw  = parseFloat(document.getElementById('orderDiscInput').value) || 0;
    var ordDiscType = document.getElementById('orderDiscType').value;
    var rows = dtInstance ? dtInstance.$('tr').get() : Array.from(document.querySelectorAll('#itemsBody tr'));

    // First pass to calculate subtotal and item discount
    var subtotal = 0;
    var totalItemDisc = 0;
    rows.forEach(function(tr) {
        var qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        if (qty <= 0) return;
        var price    = parseFloat(tr.querySelector('.price-input').value)   || 0;
        var discRaw  = parseFloat(tr.querySelector('.disc-input').value)    || 0;
        var discType = tr.querySelector('.disc-type').value;
        var base     = price * qty;
        var discVal  = discType === 'percentage' ? Math.min(discRaw,100)*base/100 : Math.min(discRaw,base);
        discVal      = Math.round(discVal * 100) / 100;
        subtotal += base;
        totalItemDisc += discVal;
    });

    var subtotalExclTax = subtotal - totalItemDisc;
    var ordDiscVal = (ordDiscType === 'percentage')
        ? Math.round(subtotalExclTax * Math.min(ordDiscRaw, 100) / 100 * 100) / 100
        : Math.round(Math.min(ordDiscRaw, subtotalExclTax) * 100) / 100;

    // Second pass to calculate taxes after order discount and push items
    rows.forEach(function(tr) {
        var qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        if (qty <= 0) return;
        var idx      = parseInt(tr.getAttribute('data-index'), 10);
        var sku      = skuData[idx];
        var price    = parseFloat(tr.querySelector('.price-input').value)   || 0;
        var taxPct   = parseFloat(tr.getAttribute('data-tax-pct')) || 0;
        var excisePu = parseFloat(tr.querySelector('.excise-input').value)  || 0;
        var addlExPu = parseFloat(tr.querySelector('.addlex-input').value)  || 0;
        var vatPct   = parseFloat(tr.querySelector('.vat-input').value)     || 0;
        var tcsPct   = parseFloat(tr.querySelector('.tcs-input').value)     || 0;
        var discRaw  = parseFloat(tr.querySelector('.disc-input').value)    || 0;
        var discType = tr.querySelector('.disc-type').value;
        var base     = price * qty;
        var discVal  = discType === 'percentage' ? Math.min(discRaw,100)*base/100 : Math.min(discRaw,base);
        discVal      = Math.round(discVal * 100) / 100;
        var afterItemDisc = base - discVal;

        // Distribute order discount proportionally
        var ordItemDisc = 0;
        if (ordDiscVal > 0 && subtotalExclTax > 0) {
            ordItemDisc = Math.round((ordDiscVal * afterItemDisc / subtotalExclTax) * 10000) / 10000;
        }
        var afterDisc = afterItemDisc - ordItemDisc;
        var exciseAmt = Math.round(excisePu * qty * 100) / 100;
        var addlExAmt = Math.round(addlExPu * qty * 100) / 100;
        var vatBase  = afterDisc + exciseAmt + addlExAmt;
        var vatAmt   = Math.round(vatBase * vatPct / 100 * 100) / 100;
        var landedAmt= afterDisc + exciseAmt + vatAmt;
        var tcsAmt   = Math.round(landedAmt * tcsPct / 100 * 100) / 100;
        var tax      = Math.round(afterDisc * taxPct / 100 * 100) / 100;
        var lineTotal= Math.round((landedAmt + tax + tcsAmt) * 100) / 100;
        items.push({
            sku_id: sku.sku_id, sku_name: sku.sku_name, sku_unit: sku.sku_unit,
            sku_unit_name: sku.sku_unit_name, sku_price: price, tax_pct: sku.tax_pct,
            excise_per_unit: excisePu, addl_excise_per_unit: addlExPu,
            vat_percentage: vatPct, tcs_percentage: tcsPct,
            qty: qty, disc_input: discRaw, disc_type: discType,
            disc_val: discVal, ord_item_disc: ordItemDisc,
            excise_amt: exciseAmt, addl_excise_amt: addlExAmt,
            vat_amt: vatAmt, tcs_amt: tcsAmt, tax_val: tax, line_total: lineTotal
        });
    });
    if (items.length === 0) { alert('Please enter quantity for at least one item.'); return; }

    var charges     = parseFloat(document.getElementById('chargesInput').value) || 0;
    var totalTaxVal   = items.reduce(function(s,it) { return s + (it.tax_val || 0); }, 0);
    var totalExciseAmt= items.reduce(function(s,it) { return s + (it.excise_amt||0); }, 0);
    var totalVat      = items.reduce(function(s,it) { return s + (it.vat_amt||0); }, 0);
    var totalTcs      = items.reduce(function(s,it) { return s + (it.tcs_amt||0); }, 0);
    var totalTax      = totalTaxVal + totalExciseAmt + totalVat + totalTcs;

    var subtotalInclTax = subtotal - totalItemDisc + totalTax;
    var ordDiscVal = (ordDiscType === 'percentage')
        ? Math.round(subtotalInclTax * Math.min(ordDiscRaw, 100) / 100 * 100) / 100
        : Math.round(Math.min(ordDiscRaw, subtotalInclTax) * 100) / 100;

    var totalDiscount = totalItemDisc + ordDiscVal;
    var totalExclTax  = subtotal - totalDiscount;
    var grand = Math.round((totalExclTax + totalTax + charges) * 100) / 100;

    var inv = document.getElementById('invoiceNo').value.trim();
    var date = document.getElementById('stockinDate').value;
    // Build SweetAlert2 confirmation popup
    var rows = items.map(function(it) {
        return '<tr>' +
            '<td>' + escHtml(it.sku_name) + '</td>' +
            '<td>' + escHtml(it.sku_unit_name) + '</td>' +
            '<td style="text-align:right;">' + fmt2(it.sku_price) + '</td>' +
            '<td style="text-align:right;">' + it.qty + '</td>' +
            '<td style="text-align:right;color:#c0392b;">' + fmt2(it.disc_val) + '</td>' +
            '<td style="text-align:right;">' + (it.excise_amt > 0 ? fmt2(it.excise_amt) : '—') + '</td>' +
            '<td style="text-align:right;">' + (it.addl_excise_amt > 0 ? fmt2(it.addl_excise_amt) : '—') + '</td>' +
            '<td style="text-align:right;">' + (it.vat_amt > 0 ? fmt2(it.vat_amt) : '—') + '</td>' +
            '<td style="text-align:right;">' + (it.tcs_amt > 0 ? fmt2(it.tcs_amt) : '—') + '</td>' +
            '<td style="text-align:right;color:#1a5276;">' + (it.tax_val > 0 ? fmt2(it.tax_val) + '<br><small>' + it.tax_pct + '%</small>' : '—') + '</td>' +
            '<td style="text-align:right;font-weight:600;">' + fmt2(it.line_total) + '</td>' +
        '</tr>';
    }).join('');

    var popup = '<table class="table table-bordered table-sm text-start" style="font-size:12px;">' +
        '<thead style="background:#f7f9fc;">' +
        '<tr><th>SKU</th><th>UOM</th><th>Price</th><th>Qty</th><th>Disc</th><th>Excise</th><th>Addl.Ex</th><th>VAT</th><th>TCS</th><th>Tax</th><th>Total</th></tr>' +
        '</thead><tbody>' + rows + '</tbody></table>' +
        '<div style="text-align:right;margin-top:12px;line-height:2;">' +
        '<div>Subtotal: <strong>' + fmt2(subtotal) + '</strong></div>' +
        '<div style="color:#c0392b;">Discount(order level/item level): -' + fmt2(totalDiscount) + '</div>' +
        '<div>Total: <strong>' + fmt2(totalExclTax) + '</strong></div>' +
        '<div style="color:#856404;">Tax Total: ' + fmt2(totalTax) + '</div>' +
        '<div style="font-size:11px;color:#666;line-height:1.4;"> GST Total: ' + fmt2(totalTaxVal) + '</div>' +
        '<div style="font-size:11px;color:#666;line-height:1.4;"> Excise Total: ' + fmt2(totalExciseAmt) + '</div>' +
        '<div style="font-size:11px;color:#666;line-height:1.4;"> VAT Total: ' + fmt2(totalVat) + '</div>' +
        '<div style="font-size:11px;color:#666;line-height:1.4;"> TCS Total: ' + fmt2(totalTcs) + '</div>' +
        (charges > 0 ? '<div style="color:#2980b9;">Charges: + ' + fmt2(charges) + '</div>' : '') +
        '<div><strong>Invoice No: ' + escHtml(inv) + '</strong></div>' +
        '<div><strong>Date: ' + escHtml(date) + '</strong></div>' +
        '<h5 style="margin-top:8px;color:#27ae60;"><strong>Grand Total: ' + fmt2(grand) + '</strong></h5>' +
        '</div>';

    Swal.fire({
        title: 'Confirm Direct Stockin',
        html: popup,
        icon: 'warning',
        width: '900px',
        showCancelButton: true,
        confirmButtonText: 'Confirm & Receive Stock',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        confirmButtonColor: '#27ae60'
    }).then(function(result) {
        if (!result.isConfirmed) return;

        showProgress();
        tickStep('step-indent');

        var fd = new FormData();
        var submitLocId = document.getElementById('locationPicker').value || '';
        fd.append('vendor_id',        document.getElementById('vendorSelect').value);
        fd.append('location_id',      submitLocId);
        fd.append('invoice_no',       inv);
        fd.append('stockin_date',     date);
        fd.append('charges',          document.getElementById('chargesInput').value || 0);
        fd.append('order_disc_value', document.getElementById('orderDiscInput').value || 0);
        fd.append('order_disc_type',  document.getElementById('orderDiscType').value);
        items.forEach(function(item, idx) {
            ['sku_id','sku_name','sku_unit','sku_unit_name','sku_price','tax_pct',
             'excise_per_unit','addl_excise_per_unit','vat_percentage','tcs_percentage',
             'qty','disc_input','disc_type']
                .forEach(function(k) { fd.append('items['+idx+']['+k+']', item[k]); });
        });

        var stepTimers = [];
        ['step-po','step-stockin','step-ledger'].forEach(function(s, i) {
            stepTimers.push(setTimeout(function() { tickStep(s); }, 900 * (i + 1)));
        });

        fetch('submit_direct_stockin', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                stepTimers.forEach(clearTimeout);
                if (data.success) {
                    ['step-indent','step-po','step-stockin','step-ledger'].forEach(completeStep);
                    setTimeout(function() { showSuccess(data); }, 400);
                } else {
                    showError(data.error || 'Unknown error occurred.');
                }
            })
            .catch(function() { stepTimers.forEach(clearTimeout); showError('Network error. Please try again.'); });
    });
});

function showProgress() {
    document.getElementById('phase-progress').style.display = 'flex';
    document.querySelectorAll('#stepList .step-icon .icon-inner').forEach(function(el) {
        el.textContent = '○'; el.parentElement.style.background = '#e9ecef';
    });
    document.querySelectorAll('#stepList .step-label').forEach(function(el) { el.style.color = '#7f8c8d'; });
    document.getElementById('successBox').style.display = 'none';
    document.getElementById('errorBox').style.display   = 'none';
}
function tickStep(stepId) {
    var s = document.getElementById(stepId); if (!s) return;
    s.querySelector('.icon-inner').textContent = '⟳';
    s.querySelector('.step-icon').style.background = '#fff3cd';
    s.querySelector('.step-label').style.color = '#856404';
    setTimeout(function() { completeStep(stepId); }, 800);
}
function completeStep(stepId) {
    var s = document.getElementById(stepId); if (!s) return;
    s.querySelector('.icon-inner').textContent = '✔';
    s.querySelector('.step-icon').style.background = '#d1e7dd';
    s.querySelector('.step-icon').style.color = '#0f5132';
    s.querySelector('.step-label').style.color = '#0f5132';
    s.querySelector('.step-label').style.fontWeight = '600';
}
function showSuccess(data) {
    document.getElementById('resIndentId').textContent  = data.indent_id;
    document.getElementById('resSerial').textContent    = data.serial_number;
    document.getElementById('resPoId').textContent      = data.po_id;
    document.getElementById('resStockinId').textContent = data.stockin_id;
    document.getElementById('successBox').style.display = 'block';
}
function showError(msg) {
    document.getElementById('errorMsg').textContent = msg;
    document.getElementById('errorBox').style.display = 'block';
}
function resetProgress() { document.getElementById('phase-progress').style.display = 'none'; }
function fmt2(n) { return parseFloat(n).toLocaleString('en-IN', { minimumFractionDigits:2, maximumFractionDigits:2 }); }
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

}); // end window.load
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#phase-progress { display: none; }
.qty-input:focus, .disc-input:focus { border-color: #3b5bdb; box-shadow: 0 0 0 2px rgba(59,91,219,.15); }
#itemsTable tbody tr:hover { background: #f8fbff; }
#submitBtn:not(:disabled) { background: #27ae60; border-color: #27ae60; }
#submitBtn:not(:disabled):hover { background: #219a52; }
</style>