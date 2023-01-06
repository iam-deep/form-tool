$("#{{ $input->multipleKey }}").on("change", 'tbody>tr>td:nth-child({{ $input->selectorChildCount }})>select', function() {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    var parent = $(this).parent().parent();
    var field = parent.find('td:nth-child({{ $input->fieldChildCount }})>select');
    var val = $(this).val();

    var vals = {};
    @foreach ($input->allDependFields as $field)
        vals.{{ $field->field }} = parent.find('td:nth-child({{ $input->selectorChildCount }})>select').val();
    @endforeach

    if (! val) {
        @if ($input->isFirstOption)
            field.html('<option value="{{ $input->firstOptionValue }}">{{ $input->firstOptionText }}</option>');
        @else
            field.html('');
        @endif

        @if ($input->isChosen)
            field.trigger("chosen:updated");
        @endif
        
        return;
    }

    $.ajax({
        url: "{{ url(config('form-tool.adminURL').'/'.$input->route.'/get-options') }}",
        type: "post",
        dataType:"json",
        data: { _token: csrf_token, values: vals, field: "{{ $input->field }}", multipleKey: '{{ $input->multipleKey }}' },
        beforeSend:function() {
            field.attr("disabled", true);
            @if ($input->isChosen) field.trigger("chosen:updated"); @endif
        },
        error: function(json) {
            if (json.responseJSON?.message) {
                alert(json.responseJSON.message);
            } else {
                alert("Something went wrong! Please refresh the page.");
            }
        },
        complete: function() {
            field.attr("disabled", false);
            @if ($input->isChosen) field.trigger("chosen:updated"); @endif
        },
        success: function(json) {
            field.html(json.data);
        }
    });
});