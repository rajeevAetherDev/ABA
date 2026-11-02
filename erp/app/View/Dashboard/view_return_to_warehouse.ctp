<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Return To Warehouse</h2>
        </div>
    </div>

    <div class="col-sm-1">
        <div class="page-title-box">
            <a href="return_to_warehouse">
                <button class="btn btn-danger" type="button">Back</button>
            </a>
        </div>
    </div>
</div>


<!-- RETURN HEADER -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">
                        <label>Return ID</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Inventory_return']['return_id'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Outlet Name</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Inventory_return']['location_name'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Warehouse Name</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Inventory_return']['warehouse_name'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Total Amount</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Inventory_return']['total_amount'] ?>">
                    </div>

                </div>

                <br>

                <div class="row">

                    <div class="col-md-6">
                        <label>Remarks</label>
                        <textarea class="form-control" readonly><?= $transfer['Inventory_return']['remarks'] ?></textarea>
                    </div>

                    <div class="col-md-3">
                        <label>Created Date</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= date('d-m-Y h:i A', strtotime($transfer['Inventory_return']['created'])) ?>">
                    </div>
                    
                    <?php
                        $statusArr = [
                            0 => ['Pending', 'warning', '⏳'],
                            1 => ['Approved', 'success', '✔'],
                            2 => ['Rejected', 'danger', '✖']
                        ];
                
                        $admin_status = $transfer['Inventory_return']['admin_status'] ?? 0;
                        $s = $statusArr[$admin_status] ?? ['Unknown', 'secondary', '?'];
                    ?>
                
                    <div class="col-md-3">
                        <label>Warehouse Updated Date</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Inventory_return']['admin_updated_date'] 
                                        ? date('d-m-Y h:i A', strtotime($transfer['Inventory_return']['admin_updated_date'])) 
                                        : '-' ?>">
                    </div>

                </div>
                <br>
                
                <div class="row">

                    <div class="col-md-6">
                        <label>Warehouse Remarks</label>
                        <textarea class="form-control" readonly><?= $transfer['Inventory_return']['admin_remarks'] ?></textarea>
                    </div>
                    
                    <div class="col-md-3">
                        <label>Warehouse Updated Amount</label>
                        <input type="text" class="form-control"
                               readonly
                               value="<?= $transfer['Inventory_return']['admin_updated_amount'] ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label>Status</label>
                        <?php
                            
                            $admin_status = $transfer['Inventory_return']['admin_status'] ?? 0;
                            $s = $statusArr[$admin_status] ?? ['Unknown', 'secondary', '?'];
                        ?>
                        <br>
                        <span class="status-badge status-<?= $s[1]; ?>">
                            <?= $s[2]; ?> <?= $s[0]; ?>
                        </span>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>


<!-- RETURN ITEMS -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <h4 class="page-title mb-3">Return Items</h4>

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
                            <th><?= $s[2]; ?> <?= $s[0]; ?> Quantity</th>
                            <th><?= $s[2]; ?> <?= $s[0]; ?> Tax</th>
                            <th><?= $s[2]; ?> <?= $s[0]; ?> Amount</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php $i = 1; foreach($items as $item): ?>

                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= $item['Inventory_return_items']['sku_name']; ?></td>
                            <td><?= $item['Inventory_return_items']['sku_brand']; ?></td>
                            <td><?= $item['Inventory_return_items']['sku_unit_name']; ?></td>
                            <td><?= $item['Inventory_return_items']['sku_price']; ?></td>
                            <td><?= $item['Inventory_return_items']['tax_percent']; ?> %</td>
                            <td><?= $item['Inventory_return_items']['tax_value']; ?></td>
                            <td><?= $item['Inventory_return_items']['sku_quantity']; ?></td>
                            <td><?= $item['Inventory_return_items']['amount']; ?></td>
                            <td><?= $item['Inventory_return_items']['admin_updated_quantity']; ?></td>
                            <td><?= $item['Inventory_return_items']['admin_updated_tax']; ?></td>
                            <td><?= $item['Inventory_return_items']['admin_updated_amount']; ?></td>
                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>
        </div>
    </div>
</div>