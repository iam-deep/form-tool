<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach ($headings as $header)
                    <th {!! $header->raw !!}>
                        {{ $header->getLabel() }}
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