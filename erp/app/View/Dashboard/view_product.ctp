<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">View Product</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="product_list"><button class='btn btn-danger' type="button">Back</button></a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php 
                       
                        // $product = $product[0]['catalog_product'];
                        // $kdsName = $products[0][0]['kds'] ?? '';
                        
                        $dishType = ['0' => 'Food', '1' => 'Beverage', '2' => 'Liquor', '3' => 'Others'][$product['dish_type']] ?? 'N/A';
                        $foodtype = ['0' => 'Veg', '1' => 'Non-Veg', '2' => 'Non-Alcoholic', '3' => 'Alcoholic'][$product['food_type']] ?? 'N/A';
                        // $foodtype = $product['food_type'] == 0 ? 'Veg' : 'Non-Veg';
                        $package = $product['package'] == 1 ? 'Yes' : 'No';
                        $subcat = $product['subcat_name'] ?: 'N/A';

                        echo $this->Form->input('name', [
                            'div' => ['class'=>'form-group'],
                            'label' => 'Product Name',
                            'class' => 'form-control',
                            'value' => $product['name'],
                            'readonly' => 'readonly'
                        ]);

                        echo $this->Form->input('category', [
                            'div' => ['class'=>'form-group'],
                            'label' => 'Category Name',
                            'class' => 'form-control',
                            'value' => $product['cat_name'],
                            'readonly' => 'readonly'
                        ]);
                        
                        if (!empty($product['subcat_name'])):

                            echo $this->Form->input('subcategory', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'SubCategory Name',
                                'class' => 'form-control',
                                'value' => $subcat,
                                'readonly' => 'readonly'
                            ]);
                        endif;
                        
                        if (!empty($product['description'])):
                            echo $this->Form->input('description', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Description',
                                'class' => 'form-control',
                                'value' => $product['description'],
                                'rows' => 3,
                                'readonly' => 'readonly'
                            ]);
                        endif;

                        echo $this->Form->input('food_type', [
                            'div' => ['class'=>'form-group'],
                            'label' => 'Dish Type',
                            'class' => 'form-control',
                            'value' =>     $dishType,
                            'readonly' => 'readonly'
                        ]);
                        
                        echo $this->Form->input('food_type', [
                            'div' => ['class'=>'form-group'],
                            'label' => 'Food Type',
                            'class' => 'form-control',
                            'value' => $foodtype,
                            'readonly' => 'readonly'
                        ]);

                        echo $this->Form->input('package', [
                            'div' => ['class'=>'form-group'],
                            'label' => 'Package',
                            'class' => 'form-control',
                            'value' => $package,
                            'readonly' => 'readonly'
                        ]);

                        echo $this->Form->input('unit', [
                            'div' => ['class'=>'form-group'],
                            'label' => 'Unit Name',
                            'class' => 'form-control',
                            'value' => $product['unit_name'],
                            'readonly' => 'readonly'
                        ]);
                        
                        if (!empty($product['price'])){
                            echo $this->Form->input('price', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Price',
                                'class' => 'form-control',
                                'value' => $product['price'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        if (!empty($product['preparation_time'])){
                            echo $this->Form->input('preparation_time', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Preparation time(In Minutes)',
                                'class' => 'form-control',
                                'value' => $product['preparation_time'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        if (!empty($product['serve_time'])){
                            echo $this->Form->input('serve_time', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Serve Time(In Minutes)',
                                'class' => 'form-control',
                                'value' => $product['serve_time'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        if (!empty($product['moq'])){
                            echo $this->Form->input('moq', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'MOQ',
                                'class' => 'form-control',
                                'value' => $product['moq'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        echo $this->Form->input('kds', [
                            'div' => ['class'=>'form-group'],
                            'label' => 'KDS',
                            'class' => 'form-control',
                            'value' => $product['kds_name'],
                            'readonly' => 'readonly'
                        ]);
                        
                        // Tax options display
                        $taxoptions = [];
                        foreach ($taxes as $tax) {
                            $taxoptions[$tax['catalog_product_tax']['id']] = $tax['0']['taxname'];
                        }
                        ?>
                        <!-- Taxes -->
                        <?php if (!empty($taxes)){ ?>
                            <div class="form-group mb-3">
                              <label><strong>Taxes</strong></label>
                              <div class="row">
                                <?php foreach ($taxes as $tax): 
                                  $taxId = $tax['cpt']['tax_id'];
                                  $taxName = $tax[0]['taxname'];
                                  $isSelected = in_array($taxId, $selectedTaxIds);
                                    // print_r($tax);exit;
                                  
                                ?>
                                  <div class="col-md-4">
                                    <label><?= h($taxName) ?></label>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                        <?php } 
                        
                        // Modifier options display
                        if (!empty($modifiers)){ ?>
                            <div class="form-group mb-3">
                              <label><strong>Modifiers</strong></label>
                              <div class="row">
                                <?php foreach ($modifiers as $modifier): 
                                  $id = $modifier['inventory_linking']['component_id'];
                                  $Name = $modifier['inventory_linking']['component_name'];
                                
                                ?>
                                  <div class="col-md-4">
                                    <label><?= h($Name) ?></label>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                        <?php } ?>
                        
                        <?php
                        if (!empty($product['sort_order'])){
                            echo $this->Form->input('sort_order', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Sort Order',
                                'class' => 'form-control',
                                'value' => $product['sort_order'],
                                'readonly' => 'readonly'
                            ]);
                        }

                        if (!empty($product['recommended'])){
                            echo $this->Form->input('recommended', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Recommended',
                                'class' => 'form-control',
                                'value' => $product['recommended'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        if (!empty($product['variation'])){
                            echo $this->Form->input('variation', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Variation',
                                'class' => 'form-control',
                                'value' => $product['variation'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        
                        if (!empty($product['markup_price'])){
                            echo $this->Form->input('markup_price', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Markup Price',
                                'class' => 'form-control',
                                'value' => $product['markup_price'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        if (!empty($product['allergy_tags'])){
                            echo $this->Form->input('allergy_tags', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Allergy Tags',
                                'class' => 'form-control',
                                'value' => $product['allergy_tags'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        if (!empty($product['weight'])){
                            echo $this->Form->input('weight', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Weight',
                                'class' => 'form-control',
                                'value' => $product['weight'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        if (!empty($product['serves_people'])){
                            echo $this->Form->input('serves_people', [
                                'div' => ['class'=>'form-group'],
                                'label' => 'Serves People',
                                'class' => 'form-control',
                                'value' => $product['serves_people'],
                                'readonly' => 'readonly'
                            ]);
                        }
                        
                        // Show image
                        if (!empty($product['image'])) {
                            $imagePath = Router::url('/', true) . 'uploads/product_images/' . $product['image'];
                            echo '<div class="form-group"><label>Product Image</label><br>';
                            echo "<a href='$imagePath' target='_blank'><img src='$imagePath' style='height:150px;'></a></div>";
                        }
                        ?>
                        
                        <!-- Custom Attributes -->
                        <?php if (!empty($attributes)):?>
                          <div class="mb-3">
                            <label><strong>Attributes</strong></label>
                            <div class="row">
                              <?php  foreach ($attributes as $attr){
                                    echo "<div class='col-md-3'>".$this->Form->input('serves_people', [
                                        'div' => ['class'=>'form-group'],
                                        'label' => $attr['catalog_product_attribute']['name'],
                                        'class' => 'form-control',
                                        'value' => $attr['catalog_product_attribute']['value'],
                                        'readonly' => 'readonly'
                                    ]);
                                    ?>
                                    </div>
                                    <?php
                                } 
                              ?>
                                
                            </div>
                          </div>
                        <?php endif; ?>
                    
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
