    
        <div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">Create Booking</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                        <div class="col-sm-1">
                            <div class="page-title-box">
                                <a href="booking_list"><button  class='btn btn-danger' type="button">Back</button></a>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('Search', array('url'=>'create_booking')); 
                        ?>
                                        <div class="row">
                                            <div class="col-md-2">
                                                <label>Booking Date</label>
                                                
                                                <?php
                                                    $bookdate = date('Y-m-d', strtotime($bookdate));
                                                    echo $this->Form->control('bookdate', array(
                                                        'type' => 'date',
                                                        'name' =>'bookdate',
                                                        'label' => 'Booking Date',
                                                        'class' => 'form-control',
                                                        'id' =>'bookdate',
                                                        'value' => $bookdate
                                                    ));
                                                ?>
                                            </div>
                                            <div class="col-md-2">
                                                <?php
                                                    if($booktime=='00'){
                                                        $selected=true;
                                                    }
                                                    $options = [
                                                        '22' => '22 PM',
                                                        '23' => '23 PM',
                                                        '00' => '00 AM',
                                                        '01' => '01 AM',
                                                        '02' => '02 AM',
                                                        '03' => '03 AM',
                                                        '04' => '04 AM',
                                                        '05' => '05 AM',
                                                        '06' => '06 AM',
                                                        '07' => '07 AM'
                                                    ];
                                                    echo $this->Form->input('booktime', array('div'=>array('class'=>'form-group'),'name'=>'booktime', 'label'=>'Booking Time', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','options'=>$options,    'default' => $booktime,'id'=>'booktime','required'=>true));
                                                ?>
                                            </div>
    
                                            <div class="col-md-2">
                                                <label></label><br>
                                                <?php echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit'));
                                                ?>
                                            </div>
                                        </div>
                                    </form>
                                    <br>
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
                                                        $todayday = date('D');
                                                        $tableno=$table['order_tables']['number'];
                                                        if($todayday=='Fri' || $todayday=='Sat'){
                                                            $cover_charge=$table['order_tables']['weekend_charge'];
                                                            if(!$cover_charge){
                                                                $cover_charge=$table['order_tables']['cover_charge'];
                                                            }
                                                        }else{
                                                            $cover_charge=$table['order_tables']['cover_charge'];
                                                        }
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
                    var bookdate = $('#bookdate').val();
                    var booktime = $('#booktime').val();
                            $.ajax({
                            type: 'POST',
                            url: "<?php echo $this->Html->url(array('controller' => 'dashboard', 'action' => 'book_now')); ?>",
                            data: {
                                number: $(this).attr('value'),
                                 bookdate: bookdate,
                                booktime: booktime
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

