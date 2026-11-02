<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Stockin</h2>
        </div>
    </div>
    
    <?php if (!empty($indents[0]['inventory_indent']['consignment_id'])){ ?>
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
                        <p class="text-dark font-weight-semibold font-14">Submitted Indent</p>
                        <h3 class="my-3"><?php echo $submitted_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col-->
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Approved Indent</p>
                        <h3 class="my-3"><?php echo $approved_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col-->
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Pending Indent Stockin</p>
                        <h3 class="my-3"><?php echo $processed_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col--> 
    
    <div class="col-md-4 col-lg-3">
        <div class="card report-card">
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <p class="text-dark font-weight-semibold font-14">Completed Indent Stockin</p>
                        <h3 class="my-3"><?php echo $stockedin_indent ?? 0;?></h3>
                    </div>
                </div>
            </div><!--end card-body--> 
        </div><!--end card--> 
    </div> <!--end col--> 
</div>

<?php
$isConsignment = !empty($indents[0]['inventory_indent']['consignment_id']);
$isPO = !empty($indents[0]['inventory_purchase_order']['p_o_id']);

$typeLabel = $isConsignment ? 'Consignment' : 'Purchase Order';

if ($isConsignment) {

    $header = $indents[0]['inventory_indent'];
    $refId  = $header['consignment_id'];

    $itemsData = array_map(function($i){
        return [
            'sku_id' => $i['inventory_indent_items']['sku_id'],
            'name'   => $i['inventory_indent_items']['sku_name'],
            'unit'   => $i['inventory_indent_items']['sku_unit_name'],
            'price'  => $i['inventory_indent_items']['approved_price'],
            'tax'    => $i['inventory_indent_items']['sku_total_tax_percentage'],
            'qty'    => $i['inventory_indent_items']['processed_quantity'],
            'type'   => $i['inventory_indent_items']['item_type']
        ];
    }, $items);

} else {

    $header = $indents[0]['inventory_purchase_order'];
    $charge  = $header['processed_charge'];
    $refId  = $header['p_o_id'];

    $itemsData = array_map(function($i){
        return [
            'sku_id'         => $i['inventory_purchase_order_items']['sku_id'],
            'name'           => $i['inventory_purchase_order_items']['sku_name'],
            'unit'           => $i['inventory_purchase_order_items']['sku_unit_name'],
            'price'          => $i['inventory_purchase_order_items']['sku_price'],
            'tax'            => $i['inventory_purchase_order_items']['sku_total_tax_percentage'],
            'qty'            => $i['inventory_purchase_order_items']['processed_quantity'],
            'stockin_qty'    => $i['inventory_purchase_order_items']['stockin_quantity'],
            'type'           => 'po',
            // UPDATED: pre-fill from process_discount_* instead of sku_discount_*
            'discount_type'      => $i['inventory_purchase_order_items']['process_discount_type']  ?? 'flat',
            'discount_value'     => $i['inventory_purchase_order_items']['process_discount']       ?? 0,
            'excise_applicable'   => !empty($i['inventory_skus']['excise_applicable']),
            'has_variable_tax'    => !empty($i['inventory_skus']['has_variable_tax']),
            // UPDATED: Prioritize values from processed PO (inventory_purchase_order_items)
            'excise_per_unit'     => floatval($i['inventory_purchase_order_items']['excise_per_unit'] 
                                        ?? $i[0]['excise_per_unit'] 
                                        ?? $i['inventory_skus']['excise_per_unit'] 
                                        ?? 0),
            'addl_excise_per_unit'=> floatval($i['inventory_purchase_order_items']['addl_excise_per_unit'] 
                                        ?? $i[0]['addl_excise_per_unit'] 
                                        ?? $i['inventory_skus']['addl_excise_per_unit'] 
                                        ?? 0),
            'vat_percentage'      => floatval($i['inventory_purchase_order_items']['vat_percentage'] 
                                        ?? $i[0]['vat_percentage'] 
                                        ?? 0),
            'tcs_percentage'      => floatval($i['inventory_purchase_order_items']['tcs_percentage'] 
                                        ?? $i[0]['tcs_percentage'] 
                                        ?? 0),
        ];
    }, $items);
}

?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?= $this->Form->create('Search', ['url' => 'stockin']); ?>
                        <?php
                        
                            $options = [
                                0 => 'Consignment',
                                1 => 'PO'
                            ];
                            
                            echo $this->Form->input('type', [
                                'div' => ['class' => 'form-group'],
                                'label' => 'Select type',
                                'class' => 'form-control',
                                'between' => '<span class="desc"></span><div class="controls">',
                                'after' => '</div>',
                                'options' => $options,
                                'id' => 'type'
                            ]);

                        ?>
                        
                        <!-- Consignment Dropdown -->
                        <div id="consignment_div">
                        <?= $this->Form->input('consignment_id', [
                            'label' => 'Select Consignment',
                            'class' => 'form-control',
                            'options' => $consignmentList ?? [],
                            'empty' => 'Select Consignment ID',
                            'id' => 'consignment_id'
                        ]); ?>
                        </div>
                        
                        <!-- PO Dropdown -->
                        <div id="po_div" style="display:none;">
                        <?= $this->Form->input('po_id', [
                            'label' => 'Select PO',
                            'class' => 'form-control',
                            'options' => $poList ?? [],
                            'empty' => 'Select PO ID',
                            'id' => 'po_id'
                        ]); ?>
                        </div><br>
                        <?= $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit']); ?>
                        <?= $this->Form->end(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php if(!empty($isConsignment) || !empty($isPO)){ ?>
<div class="card">
<div class="card-body">

<h4 class="page-title"><?= $typeLabel ?> Details</h4>

<div class="row mb-3">
    <div class="col-md-3">
        <?= $this->Form->input('Invoice No', [
            'class'=>'form-control',
            'required'=>true,
            'id'=>'invoice_no'
        ]) ?>
    </div>
<?php if(!empty($isPO)){?>
    <div class="col-md-3">
        <?= $this->Form->input('Charge', [
            'class'=>'form-control',
            'required'=>true,
            'value'=>$charge,
            'id'=>'processed_charge'
        ]) ?>
    </div>
<?php }?>

<?php $today = date('Y-m-d'); ?>
<?php if(!empty($isConsignment) || !empty($isPO)){?>
    <div class="col-sm-2">
        <label>Select Date</label>
        <input type="date" id="po_date" name="po_date" class="form-control" max="<?= h($today) ?>" value="<?= h($today) ?>"  required>
    </div>
<?php }?>
</div>

<?php if(!empty($isConsignment)){?>
    <div class="form-group row">
        
        <div class="col-sm-2">
            <?= $this->Form->input('po_ids', ['label' => 'Consignment Id', 'class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_indent']['consignment_id']]); ?>
        </div>
        <div class="col-sm-2">
            <?= $this->Form->input('Indent Id', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_indent']['indent_id']]); ?>
        </div>
        <div class="col-sm-2">
            <?= $this->Form->input('Location', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_indent']['location_name']]); ?>
        </div>
        <div class="col-sm-2">
            <?= $this->Form->input('Processed Date', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_indent']['processed_date']]); ?>
        </div>
        <div class="col-sm-2">
            <?= $this->Form->input('Total Amount', ['class' => 'form-control', 'readonly' => 'readonly', 'value' => $indents[0]['inventory_indent']['processed_amount']]); ?>
        </div>
    </div>
    
<?php }?>



<h4 class="page-title"><?= $typeLabel ?> Items</h4>

<div class="table-responsive">
<table class="table table-bordered" id="example">
<thead>
<tr>
    <th>Name</th>
    <th>Unit</th>
    <th>Price</th>
    <th>Tax %</th>
    <th>Processed Qty</th>
    <th>Balance Qty</th>
    <th>
        Qty <br>
        <input type="checkbox" id="select_all_qty" checked>
    </th>
    <?php if(!empty($isPO)){ ?>
    <th>Discount Type</th>
    <th>Discount Value</th>
    <th title="Excise ₹/unit">Excise ₹/unit</th>
    <th title="Addl. Excise ₹/unit">Addl. Excise ₹/unit</th>
    <th title="VAT% — added to landed cost">VAT %</th>
    <th title="TCS% — tracked separately">TCS %</th>
    <?php } ?>
</tr>
</thead>

<tbody>

<?php foreach ($itemsData as $row): 
    $qty_left = isset($row['stockin_qty']) ? $row['qty'] - $row['stockin_qty'] : $row['qty'];
?>
<tr class="item-row">

    <td><input class="form-control name" readonly value="<?= h($row['name']) ?>"></td>

    <td><input class="form-control unit" readonly value="<?= h($row['unit']) ?>"></td>

    <td><input class="form-control price" type="number" step="any" min="0" name="price[<?= $row['sku_id'] ?>]" value="<?= h($row['price']) ?>"></td>

    <td><input class="form-control tax" readonly value="<?= h($row['tax']) ?>"></td>

    <td><input class="form-control processed" readonly value="<?= h($row['qty']) ?>"></td>

    <td><input class="form-control" readonly value="<?= h($qty_left) ?>"></td>

    <td>
        <input type="number"
               class="form-control stockin-qty"
               name="quantity[<?= $row['sku_id'] ?>]"
               value="<?= $qty_left ?>"
               max="<?= $qty_left ?>"  
               data-max="<?= $qty_left ?>"
               step="any">
    </td>

    <?php if(!empty($isPO)){ ?>
    <td>
        <select class="form-control discount-type" name="discount_type[<?= $row['sku_id'] ?>]">
            <option value="flat"       <?= (($row['discount_type'] ?? 'flat') === 'flat')       ? 'selected' : '' ?>>Flat</option>
            <option value="percentage" <?= (($row['discount_type'] ?? 'flat') === 'percentage') ? 'selected' : '' ?>>Percentage</option>
        </select>
    </td>
    <td>
        <input type="number"
               class="form-control discount-value"
               name="discount_value[<?= $row['sku_id'] ?>]"
               value="<?= h($row['discount_value'] ?? 0) ?>"
               min="0"
               step="any">
    </td>
    <td>
        <?php
            $excRo  = (empty($row['excise_applicable']) && empty($row['has_variable_tax'])) ? 'readonly style="background:#f5f5f5;color:#aaa;"' : '';
            // UPDATED: VAT and TCS always editable as requested
            $vatRo  = ''; 
            $tcsRo  = '';
        ?>
        <input type="number"
               class="form-control stockin-excise"
               name="excise_per_unit[<?= $row['sku_id'] ?>]"
               value="<?= h($row['excise_per_unit'] ?? 0) ?>"
               min="0" step="any" <?= $excRo ?>>
    </td>
    <td>
        <input type="number"
               class="form-control stockin-addl-excise"
               name="addl_excise_per_unit[<?= $row['sku_id'] ?>]"
               value="<?= h($row['addl_excise_per_unit'] ?? 0) ?>"
               min="0" step="any" <?= $excRo ?>>
    </td>
    <td>
        <input type="number"
               class="form-control stockin-vat-pct"
               name="vat_per_unit[<?= $row['sku_id'] ?>]"
               value="<?= h($row['vat_percentage'] ?? 0) ?>"
               min="0" step="any" <?= $vatRo ?>
               title="VAT% — adds to landed cost">
    </td>
    <td>
        <input type="number"
               class="form-control stockin-tcs-pct"
               name="tcs_per_unit[<?= $row['sku_id'] ?>]"
               value="<?= h($row['tcs_percentage'] ?? 0) ?>"
               min="0" step="any" <?= $tcsRo ?>
               title="TCS% — tracked separately">
    </td>
    <?php } ?>

    <input type="hidden" name="item_type[<?= $row['sku_id'] ?>]" value="<?= $row['type'] ?>">
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>


<div class="text-right mt-3">
<button type="button"
        class="btn btn-success mark-status"
        data-id="<?= $refId ?>"
        data-type="<?= $isPO ? 'po' : 'consignment' ?>"
        data-charge="<?= $charge ?? 0 ?>">
    Stockin
</button>

</div>

</div>
</div>
<?php }?>

<?php if ($id): ?>
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

/* -------------------------
   Select All Qty Toggle
------------------------- */
$(document).on('change', '#select_all_qty', function(){

    let isChecked = $(this).is(':checked');

    $('.stockin-qty').each(function(){

        let max = parseFloat($(this).data('max')) || 0;

        if(isChecked){
            $(this).val(max);
        }else{
            $(this).val(0);
        }

    });

});

$(document).on('input', '.stockin-qty', function(){

    let allMatch = true;

    $('.stockin-qty').each(function(){
        let max = parseFloat($(this).data('max')) || 0;
        let val = parseFloat($(this).val()) || 0;

        if(val !== max){
            allMatch = false;
        }
    });

    $('#select_all_qty').prop('checked', allMatch);
});
    // document.addEventListener('DOMContentLoaded', function () {
    //     const typeSelect = document.getElementById('type');
    //     const label = document.getElementById('po_label');
    
    //     function updateLabel() {
    //         if (typeSelect.value == '0') {
    //             label.innerText = 'Enter Consignment ID';
    //         } else {
    //             label.innerText = 'Enter PO No';
    //         }
    //     }
    
    //     updateLabel(); // on page load
    //     typeSelect.addEventListener('change', updateLabel);
    // });
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('type');
        const consignmentDiv = document.getElementById('consignment_div');
        const poDiv = document.getElementById('po_div');
    
        function toggleFields() {
            if (typeSelect.value == '0') {
                consignmentDiv.style.display = 'block';
                poDiv.style.display = 'none';
            } else {
                consignmentDiv.style.display = 'none';
                poDiv.style.display = 'block';
            }
        }
    
        toggleFields(); // on load
        typeSelect.addEventListener('change', toggleFields);
    });

    
    /* -------------------------
       Quantity validation
    ------------------------- */
    $(document).on('input','.stockin-qty',function(){
    
        const max = parseFloat($(this).data('max'));
        let val = parseFloat($(this).val());
    
        if(val > max){
            Swal.fire('Limit exceeded',`Max allowed ${max}`,'warning');
            $(this).val(max);
        }
    
        if(val < 0) $(this).val(0);
    });

    /* -------------------------
       Discount value validation
    ------------------------- */
    $(document).on('input', '.discount-value', function () {
        const row        = $(this).closest('tr');
        const price      = parseFloat(row.find('.price').val())      || 0;
        const qty        = parseFloat(row.find('.stockin-qty').val()) || 0;
        const discType   = row.find('.discount-type').val();
        const baseAmount = price * qty;
        let   val        = parseFloat($(this).val()) || 0;

        if (discType === 'percentage' && val > 100) {
            Swal.fire('Invalid Discount', 'Percentage cannot exceed 100%', 'warning');
            $(this).val(100);
        } else if (discType === 'flat' && baseAmount > 0 && val > baseAmount) {
            Swal.fire('Invalid Discount', `Flat discount cannot exceed ₹${baseAmount.toFixed(2)}`, 'warning');
            $(this).val(baseAmount.toFixed(2));
        }
        if (val < 0) $(this).val(0);
    });


    $(document).on('click','.mark-status',function(e){
        e.preventDefault();
    
        const poId = $(this).data('id');
        const type = $(this).data('type');
        const invoiceNo = $('#invoice_no').val().trim();
        const charge    = $('#processed_charge').length
            ? parseFloat($('#processed_charge').val()) || 0
            : 0;
        
        const poDate = $('#po_date').length ? $('#po_date').val() : null;

        if(!invoiceNo){
            Swal.fire('Missing Invoice','Enter invoice number','warning');
            return;
        }
    
        let baseSum=0, taxSum=0, totalSum=0;
        let discountSum = 0;
        let exciseTotalVal = 0, vatTotalVal = 0, tcsTotalVal = 0;

        let html = `<table class="table table-sm table-bordered text-center" style="font-size:12px;">
            <thead>
            <tr>
            <th>SKU</th>
            <th>Price</th>
            <th>Qty</th>`;

        if(type === 'po'){
            html += `<th>Disc Type</th><th>Disc Amt</th><th>Excise</th><th>Addl.Ex</th><th>VAT</th><th>TCS</th>`;
        }

        html += `<th>Tax%</th>
            <th>Tax Amt</th>
            <th>Line Total</th>
            </tr>
            </thead>
        <tbody>`;
    
        const quantities={}, itemTypes={}, itemPrices={};
        const discountTypes  = {};
        const discountValues = {};

        const table = $('#example').DataTable();
        
        table.rows().every(function (){
            const row   = $(this.node());
        
            const name     = row.find('.name').val();
            const price    = parseFloat(row.find('.price').val())||0;
            const tax      = parseFloat(row.find('.tax').val())||0;
            const qty      = parseFloat(row.find('.stockin-qty').val())||0;
            const excisePu = type === 'po' ? (parseFloat(row.find('.stockin-excise').val())      || 0) : 0;
            const addlExPu = type === 'po' ? (parseFloat(row.find('.stockin-addl-excise').val()) || 0) : 0;
            const vatPct   = type === 'po' ? (parseFloat(row.find('.stockin-vat-pct').val())     || 0) : 0;
            const tcsPct   = type === 'po' ? (parseFloat(row.find('.stockin-tcs-pct').val())     || 0) : 0;
            const effectiveTax = (excisePu > 0 || vatPct > 0) ? 0 : tax; // excise/VAT replaces legacy GST
        
            const id = row.find('.stockin-qty').attr('name').match(/\[(.*?)\]/)[1];
        
            const base = price * qty;

            const discType = row.find('.discount-type').length ? row.find('.discount-type').val() : 'flat';
            const rawDisc  = row.find('.discount-value').length ? parseFloat(row.find('.discount-value').val()) || 0 : 0;

            let discAmt = 0;
            if (discType === 'percentage') {
                discAmt = Math.round((base * rawDisc / 100) * 100) / 100;
            } else {
                discAmt = rawDisc;
            }
            if (discAmt > base) discAmt = base;

            const afterDisc   = base - discAmt;
            const exciseAmt   = Math.round(excisePu * qty * 100) / 100;
            const addlExAmt   = Math.round(addlExPu * qty * 100) / 100;
            // VAT = (base + excise + addl_excise) × VAT%
            const vatBase     = afterDisc + exciseAmt + addlExAmt;
            const vatAmt      = Math.round(vatBase * vatPct / 100 * 100) / 100;
            // TCS = (base + excise + VAT) × TCS%
            const tcsAmt      = Math.round((afterDisc + exciseAmt + vatAmt) * tcsPct / 100 * 100) / 100;
            // Landed = base + excise + VAT
            const landed      = afterDisc + exciseAmt + vatAmt;
            const taxAmt      = Math.round(landed * effectiveTax / 100 * 100) / 100;
            const total       = Math.round((landed + taxAmt + tcsAmt) * 100) / 100;
        
            baseSum         += base;
            discountSum     += discAmt;
            exciseTotalVal  += exciseAmt + addlExAmt;
            vatTotalVal     += vatAmt;
            tcsTotalVal     += tcsAmt;
            taxSum          += taxAmt;
            totalSum        += total;
        
            quantities[id] = qty;
            itemTypes[id]  = $(`input[name="item_type[${id}]"]`).val();
            itemPrices[id] = price;
            discountTypes[id]  = discType;
            discountValues[id] = rawDisc;

            html += `<tr>
                <td>${name}</td>
                <td>${price.toFixed(2)}</td>
                <td>${qty}</td>`;

            if(type === 'po'){
                html += `<td>${discType} ${rawDisc > 0 ? rawDisc : ''}</td>
                         <td>₹${discAmt.toFixed(2)}</td>
                         <td>${exciseAmt > 0 ? '₹'+exciseAmt.toFixed(2) : '—'}</td>
                         <td>${addlExAmt > 0 ? '₹'+addlExAmt.toFixed(2) : '—'}</td>
                         <td>${vatAmt > 0 ? '₹'+vatAmt.toFixed(2) : '—'}</td>
                         <td>${tcsAmt > 0 ? '₹'+tcsAmt.toFixed(2) : '—'}</td>`;
            }

            html += `<td>${tax}%</td>
                <td>₹${taxAmt.toFixed(2)}</td>
                <td><b>₹${total.toFixed(2)}</b></td>
            </tr>`;
        });

        let grandTotal = totalSum;
        
        if(type === 'po'){
            grandTotal += charge;
        }

        html+=`</tbody></table>
        <hr>
        <div style="text-align:center;font-size:15px">
        <b>Base Total :</b> ₹${baseSum.toFixed(2)}<br>`;

        if(type === 'po'){
            html += `<b>Total Discount :</b> ₹${discountSum.toFixed(2)}<br>`;
        }
    
        if(type === 'po'){
            if(exciseTotalVal > 0) html+=`<b>Excise Total (incl. Addl.) :</b> ₹${exciseTotalVal.toFixed(2)}<br>`;
            if(vatTotalVal > 0)    html+=`<b>VAT Total (in landed cost) :</b> ₹${vatTotalVal.toFixed(2)}<br>`;
            if(tcsTotalVal > 0)    html+=`<b>TCS Total (recoverable advance) :</b> ₹${tcsTotalVal.toFixed(2)}<br>`;
            html+=`<b>Charge :</b> ₹${charge.toFixed(2)}<br>`;
        }
    
        html += `<b>Total Tax (GST/CGST/SGST) :</b> ₹${taxSum.toFixed(2)}<br>`;
    
        html+=`<b>Grand Total :</b>
        <span style="font-size:18px;color:#28a745"> ₹${grandTotal.toFixed(2)}</span>
        </div>`;
    
        Swal.fire({
            title:`Confirm ${type} Stockin`,
            html:html,
            width:900,
            showCancelButton:true,
            reverseButtons:true
        }).then((r)=>{
    
            if(!r.isConfirmed) return;
    
            if(type === 'consignment'){
                
                $.post('<?= Router::url(['controller'=>'Outlet','action'=>'invoice_id_check']) ?>',
                    {_id:poId,_invoice:invoiceNo},
                    function(res){
            
                        if(!res.status){
                            Swal.fire('Invalid Invoice',res.message,'warning');
                            return;
                        }
            
                        submitStockin();
            
                    },'json');
        
            }else{
                submitStockin();
            }
        });
        
        function submitStockin(){
        
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while stockin is being completed',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Collect per-line excise/VAT/TCS
            const excise_per_units  = {};
            const addl_excise_units = {};
            const vat_per_units     = {};
            const tcs_per_units     = {};
            $('.stockin-excise').each(function(){
                const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
                excise_per_units[id] = $(this).val();
            });
            $('.stockin-addl-excise').each(function(){
                const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
                addl_excise_units[id] = $(this).val();
            });
            $('.stockin-vat-pct').each(function(){
                const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
                vat_per_units[id] = $(this).val();
            });
            $('.stockin-tcs-pct').each(function(){
                const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
                tcs_per_units[id] = $(this).val();
            });

            $.post('<?= Router::url(['controller'=>'Outlet','action'=>'stockin_po']) ?>',
            {
                _id:               poId,
                _invoice:          invoiceNo,
                _po_date:          poDate,
                _quantities:       quantities,
                _item_type:        itemTypes,
                _prices:           itemPrices,
                _charge:           charge,
                _excise_per_units: excise_per_units,
                _addl_excise_units:addl_excise_units,
                _vat_per_units:    vat_per_units,
                _tcs_per_units:    tcs_per_units,
                _type:             type,
                _discount_types:   discountTypes,
                _discount_values:  discountValues
            },
            function(res){
        
                Swal.close();
                if(res.status){
                    Swal.fire('Success','Stockin completed','success')
                    .then(()=>window.location.href='stockin');
                }else{
                    Swal.fire('Error',res.message,'error');
                }
        
            },'json');
        }
    
    });
    

</script>
