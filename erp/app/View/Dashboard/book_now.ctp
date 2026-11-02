<div>
        <div class="row">
            <div class="col-md-7">
                <span><strong>Cover Charge:</strong> Rs. <?php echo h($tablesdata[0]['order_tables']['cover_charge']); ?></span><br><br>
            </div>
            <div class="col-md-5">
                <span><strong>Pax:</strong> <?php echo h($tablesdata[0]['order_tables']['pax']); ?></span><br><br>
            </div>
            <div class="col-md-7">
                <span><strong>Booking Date :</strong><?php echo '&nbsp'. h($bookdate); ?></span><br><br>
            </div>
            <div class="col-md-5">
                <?php
                    if($booktime==22){
                        $timestatus='PM';
                        $booktime=10;
                    }elseif($booktime==23){
                        $timestatus='PM';
                        $booktime=11;
                    }else{
                        $timestatus='AM';
                    }
                    
                ?>
                <span><strong>Booking Time :</strong> <?php echo h($booktime.' '.$timestatus); ?></span><br><br>
            </div>
        </div>
        
        <?php echo $this->Form->create('User'); ?>
           
            <div class="form-group row">
                
                <div class="col-sm-6">
                    <?php 
                        echo $this->Form->input('id', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','value'=>$tablesdata[0]['order_tables']['id']));
                        echo $this->Form->input('bookdate', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','value'=>$bookdate));
                        echo $this->Form->input('booktime', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','value'=>$booktime));
                        echo $this->Form->input('ref_id', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','value'=>$tablesdata[0]['order_tables']['reference_id']));
                        echo $this->Form->input('pax', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','value'=>$tablesdata[0]['order_tables']['pax']));
                        echo $this->Form->input('cover_charge', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','value'=>$tablesdata[0]['order_tables']['cover_charge']));
                        echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Customer Name', 'class'=>'form-control','required'=>'required'));
                    ?>
                </div>
                <div class="col-sm-6">
                    <?php    
                        echo $this->Form->input('phone', array(
                        'div' => array('class' => 'form-group'),
                        'label' => 'Customer Phone',
                        'class' => 'form-control',
                        'required' => 'required',
                        'type' => 'number',
                        'min' => '1000000000', // Minimum value for a 10-digit number
                        'max' => '9999999999', // Maximum value for a 10-digit number
                        'oninput' => 'this.value = this.value.slice(0, 10);' // Ensure input is no more than 10 digits
                        ));
                    ?>
                </div>
                <div class="col-sm-6">
                <?php
                    echo "<h5>Payment Type</h5>";
                    echo $this->Form->input('postpaid', array('label' => 'Post Paid', 'type' => 'checkbox','id'=>'postpaid', 'class' => 'payment-checkbox','checked'=>true));
                    echo $this->Form->input('prepaid', array('label' => 'Pre Paid', 'type' => 'checkbox','id'=>'prepaid', 'class' => 'payment-checkbox'));
                ?>
                </div>
                <div class="col-sm-6">
                <?php
                    echo $this->Form->input('remarks', array('label' => 'Remarks','class'=>'form-control','rows'=>3));
                ?>
                </div>
                <div class="col-sm-6 hidden" id="pay_option">
                <?php
                    $options = [
                        '1' => 'CASH',
                        '2' => 'CARD',
                        '6' => 'UPI'
                    ];
                    echo $this->Form->input('pay_type', array('div'=>array('class'=>'form-group'),'name'=>'pay_type', 'label'=>'Payment Type', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,'id'=>'pay_type'));
                ?>
                </div>
            </div>
            <a href="create_booking"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    
        <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                        
    </div>
<script>
    $(document).ready(function() {
        // Ensure only one checkbox can be checked at a time
        const checkboxes = document.querySelectorAll('.payment-checkbox');
        
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    checkboxes.forEach(function(otherCheckbox) {
                        if (otherCheckbox !== checkbox) {
                            otherCheckbox.checked = false;
                        }
                    });
                }
            });
        });

        // Validate at least one checkbox is checked before form submission
        $('#submitBtn').click(function(event) {
            let atLeastOneChecked = false;
            checkboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    atLeastOneChecked = true;
                }
            });

            if (!atLeastOneChecked) {
                event.preventDefault(); // Prevent form submission if no checkbox is checked
                alert('Please select at least one type.');
            }
        });
        $('#prepaid').on('change', function() {
            if ($(this).is(':checked')) {
                $('#pay_option').removeClass('hidden');
            } else {
                $('#pay_option').addClass('hidden');
            }
        });

        $('#postpaid').on('change', function() {
            if ($(this).is(':checked')) {
                $('#pay_option').addClass('hidden');
            }
        });
    });
</script>
