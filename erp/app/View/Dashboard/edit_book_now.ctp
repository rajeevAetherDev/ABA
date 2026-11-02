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
            <div class="col-md-7">
                <span><strong>Name:</strong> <?php echo h($name); ?></span><br><br>
            </div>
            <div class="col-md-5">
                <span><strong>Phone:</strong> <?php echo h($phone); ?></span><br><br>
            </div>
            <div class="col-md-7">
                <span><strong>Previous Table:</strong> <?php echo h($prev_table_number); ?></span><br><br>
            </div>
            <div class="col-md-5">
                <span><strong>New Table:</strong> <?php echo h($tablesdata[0]['order_tables']['number']); ?></span><br><br>
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
                        echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'type'=>'hidden', 'class'=>'form-control','value'=>$name));
                        echo $this->Form->input('phone', array('div'=>array('class'=>'form-group'), 'type'=>'hidden', 'class'=>'form-control','value'=>$phone));
                        echo $this->Form->input('payment_type', array('div'=>array('class'=>'form-group'), 'type'=>'hidden', 'class'=>'form-control','value'=>$payment_type));
                        echo $this->Form->input('pay_option', array('div'=>array('class'=>'form-group'), 'type'=>'hidden', 'class'=>'form-control','value'=>$pay_option));
                        echo $this->Form->input('pay_option_value', array('div'=>array('class'=>'form-group'), 'type'=>'hidden', 'class'=>'form-control','value'=>$pay_option_value));
                        echo $this->Form->input('remarks', array('div'=>array('class'=>'form-group'), 'type'=>'hidden', 'class'=>'form-control','value'=>$remarks));
                        echo $this->Form->input('booking_id', array('div'=>array('class'=>'form-group'), 'type'=>'hidden', 'class'=>'form-control','value'=>$booking_id));
                    ?>
                </div>
            </div>
            
            <a href="edit_booking?id=<?php echo h($booking_id);?>"><button  class='btn btn-danger' type="button">Cancel</button></a>
                                    
            <?php echo $this->Form->button('Update', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                        
    </div>
