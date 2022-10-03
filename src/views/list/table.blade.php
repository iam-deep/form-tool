<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach ($headings as $header)
                    <th {!! $header->raw !!}>
                        {!! $header->getLabel() !!}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($tableData as $rows)
                <tr {!! $rows->raw !!}>
                    @foreach ($rows->columns as $cols)
                        <td {!! $cols->raw !!}>{!! $cols->data !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
$('.selectAll').on('click', function () {
    $('.bulk').prop('checked', $(this).prop('checked'));
    updateIds();
});

$('.bulk').on('change', function () {
    let checked = true;
    let indeterminate = false;
    $('.bulk').each(function () {
        if ($(this).prop('checked')) {
            indeterminate = true;
        } else {
            checked = false;
        }
    });

    if (! checked && indeterminate) {
        $('.selectAll').prop('indeterminate', true);
    } else {
        $('.selectAll').prop('indeterminate', false);
        $('.selectAll').prop('checked', checked);
    }

    updateIds();
});

// Range selection with Shift key for the checkboxes
$.fn.shiftSelectable = function() {
    var lastChecked,
        $boxes = this;

    $boxes.click(function(evt) {
        if(!lastChecked) {
            lastChecked = this;
            return;
        }

        if(evt.shiftKey) {
            var start = $boxes.index(this),
                end = $boxes.index(lastChecked);
            $boxes.slice(Math.min(start, end), Math.max(start, end) + 1).prop('checked', lastChecked.checked);
        }

        lastChecked = this;
    });
};

$(function(){
    $('.bulk').shiftSelectable();
});

function updateIds()
{
    let ids = [];
    $('.bulk').each(function () {
        if ($(this).prop('checked')) {
            ids.push($(this).val());
        }
    });
    $('#bulkIds').val(ids.join(','));
}

function beforeBulkSubmit()
{
    if (! $('#bulkIds').val()) {
        alert('Select some rows to perform bulk action!');
        return false;
    }

    let action = $('select[name="bulkAction"]').val();
    if (action == 'destroy') {
        return confirm('Are you sure you want to DELETE the selected rows PERMANENTLY?\n\nThis action CANNOT be UNDONE!');
    }

    return true;
}
</script>