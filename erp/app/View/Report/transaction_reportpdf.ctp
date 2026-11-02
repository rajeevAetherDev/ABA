<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Transaction Report</title>

<?php
echo $this->Html->css(array(
'../assets/plugins/bootstrap/css/bootstrap.min'
));
?>

<style>
body{
    font-size:12px;
}

h3{
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th, table td{
    border:1px solid #000;
    padding:6px;
    text-align:left;
}

table th{
    background:#f2f2f2;
}

@media print{
    body{
        margin:10px;
    }
}
</style>

<script>
window.onload = function(){
    window.print();
}
</script>

</head>

<body>

<div class="container-fluid">

<h3 class="text-center">
Transaction Report From <?php echo $startDatetime; ?> To <?php echo $endDatetime; ?>
</h3>

<?php if(!empty($datas)) : ?>

<table>

<thead>
<tr>
<th>#</th>
<th>Transaction Id</th>
<th>Bill No</th>
<th>Store Name</th>
<th>Table Number</th>
<th>PAX</th>
<th>Phone</th>
<th>Name</th>
<th>Payment Mode</th>
<th>Amount</th>
<th>Status</th>
<th>Created</th>
</tr>
</thead>

<tbody>

<?php 
$count = 1;
foreach ($datas as $data): 
?>

<tr>
<td><?php echo $count; ?></td>
<td><?php echo $data['txnid']; ?></td>
<td><?php echo $data['bill_no']; ?></td>
<td><?php echo $data['store']; ?></td>
<td><?php echo $data['table_number']; ?></td>
<td><?php echo $data['pax']; ?></td>
<td><?php echo $data['phone']; ?></td>
<td><?php echo $data['name']; ?></td>
<td><?php echo $data['mode']; ?></td>
<td><?php echo number_format($data['amount'],2); ?></td>
<td><?php echo $data['status']; ?></td>
<td><?php echo $data['created']; ?></td>
</tr>

<?php
$count++;
endforeach;
?>

</tbody>

</table>

<?php endif; ?>

</div>

</body>
</html>