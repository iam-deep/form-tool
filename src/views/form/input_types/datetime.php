<?php
if ($input->type == 'single') { ?>

    <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
        <input type="text" class="<?php echo $input->classes; ?>" id="<?php echo $input->column; ?>"
            name="<?php echo $input->column; ?>" value="<?php echo old($input->column, $input->value); ?>"
            <?php echo $input->raw; ?> autocomplete="off" />
    </div>

<?php } else { ?>

    <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
        <input type="text" class="<?php echo $input->classes; ?> input-sm" id="<?php echo $input->id; ?>"
            name="<?php echo $input->name; ?>" value="<?php echo $input->value; ?>" <?php echo $input->raw; ?>
            autocomplete="off" style="position:relative;" />
    </div>

<?php } ?>

