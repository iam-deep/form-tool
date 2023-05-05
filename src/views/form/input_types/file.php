<?php
if ($input->type == 'single') { ?>

    <div class="row">
        <div style="width:80px;float:left;margin-left:15px;" id="preview-<?php echo $input->column; ?>"
            data-isimage="<?php echo $input->isImageField ? 1 : 0; ?>">
            <a href="<?php echo $input->value; ?>" target="_blank">
                <img src="<?php echo $input->imageCache; ?>" id="image-<?php echo $input->column; ?>"
                    class="img-thumbnail" style="max-height:80px;max-width:80px;<?php if (! $input->isImage) {
                        ?> display:none; <?php } ?>" alt="Image">
                <i class="<?php echo $input->icon; ?>" style="color:#4367A5;<?php if (! $input->isImage) {
                    ?> display:none; <?php } ?>"></i>
            </a>
        </div>
        <div style="float:left;margin-left:15px;">
            <label style="font-weight:550;color:#212427;margin-bottom:0;cursor: pointer;"
                for="<?php echo $input->column; ?>">
                Browse your file
            </label>
            <input type="file" class="<?php echo $input->classes; ?>" id="<?php echo $input->column; ?>"
                name="<?php echo $input->column; ?>" accept="<?php echo $input->accept; ?>"
                style="display:none;" <?php echo $input->raw; ?> />

            <div style="color:#76787a;">
                <?php if ($input->isImageField) { ?>
                    (File size: max <?php echo $input->maxSize / 1024; ?>MB | Formats: png, jpg, svg)
                <?php } else { ?>
                    (File size: max <?php echo $input->maxSize / 1024; ?>MB | Formats: png, jpg, pdf & docs)
                <?php } ?>
            </div>

            <div style="margin-top: 5px;">
                <div id="hasFile-<?php echo $input->column; ?>" style="<?php if (! $input->rawValue) {
                    ?> display:none <?php } ?>">
                    <p style="margin-bottom:0;color:#00a65a;">
                        <strong id="filename-<?php echo $input->column; ?>"></strong>
                    </p>
                    <label style="cursor: pointer;color:#337ab7;" class="text-bold"
                        for="<?php echo $input->column; ?>">
                        Replace
                    </label> &nbsp;
                    <a href="javascript:;" id="remove-<?php echo $input->column; ?>"
                        class="text-danger text-bold">Remove</a>
                    <input type="hidden" name="<?php echo $input->column; ?>"
                        id="value-<?php echo $input->column; ?>" value="<?php echo $input->rawValue; ?>">
                </div>
                <label id="noFile-<?php echo $input->column; ?>" style="cursor: pointer;<?php if ($input->rawValue) {
                    ?> display:none <?php } ?>" class="text-primary text-bold" for="<?php echo $input->column; ?>">
                    Upload
                </label>
            </div>
        </div>
    </div>

    <script>
        $('#<?php echo $input->column; ?>').on('change', function(e) {
            let image = '<?php echo $input->noImage; ?>';
            let [file] = this.files;
            if (! file) {
                return;
            }

            <?php if ($input->maxSize) { ?>
                if (file.size / 1024 > <?php echo $input->maxSize; ?>) {
                    $(this).val('');
                    alert('File size cannot be more than <?php echo $input->maxSize / 1024; ?>MB.');
                    return;
                }
            <?php } ?>

            let preview = $('#preview-<?php echo $input->column; ?>');
            preview.find('a').css({'pointer-events': 'none'});

            image = URL.createObjectURL(file);

            $('#filename-<?php echo $input->column; ?>').text(file.name);
            $('#image-<?php echo $input->column; ?>').attr('src', image);

            $('#hasFile-<?php echo $input->column; ?>').show();
            $('#noFile-<?php echo $input->column; ?>').hide();

            if (file['type'].split('/')[0] == 'image') {
                $('#image-<?php echo $input->column; ?>').show();
                preview.find('i').hide();
            } else {
                $('#image-<?php echo $input->column; ?>').hide();
                preview.find('i').removeClass().addClass('fa fa-upload fa-5x').css({color: '#4367A5'}).show();
            }
        });

        $('#remove-<?php echo $input->column; ?>').on('click', function(){
            let preview = $('#preview-<?php echo $input->column; ?>');
            $('#image-<?php echo $input->column; ?>').attr('src', '<?php echo $input->noImage; ?>');
            preview.find('i').removeClass().addClass('fa fa-upload fa-5x').css({color: '#76787a'});

            if (preview.data('isimage') == 1) {
                $('#image-<?php echo $input->column; ?>').show();
                preview.find('i').hide();
            } else {
                $('#image-<?php echo $input->column; ?>').hide();
                preview.find('i').show();
            }

            $('#<?php echo $input->column; ?>').val('');
            $('#value-<?php echo $input->column; ?>').remove();
            $('#hasFile-<?php echo $input->column; ?>').hide();
            $('#noFile-<?php echo $input->column; ?>').show();
        });
    </script>

<?php } else { ?>

    <div class="row">
        <div class="col-sm-3">
            <input type="file" class="<?php echo $input->classes; ?>" id="<?php echo $input->id; ?>"
                name="<?php echo $input->name; ?>" accept="<?php echo $input->accept; ?>"
                <?php echo $input->raw; ?> />
        </div>

        <?php if ($input->rawValue) {
            $script = "$('#".$input->groupId."').remove();";
            if ($input->isRequired) {
                $script .= "$('#".$input->id."').prop('required', 'required');";
            }

            if ($input->isImage) {
                $image = $input->imageCache;
                $file = '<img src="'.$input->imageCache.'" class="img-thumbnail"
                    style="max-height:150px;max-width:150px;" alt="Image">';
            } else {
                $file = '<i class="'.$input->icon.'"></i>';
            } ?>

            <div class="col-sm-6" id="<?php echo $input->groupId; ?>"> &nbsp;
                <a href="<?php echo asset($input->value); ?>" target="_blank"><?php echo $file; ?></a>
                <input type="hidden" name="<?php echo $input->name; ?>" value="<?php echo $input->value; ?>">
                <button class="close pull-right" aria-hidden="true" type="button" onclick="<?php echo $script; ?>">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        <?php } ?>
    </div>

<?php } ?>
