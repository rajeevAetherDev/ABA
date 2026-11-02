<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Purchase Item Ledger Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php
                        echo $this->Form->create('Search', ['url' => 'purchase_item_ledger']);

                        $itemOptions = [];
                        $itemOptions[''] = 'All Items';
                        foreach ($items as $item) {
                            $itemOptions[$item['inventory_skus']['id']] = $item['inventory_skus']['name'];
                        }


                        $selectedItem = !empty($this->request->data['Search']['item']) ? $this->request->data['Search']['item'] : '';



                        echo $this->Form->input('item', [
                            'div' => ['class' => 'form-group'],
                            'label' => 'Select Item',
                            'class' => 'form-control',
                            'options' => $itemOptions,
                            'id' => 'item',
                            'value' => $selectedItem
                        ]);


                        echo $this->Form->input('startdate', [
                            'div' => ['class' => 'form-group'],
                            'label' => ['class' => 'form-label'],
                            'class' => 'form-control calendarmultiple',
                            'label' => 'Select Date',
                            'id' => 'daterange',
                            'name' => 'daterange',
                            'required' => 'required',
                            'value' => $startdate
                        ]);

                        echo $this->Form->button('View', ['class' => 'btn btn-info', 'type' => 'submit']);
                        echo "&nbsp;" . $this->Form->button('Export', ['class' => 'btn btn-info', 'type' => 'button', 'id' => 'exportdata']);
                        echo "&nbsp;" . $this->Form->button('Print', ['class' => 'btn btn-info', 'type' => 'button', 'id' => 'btn-pdf']);
                        echo $this->Form->end();
                        ?>
                    </div>

                    <div style="height:30px; width:100%" class="clearfix"></div>

                    <?php echo $this->Form->create('Report', ['id' => 'pdfform', 'url' => 'purchaseitemledger_reportpdf', 'target' => '_blank']); ?>
                    <?php echo $this->Form->end(); ?>
                </div>

                <div style="height:30px; width:100%" class="clearfix"></div>

                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="table-responsive">
                            <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Unit</th>
                                        <th>Initial Quantity</th>
                                        <th>Initial Total Amount</th>
                                        <th>Approved Quantity</th>
                                        <th>Approved Total Amount</th>
                                        <th>Processed Quantity</th>
                                        <th>Processed Total Amount</th>
                                        <th>Stockin Quantity</th>
                                        <th>Stockin Total Amount</th>
                                        <th>Excise</th>
                                        <th>Addl. Excise</th>
                                        <th>VAT</th>
                                        <th>TCS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $x = 1;
                                    // echo "<pre/>";
                                    // print_r($purchaseorders);exit;
                                    foreach ($purchaseorders as $po):
                                        $purchaseorders = $po['ipo'];
                                        $items = $po['ipoi'];
                                        $itemsAgg = $po[0];
                                    ?>
                                        <tr>
                                            <td><?php echo $x; ?></td>
                                            <td><?php echo h($items['name']); ?></td>
                                            <td><?php echo h($items['unit']); ?></td>
                                            <td><?php echo h($itemsAgg['sku_total_qty']); ?></td>
                                            <td><?php echo number_format($itemsAgg['sku_total_amnt'], 2); ?></td>
                                            <td><?php echo h($itemsAgg['approved_total_qty']); ?></td>
                                            <td><?php echo number_format($itemsAgg['approved_total_amnt'], 2); ?></td>
                                            <td><?php echo h($itemsAgg['processed_total_qty']); ?></td>
                                            <td><?php echo number_format($itemsAgg['processed_total_amnt'], 2); ?></td>
                                            <td><?php echo h($itemsAgg['stockin_total_qty']); ?></td>
                                            <td><?php echo number_format($itemsAgg['stockin_total_amnt'], 2); ?></td>
                                            <td><?php echo number_format($itemsAgg['excise_total'], 2); ?></td>
                                            <td><?php echo number_format($itemsAgg['addl_excise_total'], 2); ?></td>
                                            <td><?php echo number_format($itemsAgg['vat_total'], 2); ?></td>
                                            <td><?php echo number_format($itemsAgg['tcs_total'], 2); ?></td>
                                        </tr>
                                    <?php 
                                    $x++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var selectedItem = '<?php echo $selectedItem; ?>';

    $("#exportdata").click(function() {
        
        var sdate = $("#daterange").val();
        // var itemname = $("#item").text();
        var itemname = $("#item option:selected").text();

        window.open('<?php echo $this->base; ?>/report/purchaseitemledger_export?sdate=' + sdate + '&item=' + selectedItem + '&itemname=' + itemname, '_blank');
    });

    $("#btn-pdf").click(function() {
        var pdfform = document.getElementById('pdfform');
        $('#pdfform').hide();

        var sdate = $("#daterange").val();
        var item = $("#item").val();
        // var itemname = $("#item").text();
        var itemname = $("#item option:selected").text();

        var sdateelement = document.createElement("input");
        sdateelement.value = sdate;
        sdateelement.name = "sdate";

        var itemelement = document.createElement("input");
        itemelement.value = item;
        itemelement.name = "item";

        pdfform.appendChild(sdateelement);
        pdfform.appendChild(itemelement);
        pdfform.submit();
    });
});
</script>
