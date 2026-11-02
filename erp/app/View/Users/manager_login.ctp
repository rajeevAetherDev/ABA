 <div class="login-wrapper">
            <div id="login" class="login loginpage col-lg-offset-4 col-lg-4 col-md-offset-3 col-md-6 col-sm-offset-3 col-sm-6 col-xs-offset-0 col-xs-12">
                <h1><a href="#" title="Login Page" tabindex="-1">POS LOGIN</a></h1>
                    <?php echo $this->Form->create('User', array('id'=>'loginform')); ?>
                    <p>
                     <?php echo $this->Form->input('username', array('div'=>false, 'id'=>'user_login', 'class'=>'input')); ?>
                    </p>
                    <p>
                      <?php echo $this->Form->input('password', array('div'=>false, 'id'=>'user_pass', 'type'=>'password', 'class'=>'input')); ?>
                        
                    </p>
                    <p class="forgetmenot">
                        <label class="icheck-label form-label" for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever" class="skin-square-orange" checked> Remember me</label>
                    </p>



                    <p class="submit">
                        <input type="submit" name="wp-submit" id="wp-submit" class="btn btn-orange btn-block" value="Sign In" />
                    </p>
                <?php echo $this->Form->end(); ?>

<!--                <p id="nav">
                    <a class="pull-left" href="#" title="Password Lost and Found">Forgot password?</a>
                    <a class="pull-right" href="#" title="Sign Up">Sign Up</a>
                </p>-->


            </div>
        </div>
