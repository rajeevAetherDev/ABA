<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Approve / Reject Return</h2>
        </div>
    </div>

    <div class="col-sm-1">
        <a href="return_to_warehouse">
            <button class="btn btn-danger">Back</button>
        </a>
    </div>
</div>


<input type="hidden" id="return_id"
       value="<?= $transfer['Inventory_return']['return_id'] ?>">


<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">


                <div class="form-group">
                    <label>Warehouse</label>

                    <input type="text"
                           class="form-control"
                           readonly
                           value="<?= $transfer['Inventory_return']['warehouse_name'] ?>">
                </div>


                <div class="form-group">
                    <label>Outlet Remarks</label>

                    <textarea class="form-control"
                              readonly><?= $transfer['Inventory_return']['remarks'] ?></textarea>
                </div>


                <div class="form-group">
                    <label>Admin Remarks</label>

                    <textarea id="admin_remarks"
                              class="form-control"
                              rows="3"></textarea>
                </div>


                <hr>

                <h4>Return Items</h4>


                <table class="table table-bordered" id="returnTable">

                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax %</th>
                            <th>Tax Value</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach($transferItems as $item): 

                            $price = $item['Inventory_return_items']['sku_price'];
                            $qty = $item['Inventory_return_items']['sku_quantity'];
                            $taxPercent = $item['Inventory_return_items']['tax_percent'];

                            $baseTotal = $price * $qty;
                            $taxValue = ($baseTotal * $taxPercent)/100;
                            $finalTotal = $baseTotal + $taxValue;
                        ?>

                        <tr>

                            <td>
                                <?= $item['Inventory_return_items']['sku_name'] ?>

                                <input type="hidden"
                                       class="sku_id"
                                       value="<?= $item['Inventory_return_items']['sku_id'] ?>">
                            </td>

                            <td><?= $item['Inventory_return_items']['sku_brand'] ?></td>

                            <td><?= $item['Inventory_return_items']['sku_unit_name'] ?></td>

                            <td>
                                <span class="price"><?= $price ?></span>
                            </td>

                            <td>
                                <span class="tax_percent"><?= $taxPercent ?></span>%
                            </td>

                            <td>
                                ₹<span class="tax_value"><?= number_format($taxValue,2) ?></span>
                            </td>

                            <td>
                                <input type="number"
                                       class="form-control qty"
                                       min="0"
                                      max="<?= $qty ?>"
                                       value="<?= $qty ?>">
                                <input type="hidden"
                                       class="requested_qty"
                                       value="<?= $qty ?>">
                            </td>

                            <td>
                                ₹<span class="row_total"
                                        data-value="<?= $finalTotal ?>">
                                    <?= number_format($finalTotal,2) ?>
                                </span>
                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>


                    <tfoot>
                        <tr>
                            <th colspan="7" style="text-align:right;">
                                Grand Total
                            </th>

                            <th>
                                ₹<span id="grand_total">0.00</span>
                            </th>
                        </tr>
                    </tfoot>

                </table>


                <br>
                
                
                <button onclick="submitReturn('rejected')"
                        class="btn btn-danger">

                    Reject

                </button>

                <button onclick="submitReturn('approved')"
                        class="btn btn-success">

                    Approve

                </button>




            </div>
        </div>
    </div>
</div>



<script>

$(document).ready(function(){

    calculateGrandTotal();

});


/*
==================================
AUTO CALCULATE ROW + GRAND TOTAL
==================================
*/
$(document).on('keyup change','.qty',function(){

    let row=$(this).closest('tr');
    
    let requestedQty = parseFloat(row.find('.requested_qty').val()) || 0;
    let qty = parseFloat($(this).val()) || 0;

    // 🔴 VALIDATION
    if(qty > requestedQty){
        Swal.fire('Error','Qty cannot be more than requested ('+requestedQty+')','error');
        $(this).val(requestedQty);
        qty = requestedQty;
    }

    if(qty < 0){
        $(this).val(0);
        qty = 0;
    }

    let price=parseFloat(row.find('.price').text()) || 0;

    let taxPercent=parseFloat(row.find('.tax_percent').text()) || 0;

    // let qty=parseFloat($(this).val()) || 0;


    let baseTotal=price*qty;

    let taxValue=(baseTotal*taxPercent)/100;

    let finalTotal=baseTotal+taxValue;


    row.find('.tax_value').text(taxValue.toFixed(2));

    // row.find('.row_total').text(finalTotal.toFixed(2));
    row.find('.row_total')
    .text(finalTotal.toFixed(2))
    .attr('data-value', finalTotal);

    calculateGrandTotal();

});



