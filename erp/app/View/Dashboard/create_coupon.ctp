<style>
  .radio-inline label {
    display: inline-block; /* Ensure the labels are displayed inline */
    margin-right: 10px; /* Adjust spacing between radio buttons and labels */
}

</style>
<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create coupon</h2>
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
                                                echo $this->Form->input('code', array('label'=>'Coupon Code', 'class'=>'form-control','required'=>'required'));
                                                echo '<br>'.$this->Form->input('coupon_value', array( 'label'=>'Coupon Value(%)', 'class'=>'form-control','required'=>'required','type'=>'number','step' => '0.1'));
                                                echo '<br>'.$this->Form->input('expiry_date', array( 'label'=>'Coupon expiry date', 'class'=>'form-control','required'=>'required','type'=>'date'));
                                            ?>
                                    </div>
                                    </div>
                                    <a href="coupon_list"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>
                    
<style>
    .hidden{
        display:none;
    }
    
</style>