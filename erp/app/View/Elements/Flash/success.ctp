<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="alert alert-success alert-dismissible fade in">
           <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
           <strong><?php echo h($message) ?></strong>
           <?php if(!empty($params['barcodelink'])) { ?>
           <a href="<?php echo $params['barcodelink']; ?>" target="_blank">Print Barcode</a>
           <?php } ?>
        </div>
    </div>
</div>
