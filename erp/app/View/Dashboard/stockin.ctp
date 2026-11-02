<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Stockin</h2>
        </div>
    </div>
    
    <?php if (!empty($indents[0]['inventory_purchase_order']['p_o_id'])){ ?>
        <div class="col-sm-2">
            <div class="page-title-box">
                <h2 class="page-title">
                    <a href="stockin">
                        <button class='btn btn-danger' type="button" style="float:right">Back</button>
                    </a>
                </h2>
            </div>
        </div>
    <?php } ?>
</div>

<div class="row">
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Pending Stockin</p>
                        <h3 class="my-3"><?php echo $processedpo ?? 0;?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Completed Stockin</p>
                        <h3 class="my-3"><?php echo $stockedinpo ?? 0;?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?= $this->Form->create('Search', ['url' => 'stockin']); ?>
                        <?= $this->Form->input('po', [
                            'div'      => ['class' => 'form-group'],
                            'label'    => 'Enter PO Id',
                            'class'    => 'form-control',
                            'required' => 'required',
                            'name'     => 'po',
                            'id'       => 'po',
                            'value'    => $id
                        ]); ?>
                        <?= $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit']); ?>
                        <?= $this->Form->end(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($indents[0]['inventory_purchase_order']['p_o_id'])): ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <?= $this->Form->input('Invoice No', ['label' => '<b>Invoice No</b>', 'class' => 'form-control', 'required' => true, 'id' => 'invoice_no']); ?>
                        </div>
                        <div class="col-sm-3">
                            <?php
                                $charge = $indents[0]['inventory_purchase_order']['processed_charge'];
                                echo $this->Form->input('charge', [
                                    'div'      => ['class' => 'form-group'],
                                    'id'       => 'charge',
                                    'label'    => '<b>Charges</b>',
                                    'class'    => 'form-control',
                                    'required' => true,
                                    'type'     => 'number',
                                    'value'    => $charge,
                                    'step'     => 'any'
                                ]);
                            ?>
                        </div>
						<?php $today = date('Y-m-d'); ?>	
						<div class="col-sm-3">
							<label>Select Date</label>
							<input type="date" id="stockin_date" name="stockin_date" class="form-control" max="<?= h($today) ?>" value="<?= h($today) ?>"  required>
						</div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-12">
                            <h4 class="page-title">Purchase Order Details</h4>
                        </div>
                        <div class="col-sm-3">
                            <?= $this->Form->input('po_ids', ['label' => 'PO Id', 'class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['p_o_id']]); ?>
                        </div>
                        <div class="col-sm-3">
                            <?= $this->Form->input('Indent Id', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['internal_indent_id']]); ?>
                        </div>
                        <div class="col-sm-3">
                            <?= $this->Form->input('Location', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['location_name']]); ?>
                        </div>
                        <div class="col-sm-3">
                            <?= $this->Form->input('Vendor Name', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['vendor_name']]); ?>
                        </div>
                        <div class="col-sm-2">
                            <?= '<br>'.$this->Form->input('Total Amount', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['total_amount']]); ?>
                        </div>
                        <div class="col-sm-2">
                            <?= '<br>'.$this->Form->input('Approved Amount', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['approved_amount']]); ?>
                        </div>
                        <div class="col-sm-2">
                            <?= '<br>'.$this->Form->input('Processed Amount', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['processed_amount']]); ?>
                        </div>
                        <div class="col-sm-3">
                            <?= '<br>'.$this->Form->input('PO Date', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['po_date']]); ?>
                        </div>
                        <?php /* UPDATED: show processed_discount_value as Total Discount reference */ ?>
                        <div class="col-sm-3">
                            <?= '<br>'.$this->Form->input('Total Discount', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_purchase_order']['processed_discount_value']]); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="page-title">Purchase Order Items</h4>

                    <div>
                    <table class="table table-bordered table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap" style="min-width:180px;">Name</th>
                                <th class="text-nowrap" style="min-width:90px;">Unit</th>
                                <th class="text-nowrap" style="min-width:100px;">Price</th>
                                <th class="text-nowrap" style="min-width:80px;">Tax</th>
                                <th class="text-nowrap" style="min-width:110px;">Approved Qty</th>
                                <th class="text-nowrap" style="min-width:110px;">Processed Qty</th>
                                <th class="text-nowrap" style="min-width:110px;">Stockin Qty</th>
                                <th class="text-nowrap" style="min-width:130px;">Discount Type</th>
                                <th class="text-nowrap" style="min-width:120px;">Discount</th>
                                <th class="text-nowrap" style="min-width:120px;">Excise ₹/unit</th>
                                <th class="text-nowrap" style="min-width:120px;">Addl. Excise ₹/unit</th>
                                <th class="text-nowrap" style="min-width:90px;" title="VAT % — irrecoverable, added to landed cost">VAT %</th>
                                <th class="text-nowrap" style="min-width:90px;" title="TCS % — recoverable advance, tracked separately">TCS %</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item):
                            $maxQty = $item['inventory_purchase_order_items']['processed_quantity'];

                            // Pre-fill discount type + raw input from process_discount_* columns
                            $processDiscType  = $item['inventory_purchase_order_items']['process_discount_type']  ?? 'flat';
                            $processDiscInput = $item['inventory_purchase_order_items']['process_discount']       ?? 0;
                            $skuId            = $item['inventory_purchase_order_items']['sku_id'];

                            // Read DIRECTLY from PO item columns (already have processed values)
                            $rowExciseApplicable  = !empty($item['inventory_skus']['excise_applicable'])  ? 1 : 0;
                            $rowHasVariableTax    = !empty($item['inventory_skus']['has_variable_tax'])   ? 1 : 0;
                            // Use PO item values directly — NO fallback to SKU
                            $rowExcisePerUnit     = floatval($item['inventory_purchase_order_items']['excise_per_unit'] ?? 0);
                            $rowAddlExcisePerUnit = floatval($item['inventory_purchase_order_items']['addl_excise_per_unit'] ?? 0);
                            $rowVatPct            = floatval($item['inventory_purchase_order_items']['vat_percentage'] ?? 0);
                            $rowTcsPct            = floatval($item['inventory_purchase_order_items']['tcs_percentage'] ?? 0);
                            // Excise readonly only if not applicable AND no variable tax
                            $exciseReadonly       = ($rowExciseApplicable || $rowHasVariableTax) ? '' : 'readonly style="background:#f5f5f5;color:#aaa;"';
                            // VAT and TCS always editable
                            $vatReadonly          = '';
                            $tcsReadonly          = '';
                        ?>
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" readonly value="<?= h($item['inventory_purchase_order_items']['sku_name']) ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" readonly value="<?= h($item['inventory_purchase_order_items']['sku_unit_name']) ?>"></td>
                                <td><input type="text" class="form-control form-control-sm sku-price" readonly value="<?= h($item['inventory_purchase_order_items']['sku_price']) ?>"></td>
                                <td><input type="text" class="form-control form-control-sm sku-tax" readonly value="<?= h($item['inventory_purchase_order_items']['sku_total_tax_percentage'] . ' %') ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" readonly value="<?= h($item['inventory_purchase_order_items']['approved_quantity']) ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" readonly value="<?= h($item['inventory_purchase_order_items']['processed_quantity']) ?>"></td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm stockin-qty"
                                           name="quantity[<?= $skuId ?>]"
                                           value="<?= $maxQty ?>"
                                           max="<?= $maxQty ?>"
                                           data-max="<?= $maxQty ?>"
                                           step="0.01"
                                           style="min-width:90px;" />
                                </td>
                                <?php /* UPDATED: Discount Type select — pre-filled from process_discount_type */ ?>
                                <td>
                                    <select class="form-control form-control-sm stockin-discount-type"
                                            name="discount_type[<?= $skuId ?>]"
                                            style="min-width:110px;">
                                        <option value="flat"       <?= $processDiscType === 'flat'       ? 'selected' : '' ?>>Flat</option>
                                        <option value="percentage" <?= $processDiscType === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm stockin-discount"
                                           name="discount[<?= $skuId ?>]"
                                           value="<?= h($processDiscInput) ?>"
                                           step="0.01"
                                           min="0"
                                           style="min-width:100px;" />
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm stockin-excise"
                                           name="excise_per_unit[<?= $skuId ?>]"
                                           value="<?= $rowExcisePerUnit ?>"
                                           step="any" min="0"
                                           <?= $exciseReadonly ?>
                                           style="min-width:100px;" />
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm stockin-addl-excise"
                                           name="addl_excise_per_unit[<?= $skuId ?>]"
                                           value="<?= $rowAddlExcisePerUnit ?>"
                                           step="any" min="0"
                                           <?= $exciseReadonly ?>
                                           style="min-width:100px;" />
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm stockin-vat-pct"
                                           name="vat_percentage[<?= $skuId ?>]"
                                           value="<?= $rowVatPct ?>"
                                           step="any" min="0" max="100"
                                           style="min-width:75px;"
                                           title="VAT% — adds to landed cost" />
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm stockin-tcs-pct"
                                           name="tcs_percentage[<?= $skuId ?>]"
                                           value="<?= $rowTcsPct ?>"
                                           step="any" min="0" max="100"
                                           style="min-width:75px;"
                                           title="TCS% — tracked separately" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div><!-- end overflow-x wrapper -->

                    <div class="text-right mt-3">
                        <?php
                            $poId          = $indents[0]['inventory_purchase_order']['p_o_id'];
                            $status        = $indents[0]['inventory_purchase_order']['status'];
                            $buttonClass   = $status == 3 ? 'btn-success' : 'btn-danger';
                            $buttonText    = $status == 3 ? 'Stockin' : 'Already Stocked In';
                            $buttonDisabled = $status == 3 ? '' : 'disabled';
                            echo "<button class='btn $buttonClass mark-status' data-id='$poId' $buttonDisabled>$buttonText</button>";
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($id): ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="page-title text-danger"><?php echo $po_error;?></h4>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>

