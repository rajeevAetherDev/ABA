<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Stock Transfer</h2>
        </div>
    </div>

    <div class="col-sm-1">
        <div class="page-title-box">
            <a href="stock_transfer">
                <button class="btn btn-danger" type="button">Back</button>
            </a>
        </div>
    </div>
</div>


<!-- TRANSFER HEADER -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">
                        <label>Transfer ID</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Stock_transfer']['transfer_id'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Transfer From</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Stock_transfer']['transfer_from_name'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Transfer To</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Stock_transfer']['transfer_to_name'] ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label>Total Tax</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Stock_transfer']['tax'] ?>">
                    </div>

                </div>

                <br>

                <div class="row">

                    
                    <div class="col-md-3">
                        <label>Total Amount</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Stock_transfer']['amount'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Created Date</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= date('d-m-Y h:i A', strtotime($transfer['Stock_transfer']['created'])) ?>">
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>


<!-- TRANSFER ITEMS -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <h4 class="page-title mb-3">Transfer Items</h4>

                <table class="table table-bordered table-hover" id="example">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SKU Name</th>
                            <th>Brand</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Tax %</th>
                            <th>Tax Value</th>
                            <th>Quantity</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php $i = 1; foreach($items as $item): ?>

                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= $item['Stock_transfer_item']['sku_name']; ?></td>
                            <td><?= $item['Stock_transfer_item']['sku_brand']; ?></td>
                            <td><?= $item['Stock_transfer_item']['sku_unit_name']; ?></td>
                            <td><?= $item['Stock_transfer_item']['sku_price']; ?></td>
                            <td><?= $item['Stock_transfer_item']['tax_percent']; ?> %</td>
                            <td><?= $item['Stock_transfer_item']['tax_value']; ?></td>
                            <td><?= $item['Stock_transfer_item']['sku_quantity']; ?></td>
                            <td><?= $item['Stock_transfer_item']['amount']; ?></td>
                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>
        </div>
    </div>
</div>