function calculateGrandTotal(){

    let grandTotal=0;

    $('.row_total').each(function(){

        let val = parseFloat($(this).attr('data-value')) || 0;
        grandTotal += val;

    });

    $('#grand_total').text(grandTotal.toFixed(2));

}



/*
==================================
SUBMIT FUNCTION
==================================
*/

function submitReturn(status){

    let items=[];
    let itemRowsHtml='';
    let grandTotal=0;

    let remarks=$('#admin_remarks').val();

    let warehouse=$('input[readonly]').val();

    /*
    ============================
    COLLECT ALL TABLE VALUES
    ============================
    */
    
    let isValid = true;

    $('#returnTable tbody tr').each(function(){
        
        let skuName=$(this).find('td:eq(0)').text().trim();

        let brand=$(this).find('td:eq(1)').text().trim();

        let unit=$(this).find('td:eq(2)').text().trim();

        let price=parseFloat($(this).find('.price').text()) || 0;

        let taxPercent=parseFloat($(this).find('.tax_percent').text()) || 0;

        let taxValue=parseFloat($(this).find('.tax_value').text()) || 0;

        let qty=parseFloat($(this).find('.qty').val()) || 0;
        
        let requestedQty = parseFloat($(this).find('.requested_qty').val()) || 0;
    
        if(qty > requestedQty){
            Swal.fire('Error','Quantity exceeds requested limit','error');
            isValid = false;
            return false; // break loop
        }
        // let total=parseFloat($(this).find('.row_total').text()) || 0;
        let total = parseFloat($(this).find('.row_total').attr('data-value')) || 0;        

        let skuId=$(this).find('.sku_id').val();


        if(qty>0){

            grandTotal += total;

            items.push({
                sku_id:skuId,
                qty:qty,
                tax_value:taxValue,
                total:total
            });


            itemRowsHtml += `
                <tr>
                    <td>${skuName}</td>
                    <td>${brand}</td>
                    <td>${unit}</td>
                    <td>${price.toFixed(2)}</td>
                    <td>${taxPercent}%</td>
                    <td>${taxValue.toFixed(2)}</td>
                    <td>${qty}</td>
                    <td>${total.toFixed(2)}</td>
                </tr>
            `;
        }

    });

    if(!isValid){
        return;
    }                   
    
    if(items.length==0){

        Swal.fire(
            'Warning',
            'Enter at least one quantity',
            'warning'
        );

        return;

    }



    /*
    ============================
    SHOW CONFIRM POPUP
    ============================
    */
    Swal.fire({

        title:'Confirm '+status.toUpperCase(),

        width:'1000px',

        html:`

            <div style="text-align:left">

                <p><strong>Warehouse:</strong> ${warehouse}</p>

                <p><strong>Admin Remarks:</strong> ${remarks}</p>

                <br>

                <table border="1"
                       width="100%"
                       cellpadding="8"
                       style="border-collapse:collapse;">

                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax %</th>
                            <th>Tax Value</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        ${itemRowsHtml}

                    </tbody>

                    <tfoot>

                        <tr>
                            <th colspan="7"
                                style="text-align:right">

                                Grand Total

                            </th>

                            <th>

                                ₹${grandTotal.toFixed(2)}

                            </th>
                        </tr>

                    </tfoot>

                </table>

            </div>

        `,

        showCancelButton:true,

        confirmButtonText:'Confirm '+status,

        cancelButtonText:'Cancel',

        reverseButtons:true

    }).then((result)=>{

        if(result.isConfirmed){

            $.ajax({

                url:"<?= $this->Html->url(['action'=>'approve_reject_return']) ?>",

                type:"POST",

                data:{

                    return_id:$('#return_id').val(),

                    status:status,

                    remarks:remarks,

                    items:JSON.stringify(items)

                },

                beforeSend:function(){

                    Swal.fire({
                        title:'Processing...',
                        allowOutsideClick:false,
                        didOpen:()=>Swal.showLoading()
                    });

                },

                success:function(res){

                    let response=JSON.parse(res);

                    if(response.response_code==1){

                        Swal.fire(
                            'Success',
                            response.data,
                            'success'
                        ).then(()=>{

                            window.location="return_to_warehouse";

                        });

                    }else{

                        Swal.fire(
                            'Error',
                            response.data,
                            'error'
                        );

                    }

                },

                error:function(){

                    Swal.fire(
                        'Error',
                        'Something went wrong',
                        'error'
                    );

                }

            });

        }

    });

}

</script>
