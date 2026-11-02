<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">View Modifier</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                   
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="modifiers"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                                if($products[0]['catalog_modifier']['food_type']==0){
                                                    $foodtype='Veg';
                                                }else{
                                                    $foodtype='Non-Veg';
                                                }
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name','class'=>'form-control','value' =>$products[0]['catalog_modifier']['name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('description', array('div'=>array('class'=>'form-group'), 'label'=>'Description', 'class'=>'form-control','value' =>$products[0]['catalog_modifier']['description'],'rows'=>3,'readonly'=>'readonly'));
                                                echo $this->Form->input('food_type', array('div'=>array('class'=>'form-group'), 'label'=>'Food type', 'class'=>'form-control','value' =>$foodtype,'readonly'=>'readonly'));
                                                echo $this->Form->input('sort_order', array('div'=>array('class'=>'form-group'), 'label'=>'Sort Order', 'class'=>'form-control','value' =>$products[0]['catalog_modifier']['sort_order'],'readonly'=>'readonly'));
                                                echo $this->Form->input('price', array('div'=>array('class'=>'form-group'), 'label'=>'Default Sales Price', 'class'=>'form-control','value' =>$products[0]['catalog_modifier']['price'],'readonly'=>'readonly'));
                                                echo $this->Form->input('weight', array('div'=>array('class'=>'form-group'), 'label'=>'Weight', 'class'=>'form-control','value' =>$products[0]['catalog_modifier']['weight'],'readonly'=>'readonly'));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
