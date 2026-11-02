<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Edit Stock Transfer</h2>
        </div>
    </div>

    <div class="col-sm-1">
        <div class="page-title-box">
            <a href="stock_transfer">
                <button class='btn btn-danger' type="button">Back</button>
            </a>
        </div>
    </div>
</div>


<input type="hidden" id="transfer_id" value="<?= $transfer['Stock_transfer']['transfer_id'] ?>">


<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?php
                $options = [];
                foreach ($locations as $location) {
                    $options[$location['Location']['id']] = $location['Location']['name'];
                }

                echo $this->Form->input('from', [
                    'label' => 'Select Transfer From',
                    'class' => 'form-control',
                    'options' => $options,
                    'id' => 'select_from',
                    'value' => $transfer['Stock_transfer']['transfer_from'],
                    'required' => true
                ]);

                $transfertooptions = [];
                foreach ($tolocations as $transferloc) {
                    $transfertooptions[$transferloc['Location']['id']] = $transferloc['Location']['name'];
                }

                echo '<br>'.$this->Form->input('to', [
                    'label' => 'Select Transfer To',
                    'class' => 'form-control',
                    'options' => $transfertooptions,
                    'id' => 'select_to',
                    'value' => $transfer['Stock_transfer']['transfer_to'],
                    'required' => true
                ]);
                ?>

                <br>

                <h5>Items</h5>

                <table class="display table table-hover table-condensed" cellspacing="0" width="100%" id="example">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax %</th>
                            <th>Qty</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($users as $user):

                            $qty = 0;

                            foreach ($transferItems as $item) {
                                if ($item['Stock_transfer_item']['sku_id'] == $user['Inventory_sku']['id']) {
                                    $qty = $item['Stock_transfer_item']['sku_quantity'];
                                }
                            }

                        ?>

                        <tr>
                            <td><?= $user['Inventory_sku']['id'] ?></td>
                            <td><?= $user['Inventory_sku']['name'] ?></td>
                            <td><?= $user['Inventory_sku']['brand'] ?></td>
                            <td><?= $user['Inventory_sku']['unit_name'] ?></td>
                            <td><?= $user['Inventory_sku']['price'] ?></td>
                            <td><?= h($user[0]['total_tax_percentage'] ?? 0) ?> %</td>

                            <td>
                                <input type="number" class="form-control quantity" value="<?= $qty ?>" min="0">

                                <input type="hidden" class="sku_id" value="<?= $user['Inventory_sku']['id'] ?>">
                                <input type="hidden" class="sku_name" value="<?= $user['Inventory_sku']['name'] ?>">
                                <input type="hidden" class="sku_brand" value="<?= $user['Inventory_sku']['brand'] ?>">
                                <input type="hidden" class="sku_price" value="<?= $user['Inventory_sku']['price'] ?>">
                                <input type="hidden" class="unit_name" value="<?= $user['Inventory_sku']['unit_name'] ?>">
                                <input type="hidden" class="unit_id" value="<?= $user['Inventory_sku']['unit'] ?>">
                                <input type="hidden" class="tax_percentage" value="<?= h($user[0]['total_tax_percentage'] ?? 0) ?>">
                            </td>
                        </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

                <br>

                <button type="button" onclick="updateTransfer()" class="btn btn-success" style="float:right;">
                    Update
                </button>

                <a href="stock_transfer">
                    <button class="btn btn-danger" type="button" style="float:right;margin-right:10px;">
                        Cancel
                    </button>
                </a>

            </div>
        </div>
    </div>
</div>

<script>

let qtyStore = {};

