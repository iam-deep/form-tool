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
$('#selectAll').on('click', function () {
    $('.bulk').prop('checked', $(this).prop('checked'));
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
        $('#selectAll').prop('indeterminate', true);
    } else {
        $('#selectAll').prop('indeterminate', false);
        $('#selectAll').prop('checked', checked);
    }
});
</script>