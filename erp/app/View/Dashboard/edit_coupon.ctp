<style>
  .radio-inline label {
    display: inline-block; /* Ensure the labels are displayed inline */
    margin-right: 10px; /* Adjust spacing between radio buttons and labels */
}

</style>
<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Update Coupon</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                          <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="coupon_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('User'); ?>
                                    <div class="form-group row">
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('id', array('value' => $coupons[0]['coupons']['id'],'type'=>'hidden'));
                                                echo $this->Form->input('code', array( 'label'=>'Coupon Code', 'class'=>'form-control','value'=>$coupons[0]['coupons']['code'],'readonly'=>true));
                                                echo '<br>'.$this->Form->input('value', array( 'label'=>'Coupon Value', 'class'=>'form-control','value'=>$coupons[0]['coupons']['value'],'type'=>'number','step' => '0.1'));
                                                echo '<br>'.$this->Form->input('expiry', array( 'label'=>'Expiry Date', 'class'=>'form-control','type'=>'date','value'=>$coupons[0]['coupons']['expiry_date']));
                                            ?>
                                        </div>
                                    </div>
                                    <a href="coupon_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Update', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
<style>
    .hidden{
        display:none;
    }
    
</style>        