$(document).ready(function () {

    var table = $('#example').DataTable({
        destroy:true,
        stateSave:false,
        order:[[1,'asc']]
    });

    /*
    PRELOAD EXISTING QTY
    */
    $('#example tbody tr').each(function(){

        let row=$(this);

        let qty=row.find('.quantity').val();

        if(qty>0){

            let skuId=row.find('.sku_id').val();

            qtyStore[skuId]={
                id: skuId,
                sku_name: row.find('.sku_name').val(),
                sku_brand: row.find('.sku_brand').val(),
                price: row.find('.sku_price').val(),
                unit_name: row.find('.unit_name').val(),
                unit_id: row.find('.unit_id').val(),
                tax_percentage: row.find('.tax_percentage').val(),
                sku_quantity: qty
            };

        }

    });


    /*
    ON QTY CHANGE
    */
    $('#example tbody').on('keyup change','.quantity',function(){

        let row=$(this).closest('tr');

        let skuId=row.find('.sku_id').val();

        let qty=$(this).val();

        if(qty>0){

            qtyStore[skuId]={
                id: skuId,
                sku_name: row.find('.sku_name').val(),
                sku_brand: row.find('.sku_brand').val(),
                price: row.find('.sku_price').val(),
                unit_name: row.find('.unit_name').val(),
                unit_id: row.find('.unit_id').val(),
                tax_percentage: row.find('.tax_percentage').val(),
                sku_quantity: qty
            };

        }else{

            delete qtyStore[skuId];

        }

    });


    /*
    RESTORE AFTER DATATABLE DRAW
    */
    table.on('draw',function(){

        $('#example tbody tr').each(function(){

            let row=$(this);

            let skuId=row.find('.sku_id').val();

            if(qtyStore[skuId]){

                row.find('.quantity').val(qtyStore[skuId].sku_quantity);

            }

        });

    });

});

function updateTransfer() {

    let from = $('#select_from').val();
    let to = $('#select_to').val();

    let fromText = $('#select_from option:selected').text();
    let toText = $('#select_to option:selected').text();

    if (from == '' || to == '') {
        Swal.fire('Warning', 'Transfer From and To required', 'warning');
        return;
    }

    if (from == to) {
        Swal.fire('Warning', 'Transfer From and To cannot be same', 'warning');
        return;
    }

    let checkedData = Object.values(qtyStore);
    let itemRowsHtml = '';
    let grandTotal = 0;

    $.each(checkedData, function (key, item) {

        let baseTotal = parseFloat(item.price) * parseFloat(item.sku_quantity);
        let taxAmount = (baseTotal * parseFloat(item.tax_percentage)) / 100;
        let rowTotal = baseTotal + taxAmount;

        grandTotal += rowTotal;

        itemRowsHtml += `
            <tr>
                <td>${item.sku_name}</td>
                <td>${item.sku_brand}</td>
                <td>${item.unit_name}</td>
                <td>${item.price}</td>
                <td>${item.tax_percentage}%</td>
                <td>${item.sku_quantity}</td>
                <td>${rowTotal.toFixed(2)}</td>
            </tr>
        `;
    });

    if (checkedData.length == 0) {
        Swal.fire('Warning', 'Enter at least one quantity', 'warning');
        return;
    }

    // Confirmation popup
    Swal.fire({
        title: 'Confirm Stock Transfer Update',
        width: '800px',
        html: `
            <div style="text-align:left">
                <p><strong>Transfer From:</strong> ${fromText}</p>
                <p><strong>Transfer To:</strong> ${toText}</p>

                <table border="1" width="100%" cellpadding="8" style="border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>SKU Name</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemRowsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" style="text-align:right;">Final Amount</th>
                            <th>${grandTotal.toFixed(2)}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirm Update',
        reverseButtons: true
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                url: "<?= $this->Html->url(['action'=>'update_stock_transfer']) ?>",
                type: "POST",
                data: {
                    transfer_id: $('#transfer_id').val(),
                    tranferfrom: from,
                    transferto: to,
                    checkedData: JSON.stringify(checkedData)
                },
                beforeSend: function () {
                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function (res) {
                    let response = JSON.parse(res);
                    if (response.status == 1) {
                        Swal.fire(
                            'Success',
                            'Transfer Updated Successfully',
                            'success'
                        ).then(() => {
                            window.location = 'stock_transfer';
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Something went wrong', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Something went wrong', 'error');
                }
            });

        }

    });

}
</script>
