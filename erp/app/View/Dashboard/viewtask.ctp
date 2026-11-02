                     <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                    <h2 class="page-title">Task Report</h2>
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
                                                if($taskdetail[0]['tt']['status']==0){
                                                    $status='Pending';
                                                }elseif($taskdetail[0]['tt']['status']==1){
                                                    $status='Approved';
                                                }else{
                                                    $status='Rejected';
                                                }
                                                
                                                if($taskdetail[0]['ta']['locationID']==0){
                                                    $location='Ground Floor';
                                                }elseif($taskdetail[0]['ta']['locationID']==1){
                                                    $location='1st Floor';
                                                }elseif($taskdetail[0]['ta']['locationID']==2){
                                                    $location='2nd Floor';
                                                }
                                                
                                                if($taskdetail[0]['ta']['sublocationID']==3){
                                                    $sublocation='Office Room';
                                                }elseif($taskdetail[0]['ta']['sublocationID']==4){
                                                    $sublocation='Meeting Room';
                                                }elseif($taskdetail[0]['ta']['sublocationID']==5){
                                                    $sublocation='Washroom';
                                                }
                                                
                                                if(isset($taskdetail[0]['tt']['completedbyName'])){
                                                    $completedbyName=$taskdetail[0]['tt']['completedbyName'];
                                                }else{
                                                    $completedbyName='N/A';
                                                }
                                                
                                                if(isset($taskdetail[0]['tt']['actionofficerName'])){
                                                    $actionofficerName=$taskdetail[0]['tt']['actionofficerName'];
                                                }else{
                                                    $actionofficerName='N/A';
                                                }
                                                
                                                if(isset($taskdetail[0]['tr']['textdescription'])){
                                                    $textdescription=$taskdetail[0]['tr']['textdescription'];
                                                }else{
                                                    $textdescription='N/A';
                                                }
                                                
                                                
                                                echo $this->Form->input('Task Name', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' =>$taskdetail[0]['ta']['taskName'], 'readonly'=>'readonly','required'=>'required'));
                                        	    echo $this->Form->input('Location Id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $location, 'readonly'=>'readonly'));
                                        		echo $this->Form->input('SubLocation Id', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' =>$sublocation, 'readonly'=>'readonly'));
                                        		echo $this->Form->input('Completed By', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $completedbyName, 'readonly'=>'readonly'));
                                            	echo $this->Form->input('Action Officer', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $actionofficerName, 'readonly'=>'readonly'));
                                            	echo $this->Form->input('Status', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' => $status, 'readonly'=>'readonly'));
                                            	echo $this->Form->input('Text Description', array('div'=>array('class'=>'form-group'), 'label'=>array('class'=>'form-label'), 'class'=>'form-control','value' =>$textdescription, 'readonly'=>'readonly'));
                                            	if(isset($taskdetail[0]['tr']['imageURL'])){
                                            	    echo "Image";
                                            ?>
                                            	<br><br>
                                                <a href="<?php echo $taskdetail[0]['tr']['imageURL'];?>" target="_blank"><img src="<?php echo $taskdetail[0]['tr']['imageURL'];?>">	</a>
                                            <?php    
                                        	} 
                                            if(isset($taskdetail[0]['tr']['imageURL'])){
                                        	    echo "<br><br>Audio";
                                        	?>
                                        	<br>
                                            <audio controls>
                                                <source src="<?php echo $taskdetail[0]['tr']['voicenoteURL'];?>" type="audio/mpeg">
                                            </audio>
                                            <?php 
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>