<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Update Waiter</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="waiter_list"><button class='btn btn-danger' type="button">Back</button></a>
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
                            echo $this->Form->input('id', [
                                'type' => 'hidden',
                                'value' => $userdetail['Order_waiter']['id']
                            ]);

                            echo $this->Form->input('name', [
                                'div' => ['class' => 'form-group'],
                                'label' => 'Name',
                                'readonly' => true,
                                'class' => 'form-control',
                                'value' => $userdetail['Order_waiter']['name']
                            ]);

                            echo $this->Form->input('phone', [
                                'div' => ['class' => 'form-group'],
                                'label' => 'Phone',
                                'class' => 'form-control',
                                'required' => 'required',
                                'type' => 'number',
                                'min' => '1000000000',
                                'max' => '9999999999',
                                'oninput' => 'this.value = this.value.slice(0, 10);',
                                'value' => $userdetail['Order_waiter']['phone']
                            ]);

                            $options = [];
                            $selectedlocationId = $userdetail['Order_waiter']['location_id'];
                            $selectedLocation = null;

                            foreach ($locations as $location) {
                                if ($location['Location']['id'] == $selectedlocationId) {
                                    $selectedLocation = $location['Location'];
                                } else {
                                    $options[$location['Location']['id']] = $location['Location']['name'];
                                }
                            }

                            if ($selectedLocation) {
                                $options = [$selectedLocation['id'] => $selectedLocation['name']] + $options;
                            }

                            echo $this->Form->input('location', [
                                'id' => 'location',
                                'div' => ['class' => 'form-group'],
                                'label' => 'Select Location',
                                'class' => 'form-control',
                                'options' => $options,
                                'default' => $selectedlocationId,
                                'required' => true
                            ]);

                            echo $this->Form->input('pin', [
                                'div' => ['class' => 'form-group'],
                                'label' => 'Pin Number',
                                'class' => 'form-control',
                                'value' => $userdetail['Order_waiter']['pin_number']
                            ]);

                            $pintypeoption = $userdetail['Order_waiter']['is_pin_required'] == 0 
                                ? ['0' => 'No', '1' => 'Yes'] 
                                : ['1' => 'Yes', '0' => 'No'];

                            echo $this->Form->input('pin_req', [
                                'div' => ['class' => 'form-group'],
                                'label' => 'Is Pin Required',
                                'class' => 'form-control',
                                'between' => '<span class="desc"></span><div class="controls">',
                                'after' => '</div>',
                                'options' => $pintypeoption
                            ]);
                        ?>
                    </div>
                </div>

                <a href="waiter_list"><button class='btn btn-danger' type="button">Cancel</button></a>
                <?php echo $this->Form->button('Save', ['class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn']); ?>
            </div>
        </div>
    </div>
</div>

<style>
.input-error {
    border: 2px solid red !important;
    background-color: #ffe6e6 !important;
}
</style>

<script>
$(document).ready(function () {
    $('#submitBtn').on('click', function (e) {
        const locationVal = $('#location').val();

        if (!locationVal) {
            e.preventDefault();
            $('#location').addClass('input-error');

            Swal.fire({
                icon: 'error',
                title: 'Location Required',
                text: 'Please select a location before saving.',
                confirmButtonText: 'OK'
            });

            return false;
        } else {
            $('#location').removeClass('input-error');
        }
    });
});
</script>
