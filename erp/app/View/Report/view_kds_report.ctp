<div class="row mb-3">
    <div class="col-sm-11">
        <h2 class="page-title">View KDS Track</h2>
    </div>
    <div class="col-sm-1 text-end">
        <a href="kds_reports" class="btn btn-danger">Back</a>
    </div>
</div>

<?php foreach ($stations as $stationName => $products): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-header  text-white" data-bs-toggle="collapse" href="#station_<?= md5($stationName) ?>" role="button" aria-expanded="true" aria-controls="station_<?= md5($stationName) ?>" style="cursor:pointer;">
            <h4 class="mb-0"><?= h($stationName) ?></h4>
        </div>
        <div class="collapse show" id="station_<?= md5($stationName) ?>">
            <div class="card-body">

                <?php foreach ($products as $productName => $items): ?>
                    <div class="mb-3">
                        <h5 class="text-secondary" data-bs-toggle="collapse" href="#product_<?= md5($stationName.$productName) ?>" role="button" aria-expanded="true" aria-controls="product_<?= md5($stationName.$productName) ?>" style="cursor:pointer;">
                            <?= h($productName) ?> <small class="text-muted">(<?= count($items) ?> items)</small>
                        </h5>
                        <div class="collapse show" id="product_<?= md5($stationName.$productName) ?>">
                            <?php foreach ($items as $product):
                                $data = isset($product['kds_tracks']) ? $product['kds_tracks'] : (isset($product[0]) ? $product[0] : $product);
                                $status = $data['status'];
                                switch ($status) {
                                    case 0: $statusLabel = 'Unfire'; $statusClass = 'badge bg-secondary'; break;
                                    case 1: $statusLabel = 'Fire'; $statusClass = 'badge bg-danger'; break;
                                    case 2: $statusLabel = 'Hold'; $statusClass = 'badge bg-warning text-dark'; break;
                                    case 3: $statusLabel = 'Start'; $statusClass = 'badge bg-info text-dark'; break;
                                    case 4: $statusLabel = 'Bump'; $statusClass = 'badge bg-primary'; break;
                                    case 5: $statusLabel = 'Flag'; $statusClass = 'badge bg-danger'; break;
                                    case 6: $statusLabel = 'Send to Table'; $statusClass = 'badge bg-success'; break;
                                    case 7: $statusLabel = 'Recall'; $statusClass = 'badge bg-warning text-dark'; break;
                                    default: $statusLabel = 'Unknown'; $statusClass = 'badge bg-dark'; break;
                                }
                            ?>
                            <div class="row align-items-center border-bottom py-2">
                                <div class="col-md-4">
                                    <strong>Group ID:</strong> <?= h($data['kds_group_id']) ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Status:</strong> <?= h($statusLabel) ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Time:</strong> <?= h(date('Y-m-d H:i:s', strtotime($data['created']))) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Include Bootstrap JS for collapse functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
