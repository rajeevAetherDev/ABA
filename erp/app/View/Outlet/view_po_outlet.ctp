<style>
    .form-section-title {
        margin-top: 1rem;
        font-weight: 600;
        font-size: 1.25rem;
    }
</style>

<div class="row align-items-center mb-3">
    <div class="col-sm-11">
        <h2 class="page-title">View Purchase Order</h2>
    </div>
    <div class="col-sm-1 text-end">
        <a href="po_grn_history" class="btn btn-danger">Back</a>
    </div>
</div>

<?php if (empty($stockinData)): ?>
    <div class="alert alert-warning">No stockin records found for this Purchase Order.</div>
<?php else: ?>

<?php

function convertNumberToWords($number) {
    $no      = floor($number);
    $decimal = round($number - $no, 2) * 100;
    $digits_length = strlen($no);
    $i   = 0;
    $str = [];
    $words = [
        0=>'',1=>'One',2=>'Two',3=>'Three',4=>'Four',5=>'Five',
        6=>'Six',7=>'Seven',8=>'Eight',9=>'Nine',10=>'Ten',
        11=>'Eleven',12=>'Twelve',13=>'Thirteen',14=>'Fourteen',
        15=>'Fifteen',16=>'Sixteen',17=>'Seventeen',18=>'Eighteen',
        19=>'Nineteen',20=>'Twenty',30=>'Thirty',40=>'Forty',
        50=>'Fifty',60=>'Sixty',70=>'Seventy',80=>'Eighty',90=>'Ninety'
    ];
    $digits = ['','Hundred','Thousand','Lakh','Crore'];
    while ($i < $digits_length) {
        $divider     = ($i == 2) ? 10 : 100;
        $number_part = floor($no % $divider);
        $no          = floor($no / $divider);
        $i          += ($divider == 10) ? 1 : 2;
        if ($number_part) {
            $str[] = ($number_part < 21)
                ? $words[$number_part].' '.$digits[count($str)]
                : $words[floor($number_part/10)*10].' '.$words[$number_part % 10].' '.$digits[count($str)];
        } else {
            $str[] = null;
        }
    }
    $rupees = implode(' ', array_reverse(array_filter($str)));
    $paise  = $decimal
        ? 'and '.$words[floor($decimal/10)*10].' '.$words[$decimal % 10].' Paise'
        : '';
    return ($rupees ? $rupees.' Rupees ' : '').$paise.' Only';
}

foreach ($stockinData as $stockIndex => $stock):

    $stockin        = $stock['indents'][0]['inventory_stockin'];
    $vendor         = $stock['indents'][0]['inventory_vendors'];
    $items          = $stock['items'];
    $locationdetail = $stock['locationdetail'];
?>

<!-- ══════════════════════════════════════════════════════════════
     PO HEADER CARD
     ══════════════════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">

                    <div class="col-sm-3">
                        <?= $this->Form->input('po_id_'.$stockIndex, [
                            'label'    => 'PO Id',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $stockin['p_o_id']
                        ]); ?>
                    </div>

                    <div class="col-sm-3">
                        <?= $this->Form->input('stockin_id_'.$stockIndex, [
                            'label'    => 'Stockin Id',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $stockin['stockin_id']
                        ]); ?>
                    </div>

                    <div class="col-sm-3">
                        <?= $this->Form->input('vendor_name_'.$stockIndex, [
                            'label'    => 'Vendor Name',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $stockin['vendor_name'] ?? ($vendor['vendor_name'] ?? '')
                        ]); ?>
                    </div>

                    <div class="col-sm-3">
                        <?= $this->Form->input('invoice_no_'.$stockIndex, [
                            'label'    => 'Invoice No',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $stockin['invoice_no']
                        ]); ?>
                    </div>

                    <div class="col-sm-3"><br>
                        <?= $this->Form->input('charge_'.$stockIndex, [
                            'div'      => ['class' => 'form-group'],
                            'label'    => 'Charges',
                            'class'    => 'form-control',
                            'type'     => 'number',
                            'value'    => $stockin['stockin_charge'],
                            'step'     => 'any',
                            'min'      => '0',
                            'readonly' => 'readonly'
                        ]); ?>
                    </div>

                    <div class="col-sm-3">
                        <?= '<br>' . $this->Form->input('date_'.$stockIndex, [
                            'label'    => 'Date',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => date('Y-m-d H:i:s', strtotime($stockin_date))
                        ]); ?>
                    </div>

                    <div class="col-sm-3">
                        <?= '<br>' . $this->Form->input('stockin_amount_'.$stockIndex, [
                            'label'    => 'Stockin Amount',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $stockin['stockin_amount']
                        ]); ?>
                    </div>

                    <div class="col-sm-3">
                        <?= '<br>' . $this->Form->input('location_'.$stockIndex, [
                            'label'    => 'Location',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $locationdetail['Location']['name']
                        ]); ?>
                    </div>

                    <?php if (!empty($vendor['gst'])): ?>
                    <div class="col-sm-3"><br>
                        <?= $this->Form->input('vendor_gst_'.$stockIndex, [
                            'label'    => 'Vendor GST',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => $vendor['gst']
                        ]); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($vendor['office_address'])): ?>
                    <div class="col-sm-3"><br>
                        <?= $this->Form->input('vendor_address_'.$stockIndex, [
                            'label'    => 'Vendor Address',
                            'class'    => 'form-control',
                            'readonly' => 'readonly',
                            'value'    => trim($vendor['office_address'].' '.$vendor['state'])
                        ]); ?>
                    </div>
                    <?php endif; ?>

                </div><!-- /.form-group row -->
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     STOCKIN ITEMS TABLE
     ══════════════════════════════════════════════════════════════ -->
