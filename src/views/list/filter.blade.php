<style>
.form-group {
    margin-right:15px;
}
</style>

@if (isset($filterData))
    <form class="well form-inline">
        @foreach ($filterData->inputs as $input)
            {!! $input !!}
        @endforeach

        <button class="btn btn-primary btn-sm btn-flat" style="margin-top:25px;">Filter</button>

        @if ($filterData->showClearButton)
            <a class="btn btn-default btn-sm btn-flat" href="{{ $filterData->clearUrl }}" style="margin-top:25px;">
                <i class="fa fa-times"></i> Clear All
            </a>
        @endif
    </form>
@endif
