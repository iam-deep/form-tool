<?php
// We will only display password on validation errors

if ($input->type == 'single') { ?>

    <div class="input-group">
        <input type="password" class="<?php echo $input->classes; ?>" id="<?php echo $input->column; ?>"
            name="<?php echo $input->column; ?>" value="<?php echo old($input->column); ?>" <?php echo $input->raw; ?> />
        <span class="input-group-btn">
            <button class="btn btn-default toggle-password" data-id="<?php echo $input->column; ?>" type="button"
                title="Show Password"><i class="fa fa-eye"></i></button>
        </span>
    </div>

<?php } else { ?>

    <div class="input-group">
        <input type="password" class="<?php echo $input->classes; ?> input-sm" id="<?php echo $input->id; ?>"
            name="<?php echo $input->name; ?>" value="<?php echo $input->oldValue; ?>" <?php echo $input->raw; ?> />
        <span class="input-group-btn">
            <button class="btn btn-default toggle-password btn-sm" data-id="<?php echo $input->id; ?>"
                type="button" title="Show Password"><i class="fa fa-eye"></i></button>
        </span>
    </div>

<?php } ?>

