@extends('form-tool::layouts.layout')

@section('content')

<style>
.ui-state-highlight {
    background-color:#ffffcc;
    border:1px dotted #ccc;
    cursor:move;
}
.handle {
    cursor:move;
}
.table tbody tr {
    width:100%;
}
</style>
<script>
// Declaring empty template is important as multiple table depends on this variable
let template = [];
</script>

{!! $page->style !!}

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $title ?? '' }}</h3>
            </div>

            <form action="{{ $page->form->action }}" method="POST" enctype="multipart/form-data"
                class="form-horizontal">
                <div class="box-body">
                    @csrf

                    @if ($page->form->isEdit)
                        <input type="hidden" name="_method" value="PUT">
                    @else
                        <!-- this is only needed when you use run() -->
                        <!-- name="method" is different from the above edit -->
                        <input type="hidden" name="method" value="CREATE">
                    @endif

                    <div id="beforeForm"></div>

                    @foreach ($page->form->fields as $field)
                        {!! $field !!}
                    @endforeach

                    <div id="afterForm"></div>
                </div>
                <div class="box-footer footer-sticky">
                    <div class="row">
                        <div class="col-sm-8 col-sm-offset-2">
                            <button class="btn btn-success btn-flat submit">Save</button>
                            &nbsp; <a href="{{ $page->form->cancel }}" class="btn btn-default btn-flat">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('form-tool::form.scripts.global')

{!! $page->script !!}

@endsection