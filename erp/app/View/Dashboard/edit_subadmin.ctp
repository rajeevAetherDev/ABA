<style>
  .radio-inline label {
    display: inline-block;
    margin-right: 10px;
}
.module-block {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-bottom: 6px;
    overflow: hidden;
}
.parent-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    background: #f7f7f7;
    cursor: pointer;
    user-select: none;
}
.parent-row input[type=checkbox] {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    cursor: pointer;
}
.parent-row .plabel {
    font-size: 14px;
    font-weight: 500;
    flex: 1;
    margin: 0;
}
.sub-count-badge {
    font-size: 11px;
    background: #e3f0ff;
    color: #1565c0;
    border-radius: 99px;
    padding: 1px 8px;
}
.chevron {
    font-size: 11px;
    color: #999;
    transition: transform 0.2s;
    display: inline-block;
}
.chevron.open {
    transform: rotate(90deg);
}
.submenu-area {
    display: none;
    padding: 8px 14px 10px 40px;
    border-top: 1px solid #e0e0e0;
    flex-wrap: wrap;
}
.submenu-area.open {
    display: flex;
}
.sub-item {
    width: 50%;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 4px 0;
}
.sub-item input[type=checkbox] {
    width: 13px;
    height: 13px;
    cursor: pointer;
}
.sub-item label {
    font-size: 13px;
    color: #555;
    margin: 0;
    cursor: pointer;
}
.no-sub .parent-row {
    background: #fff;
}
</style>

<div class="row">
    <div class="col-sm-11">
        <div class="page-title-box">
            <h2 class="page-title">Update Subadmin</h2>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="page-title-box">
            <h2 class="page-title">
                <a href="subadmin_list"><button class='btn btn-danger' type="button">Back</button></a>
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
                        echo $this->Form->input('id', array('value' => $userdetail['User']['id'], 'type' => 'hidden'));
                        echo $this->Form->input('name', array(
                            'div'      => array('class' => 'form-group'),
                            'label'    => array('class' => 'form-label'),
                            'class'    => 'form-control',
                            'required' => 'required',
                            'value'    => $userdetail['User']['name']
                        ));
                        echo $this->Form->input('mobile', array(
                            'div'      => array('class' => 'form-group'),
                            'label'    => 'Mobile',
                            'class'    => 'form-control',
                            'required' => 'required',
                            'type'     => 'number',
                            'value'    => $userdetail['User']['mobile'],
                            'min'      => '1000000000',
                            'max'      => '9999999999',
                            'oninput'  => 'this.value = this.value.slice(0, 10);'
                        ));
                        echo $this->Form->input('username', array(
                            'div'      => array('class' => 'form-group'),
                            'label'    => 'User Name',
                            'class'    => 'form-control',
                            'value'    => $userdetail['User']['username'],
                            'readonly' => 'readonly'
                        ));
                        echo $this->Form->input('password', array(
                            'div'         => array('class' => 'form-group'),
                            'label'       => 'Password',
                            'class'       => 'form-control',
                            'type'        => 'password',
                            'placeholder' => '********'
                        ));

                        // Location multi-select
                        $options = [];
                        foreach ($locations as $location) {
                            $options[$location['Location']['id']] = $location['Location']['name'];
                        }
                        echo $this->Form->input('location', array(
                            'div'      => array('class' => 'form-group'),
                            'label'    => 'Select Location',
                            'multiple' => 'multiple',
                            'class'    => 'form-control select2',
                            'options'  => $options,
                            'default'  => $selectedLocationIds,
                            'required' => true
                        ));

                        // Role select
                        $selectedrole = $userdetail['User']['role'];
                        if ($selectedrole == 'subadmin') {
                            $roleoptions = ['subadmin' => 'Sub-admin', 'kitchen' => 'Kitchen'];
                        } else {
                            $roleoptions = ['kitchen' => 'Kitchen', 'subadmin' => 'Sub-admin'];
                        }
                        echo $this->Form->input('role', array(
                            'div'     => array('class' => 'form-group'),
                            'label'   => 'Select Role',
                            'class'   => 'form-control',
                            'between' => '<span class="desc"></span><div class="controls">',
                            'after'   => '</div>',
                            'options' => $roleoptions,
                            'default' => $selectedrole
                        ));

                        // =====================================================
                        // CHANGED: Build savedParents and savedSubs from
                        // $selectemodules (existing user_modules rows)
                        // =====================================================
                        $savedParents = [];
                        $savedSubs    = []; // ['inventory_management' => ['sku_list' => true]]

                        foreach ($selectemodules as $row) {
                            $entry = trim($row['user_modules']['name'] ?? '');
                            if ($entry === '') continue;

                            if (strpos($entry, ':') !== false) {
                                // submenu entry e.g. 'inventory_management:sku_list'
                                list($parent, $sub) = explode(':', $entry, 2);
                                $savedSubs[$parent][$sub] = true;
                            } else {
                                // parent module entry
                                $savedParents[$entry] = true;
                            }
                        }
                        ?>

                        <h5 style="margin: 16px 0 10px;">Assign To</h5>

                        <?php
                        // $modules     = subadminmoduleswithlabel (from controller)
                        // $allSubmenus = subadminsubmenus (from controller)
                        foreach ($modules as $moduleKey => $moduleLabel):
                            $hasSubs    = !empty($allSubmenus[$moduleKey]);
                            $isChecked  = !empty($savedParents[$moduleKey]);
                            $isOpen     = $isChecked && $hasSubs;
                        ?>

                        <div class="module-block <?= $hasSubs ? '' : 'no-sub' ?>">

                            <!-- Parent checkbox row -->
                            <div class="parent-row" <?= $hasSubs ? "onclick=\"toggleSubs('{$moduleKey}', event)\"" : '' ?>>
                                <input type="checkbox"
                                       name="data[modules][<?= $moduleKey ?>]"
                                       id="mod_<?= $moduleKey ?>"
                                       value="1"
                                       <?= $isChecked ? 'checked' : '' ?>
                                       <?= $hasSubs ? "onchange=\"onParentChange('{$moduleKey}', this)\"" : '' ?>>
                                <label class="plabel" for="mod_<?= $moduleKey ?>"><?= $moduleLabel ?></label>
                                <?php if ($hasSubs): ?>
                                    <span class="sub-count-badge"><?= count($allSubmenus[$moduleKey]) ?> submenus</span>
                                    <span class="chevron <?= $isOpen ? 'open' : '' ?>" id="chv_<?= $moduleKey ?>">&#9658;</span>
                                <?php endif; ?>
                            </div>

                            <!-- Submenu area -->
                            <?php if ($hasSubs): ?>
                            <div class="submenu-area <?= $isOpen ? 'open' : '' ?>" id="subs_<?= $moduleKey ?>">
                                <?php foreach ($allSubmenus[$moduleKey] as $subKey => $subLabel):
                                    $subChecked = !empty($savedSubs[$moduleKey][$subKey]);
                                ?>
                                <div class="sub-item">
                                    <input type="checkbox"
                                           name="data[subs][<?= $moduleKey ?>][<?= $subKey ?>]"
                                           id="sub_<?= $moduleKey ?>_<?= $subKey ?>"
                                           value="1"
                                           <?= $subChecked ? 'checked' : '' ?>
                                           <?= !$isChecked ? 'disabled' : '' ?>>
                                    <label for="sub_<?= $moduleKey ?>_<?= $subKey ?>"><?= $subLabel ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                        </div>

                        <?php endforeach; ?>

                    </div>
                </div>

                <a href="subadmin_list"><button class='btn btn-danger' type="button">Cancel</button></a>
                <?php echo $this->Form->button('Save', array('class' => 'btn btn-success', 'type' => 'submit', 'id' => 'submitBtn')); ?>

                <?php echo $this->Form->end(); ?>
            </div>
        </div>
    </div>
