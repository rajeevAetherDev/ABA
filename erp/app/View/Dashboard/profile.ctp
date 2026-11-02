                     <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                    <h2 class="page-title">Profile</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    
                    <div class="row" >
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group row">
                                        
                                        <div class="col-sm-8">
                                            <?php 
                                                echo $this->Form->input('Name', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' =>$userdetail['Employee']['empName'], 'readonly'=>'readonly','required'=>'required'));
                                        	    echo $this->Form->input('Email', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $userdetail['Employee']['username'], 'readonly'=>'readonly'));
                                        		echo $this->Form->input('Phone', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' =>$userdetail['Employee']['phone'], 'readonly'=>'readonly'));
                                        		echo $this->Form->input('Date of Birth', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $userdetail['Employee']['dateofbirth'], 'readonly'=>'readonly'));
                                            	echo $this->Form->input('Role', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $userdetail['Employee']['dateofjoining'], 'readonly'=>'readonly'));
                                            	echo $this->Form->input('Department', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $userdetail['Employee']['department'], 'readonly'=>'readonly'));
                                            	
                                        	?>
                                        </div>
                                      
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
                    
                