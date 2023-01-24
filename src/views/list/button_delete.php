<a href="javascript:;" onClick="$('#<?php echo $button->id; ?>').submit()" class="{class}">
    <i class="fa fa-trash"></i> <?php echo $button->name; ?>
</a>
<form id="<?php echo $button->id; ?>" action="<?php echo $button->action; ?>" method="POST"
    onsubmit="return confirm('Are you sure you want to delete?')" style="display:none">

    <?php echo csrf_field(); ?>
    <?php echo method_field('DELETE'); ?>
</form>