</div>

<style>
.hidden { display: none; }
</style>

<script>
// Toggle submenu open/close when clicking parent row header
function toggleSubs(key, event) {
    if (event.target.type === 'checkbox') return;
    var cb = document.getElementById('mod_' + key);
    if (!cb.checked) return;
    var area = document.getElementById('subs_' + key);
    var chv  = document.getElementById('chv_' + key);
    var isOpen = area.classList.contains('open');
    area.classList.toggle('open', !isOpen);
    chv.classList.toggle('open', !isOpen);
}

// When parent checkbox is checked/unchecked
function onParentChange(key, cb) {
    var area = document.getElementById('subs_' + key);
    var chv  = document.getElementById('chv_' + key);
    if (!area) return;

    if (cb.checked) {
        area.classList.add('open');
        chv.classList.add('open');
        area.querySelectorAll('input[type=checkbox]').forEach(function(s) {
            s.disabled = false;
        });
    } else {
        area.classList.remove('open');
        chv.classList.remove('open');
        area.querySelectorAll('input[type=checkbox]').forEach(function(s) {
            s.checked  = false;
            s.disabled = true;
        });
    }
}

// Validate at least one parent module is selected
$('#submitBtn').click(function(event) {
    var atLeastOneChecked = false;
    document.querySelectorAll('.module-block input[type="checkbox"][id^="mod_"]').forEach(function(checkbox) {
        if (checkbox.checked) atLeastOneChecked = true;
    });
    if (!atLeastOneChecked) {
        event.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Please select at least one module.',
            confirmButtonText: 'OK'
        });
    }
});

jQuery(document).ready(function($) {
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: "Select locations",
            allowClear: true,
            width: '100%'
        });
    } else {
        console.error("Select2 library not loaded!");
    }
});
</script>
