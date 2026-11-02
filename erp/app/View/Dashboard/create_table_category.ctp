<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Create Table Category</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title"><a href="table_category_list"><button class='btn btn-danger' type="button">Back</button></a></h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php echo $this->Form->create('User'); ?>
                <div class="form-group row">
                    <div class="col-sm-8">
                        <?php 
                            echo $this->Form->input('name', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Category Name *',
                                'class' => 'form-control',
                                'required' => 'required',
                                'placeholder' => 'e.g. Fine Dine, AC, Lounge, BAR'
                            ));
                        ?>
                    </div>
                </div>
                <a href="table_category_list"><button class='btn btn-danger' type="button">Cancel</button></a>
                <?php echo $this->Form->button('Save', array('class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn')); ?>
                <?php echo $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>
