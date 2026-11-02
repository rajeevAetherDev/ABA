 <div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>
                        <div class="page-title">

                            <div class="pull-left">
                                <h1 class="title">Welcome Please selct the following Options</h1>                            </div>

                            

                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <section class="box ">
                            <header class="panel_header">
                                <h2 class="title pull-left">Choose Option</h2>
                                <div class="actions panel_actions pull-right">
                                    <i class="box_toggle fa fa-chevron-down"></i>
                                    <i class="box_setting fa fa-cog" data-toggle="modal" href="#section-settings"></i>
                                    <i class="box_close fa fa-times"></i>
                                </div>
                            </header>
                            <div class="content-body">
                                <div class="row">
                                   
                                    
                                    <div class="col-md-4">
                                        <?php echo $this->Html->link('Import', array('controller'=>'imports', 'action'=>'import'), array('class'=>'btn btn-success btn-lg')); ?>
                                        
                                    </div>
                                    
                                     <div class="col-md-4"><?php echo $this->Html->link('Export', array('controller'=>'imports', 'action'=>'export'), array('class'=>'btn btn-warning btn-lg')); ?></div>
                                     
                                     <div class="col-md-4">
                                         <?php echo $this->Html->link('Update', array('controller'=>'imports', 'action'=>'update'), array('class'=>'btn btn-danger btn-lg')); ?>
                                     </div>
                                            
                                        </div>

                              </div>


                            </div>
                        </section></div>
