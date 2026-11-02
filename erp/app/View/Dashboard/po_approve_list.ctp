<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h2 class="page-title">Purchase Order List</h2>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="row mt-3">
    <div class="col-sm-12">
        <?= $this->Form->create('Search', ['url' => 'po_approve_list']); ?>

        <?php
        $options = [];
        if ($this->Session->read('Auth.User.role') == 'admin') {
            $options[''] = 'All Stores';
        }
        foreach ($stores as $store) {
            $options[$store['Location']['id']] = $store['Location']['name'];
        }

        echo $this->Form->input('cashier', [
            'label' => 'Select Store',
            'class' => 'form-control',
            'options' => $options
        ]);
        ?>

        <?= '<br>'.$this->Form->input('startdate', [
            'label' => 'Select Date',
            'class' => 'form-control calendarmultiple',
            'name' => 'daterange',
            'required' => true,
            'value' => $startdate
        ]) ?>

        <?= '<br>'.$this->Form->button('View', ['class' => 'btn btn-info mt-2']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mt-4">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#submitted">Pending</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#approved">Approved</a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">

<?php
$tabs = [
    'submitted' => 1,
    'approved'  => 2
];

foreach ($tabs as $tabId => $statusVal):
?>

<div class="tab-pane fade <?= $tabId == 'submitted' ? 'show active' : '' ?>" id="<?= $tabId ?>" style="text-decoration:none;">
    <div class="card mt-3">
        <div class="card-body">
            <table id="table_<?= $tabId ?>" 
       class="display table table-hover table-condensed" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>PO ID</th>
                        <th>Indent ID</th>
                        <th>Location</th>
                        <th>Vendor</th>
                        <th>Total</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $x = 1;
                foreach ($purchaseOrders as $row):
                    $po = $row['Inventory_purchase_order'];
                    if ($po['status'] != $statusVal) continue;

                    $poid = h($po['p_o_id']);
                    $formId = 'pdfForm_' . $poid;
                ?>
                    <tr>
                        <td><?= $x++ ?></td>
                        <td><?= $poid ?></td>
                        <td><?= h($po['internal_indent_id']) ?></td>
                        <td><?= h($po['location_name']) ?></td>
                        <td><?= h($po['vendor_name']) ?></td>
                        <td><?= h($po['total_amount']) ?></td>
                        <td><?= h($po['created']) ?></td>
                        <td>
                            <a href="view_approve_po?id=<?= $poid ?>" class="btn btn-info btn-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endforeach; ?>
</div>

<script>
$(document).on('click', '.btn-pdf', function () {
    document.getElementById($(this).data('form')).submit();
});
</script>

<style>
.nav-tabs .nav-link {
    background: #f8f9fa;
    color: #495057;
    margin-right: 4px;
}
.nav-tabs .nav-link.active {
    background: #28a745;
    color: #fff;
}
/* Remove underline from table links */
table.dataTable a {
    text-decoration: none !important;
}
table.dataTable a:hover {
    text-decoration: none !important;
}

</style>
<script>
$(document).ready(function () {

    // Init first tab immediately
    $('#table_submitted').DataTable({
        ordering: true,
        searching: true,
        paging: true
    });

    // Init when tab is clicked (IMPORTANT)
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href"); // #submitted or #approved
        const tableId = '#table_' + target.replace('#', '');

        if (!$.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable({
                ordering: true,
                searching: true,
                paging: true
            });
        }
    });

    // PDF button
    $(document).on('click', '.btn-pdf', function () {
        document.getElementById($(this).data('form')).submit();
    });

});
</script>
