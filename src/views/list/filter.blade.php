<style>
.quick-filter {
    list-style: none;
    margin-bottom: 15px;
    padding: 0;
    font-size: 13px;
    float: left;
    color: #646970;
}
.quick-filter li {
    display: inline-block;
    margin: 0;
    padding: 0;
    white-space: nowrap;
}
.quick-filter a {
    line-height: 2;
    padding: 0.2em;
    text-decoration: none;
    font-size: 13px;
}
.quick-filter a .count, .quick-filter a.active .count {
    color: #50575e;
    font-weight: 400;
    margin-right:5px;
}
.quick-filter a.active {
    color: #000;
    font-weight: 600;
}
.form-group {
    margin-right:15px;
}
</style>

@if (isset($filterInputs))
    <form class="well form-inline">
        @foreach ($filterInputs as $input)
            {!! $input !!}
        @endforeach
    </form>
@endif

<ul class="quick-filter">
    @foreach ($quickFilters as $key => $row)
        <li>
            <a href="{{ $row['href'] }}" @if($row['active']) class="active" @endif>
                {{ $row['label'] }} <span class="count">({{ $row['count'] }})</span> @if($row['separator']) | @endif
            </a>
        </li>
    @endforeach
</ul>