<div class="card">
    <div class="card-body">
        <h4 class="form-section-title">Stockin Items</h4>

        <div style="overflow-x: auto; width: 100%;">
        <table class="table table-bordered table-hover table-sm align-middle mb-0" style="min-width: max-content;">
            <thead class="table-light">
                <tr>
                    <th class="text-nowrap px-2 py-2" style="min-width:50px;">SR NO</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:180px;">Product Name</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:100px;">Unit</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:100px;">Rate</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:100px;">Qty</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:120px;">Discount (₹)</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:110px;">Excise (₹)</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:110px;">Addl.Ex (₹)</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:100px;">VAT (₹)</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:100px;">TCS (₹)</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:120px;">Total Tax</th>
                    <th class="text-nowrap px-2 py-2" style="min-width:120px;">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $x                  = 1;
            $stockin_amount_sum = 0;
            $stockin_tax_sum    = 0;
            $total_discount_sum = 0;

            foreach ($items as $item):
                $si_item = $item['inventory_stockin_items'];
                $po_item = $item['inventory_purchase_order_items'];

                $price       = (float)$si_item['sku_price'];
                $qty         = (float)$si_item['stockin_quantity'];
                $taxVal      = (float)($si_item['stockin_total_tax_value'] ?? 0);
                $discVal     = (float)($po_item['stockin_discount_value']  ?? 0);
                $exciseAmt   = (float)($po_item['excise_duty_amt']         ?? 0);
                $addlExAmt   = (float)($po_item['addl_excise_duty_amt']    ?? 0);
                $vatAmt      = (float)($po_item['vat_amt']                 ?? 0);
                $tcsAmt      = (float)($po_item['tcs_amt']                 ?? 0);
                $storedSinAmt= (float)($po_item['stockin_amount']          ?? 0);
                $lineAmt     = $storedSinAmt > 0
                    ? $storedSinAmt
                    : round(($price * $qty) - $discVal + $exciseAmt + $addlExAmt + $vatAmt + $tcsAmt + $taxVal, 2);

                $total_discount_sum += $discVal;
                $stockin_amount_sum += $lineAmt;
                $stockin_tax_sum    += $taxVal;
            ?>
                <tr>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm text-center"
                            value="<?= $x++ ?>" readonly style="min-width:40px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= h($si_item['sku_name']) ?>" readonly style="min-width:160px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= h($si_item['sku_unit_name']) ?>" readonly style="min-width:80px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= h($price) ?>" readonly style="min-width:80px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= h($qty) ?>" readonly style="min-width:80px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= number_format($discVal, 2) ?>" readonly style="min-width:100px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= $exciseAmt > 0 ? number_format($exciseAmt, 2) : '—' ?>" readonly style="min-width:90px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= $addlExAmt > 0 ? number_format($addlExAmt, 2) : '—' ?>" readonly style="min-width:90px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= $vatAmt > 0 ? number_format($vatAmt, 2) : '—' ?>" readonly style="min-width:80px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= $tcsAmt > 0 ? number_format($tcsAmt, 2) : '—' ?>" readonly style="min-width:80px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= number_format($taxVal, 2) ?>" readonly style="min-width:100px;">
                    </td>
                    <td class="px-2">
                        <input type="text" class="form-control form-control-sm"
                            value="<?= number_format($lineAmt, 2) ?>" readonly style="min-width:100px;">
                    </td>
                </tr>
            <?php endforeach; ?>

            <!-- Totals row -->
            <tr class="fw-bold table-light">
                <td colspan="5" class="px-2"></td>
                <td></td><td></td><td></td><td></td>
                <td class="px-2">
                    <input type="text" class="form-control form-control-sm fw-bold"
                        value="<?= number_format($total_discount_sum, 2) ?>" readonly style="min-width:100px;">
                </td>
                <td class="px-2">
                    <input type="text" class="form-control form-control-sm fw-bold"
                        value="<?= number_format($stockin_tax_sum, 2) ?>" readonly style="min-width:100px;">
                </td>
                <td class="px-2">
                    <input type="text" class="form-control form-control-sm fw-bold"
                        value="<?= number_format($stockin_amount_sum, 2) ?>" readonly style="min-width:100px;">
                </td>
            </tr>
            </tbody>
        </table>
        </div>

        <!-- ── Summary block ── -->
        <?php
        $charge                = (float)$stockin['stockin_charge'];
        $totalAmountwithcharge = $stockin_amount_sum + $charge;
        $currencySymbol        = $locationdetail['Location']['currency_symbol'] ?? '₹';
        $amountinwords         = convertNumberToWords($totalAmountwithcharge);
        ?>
        <div class="row mt-3 justify-content-end">
            <div class="col-sm-4">
                <table class="table table-sm table-bordered">
                    <tr>
                        <td><strong>Amount</strong></td>
                        <td><?= h($currencySymbol.' '.number_format($stockin_amount_sum, 2)) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Charges</strong></td>
                        <td><?= h($currencySymbol.' '.number_format($charge, 2)) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Discount</strong></td>
                        <td><?= h($currencySymbol.' '.number_format($total_discount_sum, 2)) ?></td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td><strong>Total Amount</strong></td>
                        <td><?= h($currencySymbol.' '.number_format($totalAmountwithcharge, 2)) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Amount in Words</strong></td>
                        <td class="fst-italic text-muted small"><?= h($amountinwords) ?></td>
                    </tr>
                </table>
            </div>
        </div>


    </div>
</div>

<?php endforeach; ?>
<?php endif; ?>