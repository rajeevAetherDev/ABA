<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Service PO — <?php echo h($spo['Service_purchase_order']['spo_id']); ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 15px; color: #000; }
  h1,h2,h3,h4 { margin: 0 0 4px; padding: 0; }
  .header-block { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }
  .meta-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
  .meta-box { border: 1px solid #888; padding: 8px 12px; width: 48%; font-size: 11px; }
  .meta-box table { width: 100%; border-collapse: collapse; }
  .meta-box td { padding: 2px 4px; vertical-align: top; }
  .meta-box td:first-child { font-weight: bold; white-space: nowrap; width: 45%; }
  table.items { width: 100%; border-collapse: collapse; margin: 10px 0; }
  table.items th { background: #333; color: #fff; padding: 6px 8px; text-align: left; font-size: 11px; }
  table.items td { border: 1px solid #ccc; padding: 5px 8px; font-size: 11px; }
  table.items tr:nth-child(even) td { background: #f9f9f9; }
  .totals-box { float: right; width: 280px; border: 1px solid #888; margin-top: 8px; }
  .totals-box table { width: 100%; border-collapse: collapse; }
  .totals-box td { padding: 4px 10px; border-bottom: 1px solid #eee; }
  .totals-box td:first-child { font-weight: bold; }
  .totals-box tr.grand td { background: #333; color: #fff; font-weight: bold; }
  .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
  .status-1 { background: #f0ad4e; color: #fff; }
  .status-2 { background: #5bc0de; color: #fff; }
  .status-3 { background: #5cb85c; color: #fff; }
  .sign-row { display: flex; justify-content: space-between; margin-top: 40px; }
  .sign-box { text-align: center; width: 30%; border-top: 1px solid #000; padding-top: 4px; font-size: 11px; }
  .clearfix::after { content: ""; display: table; clear: both; }
  @media print {
    body { margin: 0; padding: 10px; }
    .no-print { display: none !important; }
    @page { margin: 10mm; }
  }
</style>
</head>
<body>
<?php
$s    = $spo['Service_purchase_order'];
$stLabel = array(1=>'Submitted', 2=>'Approved', 3=>'Processed', 0=>'Cancelled');
$stStatus = (int)$s['status'];
?>
<div class="no-print" style="text-align:right;margin-bottom:10px">
  <button onclick="window.print()" style="padding:6px 16px;background:#333;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:13px">&#128438; Print / Save as PDF</button>
  <button onclick="window.history.back()" style="padding:6px 14px;background:#888;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:13px;margin-left:8px">Back</button>
</div>

<!-- Header -->
<div class="header-block">
  <h2 style="font-size:16px;text-transform:uppercase"><?php echo h($s['location_name']); ?></h2>
  <p style="font-size:11px;margin:3px 0">SERVICE PURCHASE ORDER</p>
  <p style="font-size:13px;margin:3px 0;font-weight:bold"><?php echo h($s['spo_id']); ?> &nbsp;|&nbsp;
    <span class="status-badge status-<?php echo $stStatus; ?>"><?php echo $stLabel[$stStatus] ?? ''; ?></span>
  </p>
</div>

<!-- Meta: Vendor + PO details -->
<div class="meta-row">
  <div class="meta-box">
    <strong>VENDOR DETAILS</strong><br>
    <table>
      <tr><td>Name</td><td><?php echo h($s['vendor_name']); ?></td></tr>
      <tr><td>Service Type</td><td><?php echo h($s['service_type_name']); ?></td></tr>
      <tr><td>Description</td><td><?php echo h($s['description']); ?></td></tr>
    </table>
  </div>
  <div class="meta-box">
    <strong>PO DETAILS</strong><br>
    <table>
      <tr><td>SPO ID</td><td><?php echo h($s['spo_id']); ?></td></tr>
      <tr><td>Store</td><td><?php echo h($s['location_name']); ?></td></tr>
      <tr><td>Period</td><td>
        <?php echo $s['period_from'] ? date('d M Y',strtotime($s['period_from'])).' to '.date('d M Y',strtotime($s['period_to'])) : '—'; ?>
      </td></tr>
      <tr><td>Created</td><td><?php echo date('d M Y', strtotime($s['created'])); ?> by <?php echo h($s['created_by_name']); ?></td></tr>
      <?php if($stStatus >= 2): ?>
      <tr><td>Approved</td><td><?php echo date('d M Y', strtotime($s['approved_on'])); ?> by <?php echo h($s['approved_by_name']); ?></td></tr>
      <?php endif; ?>
      <?php if($stStatus >= 3): ?>
      <tr><td>Invoice No</td><td><?php echo h($s['invoice_no']); ?></td></tr>
      <tr><td>Invoice Date</td><td><?php echo $s['invoice_date'] ? date('d M Y',strtotime($s['invoice_date'])) : '—'; ?></td></tr>
      <tr><td>Processed</td><td><?php echo date('d M Y', strtotime($s['processed_on'])); ?> by <?php echo h($s['processed_by_name']); ?></td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Line Items -->
<table class="items">
  <thead>
    <tr>
      <th>#</th>
      <th>Service / Item</th>
      <th>Description</th>
      <th>Unit</th>
      <th>Qty</th>
      <th>Unit Price (₹)</th>
      <th>Discount (₹)</th>
      <th>Tax %</th>
      <th>Tax Amt (₹)</th>
      <th>Total (₹)</th>
      <?php if($stStatus >= 2): ?><th>Approved (₹)</th><?php endif; ?>
      <?php if($stStatus >= 3): ?><th>Processed (₹)</th><?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php $n=1; foreach($items as $item): $i=$item['Service_purchase_order_item']; ?>
    <tr>
      <td><?php echo $n++; ?></td>
      <td><?php echo h($i['service_name']); ?></td>
      <td><?php echo h($i['description']); ?></td>
      <td><?php echo h($i['unit']); ?></td>
      <td class="text-right"><?php echo $i['quantity']; ?></td>
      <td style="text-align:right"><?php echo number_format($i['unit_price'],2); ?></td>
      <td style="text-align:right"><?php echo number_format($i['discount_value'],2); ?></td>
      <td style="text-align:right"><?php echo $i['tax_percentage']; ?>%</td>
      <td style="text-align:right"><?php echo number_format($i['tax_value'],2); ?></td>
      <td style="text-align:right"><strong><?php echo number_format($i['total_amount'],2); ?></strong></td>
      <?php if($stStatus >= 2): ?><td style="text-align:right"><?php echo number_format($i['approved_amount'],2); ?></td><?php endif; ?>
      <?php if($stStatus >= 3): ?><td style="text-align:right"><?php echo number_format($i['processed_amount'],2); ?></td><?php endif; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="clearfix">
  <div class="totals-box">
    <table>
      <tr><td>Sub-total</td><td style="text-align:right">₹ <?php echo number_format($s['subtotal'],2); ?></td></tr>
      <tr><td>Discount</td><td style="text-align:right">₹ <?php echo number_format($s['discount'],2); ?></td></tr>
      <tr><td>Total Tax</td><td style="text-align:right">₹ <?php echo number_format($s['total_tax'],2); ?></td></tr>
      <tr class="grand"><td>Total Amount</td><td style="text-align:right">₹ <?php echo number_format($s['total_amount'],2); ?></td></tr>
      <?php if($stStatus >= 2): ?>
      <tr><td>Approved Amt</td><td style="text-align:right">₹ <?php echo number_format($s['approved_amount'],2); ?></td></tr>
      <?php endif; ?>
      <?php if($stStatus >= 3): ?>
      <tr><td>Processed Amt</td><td style="text-align:right">₹ <?php echo number_format($s['processed_amount'],2); ?></td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php if($s['remarks']): ?>
<div style="margin-top:50px;clear:both;font-size:11px"><strong>Remarks:</strong> <?php echo h($s['remarks']); ?></div>
<?php endif; ?>

<div class="sign-row" style="margin-top:50px">
  <div class="sign-box">Prepared By</div>
  <div class="sign-box">Approved By</div>
  <div class="sign-box">Authorized Signatory</div>
</div>
</body>
</html>
