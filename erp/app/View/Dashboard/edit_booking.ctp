    
        <div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Switch Booking</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
                            <div class="page-title-box">
                                <a href="booking_list"><button  class='btn btn-lg btn-danger' type="button">Back</button></a>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>Booking Date - </label>
                                                <?php
                                                    $bookdate = date('d-m-Y', strtotime($bookdate));
                                                    echo '<b>'.$bookdate.'</b>';
                                                ?>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Booking Time - </label>
                                                <?php
                                                $newbooktime=$booktime;
                                                    if($booktime==22){
                                                        $timestatus='PM';
                                                        $booktime=10;
                                                    }elseif($booktime==23){
                                                        $timestatus='PM';
                                                        $booktime=11;
                                                    }else{
                                                        $timestatus='AM';
                                                    }
                                                    echo '<b>'.$booktime.' '.$timestatus.'</b>';
                                                ?>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Table Number - </label>
                                                <?php
                                                    echo '<b>'.$table_number.'</b>';
                                                ?>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Cover Charge - </label>
                                                <?php
                                                    echo '<b>'.$cover_charge.'</b>';
                                                ?>
                                            </div>
                                            <div class="col-md-3">
                                                <label>PAX - </label>
                                                <?php
                                                    echo '<b>'.$pax.'</b>';
                                                ?>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Name - </label>
                                                <?php
                                                    echo '<b>'.$name.'</b>';
                                                ?>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Phone - </label>
                                                <?php
                                                    echo '<b>'.$phone.'</b>';
                                                ?>
                                            </div>
                                        </div>
                                    
                                    <br>
                                    <?php 
                                        $newbookdate = date('Y-m-d', strtotime($bookdate));
                                                    
                                        echo $this->Form->create('User'); 
                                        echo $this->Form->input('id', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'booking_id','value'=>$booking_id));
                                        echo $this->Form->input('cover_charge', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'cover_charge','value'=>$cover_charge));
                                        echo $this->Form->input('pax', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'pax','value'=>$pax));
                                        echo $this->Form->input('bookdate', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'bookdate','value'=>$newbookdate));
                                        echo $this->Form->input('booktime', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'booktime','value'=>$newbooktime));
                                        echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'name','value'=>$name));
                                        echo $this->Form->input('phone', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'phone','value'=>$phone));
                                        echo $this->Form->input('table_number', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'table_number','value'=>$table_number));
                                        echo $this->Form->input('payment_type', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'payment_type','value'=>$payment_type));
                                        echo $this->Form->input('pay_option', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'pay_option','value'=>$pay_option));
                                        echo $this->Form->input('pay_option_value', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'pay_option_value','value'=>$pay_option_value));
                                        echo $this->Form->input('remarks', array('div'=>array('class'=>'form-group'), 'type'=>'hidden','id'=>'remarks','value'=>$remarks));
                                        echo $this->Form->end(); 
                                    ?>
                                    
                                    <div class="container">
                                        <table class="booking-table">
                                            <?php
                                                $counter = 0;

                                                foreach ($tables as $table): 
                                                    if ($counter % 4 == 0) {
                                                        if ($counter != 0) {
                                                            echo "</tr>";
                                                        }
                                                        echo "<tr>";
                                                    }
                                                    if(!isset($table[0]['bookstatus'])){
                                                            $status='Vacant';
                                                            $classstatus='vacant';
                                                                                $href = "<a href='#' data-toggle='modal' data-target='#myModal{$table['order_tables']['number']}' id='getdataid{$table['order_tables']['number']}' value='{$table['order_tables']['number']}'>";
}else{
                                                            $status='Booked';
                                                            $classstatus='booked';
                                                            $href = '';
                                                        }
                                                        
                                                        $tableno=$table['order_tables']['number'];
                                                        $cover_charge=$table['order_tables']['cover_charge'];
                                                        $pax=$table['order_tables']['pax'];
                    echo "<td class='$classstatus'>$href <b>$tableno</b><span>Cover Charge - Rs. $cover_charge</span><span>Pax - $pax</span>" . ($href ? "</a>" : "") . "</td>";
                                                                   $counter++;

                                                    endforeach;
                                                                echo "</tr>";

                                                ?>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>

                        
       <!-- Modals for each table -->
    <?php foreach ($tables as $table): ?>
        <?php 
        if(!isset($table[0]['bookstatus'])) : ?>
            <div class="modal fade" id="myModal<?php echo $table['order_tables']['number']; ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel">Table Details For - <?php print_r($table['order_tables']['number']);?></h4>
                        </div>
                        <div class="modal-body" id="modelbody<?php echo $table['order_tables']['number']; ?>">
                             <!--Content will be loaded here from the AJAX call -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function(){
                    $('#getdataid<?php echo $table['order_tables']['number']; ?>').click(function () {
                    var booking_id = $('#booking_id').val();
                    var cover_charge = $('#cover_charge').val();
                    var pax = $('#pax').val();
                    var bookdate = $('#bookdate').val();
                    var booktime = $('#booktime').val();
                    var name = $('#name').val();
                    var phone = $('#phone').val();
                    var table_number = $('#table_number').val();
                    var payment_type = $('#payment_type').val();
                    var pay_option = $('#pay_option').val();
                    var pay_option_value = $('#pay_option_value').val();
                    var remarks = $('#remarks').val();
                            $.ajax({
                            type: 'POST',
                            url: "<?php echo $this->Html->url(array('controller' => 'dashboard', 'action' => 'edit_book_now')); ?>",
                            data: {
                                number: $(this).attr('value'),
                                 booking_id: booking_id,
                                cover_charge: cover_charge,
                                pax: pax,
                                bookdate: bookdate,
                                booktime: booktime,
                                name: name,
                                phone: phone,
                                prev_table_number:table_number,
                                payment_type:payment_type,
                                pay_option:pay_option,
                                pay_option_value:pay_option_value,
                                remarks:remarks
                            },
                            beforeSend: function() {
                                $("#modelbody<?php echo $table['order_tables']['number']; ?>").html("loading.....");
                            },
                            success: function(response){
                                $('#modelbody<?php echo $table['order_tables']['number']; ?>').html(response);
                            },
                            error: function() {
                                $('#modelbody<?php echo $table['order_tables']['number']; ?>').html("An error occurred");
                            }
                        });
                    });
                });
            </script>
        <?php endif; ?>
    <?php endforeach; ?>