/*
 * UPDATED: showStockinConfirmation now reads stockin-discount-type per row
 * and applies flat vs percentage logic — mirrors create_po / approve_po / process_po exactly.
 */
function showStockinConfirmation(stockin_date, charge, onConfirm) {

    let html = `
        <table class="table table-bordered table-sm text-center">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Base</th>
                    <th>Disc Type</th>
                    <th>Discount</th>
                    <th>Tax %</th>
                    <th>Tax Amount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    let baseSum     = 0;
    let taxSum             = 0;
    let discountSum        = 0;
    let totalSum           = 0;
    let exciseTotalVal     = 0;
    let addlExciseTotalVal = 0;
    let vatTotalVal        = 0;
    let tcsTotalVal        = 0;
    let hasQty             = false;

    $('.stockin-qty').each(function () {

        const qty = parseFloat($(this).val()) || 0;
        if (qty <= 0) return;

        hasQty = true;

        // UPDATED: traverse the parent <tr> to find sibling cells
        const row = $(this).closest('tr');

        const sku        = row.find('.sku-price').closest('td').prev('td').find('input').val()
                        || row.find('td').eq(0).find('input').val();
        const price      = parseFloat(row.find('.sku-price').val()) || 0;
        const taxStr     = row.find('.sku-tax').val() || '';
        const taxPercent = parseFloat(taxStr) || 0;

        // UPDATED: read discount type from the per-row select
        const discType   = row.find('.stockin-discount-type').val() || 'flat';
        let   discInput  = parseFloat(row.find('.stockin-discount').val()) || 0;

        const baseAmount = price * qty;

        // UPDATED: flat vs percentage logic — mirrors all other stages exactly
        let discountValue = 0;
        if (discType === 'percentage') {
            if (discInput > 100) discInput = 100;
            discountValue = (baseAmount * discInput) / 100;
        } else {
            if (discInput > baseAmount) discInput = baseAmount;
            discountValue = discInput;
        }

        const afterDiscount = baseAmount - discountValue;

        // Read excise/VAT/TCS for this row
        const excisePu   = parseFloat(row.find('.stockin-excise').val())      || 0;
        const addlExcPu  = parseFloat(row.find('.stockin-addl-excise').val()) || 0;
        const vatPct     = parseFloat(row.find('.stockin-vat-pct').val())     || 0;
        const tcsPct     = parseFloat(row.find('.stockin-tcs-pct').val())     || 0;

        // Zero out GST when excise/VAT applies (state excise replaces GST)
        const effectiveTaxPct = (excisePu > 0 || vatPct > 0) ? 0 : taxPercent;

        const exciseAmt  = Math.round(excisePu  * qty * 100) / 100;
        const addlExAmt  = Math.round(addlExcPu * qty * 100) / 100;
        const vatBase    = afterDiscount + exciseAmt + addlExAmt;
        const vatAmt     = Math.round(vatBase * vatPct / 100 * 100) / 100;
        // TCS = (base_after_disc + excise + VAT) x TCS%
        const tcsAmt     = Math.round((afterDiscount + exciseAmt + vatAmt) * tcsPct / 100 * 100) / 100;
        const landed     = afterDiscount + exciseAmt + vatAmt;
        const tax        = Math.round(landed * effectiveTaxPct / 100 * 100) / 100;
        const total      = Math.round((landed + tax + tcsAmt) * 100) / 100;

        baseSum            += baseAmount;
        discountSum        += discountValue;
        exciseTotalVal     += exciseAmt;
        addlExciseTotalVal += addlExAmt;
        vatTotalVal        += vatAmt;
        tcsTotalVal        += tcsAmt;
        taxSum             += tax;
        totalSum           += total;

        html += `
            <tr>
                <td>${sku}</td>
                <td>₹${price.toFixed(2)}</td>
                <td><b>${qty}</b></td>
                <td>₹${baseAmount.toFixed(2)}</td>
                <td>${discType === 'percentage' ? 'Percentage' : 'Flat'}</td>
                <td>${discType === 'percentage' ? discInput + '%' : '₹' + discountValue.toFixed(2)}</td>
                <td>${effectiveTaxPct.toFixed(2)}%</td>
                <td>₹${tax.toFixed(2)}</td>
                <td><b>₹${total.toFixed(2)}</b></td>
            </tr>
        `;
    });

    if (!hasQty) {
        Swal.fire({ icon: 'warning', title: 'No Quantity Entered', text: 'Please enter at least one stock-in quantity.' });
        return;
    }

    // excise/VAT/TCS totals are already accumulated in the per-row loop above
    const chargeVal  = parseFloat(charge) || 0;
    // Grand total = sum of all row totals (each already includes excise+VAT+TCS+GST) + charge
    const grandTotal = totalSum + chargeVal;

    html += `
            </tbody>
        </table>
        <div class="text-end mt-3">
			<b>Stockin Date : ${stockin_date}<br>
            <b>Base Total :</b> ₹${baseSum.toFixed(2)}<br>
            <b>Total Discount :</b> ₹${discountSum.toFixed(2)}<br>
            <b>Tax Total (GST) :</b> ₹${taxSum.toFixed(2)}<br>
            ${exciseTotalVal > 0 ? `<b>Excise Duty Total :</b> ₹${exciseTotalVal.toFixed(2)}<br>` : ''}
            ${addlExciseTotalVal > 0 ? `<b>Addl. Excise Total :</b> ₹${addlExciseTotalVal.toFixed(2)}<br>` : ''}
            ${vatTotalVal > 0 ? `<b>VAT Total (in landed cost) :</b> ₹${vatTotalVal.toFixed(2)}<br>` : ''}
            ${tcsTotalVal > 0 ? `<b>TCS Total (recoverable) :</b> ₹${tcsTotalVal.toFixed(2)}<br>` : ''}
            <b>Charges :</b> ₹${chargeVal.toFixed(2)}<br>
            <hr>
            <h5><b>Grand Total : ₹${grandTotal.toFixed(2)}</b></h5>
        </div>
    `;

    Swal.fire({
        title: 'Confirm Stock-in',
        html: html,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        width: '1000px'
    }).then((result) => {
        if (result.isConfirmed) { onConfirm(); }
    });
}

$(document).ready(function () {

    // UPDATED: discount validation considers discount_type per row
    $(document).on('input', '.stockin-discount', function () {

        const row        = $(this).closest('tr');
        const price      = parseFloat(row.find('.sku-price').val()) || 0;
        const qty        = parseFloat(row.find('.stockin-qty').val()) || 0;
        const baseAmount = price * qty;

        // UPDATED: read discount type to apply correct cap
        const discType   = row.find('.stockin-discount-type').val() || 'flat';
        let   discVal    = parseFloat($(this).val()) || 0;

        if (discType === 'percentage') {
            if (discVal > 100) {
                Swal.fire({ toast: true, icon: 'warning', title: 'Percentage discount cannot exceed 100%', showConfirmButton: false, timer: 2000 });
                $(this).val(100);
            }
        } else {
            if (discVal > baseAmount) {
                Swal.fire({ toast: true, icon: 'warning', title: `Discount cannot exceed ₹${baseAmount.toFixed(2)}`, showConfirmButton: false, timer: 2000 });
                $(this).val(baseAmount.toFixed(2));
            }
        }

        if (discVal < 0) { $(this).val(0); }
    });

    // UPDATED: re-validate discount when type changes
    $(document).on('change', '.stockin-discount-type', function () {
        $(this).closest('tr').find('.stockin-discount').trigger('input');
    });

    // Quantity restriction: cap at processed_quantity
    $(document).on('input', '.stockin-qty', function () {
        const max = parseFloat($(this).data('max'));
        let   val = parseFloat($(this).val());
        if (val > max) {
            Swal.fire({ icon: 'warning', title: 'Limit Exceeded', text: `You can't enter more than ${max} units.`, timer: 2000, showConfirmButton: false });
            $(this).val(max);
        } else if (val < 0) {
            $(this).val(0);
        }
    });

    // Submit stock-in
    $(document).on('click', '.mark-status', function () {
        
        const btn = $(this); // store button reference
        // 🚫 prevent multiple clicks
        if (btn.prop('disabled')) return;
        
        const poId      = $(this).data('id');
        const invoiceNo    = $('#invoice_no').val().trim();
        const charge       = parseFloat($('#charge').val()) || 0;
		const stockin_date = $('#stockin_date').val();

        if (!invoiceNo) {
            Swal.fire('Missing Invoice', 'Please enter invoice number', 'warning');
            return;
        }

        const quantities        = {};
        const discounts         = {};
        const discount_types    = {};
        const excise_per_units  = {};
        const addl_excise_units = {};
        const vat_per_units     = {};
        const tcs_per_units     = {};

        $('.stockin-qty').each(function () {
            const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            quantities[id] = $(this).val();
        });
        $('.stockin-discount').each(function () {
            const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            discounts[id] = $(this).val();
        });
        $('.stockin-discount-type').each(function () {
            const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            discount_types[id] = $(this).val();
        });
        $('.stockin-excise').each(function () {
            const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            excise_per_units[id] = $(this).val();
        });
        $('.stockin-addl-excise').each(function () {
            const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            addl_excise_units[id] = $(this).val();
        });
        $('.stockin-vat-pct').each(function () {
            const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            vat_per_units[id] = $(this).val();
        });
        $('.stockin-tcs-pct').each(function () {
            const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            tcs_per_units[id] = $(this).val();
        });

        showStockinConfirmation(stockin_date, charge, function () {
            
            // ✅ disable button + change text
            btn.prop('disabled', true).text('Processing...');
    
            // ✅ show loader popup
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait, stock-in in progress',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: '<?= Router::url(['controller' => 'Dashboard', 'action' => 'stockin_po']) ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    _id:                  poId,
                    _invoice:             invoiceNo,
                    _charge:              charge,
                    _quantities:          quantities,
                    _discounts:           discounts,
                    _discount_types:      discount_types,
                    _excise_per_units:    excise_per_units,
                    _addl_excise_units:   addl_excise_units,
                    _vat_per_units:       vat_per_units,
                    _tcs_per_units:       tcs_per_units,
                    _stockin_date:        stockin_date
                },
                success: function (res) {
                    Swal.close(); // close loader

                    if (res.status) {
                        Swal.fire({ icon: 'success', title: 'Success', text: 'Stock-in successful!', confirmButtonText: 'OK' })
                            .then(() => { window.location.href = 'po_list'; });
                    } else {
                        btn.prop('disabled', false).text('Stockin'); // re-enable
                        Swal.fire('Failed', 'Stock-in failed.', 'error');
                    }
                },
                error: function () {
                    Swal.close();
                    btn.prop('disabled', false).text('Stockin'); // re-enable
                    Swal.fire('Error', 'Something went wrong.', 'error');
                }
            });

        });
    });

});
</script>
