<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach ($headings as $header)
                    <th {!! $header->styleClass . ' ' . $header->styleCSS !!} >
                        {{ $header->getLabel() }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($tableData as $rows)
                <tr {!! $rows->styleClass . ' ' . $rows->styleCSS !!} >
                    @foreach ($rows->columns as $cols)
                        <td {!! $cols->styleClass . ' ' . $cols->styleCSS !!}>{!! $cols->data !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>