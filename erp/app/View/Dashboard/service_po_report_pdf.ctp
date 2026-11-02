<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Service PO Report — <?php echo date('d M Y',strtotime($startDate)).' to '.date('d M Y',strtotime($endDate)); ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 15px; color: #000; }
  .report-header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 12px; padding-bottom: 8px; }
  .report-header h2 { font-size: 16px; margin: 0 0 4px; text-transform: uppercase; }
  .report-header p { margin: 2px 0; font-size: 12px; }
  .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; }
  .summary-box { border: 1px solid #888; padding: 8px 14px; width: 32%; text-align: center; }
  .summary-box .val { font-size: 16px; font-weight: bold; margin-top: 4px; }
  .summary-box .lbl { font-size: 10px; color: #555; text-transform: uppercase; }
  table.rt { width: 100%; border-collapse: collapse; margin-top: 8px; }
  table.rt th { background: #2c3e50; color: #fff; padding: 6px 6px; text-align: left; font-size: 10px; white-space: nowrap; }
  table.rt td { border: 1px solid #ddd; padding: 4px 6px; font-size: 10px; }
  table.rt tr:nth-child(even) td { background: #f5f5f5; }
  table.rt tfoot td { background: #2c3e50; color: #fff; font-weight: bold; }
  .badge { padding: 1px 6px; border-radius: 3px; font-size: 10px; color: #fff; }
  .s1 { background: #e67e22; }
  .s2 { background: #2980b9; }
  .s3 { background: #27ae60; }
  @media print {
    .no-print { display: none !important; }
    @page { margin: 8mm; size: landscape; }
    body { font-size: 10px; }
  }
</style>
</head>
<body>

<div class="no-print" style="text-align:right;margin-bottom:10px">
  <button onclick="window.print()" style="padding:6px 16px;background:#2c3e50;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:13px">&#128438; Print / Save as PDF</button>
  <button onclick="window.history.back()" style="padding:6px 14px;background:#888;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:13px;margin-left:8px">Back</button>
</div>

<div class="report-header">
  <h2>TGIF — Service Purchase Order Report</h2>
  <p><?php echo date('d M Y',strtotime($startDate)).' to '.date('d M Y',strtotime($endDate)); ?></p>
  <p>Generated: <?php echo date('d M Y H:i'); ?></p>
</div>

<!-- Summary cards -->
<div class="summary-row">
  <div class="summary-box">
    <div class="lbl">Total Submitted Value</div>
    <div class="val">₹ <?php echo number_format($grandTotal['submitted'],2); ?></div>
  </div>
  <div class="summary-box">
    <div class="lbl">Total Approved Value</div>
    <div class="val">₹ <?php echo number_format($grandTotal['approved'],2); ?></div>
  </div>
  <div class="summary-box">
    <div class="lbl">Total Processed Value</div>
    <div class="val">₹ <?php echo number_format($grandTotal['processed'],2); ?></div>
  </div>
</div>

<table class="rt">
  <thead>
    <tr>
      <th>#</th>
      <th>SPO ID</th>
      <th>Vendor</th>
      <th>Store</th>
      <th>Service Type</th>
      <th>Period</th>
      <th>Invoice No</th>
      <th>Lines</th>
      <th>Total (₹)</th>
      <th>Approved (₹)</th>
      <th>Processed (₹)</th>
      <th>Status</th>
      <th>Created</th>
    </tr>
  </thead>
  <tbody>
    <?php $n=1; $tt=0; $ta=0; $tp=0;
    foreach($spos as $spo): ?>
    <tr>
      <td><?php echo $n++; ?></td>
      <td><?php echo h($spo['spo_id']); ?></td>
      <td><?php echo h($spo['vendor']); ?></td>
      <td><?php echo h($spo['store']); ?></td>
      <td><?php echo h($spo['service_type']); ?></td>
      <td style="white-space:nowrap">
        <?php if($spo['period_from']): ?>
          <?php echo date('d M Y',strtotime($spo['period_from'])); ?><br>
          to <?php echo date('d M Y',strtotime($spo['period_to'])); ?>
        <?php else: echo '—'; endif; ?>
      </td>
      <td><?php echo h($spo['invoice_no']) ?: '—'; ?></td>
      <td style="text-align:center"><?php echo $spo['line_count']; ?></td>
      <td style="text-align:right"><?php echo number_format($spo['total_amount'],2); ?></td>
      <td style="text-align:right"><?php echo $spo['status']>=2 ? number_format($spo['approved_amount'],2) : '—'; ?></td>
      <td style="text-align:right"><?php echo $spo['status']>=3 ? number_format($spo['processed_amount'],2) : '—'; ?></td>
      <td>
        <?php $bc=array(1=>'s1',2=>'s2',3=>'s3')[$spo['status']]??''; ?>
        <span class="badge <?php echo $bc; ?>"><?php echo $spo['status_label']; ?></span>
      </td>
      <td style="white-space:nowrap"><?php echo date('d M Y',strtotime($spo['created'])); ?><br>
        <small><?php echo h($spo['created_by']); ?></small></td>
    </tr>
    <?php $tt+=$spo['total_amount']; $ta+=$spo['approved_amount']; $tp+=$spo['processed_amount']; endforeach; ?>
    <?php if(empty($spos)): ?>
    <tr><td colspan="13" style="text-align:center;color:#888">No records found for the selected period.</td></tr>
    <?php endif; ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="8" style="text-align:right">Grand Total</td>
      <td style="text-align:right">₹ <?php echo number_format($tt,2); ?></td>
      <td style="text-align:right">₹ <?php echo number_format($ta,2); ?></td>
      <td style="text-align:right">₹ <?php echo number_format($tp,2); ?></td>
      <td colspan="2"></td>
    </tr>
  </tfoot>
</table>

</body>
</html>
