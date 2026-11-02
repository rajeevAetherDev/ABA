<div class="row">
<div class="col-sm-12">
<div class="card">
<div class="card-body">

<h3>Add Service</h3>

<?php
echo $this->Form->create('Service');

echo $this->Form->input('name', [
    'class' => 'form-control',
    'required' => true
]);

echo $this->Form->input('description', [
    'class' => 'form-control',
    'type' => 'textarea'
]);

echo "<br>";

echo $this->Form->button('Save', ['class' => 'btn btn-success']);

echo $this->Form->end();
?>

</div>
</div>
</div>
</div>