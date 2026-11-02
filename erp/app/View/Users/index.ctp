<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>
                        <div class="page-title">

                            <div class="pull-left">
                                <h1 class="title">Users</h1>                            </div>

                            <div class="pull-right hidden-xs">
                                <ol class="breadcrumb">
                                    <li>
                                        <?php echo $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-home')) .'Home', array('controller'=>'users', 'action'=>'dashboard'),array('escape'=>false)); ?>
                                    </li>
                                    <li>
                                        <a href="#"><?php echo __('Users'); ?></a>
                                    </li>
                                    <li class="active">
                                        <strong>All Users</strong>
                                    </li>
                                </ol>
                            </div>

                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <div class="col-lg-12">
                        <section class="box ">
                            <header class="panel_header">
                                <h2 class="title pull-left">All Users</h2>
                                <div class="actions panel_actions pull-right">
                                    <i class="box_toggle fa fa-chevron-down"></i>
                                    <i class="box_setting fa fa-cog" data-toggle="modal" href="#section-settings"></i>
                                    <i class="box_close fa fa-times"></i>
                                </div>
                            </header>
                            <div class="content-body">    <div class="row">
                                    <div class="col-md-12 col-sm-12 col-xs-12">



                                        <!-- ********************************************** -->


                                        <table id="example" class="display table table-hover table-condensed" cellspacing="0" width="100%">
                                            <thead>
                                                <tr>
			<th><?php echo $this->Paginator->sort('username'); ?></th>
			<th><?php echo $this->Paginator->sort('email'); ?></th>
                        <th><?php echo $this->Paginator->sort('role'); ?></th>
                        <th><?php echo $this->Paginator->sort('phone'); ?></th>
                        <th><?php echo $this->Paginator->sort('image'); ?></th>
			<th><?php echo $this->Paginator->sort('status'); ?></th>
			<th><?php echo $this->Paginator->sort('created'); ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                   <?php foreach ($users as $user): ?>
	<tr>
		<td><?php echo h($user['User']['username']); ?>&nbsp;</td>
		<td><?php echo h($user['User']['email']); ?>&nbsp;</td>
                <td><?php echo h($user['User']['role']); ?>&nbsp;</td>
                <td><?php echo h($user['User']['phone']); ?>&nbsp;</td>
                <td><?php echo $this->Html->image('uploads/users/'.$user['User']['image'], array('width'=>'35px', 'height'=>'35px','class'=>'img-circle img-inline'));  ?></td>
		<td><?php echo h($user['User']['status'] == 1 ? 'Active' : 'InActive'); ?>&nbsp;</td>
		<td><?php echo h($user['User']['created']); ?>&nbsp;</td>
	</tr>
<?php endforeach; ?>             
                                            </tbody>
                                        </table>
                                        <!-- ********************************************** -->




                                    </div>
                                </div>
                            </div>
                        </section></div>
