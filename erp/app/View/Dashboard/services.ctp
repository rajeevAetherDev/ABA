<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Services List</h2>
        </div>
    </div>

    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="add_service">
                    <button class='btn btn-success' type="button" style="float:right">
                        Add Service
                    </button>
                </a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
<div class="col-sm-12">
<div class="card">
<div class="card-body">

<div class="row">
<div class="col-lg-12">

<table id="example" class="display table table-hover table-condensed" width="100%">
<thead>
<tr>
    <th>#</th>
    <th>Name</th>
    <th>Description</th>
    <th>Status</th>
    <th>Created</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php $x = 1; foreach ($services as $service): 
    $id = $service['Service']['id'];

    if ($service['Service']['status'] == 0) {
        $status = 'Inactive';
        $btnClass = 'btn-success';
        $btnText = 'Mark Active';
    } else {
        $status = 'Active';
        $btnClass = 'btn-danger';
        $btnText = 'Mark Inactive';
    }
?>

<tr>
<td><?php echo $x; ?></td>
<td><?php echo $service['Service']['name']; ?></td>
<td><?php echo $service['Service']['description']; ?></td>
<td><?php echo $status; ?></td>
<td><?php echo $service['Service']['created']; ?></td>

<td>
<a href="edit_service?id=<?php echo $id; ?>">
    <button class="btn btn-info">Edit</button>
</a>

<button class="btn <?php echo $btnClass; ?> toggle-status" data-id="<?php echo $id; ?>">
    <?php echo $btnText; ?>
</button>
</td>
</tr>

<?php $x++; endforeach; ?>
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

$(document).on('click', '.toggle-status', function () {

	var id = $(this).data('id');

	$.ajax({
		url: '<?php echo Router::url(['controller'=>'dashboard','action'=>'delete_service']); ?>',
		type: 'POST',
		data: { _id: id },
		dataType: 'json',

		success: function (res) {
			if (res.status) {
				location.reload();
			} else {
				alert(res.msg || 'Failed');
			}
		},

		error: function (xhr) {
			console.log(xhr.responseText);
			alert('Server error');
		}
	});

});

</script>