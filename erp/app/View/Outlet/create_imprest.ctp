<div class="row">
    <div class="col-sm-10">
        <div class="page-title-box">
            <h2 class="page-title">Create Imprest</h2>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="imprest">
                    <button class='btn btn-danger' type="button" style="float:right">Back</button>
                </a>
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <?php
                echo $this->Form->create(false, ['id' => 'internalIndentForm']);
                echo $this->Form->input('amount', array('div'=>array('class'=>'form-group'),'value'=>0,'type'=>'number','required'=>'required', 'label'=>'Imprest Amount', 'class'=>'form-control', 'between'=>'<span class="desc"></span><div class="controls">', 'after'=>'</div>','id'=>'amount'));
                                                
                ?>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="getCheckedCheckboxes()" class="btn btn-success" style="float:right;">Save</button>
                        <a href="imprest">
                            <button class='btn btn-danger' type="button" style="float:right; margin-right: 10px;">Cancel</button>
                        </a>
                    </div>
                </div>
                <?= $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>

<script>
function getCheckedCheckboxes() {
    const amount = $('#amount').val();

    if (!amount || amount<=0) {
        Swal.fire({
            icon: 'warning',
            title: 'Amount not valid',
            text: 'Please enter valid amount before saving.',
            confirmButtonColor: '#d33',
        });
        return;
    }

    $.ajax({
        url: '<?= $this->Html->url(['controller' => 'outlet', 'action' => 'add_imprest']); ?>',
        type: 'POST',
        data: {
            amount: amount
        },
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Saved successfully!',
                showConfirmButton: false,
                timer: 1200
            }).then(() => {
                window.location.href = 'imprest';
            });
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'An error occurred',
                text: error,
                confirmButtonColor: '#d33',
            });
        }
    });
}
</script>
