<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Create Payment Partner</h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="payment_partner_list"><button class='btn btn-danger' type="button">Back</button></a>
            </h2>
        </div><!--end page-title-box-->
    </div><!--end col-->
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('PaymentPartner'); ?>
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php 
                            echo $this->Form->input('name', array(
                                'div' => array('class' => 'form-group'),
                                'label' => array('class' => 'form-label', 'text' => 'Payment Partner Name'),
                                'class' => 'form-control',
                                'required' => 'required'
                            ));
                        ?>
                    </div>
                </div>
                <a href="payment_partner_list"><button class='btn btn-danger' type="button">Cancel</button></a>
                <?php echo $this->Form->button('Save', array('class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn')); ?>
                <?php echo $this->Form->end(); ?>
            </div>
        </div>
    </div> 
</div>
