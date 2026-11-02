 <div class="login-wrapper">
            <div id="login" class="login loginpage col-lg-offset-4 col-lg-4 col-md-offset-3 col-md-6 col-sm-offset-3 col-sm-6 col-xs-offset-0 col-xs-12">
			   <h1 style="margin-top:30px !important;" class="text-center"><?php echo $this->Html->image('../assets/images/ipayon logosmall.png', array('height' => '150px'));?>
</h1>
                    <?php echo $this->Form->create('User', array('id'=>'loginform')); ?>
                    <p>
                     <?php echo $this->Form->input('username', array('div'=>false, 'class'=>'input')); ?>
                    </p>
                    <p>
                      <?php echo $this->Form->input('password', array('div'=>false,  'type'=>'password', 'class'=>'input')); ?>
                        
                    </p>
                    


                    <p class="submit">
                        <input type="submit" name="wp-submit" id="wp-submit" class="btn btn-info btn-block" value="Sign In" />
                    </p>
                <?php echo $this->Form->end(); ?>

                <p id="nav">
				<p class="pull-right">
                </p>
                </p>


            </div>
        </div>
