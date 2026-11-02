                    <div class="col-lg-12">
                        <section class="box ">
                            <header class="panel_header"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                                <h2 class="title pull-left"><?php echo __('Task Management'); ?></h2>
                            </header>
                            <div class="content-body">    
                                <div class="row clearfix">
    								
                                <?php 
                                if($this->Session->read('Auth.User.role_sort')>='2'){ ?>
    									<div class="col-md-4">
    									      <?php echo $this->Html->link( $this->Html->tag('span','Create Task', array('class'=>'title btn-block btn btn-lg btn-info')), array('controller'=>'task', 'action'=>'taskcreate'), array('escape'=>false)); ?>
    									</div>
    							<?php } ?>
    									<div class="col-md-4">
    									       <?php echo $this->Html->link( $this->Html->tag('span','View Task', array('class'=>'title btn-block btn btn-lg btn-info')), array(), array('escape'=>false, )); ?>
    									</div>
                                    </div>
								
								
                            </div>
                        </section>
                    </div>
                    
                    




