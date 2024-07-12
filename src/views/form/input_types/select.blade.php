<?php

if ($input->type == 'single') {

    if ($input->isQuickAdd) { ?>
        <div class="input-group">
            <select class="{{ $input->classes }}" id="{{ $input->column }}" name="{{ $input->column.($input->isMultiple ? '[]' : '') }}" {{ $input->raw }}>
                {!! $input->options !!}
            </select>
            <span class="input-group-btn">
                <button class="btn btn-default" data-id="{{ $input->column }}" type="button" title="Add" id="quickAddButton_{{ $input->column }}"
                    @if ($input->plugin == 'chosen') style="padding: 2px 7px;"@endif >
                    <i class="fa fa-plus"></i>
                </button>
            </span>
        </div>

        <script>
            $('#quickAddButton_{{ $input->column }}').on('click', function() {
                $('#quickAddTitle').html('Add {{ $input->quickData->title }}');
                $('#quickAddBody').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
                $('#quickAddModal .submit').hide();
                $('#quickSelectToUpdate').attr('data-name', '{{ $input->column }}');
                $('#quickSelectToUpdate').attr('data-is-chosen', '{{ $input->plugin == 'chosen' ? 1 : 0 }}');
                $('#quickOption').val('{{ $input->quickData->optionData }}');
                
                $('#quickAddModal').modal('show');

                $.ajax({
                    url: "{{ url($input->quickData->route) }}",
                    type: "get",
                    dataType: "json",
                    error: function(json) {
                        if (json.responseJSON?.message) {
                            alert(json.responseJSON.message);
                        } else {
                            alert("Something went wrong! Please refresh the page.");
                        }
                    },
                    success: function(json) {
                        if (json.status) {
                            $('#quickAddModal .submit').show();
                            $('#quickAddForm').attr('action', json.data.routeUpdate);
                            $('#quickAddBody').html(json.data.form);
                        } else {
                            $('#quickAddBody').html("Something went wrong! Please refresh the page.");
                        }
                    }
                });
            });
        </Script>

    <?php } else { ?>
        <select class="{{ $input->classes }}" id="{{ $input->column }}" name="{{ $input->column.($input->isMultiple ? '[]' : '') }}" {{ $input->raw }}>
            {!! $input->options !!}
        </select>
    <?php }

} else { ?>

    <select class="{{ $input->classes.' input-sm' }}" id="{{ $input->id }}" name="{{ $input->name.($this->isMultiple ? '[]' : '') }}" {{ $input->raw }}>
        {!! $input->options !!}
    </select>

<?php } ?>
