<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Hourly Sales Report</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <!-- Search Form -->
                <div class="form-group">
                    <?php
                    echo $this->Form->create("Search", array("url" => "hourly_sales"));

                    $options = array();
                    if ($this->Session->read("Auth.User.role") == "admin") {
                        $options[""] = "All Stores";
                    }
                    foreach ($users as $user) {
                        $options[$user["Location"]["id"]] = $user["Location"]["name"];
                    }

                    echo $this->Form->input("cashier", array(
                        "div"     => array("class" => "form-group"),
                        "label"   => "Select Store",
                        "class"   => "form-control",
                        "options" => $options,
                        "id"      => "cashier"
                    ));

                    echo $this->Form->input("startdate", array(
                        "div"      => array("class" => "form-group"),
                        "label"    => "Select Date",
                        "class"    => "form-control calendarmultiple",
                        "id"       => "startdate",
                        "name"     => "daterange",
                        "required" => true,
                        "value"    => $startdate
                    ));

                    echo $this->Form->button("View",   array("class" => "btn btn-info", "type" => "submit"));
                    echo "&nbsp;";
                    echo $this->Form->button("Export", array("class" => "btn btn-info", "type" => "button", "id" => "exportdata"));
                    echo $this->Form->end();
                    ?>
                </div>

                <!-- Hourly Sales Table -->
                <div class="table-responsive">
                    <table id="hourlyTable" class="display table table-bordered table-hover table-condensed" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Time Slot</th>
                                <th>Orders</th>
                                <th>PAX</th>
                                <th>Basic Sale (&#8377;)</th>
                                <th>Discount (&#8377;)</th>
                                <th>Net Sale (&#8377;)</th>
                                <th>Tax (&#8377;)</th>
                                <th>Service Charge (&#8377;)</th>
                                <th>Total Sale (&#8377;)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // ksort($itemArray);
                        $idx = 1;
                        foreach ($itemArray as $h => $row):
                            $totalSale = round(
                                (float)($row["net_sale"]       ?? 0) +
                                (float)($row["tax"]            ?? 0) +
                                (float)($row["service_charge"] ?? 0), 2
                            );
                        ?>
                            <tr>
                                <td><?= $idx++ ?></td>
                                <td><?= h($row["label"]) ?></td>
                                <td><?= number_format((int)($row["orders"] ?? 0)) ?></td>
                                <td><?= number_format((int)($row["pax"]    ?? 0)) ?></td>
                                <td><?= number_format((float)($row["basic_sale"]       ?? 0), 2) ?></td>
                                <td><?= number_format((float)($row["discount"]         ?? 0), 2) ?></td>
                                <td><strong><?= number_format((float)($row["net_sale"] ?? 0), 2) ?></strong></td>
                                <td><?= number_format((float)($row["tax"]              ?? 0), 2) ?></td>
                                <td><?= number_format((float)($row["service_charge"]   ?? 0), 2) ?></td>
                                <td><strong><?= number_format($totalSale, 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-success text-white font-weight-bold">
                                <td colspan="2"><strong>Shift Totals (Total Sale)</strong></td>
                                <td><?= number_format((int)($shiftTotals["totalinvoice"]   ?? 0)) ?></td>
                                <td><?= number_format((int)($shiftTotals["pax"]            ?? 0)) ?></td>
                                <td>&#8377; <?= number_format((float)($shiftTotals["basic_sale"]      ?? 0), 2) ?></td>
                                <td>&#8377; <?= number_format((float)($shiftTotals["discount"]        ?? 0), 2) ?></td>
                                <td>&#8377; <?= number_format((float)($shiftTotals["netsale"]         ?? 0), 2) ?></td>
                                <td>&#8377; <?= number_format((float)($shiftTotals["shifttax"]        ?? 0), 2) ?></td>
                                <td>&#8377; <?= number_format((float)($shiftTotals["servicecharge"]   ?? 0), 2) ?></td>
                                <td>&#8377; <?= number_format((float)($shiftTotals["totalsale"]       ?? 0), 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <script>
                    $(document).ready(function () {
                        $("#hourlyTable").DataTable({
                            paging      : false,
                            order       : [],
                            columnDefs  : [{ orderable: false, targets: "_all" }],
                            footerCallback: function () {}
                        });
                    });

                    $("#exportdata").click(function () {
                        var sdate   = $("#startdate").val();
                        var cashier = $("#cashier").val();
                        window.open("<?= $this->base ?>/report/hourly_sales_export?sdate=" + sdate + "&cashier=" + cashier, "_blank");
                    });
                </script>

            </div>
        </div>
    </div>
</div>