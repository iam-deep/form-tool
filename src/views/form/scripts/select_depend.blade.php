$("#{{ $input->dependField }}").on("change", function() {
    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    var previousHelpText = "";
    var field = $('#{{ $input->field }}');
    var helpBlock = field.parent().next();
    var val = $(this).val();

    if (! val || val == 0) {
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
        data: { _token: csrf_token, id: val, field: "{{ $input->field }}" },
        beforeSend:function() {
            field.attr("disabled", true);
            @if ($input->isChosen) field.trigger("chosen:updated"); @endif
 
            previousHelpText = helpBlock.html();
            helpBlock.html("<i class='fa fa-spinner fa-pulse'></i>");
        },
        error: function(json) {
            if (json.responseJSON.message) {
                alert(json.responseJSON.message);
            } else {
                alert("Something went wrong! Please refresh the page.");
            }
        },
        complete: function() {
            field.attr("disabled", false);
            @if ($input->isChosen) field.trigger("chosen:updated"); @endif

            helpBlock.html(previousHelpText);
        },
        success: function(json) {
            if (json.isSuccess) {
                field.html(json.data);
            } else if (json.message) {
                alert(json.message);
            }
        }
    });
});