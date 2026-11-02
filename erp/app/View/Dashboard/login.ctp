<!-- Log In page -->
        <div class="container">
            <div class="row vh-100 ">
                <div class="col-3"></div>
                <div class="col-6 align-self-center">
                    <div class="auth-page">
                        <div class="card auth-card shadow-lg">
                            <div class="card-body">
                                <div class="px-3">
                                
                                    <div class="" style="text-align:center">
                                        <a href="" class="logo logo-admin"><?php echo $this->Html->image('/assets/images/logo.png', array('height' => '150px'));?></a>
                                    </div><!--end auth-logo-box-->
                                    
                                
                    <?php echo $this->Form->create('User', array('id'=>'loginform','class'=>'form-horizontal auth-form my-4')); ?>

                                        <div class="form-group">
                                            <div class="input-group mb-3">
                                                <div class="col-sm-12">
                                                    <?php echo $this->Form->input('username', array('div'=>false, 'class'=>'form-control')); ?>
                                                </div>
                                            </div>                                    
                                        </div><!--end form-group--> 
            
                                        <div class="form-group">
                                            <div class="input-group mb-3"> 
                                                <div class="col-sm-12">
                                                    <?php echo $this->Form->input('password', array('div'=>false, 'type'=>'password', 'class'=>'form-control')); ?>
                                                </div>
                                            </div>                               
                                        </div><!--end form-group--> 
                                        <div class="form-group mb-0 row">
                                            <div class="col-12 mt-2">
                                                <button class="btn btn-gradient-primary btn-round btn-block waves-effect waves-light" type="submit">Log In <i class="fas fa-sign-in-alt ml-1"></i></button>
                                            </div><!--end col--> 
                                        </div> <!--end form-group-->                           
                                    <?php echo $this->Form->end(); ?>
                                </div><!--end /div-->
                                
                                <div class="m-3 text-center text-muted">
                                    
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                        
                    </div><!--end auth-page-->
                </div><!--end col-->           
            </div><!--end row-->
        </div><!--end container-->
        <!-- End Log In page -->