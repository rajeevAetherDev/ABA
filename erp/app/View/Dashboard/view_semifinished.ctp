<div class="row">
                        <div class="col-sm-11">
                            <div class="page-title-box">
                                    <h2 class="page-title">View Semi-Finished</h2>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                   
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="semifinished_list"><button  class='btn btn-danger' type="button">Back</button></a></h2>
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
                                                echo $this->Form->input('name', array('div'=>array('class'=>'form-group'), 'label'=>'Name','class'=>'form-control','value' =>$products[0]['inventory_semifinished']['name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('cat', array('div'=>array('class'=>'form-group'), 'label'=>'Category','class'=>'form-control','value' =>$products[0]['inventory_semifinished']['cat_name'],'readonly'=>'readonly'));
                                                if($products[0]['inventory_semifinished']['subcat_name']){
                                                    echo $this->Form->input('subcat', array('div'=>array('class'=>'form-group'), 'label'=>'Sub Category','class'=>'form-control','value' =>$products[0]['inventory_semifinished']['subcat_name'],'readonly'=>'readonly'));
                                                }
                                                echo $this->Form->input('unit', array('div'=>array('class'=>'form-group'), 'label'=>'Unit Name', 'class'=>'form-control','value' =>$products[0]['inventory_semifinished']['unit_name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('price', array('div'=>array('class'=>'form-group'), 'label'=>'Price', 'class'=>'form-control','value' =>$products[0]['inventory_semifinished']['price'],'readonly'=>'readonly'));
                                                
                                                echo $this->Form->input('conunit', array('div'=>array('class'=>'form-group'), 'label'=>'Consumption Unit Name', 'class'=>'form-control','value' =>$products[0]['inventory_semifinished']['consumption_unit_name'],'readonly'=>'readonly'));
                                                echo $this->Form->input('confacot', array('div'=>array('class'=>'form-group'), 'label'=>'Consumption Factor', 'class'=>'form-control','value' =>$products[0]['inventory_semifinished']['consumption_factor'],'readonly'=>'readonly'));
                                                echo $this->Form->input('conprice', array('div'=>array('class'=>'form-group'), 'label'=>'Consumption Price', 'class'=>'form-control','value' =>$products[0]['inventory_semifinished']['consumption_unit_price'],'readonly'=>'readonly'));
                                                
                                                echo $this->Form->input('remarks', array('div'=>array('class'=>'form-group'), 'label'=>'Remarks', 'class'=>'form-control','value' =>$products[0]['inventory_semifinished']['remarks'],'readonly'=>'readonly','rows'=>3,'readonly'=>'readonly'));
                                                   echo $this->Form->input('moq', [
                                                        'div' => ['class'=>'form-group'],
                                                        'label' => 'MOQ',
                                                        'class' => 'form-control',
                                                        'value' => $products[0]['inventory_semifinished']['moq'],
                                                        'readonly' => 'readonly'
                                                    ]);
                                                    echo $this->Form->input('stock', [
                                                        'div' => ['class'=>'form-group'],
                                                        'label' => 'Required Stock',
                                                        'class' => 'form-control',
                                                        'value' => $products[0]['inventory_semifinished']['stock'],
                                                        'readonly' => 'readonly'
                                                    ]);
                                                $taxoptions=[];
                                                foreach($taxes as $tax){
                                                    // print_r($tax);exit;
                                                    $taxoptions[$tax['inventory_semifinished_tax']['id']] = $tax['0']['taxname'];
                                                }
                                                echo $this->Form->input('tax', array('label'=>'Selected Tax', 'class'=>'form-control','options'=>$taxoptions, 'multiple'=>true,'readonly'=>'readonly'));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
