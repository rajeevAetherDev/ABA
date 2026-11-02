
    <div class="row">
        <div class="col-sm-4">
        </div>
        <div class="col-sm-4">
            <div class="page-title-box">
                    <center><h2 class="page-title">Create PIN</h2></center>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <div class="row" >
        
        <div class="col-sm-3">
        </div>
                        <div class="col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <?php echo $this->Form->create('User', array('onsubmit' => 'return validatePins();')); ?>
                                   
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <?php 
                                                echo $this->Form->input('id', array(
                                                    'class' => 'form-control',
                                                    'type' => 'hidden',
                                                    'value' => $id
                                                ));
                                                echo $this->Form->input('pin', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Pin',
                                                    'class' => 'form-control',
                                                    'required' => 'required',
                                                    'type' => 'number',
                                                    'id' =>'pin',
                                                    'min' => '0000', // Minimum value for a 10-digit number
                                                    'max' => '9999', // Maximum value for a 10-digit number
                                                    'oninput' => 'this.value = this.value.slice(0, 4);' // Ensure input is no more than 10 digits
                                                ));
                                                echo $this->Form->input('confirmpin', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Re-Enter Pin',
                                                    'class' => 'form-control',
                                                    'required' => 'required',
                                                    'type' => 'number',
                                                    'id' =>'cnf_pin',
                                                    'min' => '0000', // Minimum value for a 10-digit number
                                                    'max' => '9999', // Maximum value for a 10-digit number
                                                    'oninput' => 'this.value = this.value.slice(0, 4);' // Ensure input is no more than 10 digits
                                                ));
                                                echo $this->Form->input('otp', array(
                                                    'div' => array('class' => 'form-group'),
                                                    'label' => 'Enter OTP',
                                                    'class' => 'form-control',
                                                    'required' => 'required',
                                                    'type' => 'number'
                                                ));
                                            ?>
                                        </div>
                                    </div>
                                    <?php echo $this->Form->button('Save', array('class'=>'btn btn-success', 'type'=>'submit', 'id' => 'submitBtn')); ?>
                                </div>
                            </div>
                        </div> 
                    </div>   
                    <script>
function validatePins() {
    var pin = document.getElementById('pin').value;
    var cnfPin = document.getElementById('cnf_pin').value;
    
    if (pin !== cnfPin) {
        alert("Pins do not match. Please try again.");
        return false;
    }
    return true;
}
</script>