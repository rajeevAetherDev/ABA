<!-- Include SweetAlert2 and custom CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.input-error {
    border: 2px solid red !important;
    background-color: #ffe6e6;
}
</style>

<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Update Table</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="table_list">
                    <button class='btn btn-danger' type="button">Back</button>
                </a>
            </h2>
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
                            echo $this->Form->input('id', array(
                                'value' => $userdetail['Order_table']['id'],
                                'type' => 'hidden'
                            ));
                            echo $this->Form->input('number', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Table Number',
                                'readonly' => true,
                                'class' => 'form-control',
                                'value' => $userdetail['Order_table']['number']
                            ));
                            echo $this->Form->input('cover_charge', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Cover Charge',
                                'class' => 'form-control',
                                'value' => $userdetail['Order_table']['cover_charge']
                            ));
                            echo $this->Form->input('weekend_charge', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Weekend Charge',
                                'class' => 'form-control',
                                'value' => $userdetail['Order_table']['weekend_charge']
                            ));
                            echo $this->Form->input('pax', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'PAX',
                                'class' => 'form-control',
                                'value' => $userdetail['Order_table']['pax']
                            ));

                            $options = [];
                            $selectedlocationId = $userdetail['Order_table']['location_id'];

                            $selectedLocation = null;
                            foreach ($locations as $location) {
                                if ($location['Location']['id'] == $selectedlocationId) {
                                    $selectedLocation = $location['Location'];
                                } else {
                                    $options[$location['Location']['id']] = $location['Location']['name'];
                                }
                            }
                            if ($selectedLocation) {
                                $options = array($selectedLocation['id'] => $selectedLocation['name']) + $options;
                            }

                            echo $this->Form->input('location', array(
                                'id' => 'location', // Required for JS
                                'div' => array('class' => 'form-group'),
                                'label' => 'Select Location',
                                'class' => 'form-control',
                                'options' => $options,
                                'default' => $selectedlocationId,
                                'required' => true
                            ));

                            $cat_options = ['' => 'Select Table Category'];
                            $selectedCategoryId = $userdetail['Order_table']['table_category_id'] ?? '';
                            if (!empty($table_categories)) {
                                foreach ($table_categories as $tcat) {
                                    $cat_options[$tcat['Table_category']['id']] = $tcat['Table_category']['name'];
                                }
                            }

                            echo $this->Form->input('table_category_id', array(
                                'id' => 'table_category_id',
                                'div' => array('class' => 'form-group'),
                                'label' => 'Select Table Category *',
                                'class' => 'form-control',
                                'options' => $cat_options,
                                'default' => $selectedCategoryId,
                                'required' => true
                            ));

                            $waitertypeoption = $userdetail['Order_table']['is_waiter_required'] == 0
                                ? ['0' => 'No', '1' => 'Yes']
                                : ['1' => 'Yes', '0' => 'No'];

                            echo $this->Form->input('waiter', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Is Waiter Required',
                                'class' => 'form-control',
                                'options' => $waitertypeoption
                            ));
                            
                            $customeroption = $userdetail['Order_table']['is_customer_detail_required'] == 0
                                ? ['0' => 'No', '1' => 'Yes']
                                : ['1' => 'Yes', '0' => 'No'];

                            echo $this->Form->input('customer', array(
                                'div' => array('class' => 'form-group'),
                                'label' => 'Is Customer Detail Required',
                                'class' => 'form-control',
                                'options' => $customeroption
                            ));
                        ?>
                    </div>
                </div>

                <a href="table_list">
                    <button class='btn btn-danger' type="button">Cancel</button>
                </a>
                <?php echo $this->Form->button('Save', array('class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn')); ?>
                <?php echo $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#submitBtn').on('click', function (e) {
        const locationVal = $('#location').val();

        if (!locationVal) {
            e.preventDefault();
            $('#location').addClass('input-error');

            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select a location.',
                confirmButtonText: 'OK'
            });

            return false;
        } else {
            $('#location').removeClass('input-error');
        }

        const catVal = $('#table_category_id').val();
        if (!catVal) {
            e.preventDefault();
            $('#table_category_id').addClass('input-error');

            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select a Table Category.',
                confirmButtonText: 'OK'
            });

            return false;
        } else {
            $('#table_category_id').removeClass('input-error');
        }
    });
});
</script>
