 <div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>
                        <div class="page-title">

                            <div class="pull-left">
                                <h1 class="title"><?php echo __('Import Inventory'); ?></h1>                            </div>

                            

                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                        <section class="box ">
                            <header class="panel_header">
                                <h2 class="title pull-left">Import</h2>
                                <div class="actions panel_actions pull-right">
                                    <i class="box_toggle fa fa-chevron-down"></i>
                                    <i class="box_setting fa fa-cog" data-toggle="modal" href="#section-settings"></i>
                                    <i class="box_close fa fa-times"></i>
                                </div>
                            </header>
                            <div class="content-body">
                                <div class="row">
                                   <?php echo $this->Form->create('Import', array( 'type'=>'file'));?>
                                        <div class="col-lg-8 col-md-8 col-sm-9 col-xs-12">
                                            
                                            <?php if (!empty($this->data['Import']['filepath'])): ?>
						<div class="input">
							<label>Uploaded File</label>
							<?php
							echo $this->Form->input('filepath', array('type'=>'hidden'));
							echo $this->Html->link(basename($this->data['Import']['filepath']), $this->data['Import']['filepath']);
							?>
						</div>
					<?php else: ?>
						<?php echo $this->Form->input('image',array(
							'type' => 'file',
                                                        'label'=>'Upload a file',
                                                    'div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control', 'between'=>'<span class="desc">Upload Xls File only</span><div class="controls">', 'after'=>'</div>'
						)); ?>
					<?php endif;                                       
?>
                                            
                                        </div>

                                        <div class="col-lg-8 col-md-8 col-sm-9 col-xs-12 padding-bottom-30">
                                            <div class="text-left">
                                  <?php echo $this->Form->button('Import', array('class'=>'btn btn-primary', 'type'=>'submit')); ?>
                                            </div>
                                        </div>
                                    <?php echo $this->Form->end(); ?>
                                </div>


                            </div>
                        </section></div>
