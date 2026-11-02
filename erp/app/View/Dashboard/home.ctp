<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                </div>
                <?php echo $this->Form->create('home'); ?>
                <h4 class="page-title">LIVE DATA
                <?php echo '&nbsp&nbsp&nbsp'.$this->Form->button('REFRESH', array('class'=>'btn btn-info', 'type'=>'submit'));
                ?>
                </h4>
                <?php echo $this->Form->end(); 
                ?>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <div class="row">
        <div class="col-md-4 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Open Table Count</p>
                            <h3 class="my-3"><?php echo $open_table;?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-4 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Open Table Amount</p>
                            <h3 class="my-3"><?php echo round($open_table_amount,2);?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
        <!--<div class="col-md-4 col-lg-3">-->
        <!--    <div class="card report-card">-->
        <!--        <div class="card-body">-->
        <!--            <div class="row d-flex justify-content-center">-->
        <!--                <div class="col-12">-->
        <!--                    <p class="text-dark font-weight-semibold font-14">Total Users</p>-->
        <!--                    <h3 class="my-3"><?php echo $totalusers;?></h3>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--    </div><!--end card-->
        <!--</div> <!--end col-->
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right"></div>
                <h4 class="page-title">DASHBOARD</h4><br>
                <?php echo $this->Form->create('home'); ?>
                <div class="row">
                    <div class="col-sm-6">
                        <?php echo $this->Form->input('startdate', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control calendarmultiple', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>', 'label'=>'Select Date','id'=>'startdate','name'=>'daterange','required'=>'required'	, 'value' => $startdate )); 
                    ?>
                    </div>
                    <div class="col-sm-6" style="margin-top:6px;"><br>
                        <?php echo $this->Form->button('View', array('class'=>'btn btn-info', 'type'=>'submit')); ?>
                    </div>
                    <?php 
                        echo $this->Form->end(); 
                    ?>
                </div>
            </div>
        </div><!--end page-title-box-->
    </div><!--end col-->
    
    <!-- end page title end breadcrumb -->
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Orders</p>
                            <h3 class="my-3"><?php echo !empty($transactions[0][0]['total_count']) ? $transactions[0][0]['total_count'] : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">PAX</p>
                            <h3 class="my-3"><?php echo !empty($transactions[0][0]['pax']) ? $transactions[0][0]['pax'] : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Sales</p>
                            <h3 class="my-3"><?php echo !empty(round($payments[0][0]['total_amount']-$payments[0][0]['no_charge_with_tax'],2)) ? round(round($payments[0][0]['total_amount']-$payments[0][0]['no_charge_with_tax'],2),2) : 0; ?>
                                </h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Taxable Price</p>
                            <h3 class="my-3"><?php echo !empty(round($taxable[0][0]['total_taxable'],2)) ? round(round($taxable[0][0]['total_taxable']-$transactionsubtotal[0][0]['discount'],2),2) : 0; ?>
                                </h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Discount</p>
                            <h3 class="my-3"><?php echo !empty($transactionsubtotal[0][0]['discount']) ? round($transactionsubtotal[0][0]['discount'],2) : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Service Charge</p>
                            <h3 class="my-3"><?php echo !empty($transactions[0][0]['servicecharge']) ? round($transactions[0][0]['servicecharge'],2) : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Tax</p>
                            <h3 class="my-3"><?php echo !empty($transactions[0][0]['total_tax']) ? round($transactions[0][0]['total_tax'],2) : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Cash</p>
                            <h3 class="my-3"><?php echo !empty($payments[0][0]['cash']) ? round($payments[0][0]['cash'],2) : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Card</p>
                            <h3 class="my-3"><?php echo !empty($payments[0][0]['card']) ? round($payments[0][0]['card'],2) : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Online</p>
                            <h3 class="my-3"><?php echo !empty($payments[0][0]['online']) ? round($payments[0][0]['online'],2) : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total No Charge</p>
                            <h3 class="my-3"><?php echo !empty(round($payments[0][0]['no_charge_with_tax']-$transactionsnc[0][0]['total_tax'],2)) ? round(round($payments[0][0]['no_charge_with_tax']-$transactionsnc[0][0]['total_tax'],2),2) : 0; ?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
<?php if(cards!=0){ ?>        
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Active Cards</p>
                            <h3 class="my-3"><?php echo $activecards;?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Inactive Cards</p>
                            <h3 class="my-3"><?php echo $inactivecards;?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Customers</p>
                            <h3 class="my-3"><?php echo $users;?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">                                                
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Net Balance</p>
                            <h3 class="my-3"><?php echo round(($totaladded-$totalused),2);?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">                                                
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Balance Added</p>
                            <h3 class="my-3"><?php echo round($totaladded,2);?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">                                                
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Total Used Amount</p>
                            <h3 class="my-3"><?php echo round($totalused,2);?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
    
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Refunded Amount</p>
                            <h3 class="my-3"><?php echo round($totalrefund,2);?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col--> 
        
        <div class="col-md-6 col-lg-3">
            <div class="card report-card">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-12">
                            <p class="text-dark font-weight-semibold font-14">Security Amount</p>
                            <h3 class="my-3"><?php echo round($totalsecurity,2);?></h3>
                        </div>
                    </div>
                </div><!--end card-body--> 
            </div><!--end card--> 
        </div> <!--end col-->
<?php } ?>
        <div class="col-md-6 col-lg-3">
            
        </div> <!--end col--> 
        <div class="col-md-6 col-lg-3">
            
        </div> <!--end col--> 
        <div class="col-md-6 col-lg-3">
            
        </div> <!--end col-->             
    </div><!--end row-->
</div><!-- container -->


